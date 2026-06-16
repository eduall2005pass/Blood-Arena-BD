# 🩸 Blood Arena BD

Bangladesh online blood-donation portal. The main web app is **PHP** (runs on
shared hosting). Two **Node.js OTP bots** — Telegram & WhatsApp — run separately
on a VM and handle account verification. This guide is the full deployment setup
for those bots.

- Repo: <https://github.com/eduall2005pass/Blood-Arena-BD>
- Live: <https://bloodarenabd.tech>

---

## 🧭 Architecture (how the pieces talk)

```
                         ┌──────────────────────────── VM (e.g. Azure 52.184.98.228) ───────────────────────────┐
                         │                                                                                       │
Browser ──HTTPS──▶ PHP app (bloodarenabd.tech)                  Caddy :443 (one reverse proxy)                   │
                         │   │  server-to-server curl ──HTTPS──▶  ├── /tg/*  ──HTTP──▶ 127.0.0.1:3002  Telegram bot │
                         │   │                                    └── /*     ──HTTP──▶ 127.0.0.1:3001  WhatsApp bot │
                         │   ▼                                                          │              │            │
                         │  generates 6-digit OTP, POSTs to bot                         ▼              ▼            │
                         │                                                        Telegram API     WhatsApp Web     │
                         └───────────────────────────────────────────────────────────────────────────────────────┘
```

- **Only ports 22, 80, 443** need to be open on the VM. Both bots listen on
  `127.0.0.1` (never public); Caddy on 443 fronts them.
- PHP → bot calls are **server-to-server** (PHP `curl`), so no browser CSP change
  is needed.
- An account becomes **verified** after binding Telegram *or* WhatsApp (or a
  Firebase Phone-OTP sign-in if `PHONE_OTP_COUNTS_VERIFIED=true`). **Unverified
  users can post blood requests but cannot call donors** — enforced server-side.

---

## ✅ Prerequisites

