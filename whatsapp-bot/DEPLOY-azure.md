# Azure VM-এ WhatsApp bot চালানো (শুধু 22/80/443 খোলা)

তোমার VM: **52.184.98.228**, NSG-তে খোলা পোর্ট: **22, 80, 443** — আর কিছু যোগ করা
যাচ্ছে না (Azure portal access নেই)। সমস্যা নেই: bot ভেতরে `127.0.0.1:3001`-এ চলবে,
আর **443-এ একটা reverse proxy** সেটি বাইরে দেবে। নতুন কোনো NSG rule লাগবে না।

```
PHP (bloodarenabd.tech) ──HTTPS :443──▶ Caddy (VM) ──HTTP 127.0.0.1:3001──▶ bot ──▶ WhatsApp
```

---

## ০) OS firewall-ও চেক করো (NSG ছাড়াও)

Azure NSG আলাদা, কিন্তু VM-এর ভেতরেও ufw থাকতে পারে:
```bash
sudo ufw status
sudo ufw allow 80 && sudo ufw allow 443   # বন্ধ থাকলে খোলো
```
পোর্ট **3001 কখনো খুলবে না** — সেটা শুধু localhost-এ থাকবে।

## ১) Node + bot বসাও
```bash
sudo apt update && sudo apt install -y nodejs npm chromium-browser
# (chromium puppeteer-এর জন্য)
cd ~ && mkdir -p bloodarena-bot && cd bloodarena-bot
# এই bot/ ফোল্ডারের সব ফাইল এখানে কপি করো (scp / git / rsync)
cp .env.example .env
nano .env        # WA_BOT_SECRET= লম্বা random; HOST=127.0.0.1; PORT=3001
npm install
npm start        # প্রথমবার — টার্মিনালে QR দেখাবে, bot-এর WhatsApp দিয়ে scan করো
```
`✅ WhatsApp bot ready` দেখালে Ctrl+C দিয়ে থামিয়ে pm2-তে চালাও:
```bash
sudo npm i -g pm2
pm2 start index.js --name wa-bot
pm2 save && pm2 startup     # reboot-এও অটো চালু
```

## ২) 443-এ reverse proxy — দুটি পথের একটি বেছে নাও

### পথ A (সুপারিশ): subdomain + Caddy (auto HTTPS, ভ্যালিড cert)
DNS-এ একটা A record বানাও: **`wabot.bloodarenabd.tech` → 52.184.98.228**
(bloodarenabd.tech যেখানে manage করো সেখানে; Azure লাগবে না)। তারপর:
```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```
`/etc/caddy/Caddyfile`:
```
wabot.bloodarenabd.tech {
    reverse_proxy 127.0.0.1:3001
}
```
```bash
sudo systemctl restart caddy   # Let's Encrypt cert নিজে নিয়ে নেবে (port 80 খোলা আছে বলে)
```
**config.php-তে:**
```php
const WA_BOT_URL          = 'https://wabot.bloodarenabd.tech';
const WA_BOT_SECRET       = '...';   // .env-এর মতো হুবহু এক
const WA_BOT_INSECURE_TLS = false;   // ভ্যালিড cert, তাই false
```

### পথ B (domain নেই): self-signed cert, bare IP
Caddy দিয়ে internal cert:
```
# /etc/caddy/Caddyfile
https://52.184.98.228 {
    tls internal
    reverse_proxy 127.0.0.1:3001
}
```
```bash
sudo systemctl restart caddy
```
**config.php-তে:**
```php
const WA_BOT_URL          = 'https://52.184.98.228';
const WA_BOT_SECRET       = '...';
const WA_BOT_INSECURE_TLS = true;    // self-signed → PHP peer-verify বন্ধ
```
> server-to-server কল + shared secret থাকায় self-signed গ্রহণযোগ্য। তবে domain
> থাকলে পথ A-ই ভালো।

## ৩) যাচাই
```bash
# VM-এ:
curl -k https://localhost/health        # {"ok":true,"ready":true} আশা করি
# বাইরে থেকে:
curl -k https://52.184.98.228/health    # বা https://wabot.bloodarenabd.tech/health
```
এরপর সাইটে login → "WhatsApp দিয়ে verify" → নম্বর দাও → WhatsApp-এ কোড আসবে।

---

## বিকল্প: Cloudflare Tunnel (কোনো inbound port-ই লাগে না)
bloodarenabd.tech যদি Cloudflare-এ থাকে, `cloudflared` দিয়ে outbound tunnel খুললে
443/80-ও খুলতে হয় না — VM শুধু বাইরে dial করে। NSG পুরো locked থাকলেও চলে।
দরকার হলে বলো, সেটআপ দিয়ে দেব।
