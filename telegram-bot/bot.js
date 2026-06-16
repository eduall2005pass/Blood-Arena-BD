'use strict';
// ════════════════════════════════════════════════════════════════════
//  Blood Arena — Telegram OTP bot (long-polling, no webhook)
//  - user bot-এ গিয়ে নিজের নম্বর (+8801…) পাঠায় → phone→chatId ম্যাপ হয়
//  - PHP backend POST /send {secret, phone, message} করলে ঐ chatId-এ কোড যায়
//  long-polling ব্যবহার করায় Telegram-এর জন্য কোনো inbound/HTTPS/cert লাগে না।
//  শুধু PHP→bot /send-এর জন্য 443-এ reverse proxy (Caddy /tg/*) লাগে।
// ════════════════════════════════════════════════════════════════════
require('dotenv').config();
const express = require('express');
const fs = require('fs');
const https = require('https');
const path = require('path');

const TOKEN   = process.env.TG_BOT_TOKEN;
const SECRET  = process.env.TG_BOT_SECRET;
const PORT    = parseInt(process.env.TG_PORT || '3002', 10);
const HOST    = process.env.HOST || '127.0.0.1';
const DB_FILE = path.join(__dirname, 'phone_map.json');

if (!TOKEN)  { console.error('❌ TG_BOT_TOKEN সেট নেই (.env দেখুন)।'); process.exit(1); }
if (!SECRET) { console.error('❌ TG_BOT_SECRET সেট নেই (.env দেখুন)।'); process.exit(1); }

let phoneMap = {};
try {
  phoneMap = JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
  console.log(`📂 Loaded ${Object.keys(phoneMap).length} linked numbers`);
} catch (e) { phoneMap = {}; }

function saveMap() { fs.writeFileSync(DB_FILE, JSON.stringify(phoneMap, null, 2)); }

// pending[phone] = otp  — PHP /prepare এ set হয়, user deep link খুললে bot পাঠায়
let pending = {};

// ── Telegram API helper ──
function tgApi(method, payload) {
  return new Promise((resolve, reject) => {
    const body = JSON.stringify(payload);
    const req = https.request({
      hostname: 'api.telegram.org',
      path: `/bot${TOKEN}/${method}`,
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body) },
    }, (res) => {
      let data = '';
      res.on('data', d => { data += d; });
      res.on('end', () => { try { resolve(JSON.parse(data)); } catch (e) { reject(e); } });
    });
    req.on('error', reject);
    req.write(body); req.end();
  });
}
const sendTelegram = (chatId, text) => tgApi('sendMessage', { chat_id: chatId, text });

// ── incoming message handler (নম্বর লিংক) ──
async function handleMessage(msg) {
  const chatId = msg.chat.id;
  const text = (msg.text || '').trim();

  if (text.startsWith('/start')) {
    const param = text.slice(6).trim();
    if (param) {
      // deep link থেকে আসা phone: 8801XXXXXXXXX (+ ছাড়া)
      const phone = '+' + param;
      if (/^\+8801[3-9]\d{8}$/.test(phone)) {
        phoneMap[phone] = chatId;
        saveMap();
        const otp = pending[phone];
        if (otp) {
          delete pending[phone];
          await sendTelegram(chatId,
            '🩸 Blood Arena যাচাইকরণ কোড\n\n' +
            'আপনার কোড: ' + otp + '\n\n' +
            'এই কোডটি ওয়েবসাইটে প্রবেশ করিয়ে অ্যাকাউন্ট যাচাই সম্পন্ন করুন। ' +
            'কোডটি ৫ মিনিটের জন্য বৈধ। নিরাপত্তার স্বার্থে কারও সাথে শেয়ার করবেন না।');
        } else {
          await sendTelegram(chatId,
            '✅ আপনার নম্বর (' + phone + ') সফলভাবে যুক্ত হয়েছে।\n\n' +
            'এখন Blood Arena ওয়েবসাইটে গিয়ে যাচাইকরণ কোড সংগ্রহ করুন।');
        }
        return;
      }
    }
    await sendTelegram(chatId,
      '🩸 Blood Arena যাচাইকরণ বট-এ স্বাগতম।\n\nঅ্যাকাউন্ট যাচাই করতে আপনার মোবাইল নম্বরটি নিচের ফরম্যাটে পাঠান:\n+8801XXXXXXXXX');
    return;
  }

  let phone = text.replace(/\s/g, '');
  if (/^01[3-9]\d{8}$/.test(phone)) phone = '+88' + phone;

  if (/^\+8801[3-9]\d{8}$/.test(phone)) {
    phoneMap[phone] = chatId;
    saveMap();
    await sendTelegram(chatId,
      '✅ আপনার নম্বর (' + phone + ') সফলভাবে যুক্ত হয়েছে।\n\n' +
      'এখন Blood Arena ওয়েবসাইটে লগ-ইন করে যাচাইকরণ কোড সংগ্রহ করুন।');
  } else {
    await sendTelegram(chatId,
      '⚠️ নম্বরটি সঠিক নয়।\n\nঅনুগ্রহ করে +8801XXXXXXXXX ফরম্যাটে আপনার নম্বর পাঠান।');
  }
}

// ── long-polling loop ──
let offset = 0;
async function poll() {
  try {
    const r = await tgApi('getUpdates', { offset, timeout: 30 });
    if (r && r.ok && Array.isArray(r.result)) {
      for (const u of r.result) {
        offset = u.update_id + 1;
        if (u.message) { try { await handleMessage(u.message); } catch (e) { console.error('msg err', e.message); } }
      }
    }
  } catch (e) { console.error('poll err', e.message); await new Promise(s => setTimeout(s, 3000)); }
  setImmediate(poll);
}

// ── HTTP API (PHP → bot) ──
const app = express();
app.use(express.json());

// PHP এর tg_send_otp handler এটা call করে — OTP জমা রাখে, user deep link খুললে দেয়
app.post('/prepare', (req, res) => {
  const { secret, phone, otp } = req.body || {};
  if (secret !== SECRET) return res.status(403).json({ ok: false, error: 'Forbidden' });
  if (!phone || !otp) return res.status(400).json({ ok: false, error: 'phone and otp required' });
  pending[phone] = String(otp);
  res.json({ ok: true });
});

app.post('/send', async (req, res) => {
  const { secret, phone, message } = req.body || {};
  if (secret !== SECRET) return res.status(403).json({ ok: false, error: 'Forbidden' });
  if (!phone || !message) return res.status(400).json({ ok: false, error: 'phone & message required' });
  const chatId = phoneMap[phone];
  if (!chatId) return res.status(404).json({ ok: false, error: 'Phone not linked' });
  try {
    const r = await sendTelegram(chatId, message);
    if (r && r.ok) return res.json({ ok: true });
    return res.status(500).json({ ok: false, error: (r && r.description) || 'send failed' });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

app.get('/health', (req, res) => res.json({ ok: true, linked: Object.keys(phoneMap).length }));

app.listen(PORT, HOST, () => {
  console.log(`🤖 Telegram OTP bot HTTP → ${HOST}:${PORT}`);
  poll();
  console.log('📡 Telegram long-polling শুরু হলো।');
});
