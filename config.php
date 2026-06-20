<?php
// ════════════════════════════════════════════════════════════════════
//  config.php — REBRAND CONTROL PANEL  (DB/UI-editable via overlay)
//
//  এখন থেকে মানগুলো Admin Panel → ⚙️ Settings → 🎛️ Site Config থেকেও
//  বদলানো যায় — notepad এ এই ফাইল edit করার দরকার নেই।
//
//  কীভাবে কাজ করে (নিরাপদ ডিজাইন):
//   1) নিচের $CFG_DEFAULTS = hardcoded ডিফল্ট (কখনো নষ্ট হয় না, fallback)।
//   2) থাকলে config_overrides.json (data-only) পড়ে ডিফল্টের উপর merge হয়।
//      JSON data-only — তাই ভুল মান কখনো PHP fatal/500 ঘটাতে পারে না।
//      ফাইল না থাকলে বা নষ্ট হলে চুপচাপ ডিফল্টে fallback করে।
//   3) তারপর হুবহু আগের const-নামেই define() হয় — কোডের বাকি অংশে কিছু বদলায় না।
//
//  Admin UI শুধু config_overrides.json লেখে — এই ফাইল (config.php) কখনো
//  rewrite করে না। তাই UI দিয়ে সাইট ভাঙা অসম্ভব; "Reset to default" শুধু
//  overlay ফাইল মুছে দেয়।
// ════════════════════════════════════════════════════════════════════

// ───── Hardcoded defaults (fallback — never edited by the UI) ────────
$CFG_DEFAULTS = [
    // Brand / Identity
    'BRAND_NAME'    => 'Blood Arena',
    'BRAND_SHORT'   => 'Blood Arena',
    'BRAND_TAGLINE' => 'স্বেচ্ছাসেবী রক্তদান প্ল্যাটফর্ম',
    'ORG_NAME'      => 'Blood Arena Bangladesh',
    'ORG_NAME_BN'   => 'ব্লাড অ্যারেনা বাংলাদেশ',
    'APP_DESC'      => 'Blood Arena — বাংলাদেশের রক্তদান পোর্টাল — জরুরি রক্ত খুঁজুন, রক্তদাতা হিসেবে যোগ দিন',

    // Contact / Links
    'CONTACT_PHONE' => '+8801518981827',
    'SITE_URL'      => 'https://bloodarenabd.tech',
    'LOGO_PATH'     => 'logo.png',
    'ICON_PATH'     => 'icon.png',

    // Social media links
    'SOCIAL_FACEBOOK' => 'https://facebook.com/',
    'SOCIAL_TELEGRAM' => 'https://t.me/',
    'SOCIAL_YOUTUBE'  => 'https://youtube.com/',
    'SOCIAL_WHATSAPP' => 'https://wa.me/',

    // Theme colours
    'COLOR_PRIMARY'       => '#dc2743',
    'COLOR_PRIMARY_HOVER' => '#b71d38',
    'COLOR_BG_MAIN'       => '#0d1320',
    'COLOR_THEME'         => '#d12d36',

    // Splash screen
    'SPLASH_ENABLED'           => true,
    'SPLASH_MIN_MS'            => 600,
    'SPLASH_MIN_MS_STANDALONE' => 250,
    'SPLASH_MAX_MS'            => 1500,
    'SPLASH_BG'                => '#f6f8fb',
    'SPLASH_BG_DARK'           => '#0d1320',

    // Firebase (client config + server token-verify key)
    'FIREBASE' => [
        'apiKey'            => 'AIzaSyAXKVJLxgZsOTCBJRTJmBs5H3wLlZdj514',
        'authDomain'        => 'shsmc-blood-portal.firebaseapp.com',
        'projectId'         => 'shsmc-blood-portal',
        'storageBucket'     => 'shsmc-blood-portal.firebasestorage.app',
        'messagingSenderId' => '968307626441',
        'appId'             => '1:968307626441:web:0186bc2d4adcaf434a9818',
        'measurementId'     => 'G-DGFTNSS1MJ',
        'vapidKey'          => 'BI8rH7TpZ7DB05KHQwRfVVYOO3tNvsS50F64F3EraGM0njJ6SkjgW6YjQGeLm9dmNfaP2zbY09H0JclgciLeZ3I',
    ],

    // Account verification — Telegram + WhatsApp bot OTP
    'TELEGRAM_BOT_URL'          => 'https://52.184.98.228/tg',
    'TELEGRAM_BOT_SECRET'       => 'bloodarena_tg_secret_2024',
    'TELEGRAM_BOT_USERNAME'     => 'BloodArenaOTP_bot',
    'TELEGRAM_BOT_INSECURE_TLS' => true,
    'WA_BOT_URL'                => 'https://52.184.98.228',
    'WA_BOT_SECRET'             => 'bloodarena_super_secret_2024',
    'WA_BOT_INSECURE_TLS'       => true,
    'VERIFY_OTP_TTL'            => 300,
    'PHONE_OTP_COUNTS_VERIFIED' => true,

    // Blood request documents
    'AUTO_DELETE_DAYS'  => 3,
    'REQ_DOC_MAX_FILES' => 2,
    'REQ_DOC_MAX_MB'    => 5,    // UI-friendly MB (REQ_DOC_MAX_BYTES নিচে compute হয়)
    'REQ_DOC_TARGET_KB' => 500,
];

