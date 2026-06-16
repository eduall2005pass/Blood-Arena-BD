# Blood Arena BD — Easy Deployment Guide

This project is a PHP + MySQL blood donation portal with optional OTP verification bots (Telegram and WhatsApp).

## 1) Requirements

- PHP 8.0+ with `mysqli`, `json`, `curl`, `mbstring`
- MySQL / MariaDB
- HTTPS-enabled domain (recommended)
- Node.js 18+ (only if you run Telegram/WhatsApp OTP bots)

## 2) Deploy the main web app (shared hosting friendly)

1. Upload all repository files to your web root (`public_html` or equivalent).
2. Create a MySQL database and user in your hosting panel.
3. Update database credentials in:
   - `db.php`
4. Update branding, domain, and integration settings in:
   - `config.php`
5. Open your site in a browser once.  
   The app auto-creates/updates runtime tables during first run.

## 3) Security checklist (must do before production)

- Replace all placeholder/shared secrets in `config.php` (Telegram + WhatsApp secrets).
- Keep `admin_config.php` private and use a strong admin password hash.
- Use HTTPS for the main site and bot endpoints.
- Do **not** commit real credentials/secrets to git.

## 4) Optional OTP bot deployment

If you want account verification by bot OTP:

- WhatsApp bot setup:  
  `whatsapp-bot/README.md`
- Azure-specific WhatsApp deployment:  
  `whatsapp-bot/DEPLOY-azure.md`
- Telegram bot setup:
  1. Copy `telegram-bot/.env.example` to `.env`
  2. Set `TG_BOT_TOKEN`, `TG_BOT_SECRET`, `TG_PORT`, `HOST`
  3. Run:
     ```bash
     cd telegram-bot
     npm install
     npm start
     ```

After bot setup, make sure bot URLs/secrets in `config.php` match bot `.env` values exactly.

## 5) Quick post-deploy check

- Homepage loads without PHP/database error
- Donor registration and search work
- Emergency request submission works
- Admin page opens and authenticates
- (If enabled) Telegram/WhatsApp OTP verification works end-to-end

---

If you want, I can also add a one-command Docker deployment in a follow-up update.