- A VM/VPS with **Ubuntu/Debian** and **Node.js 18+**.
- SSH access to the VM.
- Ports **80 and 443** reachable (for Caddy + Let's Encrypt). Port **3001/3002
  stay private** — never open them.
- A **Telegram bot token** from [@BotFather](https://t.me/BotFather) (for the TG bot).
- A spare **WhatsApp number** for the WA bot (⚠️ unofficial API — see warning below).

---

## 1️⃣ One-time VM setup

SSH into the VM, then install Node, git, and Chromium (Chromium is needed by the
WhatsApp bot's headless browser):

```bash
sudo apt update
sudo apt install -y nodejs npm git chromium-browser
node -v        # confirm v18+ (if older, install Node 18 LTS via nodesource)
```

> If `nodejs` is older than 18, install Node 18 LTS:
> ```bash
> curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
> sudo apt install -y nodejs
> ```

Check the OS firewall (Azure NSG is separate, but `ufw` may also be on):

```bash
sudo ufw status
sudo ufw allow 80 && sudo ufw allow 443    # only if ufw is active and they're blocked
# Do NOT open 3001 / 3002 — they must stay on localhost.
```

---

## 2️⃣ Clone the repo on the VM (easy way)

Clone once; both bot folders come with it.

```bash
cd ~
git clone https://github.com/eduall2005pass/Blood-Arena-BD.git
cd Blood-Arena-BD
ls            # you'll see telegram-bot/  and  whatsapp-bot/
```

> To update the bots later: `cd ~/Blood-Arena-BD && git pull && (cd telegram-bot && npm install) && (cd whatsapp-bot && npm install)` then restart with pm2 (step 6).

---

## 3️⃣ Telegram bot (`telegram-bot/`)

Uses **long-polling** (no webhook, no inbound port for Telegram itself). Users DM
the bot their `+8801…` number to link it; PHP then sends OTPs through it.

```bash
cd ~/Blood-Arena-BD/telegram-bot
cp .env.example .env
nano .env
```

Fill `.env`:

```ini
TG_BOT_TOKEN=123456:ABC-your-BotFather-token
TG_BOT_SECRET=bloodarena_tg_secret_2024     # MUST match config.php → TELEGRAM_BOT_SECRET
TG_PORT=3002
HOST=127.0.0.1
```

Install & test-run:

```bash
npm install
npm start
# expect:
#   🤖 Telegram OTP bot HTTP → 127.0.0.1:3002
#   📡 Telegram long-polling শুরু হলো।
```

Press `Ctrl+C` once it's confirmed working (we'll run it under pm2 in step 6).

> The bot writes `phone_map.json` (phone → chatId). It's git-ignored — don't commit it.

---

## 4️⃣ WhatsApp bot (`whatsapp-bot/`)

> ⚠️ **Unofficial API warning.** This uses `whatsapp-web.js`, which automates
> WhatsApp Web and **violates Meta's Terms of Service**. The number used can be
> **banned** at any time. Use a dedicated/spare number and run at your own risk.
> For production, migrate to the official WhatsApp Cloud API later.

```bash
cd ~/Blood-Arena-BD/whatsapp-bot
cp .env.example .env
nano .env
```

Fill `.env`:

```ini
WA_BOT_SECRET=bloodarena_super_secret_2024   # MUST match config.php → WA_BOT_SECRET
PORT=3001
HOST=127.0.0.1
WA_SESSION_DIR=./.wwebjs_auth
```

Install & first run (shows a QR code):

```bash
npm install
npm start
```

On first start a **QR code** prints in the terminal. On the bot's phone open
**WhatsApp → Linked devices → Link a device** and scan it. Wait for:

```
✅ WhatsApp bot ready — কোড পাঠাতে প্রস্তুত।
```

The session is cached in `./.wwebjs_auth` (git-ignored), so you won't need to
scan again. Press `Ctrl+C` (we'll run it under pm2 next).

---

## 5️⃣ Reverse proxy — one Caddy on :443 for both bots

Caddy gives auto-HTTPS and path-routes `/tg/*` → Telegram (3002) and everything
else → WhatsApp (3001), matching `config.php`.

Install Caddy:

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```

Edit `/etc/caddy/Caddyfile` (`sudo nano /etc/caddy/Caddyfile`):

### Option A — bare IP, self-signed cert (no domain)

```caddyfile
https://52.184.98.228 {
    tls internal
    handle_path /tg/* {
        reverse_proxy 127.0.0.1:3002
    }
    handle {
        reverse_proxy 127.0.0.1:3001
    }
}
```

In `config.php` set `*_INSECURE_TLS = true` (self-signed):

```php
const TELEGRAM_BOT_URL          = 'https://52.184.98.228/tg';
const TELEGRAM_BOT_SECRET       = 'bloodarena_tg_secret_2024';   // == TG_BOT_SECRET
const TELEGRAM_BOT_USERNAME     = 'BloodArenaOTP_bot';           // @-less bot username
const TELEGRAM_BOT_INSECURE_TLS = true;
const WA_BOT_URL                = 'https://52.184.98.228';
const WA_BOT_SECRET             = 'bloodarena_super_secret_2024'; // == WA_BOT_SECRET
const WA_BOT_INSECURE_TLS       = true;
```

### Option B — subdomain, valid Let's Encrypt cert (recommended)

Add a DNS **A record**: `bot.bloodarenabd.tech → <VM IP>`, then:

```caddyfile
bot.bloodarenabd.tech {
    handle_path /tg/* {
        reverse_proxy 127.0.0.1:3002
    }
    handle {
        reverse_proxy 127.0.0.1:3001
    }
}
```

In `config.php` use the domain and set `*_INSECURE_TLS = false`:

```php
const TELEGRAM_BOT_URL          = 'https://bot.bloodarenabd.tech/tg';
const TELEGRAM_BOT_INSECURE_TLS = false;
const WA_BOT_URL                = 'https://bot.bloodarenabd.tech';
const WA_BOT_INSECURE_TLS       = false;
```

Reload Caddy:

```bash
sudo systemctl restart caddy
sudo systemctl status caddy --no-pager
```

> ⚠️ The `*_BOT_SECRET` values in `config.php` **must exactly equal** the
> `TG_BOT_SECRET` / `WA_BOT_SECRET` in each bot's `.env`. If they differ, the bot
> returns `403 Forbidden` and OTPs silently fail.

---

## 6️⃣ Keep both bots alive with pm2 (auto-restart on crash/reboot)

```bash
sudo npm i -g pm2

cd ~/Blood-Arena-BD/telegram-bot && pm2 start bot.js   --name tg-bot
cd ~/Blood-Arena-BD/whatsapp-bot && pm2 start index.js --name wa-bot

pm2 save
pm2 startup          # run the command it prints (sets up systemd auto-start)
```

Handy pm2 commands:

```bash
pm2 list                 # status of both bots
pm2 logs wa-bot          # follow WhatsApp bot logs (e.g. to (re)scan QR)
pm2 logs tg-bot          # follow Telegram bot logs
pm2 restart wa-bot       # restart after a config/.env change
pm2 restart tg-bot
```

> If the WhatsApp session ever logs out, `pm2 logs wa-bot` will show a new QR to
> scan.

---

## 7️⃣ Verify it works

On the VM (self-signed → use `-k`):

```bash
curl -k https://localhost/tg/health      # {"ok":true,"linked":N}
curl -k https://localhost/health         # {"ok":true,"ready":true}  (WhatsApp)
```

From anywhere:

```bash
curl -k https://52.184.98.228/tg/health
curl -k https://52.184.98.228/health
# or, Option B:  https://bot.bloodarenabd.tech/...
```

Then on the live site: **log in → "Verify account" → Telegram or WhatsApp →
enter number → receive the 6-digit code → enter it**. Once verified, the user
can call donors.

---

## 🔐 Endpoints reference

**Telegram bot** (`telegram-bot/bot.js`, port 3002, behind `/tg/`)

| Method | Path       | Body                              | Purpose |
|--------|------------|-----------------------------------|---------|
| POST   | `/prepare` | `{secret, phone, otp}`            | Stash OTP; delivered when user opens the deep link |
| POST   | `/send`    | `{secret, phone:"+8801…", message}` | Send to a linked chat (`404` if not linked) |
| GET    | `/health`  | —                                 | `{ok, linked}` |

**WhatsApp bot** (`whatsapp-bot/index.js`, port 3001)

| Method | Path      | Body                                | Purpose |
|--------|-----------|-------------------------------------|---------|
| POST   | `/send`   | `{secret, phone:"+8801…", message}` | Send code (`403` bad secret · `503` not ready · `422` not on WhatsApp) |
| GET    | `/health` | —                                   | `{ok, ready}` |

---

## 🛡️ Security notes

- **Never commit** `.env`, `phone_map.json`, or `.wwebjs_auth/` — they're already
  in each bot's `.gitignore`.
- Keep `*_BOT_SECRET` long and random; a leak lets anyone send messages through
  your bots.
- Bots must stay bound to `127.0.0.1`; only Caddy (443) is public.
- If you change the secret in `.env`, update `config.php` to match and
  `pm2 restart` the bot.

---

## 🧯 Troubleshooting

| Symptom | Likely cause / fix |
|---------|--------------------|
| OTP never arrives, bot logs show `403` | `*_BOT_SECRET` in `.env` ≠ `config.php`. Make them identical, `pm2 restart`. |
| WhatsApp `/send` returns `503 not_ready` | Session logged out — `pm2 logs wa-bot`, scan the new QR. |
| WhatsApp `422 not_on_whatsapp` | The target number isn't on WhatsApp. |
| Telegram `/send` returns `404 not linked` | User hasn't DMed the bot their number yet. Send them `t.me/<TELEGRAM_BOT_USERNAME>`. |
| `curl https://.../health` fails with cert error | Self-signed (Option A) → use `curl -k`, and set `*_INSECURE_TLS=true` in `config.php`. |
| Channel shows "এখনো চালু হয়নি" on the site | That channel's constants in `config.php` are blank — fill them in. |