// ───── Overlay merge — data-only JSON, can NEVER cause a PHP error ───
// config_overrides.json = Admin UI-এর লেখা মান। না থাকলে/নষ্ট হলে ignore।
$CFG = $CFG_DEFAULTS;
$__cfg_overlay_file = __DIR__ . '/config_overrides.json';
if (is_file($__cfg_overlay_file)) {
    $__raw = @file_get_contents($__cfg_overlay_file);
    $__ov  = $__raw ? json_decode($__raw, true) : null;
    if (is_array($__ov)) {
        foreach ($CFG_DEFAULTS as $k => $def) {
            if (!array_key_exists($k, $__ov)) continue;
            $val = $__ov[$k];
            if ($k === 'FIREBASE') {
                // merge sub-keys only; ignore non-array overlay
                if (is_array($val)) {
                    foreach ($def as $fk => $fv) {
                        if (isset($val[$fk]) && is_string($val[$fk]) && $val[$fk] !== '') $CFG[$k][$fk] = $val[$fk];
                    }
                }
                continue;
            }
            // type-coerce to the default's type so a wrong type can't break anything
            if (is_bool($def))      $CFG[$k] = (bool)$val;
            elseif (is_int($def))   $CFG[$k] = (int)$val;
            elseif (is_string($def)) { if (is_string($val)) $CFG[$k] = $val; }
        }
    }
}

// ───── Emit constants — SAME names as before (nothing else changes) ──
define('BRAND_NAME',    $CFG['BRAND_NAME']);
define('BRAND_SHORT',   $CFG['BRAND_SHORT']);
define('BRAND_TAGLINE', $CFG['BRAND_TAGLINE']);
define('ORG_NAME',      $CFG['ORG_NAME']);
define('ORG_NAME_BN',   $CFG['ORG_NAME_BN']);
define('APP_DESC',      $CFG['APP_DESC']);

define('CONTACT_PHONE', $CFG['CONTACT_PHONE']);
define('SITE_URL',      $CFG['SITE_URL']);
define('LOGO_PATH',     $CFG['LOGO_PATH']);
define('ICON_PATH',     $CFG['ICON_PATH']);

define('SOCIAL_FACEBOOK', $CFG['SOCIAL_FACEBOOK']);
define('SOCIAL_TELEGRAM', $CFG['SOCIAL_TELEGRAM']);
define('SOCIAL_YOUTUBE',  $CFG['SOCIAL_YOUTUBE']);
define('SOCIAL_WHATSAPP', $CFG['SOCIAL_WHATSAPP']);

define('COLOR_PRIMARY',       $CFG['COLOR_PRIMARY']);
define('COLOR_PRIMARY_HOVER', $CFG['COLOR_PRIMARY_HOVER']);
define('COLOR_BG_MAIN',       $CFG['COLOR_BG_MAIN']);
define('COLOR_THEME',         $CFG['COLOR_THEME']);

define('SPLASH_ENABLED',           $CFG['SPLASH_ENABLED']);
define('SPLASH_MIN_MS',            $CFG['SPLASH_MIN_MS']);
define('SPLASH_MIN_MS_STANDALONE', $CFG['SPLASH_MIN_MS_STANDALONE']);
define('SPLASH_MAX_MS',            $CFG['SPLASH_MAX_MS']);
define('SPLASH_BG',                $CFG['SPLASH_BG']);
define('SPLASH_BG_DARK',           $CFG['SPLASH_BG_DARK']);

define('FIREBASE', $CFG['FIREBASE']);

define('TELEGRAM_BOT_URL',          $CFG['TELEGRAM_BOT_URL']);
define('TELEGRAM_BOT_SECRET',       $CFG['TELEGRAM_BOT_SECRET']);
define('TELEGRAM_BOT_USERNAME',     $CFG['TELEGRAM_BOT_USERNAME']);
define('TELEGRAM_BOT_INSECURE_TLS', $CFG['TELEGRAM_BOT_INSECURE_TLS']);
define('WA_BOT_URL',                $CFG['WA_BOT_URL']);
define('WA_BOT_SECRET',             $CFG['WA_BOT_SECRET']);
define('WA_BOT_INSECURE_TLS',       $CFG['WA_BOT_INSECURE_TLS']);
define('VERIFY_OTP_TTL',            $CFG['VERIFY_OTP_TTL']);
define('PHONE_OTP_COUNTS_VERIFIED', $CFG['PHONE_OTP_COUNTS_VERIFIED']);

// UPLOAD_DIR is a filesystem path computed from __DIR__ — deliberately NOT
// overridable from the UI (a wrong path would break uploads silently).
define('UPLOAD_DIR',        __DIR__ . '/../storage/req_docs');
define('AUTO_DELETE_DAYS',  max(1, (int)$CFG['AUTO_DELETE_DAYS']));   // floor 1 — never 0/negative
define('REQ_DOC_MAX_FILES', max(1, (int)$CFG['REQ_DOC_MAX_FILES']));
define('REQ_DOC_MAX_BYTES', max(1, (int)$CFG['REQ_DOC_MAX_MB']) * 1024 * 1024);
define('REQ_DOC_TARGET_KB', max(50, (int)$CFG['REQ_DOC_TARGET_KB']));

// Expose the editable default-set + overlay path for the Admin config editor.
// (Admin reads these to render the form and to know what's editable.)
define('CFG_OVERLAY_FILE', $__cfg_overlay_file);
$GLOBALS['CFG_DEFAULTS'] = $CFG_DEFAULTS;
$GLOBALS['CFG_EFFECTIVE'] = $CFG;
