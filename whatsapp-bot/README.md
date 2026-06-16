# Blood Arena — WhatsApp OTP bot

PHP backend থেকে আসা request-এ WhatsApp-এ account-verification কোড পাঠায়
([whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) দিয়ে)।

> ⚠️ **সতর্কতা — Official API নয়।** এটি unofficial whatsapp-web.js, যা WhatsApp
> Web-কে স্বয়ংক্রিয়ভাবে নিয়ন্ত্রণ করে। এটি **Meta-র Terms of Service ভঙ্গ করে**
> — bot হিসেবে ব্যবহৃত WhatsApp নম্বরটি যেকোনো সময় **ban** হতে পারে। আলাদা/ডেডিকেটেড
> নম্বর ব্যবহার করুন এবং ঝুঁকি বুঝে চালান। নির্ভরযোগ্য production-এর জন্য পরে WhatsApp
> Cloud API (official)-এ যাওয়া উচিত।

## কেন আলাদা service?

মূল অ্যাপটি **shared PHP hosting**-এ (Node/Composer ছাড়া) চলে, তাই persistent
WhatsApp bot সেখানে চালানো যায় না। এই bot একটি **আলাদা VPS**-এ চলবে; PHP backend
HTTPS দিয়ে এর `POST /send` কল করে।

```
PHP (bloodarenabd.tech)  ──HTTPS POST /send {secret,phone,message}──▶  এই bot (VPS)  ──▶  WhatsApp
```

## সেটআপ

1. একটি VPS-এ Node 18+ ইনস্টল করুন (Chromium-এর dependency সহ — Debian/Ubuntu-তে:
   `apt-get install -y chromium-browser` বা puppeteer-এর bundled Chromium ব্যবহার হবে)।
2. এই `bot/` ফোল্ডারটি VPS-এ কপি করুন, তারপর:
   ```bash
   cp .env.example .env
   # .env খুলে WA_BOT_SECRET (লম্বা random) ও PORT সেট করুন
   npm install
   npm start
   ```
3. প্রথমবার চালালে টার্মিনালে একটি **QR কোড** দেখাবে — bot-এর WhatsApp নম্বরের
   ফোন থেকে **WhatsApp → Linked devices → Link a device** দিয়ে স্ক্যান করুন।
   একবার লিংক হলে session `./.wwebjs_auth`-এ থাকে, বারবার লাগে না।
4. `✅ WhatsApp bot ready` দেখালে service চালু।

## অ্যাপের সাথে যুক্ত করা (`config.php`)

```php
const WA_BOT_URL    = 'https://your-vps-domain:3001'; // এই service-এর base URL (HTTPS)
const WA_BOT_SECRET = '...';                          // .env-এর WA_BOT_SECRET-এর সাথে হুবহু এক
```

- `WA_BOT_URL` অবশ্যই **HTTPS** হতে হবে (reverse proxy যেমন nginx/Caddy দিয়ে TLS দিন)।
- backend-এর CSP `connect-src`-এ এই domain যোগ করার দরকার **নেই** — কলটি
  server-to-server (PHP curl), browser থেকে নয়।

## Endpoints

| Method | Path      | Body                                  | কাজ |
|--------|-----------|---------------------------------------|-----|
| GET    | `/health` | —                                     | `{ok, ready}` status |
| POST   | `/send`   | `{secret, phone:"+8801…", message}`   | কোড পাঠায় |

`/send` শুধু `+8801XXXXXXXXX` ফরম্যাটের নম্বর নেয়, secret না মিললে `403`, WhatsApp
ready না হলে `503`, নম্বরটি WhatsApp-এ না থাকলে `422` ফেরত দেয়।

## চালু রাখা

`pm2` দিয়ে চালালে crash/reboot-এ অটো রিস্টার্ট হবে:
```bash
npm i -g pm2
pm2 start index.js --name bloodarena-wa-bot
pm2 save && pm2 startup
```

## নিরাপত্তা টিপস

- `.env` ও `.wwebjs_auth/` কখনো git-এ commit করবেন না (নিচের `.gitignore` দেখুন)।
- `WA_BOT_SECRET` লম্বা ও random রাখুন; ফাঁস হলে যে কেউ আপনার নম্বর থেকে message
  পাঠাতে পারবে।
- bot-এর port সরাসরি পাবলিক না রেখে reverse proxy-র পেছনে রাখুন।
