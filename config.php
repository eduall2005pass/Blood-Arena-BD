<?php
// ════════════════════════════════════════════════════════════════════
//  config.php — REBRAND CONTROL PANEL
//  এই ফাইলের মানগুলো বদলালেই পুরো অ্যাপ re-skin হয়ে যাবে।
//  Change the values below to rebrand the whole app in one place.
// ════════════════════════════════════════════════════════════════════

// ───── Brand / Identity ─────────────────────────────────────────────
const BRAND_NAME    = 'Blood Arena';                       // full app name
const BRAND_SHORT   = 'Blood Arena';                       // short / PWA name
const BRAND_TAGLINE = 'স্বেচ্ছাসেবী রক্তদান প্ল্যাটফর্ম';          // splash tagline
const ORG_NAME      = 'Blood Arena Bangladesh';            // parent org (English)
const ORG_NAME_BN   = 'ব্লাড অ্যারেনা বাংলাদেশ';            // parent org (Bangla)
const APP_DESC      = 'Blood Arena — বাংলাদেশের রক্তদান পোর্টাল — জরুরি রক্ত খুঁজুন, রক্তদাতা হিসেবে যোগ দিন';

// ───── Contact / Links ──────────────────────────────────────────────
const CONTACT_PHONE = '+8801518981827';                    // emergency contact
const SITE_URL      = 'https://bloodarenabd.tech';         // canonical site URL
const LOGO_PATH     = 'logo.png';                          // logo image
const ICON_PATH     = 'icon.png';                          // app/PWA icon

// ───── Social media links (footer "আমাদের সাথে যুক্ত থাকুন") ──────────
//  এই লিংকগুলো বদলালেই footer-এর social বাটনগুলো আপডেট হবে।
const SOCIAL_FACEBOOK = 'https://facebook.com/';           // Facebook page URL
const SOCIAL_TELEGRAM = 'https://t.me/';                   // Telegram channel/group URL
const SOCIAL_YOUTUBE  = 'https://youtube.com/';            // YouTube channel URL
const SOCIAL_WHATSAPP = 'https://wa.me/';                  // WhatsApp link (wa.me/8801XXXXXXXXX)

// ───── Theme colours (drive CSS :root tokens) ───────────────────────
const COLOR_PRIMARY       = '#dc2743';                     // refined medical crimson (brand red)
const COLOR_PRIMARY_HOVER = '#b71d38';                     // hover/darker red
const COLOR_BG_MAIN       = '#0d1320';                     // dark-theme background (deep navy-slate)
const COLOR_THEME         = '#d12d36';                     // PWA / status-bar theme

// ───── Splash screen ────────────────────────────────────────────────
//  এই মানগুলো বদলালেই splash screen behaviour ও look বদলে যায়।
//  Change these to control the launch splash. Speed is decoupled from
//  page resources — splash never waits for Leaflet/Firebase/fonts.
const SPLASH_ENABLED          = true;       // master on/off switch
const SPLASH_MIN_MS           = 600;        // min visible time in a browser tab (ms)
const SPLASH_MIN_MS_STANDALONE = 250;       // min visible time as installed PWA (ms)
const SPLASH_MAX_MS           = 1500;       // hard cap — never show longer than this (ms)
const SPLASH_BG               = '#f6f8fb';  // splash background (light / default)
const SPLASH_BG_DARK          = '#0d1320';  // splash background (dark theme)
//  Splash tagline reuses BRAND_TAGLINE above.

// ───── Firebase (client config + server token-verify key) ───────────
const FIREBASE = [
    'apiKey'            => 'AIzaSyAXKVJLxgZsOTCBJRTJmBs5H3wLlZdj514',
    'authDomain'        => 'shsmc-blood-portal.firebaseapp.com',
    'projectId'         => 'shsmc-blood-portal',
    'storageBucket'     => 'shsmc-blood-portal.firebasestorage.app',
    'messagingSenderId' => '968307626441',
    'appId'             => '1:968307626441:web:0186bc2d4adcaf434a9818',
    'measurementId'     => 'G-DGFTNSS1MJ',
    'vapidKey'          => 'BI8rH7TpZ7DB05KHQwRfVVYOO3tNvsS50F64F3EraGM0njJ6SkjgW6YjQGeLm9dmNfaP2zbY09H0JclgciLeZ3I',
];

// ───── Account verification — Telegram + WhatsApp bot OTP ────────────
//  Google/Phone দিয়ে login করলেও Telegram বা WhatsApp bind করা বাধ্যতামূলক।
//  bind না করলে account "unverified" — blood request করা যাবে কিন্তু call নয়।
//  নিচের মানগুলো ফাঁকা থাকলে ঐ চ্যানেলটি স্বয়ংক্রিয়ভাবে নিষ্ক্রিয় থাকে।
//
//  দুটো চ্যানেলই একই model: PHP কোড generate করে bot-এর /send endpoint-এ POST
//  করে; bot user-এর কাছে কোড পৌঁছে দেয়। bot গুলো আলাদা VM-এ চলে (bot/ ও
//  telegram-bot/ ফোল্ডার দেখুন)।
//
//  TELEGRAM (Node bot — phone→chatId map; user আগে bot-এ নম্বর লিংক করে):
//   TELEGRAM_BOT_URL      = bot-এর base URL (যেমন https://52.184.98.228/tg)
//   TELEGRAM_BOT_SECRET   = PHP ↔ Node শেয়ার্ড সিক্রেট (.env-এর TG_BOT_SECRET)
//   TELEGRAM_BOT_USERNAME = bot username (@ ছাড়া) — "bot-এ নম্বর লিংক করুন" লিংকে লাগে
//
//  WHATSAPP (whatsapp-web.js bot — bot/ ফোল্ডার দেখুন):
//   WA_BOT_URL    = bot service-এর base URL (যেমন https://52.184.98.228)
//   WA_BOT_SECRET = PHP ↔ Node শেয়ার্ড সিক্রেট (দুই জায়গায় একই হতে হবে)।
const TELEGRAM_BOT_URL          = 'https://52.184.98.228/tg';       // Telegram bot base URL (no trailing slash)
const TELEGRAM_BOT_SECRET       = 'bloodarena_tg_secret_2024';      // .env-এর TG_BOT_SECRET-এর সাথে হুবহু এক
const TELEGRAM_BOT_USERNAME     = 'BloodArenaOTP_bot';             // bot username, @ ছাড়া — t.me/<username>
const TELEGRAM_BOT_INSECURE_TLS = true;                             // self-signed cert হলে true
const WA_BOT_URL                = 'https://52.184.98.228';          // Azure VM bot (no trailing slash)
const WA_BOT_SECRET             = 'bloodarena_super_secret_2024';   // .env-এর WA_BOT_SECRET-এর সাথে হুবহু এক
//  bot-এ self-signed TLS cert (যেমন bare-IP HTTPS) হলে true করুন — তখন PHP
//  curl peer-verify বন্ধ করে। ভ্যালিড domain + Let's Encrypt cert থাকলে false রাখুন।
const WA_BOT_INSECURE_TLS       = true;
const VERIFY_OTP_TTL            = 300;  // OTP validity in seconds (5 minutes)
//  Firebase Phone-OTP (SMS) sign-in নিজেই phone ownership প্রমাণ করে — তাই এটিকেও
//  verified ধরা হয়। শুধু Telegram/WhatsApp-কেই verified ধরতে চাইলে false করুন।
const PHONE_OTP_COUNTS_VERIFIED = true;
