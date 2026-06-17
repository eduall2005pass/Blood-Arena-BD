// ════════════════════════════════════════════════════════════════════
//  Blood Arena — WhatsApp OTP bot (whatsapp-web.js)
//  PHP backend এই service-এর POST /send endpoint কল করে verification কোড
//  পাঠায়। এটি shared PHP host-এ চলবে না — আলাদা VPS-এ চালাতে হবে (README দেখুন)।
//
//  ⚠️ এটি WhatsApp-এর OFFICIAL API নয় (unofficial whatsapp-web.js)। Meta-র
//     Terms of Service ভঙ্গ করে — যে নম্বরটি bot হিসেবে ব্যবহার করবেন সেটি
//     ban হওয়ার ঝুঁকি আছে। ব্যবহার নিজ দায়িত্বে।
// ════════════════════════════════════════════════════════════════════

try { require('dotenv').config(); } catch (e) { /* dotenv optional — env vars সরাসরি দিলেও চলবে */ }
const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

const PORT       = parseInt(process.env.PORT || '3001', 10);
// default localhost — bot সরাসরি ইন্টারনেটে খোলে না; 443-এর reverse proxy
// (Caddy/nginx) এটিতে forward করে। সব interface-এ চাইলে HOST=0.0.0.0 দিন।
const HOST       = process.env.HOST || '127.0.0.1';
const WA_SECRET  = process.env.WA_BOT_SECRET || '';

if (!WA_SECRET) {
  console.error('❌ WA_BOT_SECRET সেট করা নেই (.env দেখুন)। বন্ধ করা হচ্ছে।');
  process.exit(1);
}

// ── WhatsApp client (session local-এ cache হয় → বারবার QR লাগে না) ──
const client = new Client({
  authStrategy: new LocalAuth({ dataPath: process.env.WA_SESSION_DIR || './.wwebjs_auth' }),
  puppeteer: {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
  }
});

let isReady = false;

client.on('qr', (qr) => {
  console.log('\n📱 এই QR কোডটি bot-এর WhatsApp নম্বর দিয়ে স্ক্যান করুন:\n');
  qrcode.generate(qr, { small: true });
});
client.on('authenticated', () => console.log('🔐 WhatsApp authenticated।'));
client.on('ready', () => { isReady = true; console.log('✅ WhatsApp bot ready — কোড পাঠাতে প্রস্তুত।'); });
client.on('disconnected', (r) => { isReady = false; console.warn('⚠️ WhatsApp disconnected:', r); });
client.initialize();

// ── HTTP API ──
const app = express();
app.use(express.json());

// timing-safe secret তুলনা
function secretOk(given) {
  const a = Buffer.from(String(given || ''));
  const b = Buffer.from(WA_SECRET);
  if (a.length !== b.length) return false;
  try { return require('crypto').timingSafeEqual(a, b); } catch (e) { return false; }
}

app.get('/health', (req, res) => res.json({ ok: true, ready: isReady }));

// POST /send  { secret, phone: "+8801XXXXXXXXX", message: "..." }
app.post('/send', async (req, res) => {
  const { secret, phone, message } = req.body || {};
  if (!secretOk(secret)) return res.status(403).json({ ok: false, error: 'forbidden' });
  if (!isReady) return res.status(503).json({ ok: false, error: 'not_ready' });
  if (!/^\+8801\d{9}$/.test(String(phone || ''))) {
    return res.status(400).json({ ok: false, error: 'bad_phone' });
  }
  if (!message || String(message).length > 1000) {
    return res.status(400).json({ ok: false, error: 'bad_message' });
  }
  // whatsapp-web.js chatId = আন্তর্জাতিক নম্বর '+' ছাড়া + "@c.us"
  const num = String(phone).replace('+', '');
  let chatId = num + '@c.us';
  try {
    // ⚠️ getNumberId() নির্ভরযোগ্য নয় — valid WhatsApp নম্বরেও মাঝে মাঝে null
    //    দেয় (contact-sync/privacy quirk)। তাই এটি দিয়ে আর block করি না; id
    //    পেলে সেই সঠিক serialized id ব্যবহার করি, না পেলে সরাসরি <num>@c.us-এ
    //    পাঠাই। এতে আসল নম্বর "not_on_whatsapp" বলে ভুলভাবে আটকে যায় না।
    let numId = null;
    try { numId = await client.getNumberId(num); } catch (e) { /* উপেক্ষা করো — সরাসরি পাঠাবো */ }
    if (numId && numId._serialized) chatId = numId._serialized;
    await client.sendMessage(chatId, String(message));
    return res.json({ ok: true });
  } catch (e) {
    console.error('send error:', e && e.message);
    return res.status(500).json({ ok: false, error: 'send_failed' });
  }
});

app.listen(PORT, HOST, () => console.log(`🚀 Blood Arena WA bot HTTP API → ${HOST}:${PORT}`));
