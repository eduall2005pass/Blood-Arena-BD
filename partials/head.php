<head>
<meta charset="UTF-8">
<link rel="preload" as="image" href="logo.png">
<link rel="preload" as="image" href="logo1.png">
 
<meta name="viewport" content="width=device-width, initial-scale=0.8, maximum-scale=0.8, user-scalable=no, viewport-fit=cover">
<link rel="manifest" href="/?manifest=1">
<meta name="theme-color" content="<?= COLOR_THEME ?>">
<!-- PWA: iOS Support -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= BRAND_NAME ?>">
<link rel="apple-touch-icon" sizes="192x192" href="icon.png">
<link rel="apple-touch-icon" href="icon.png">
<meta name="application-name" content="<?= BRAND_NAME ?>">
<meta name="msapplication-TileColor" content="<?= COLOR_THEME ?>">
<meta name="msapplication-TileImage" content="icon.png">
<title><?= BRAND_NAME ?></title>
<meta name="description" content="Blood Arena - বাংলাদেশের একটি অনলাইন রক্তদান প্ল্যাটফর্ম। জরুরি প্রয়োজনে রক্তদাতা খুঁজে পেতে বা রক্তদাতা হিসেবে নাম লেখাতে আজই ভিজিট করুন।">
<meta name="keywords" content="Blood donation, Bangladesh, Blood donor, বাংলাদেশ, রক্তদান, রক্তদাতা, Blood Arena, Siam, Rafi">
<meta property="og:title" content="<?= BRAND_NAME ?> | বাংলাদেশের রক্তদান কেন্দ্র">
<meta property="og:description" content="রক্তের জন্য আর নয় অস্থিরতা। আমাদের অনলাইন ডাটাবেজে যুক্ত হোন।">
<meta property="og:image" content="<?= SITE_URL ?>/logo.png">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MedicalOrganization",
  "name": "<?= BRAND_NAME ?>",
  "description": "Bangladesh Online Blood Donation Portal",
  "url": "<?= SITE_URL ?>/",
  "logo": "<?= SITE_URL ?>/logo.png",
  "parentOrganization": {
    "@type": "MedicalOrganization",
    "name": "<?= ORG_NAME ?>"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "<?= CONTACT_PHONE ?>",
    "contactType": "Emergency Blood Support"
  }
}
</script>

<!-- PREVENT FOUC FOR DAY/NIGHT MODE -->
<script>
    // Light is the default. Dark only when the user explicitly chose it.
    if(localStorage.getItem('theme') !== 'dark'){
        document.documentElement.setAttribute('data-theme', 'light');
    }
</script>

<link rel="icon" type="image/png" href="icon.png"> 
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<!-- Modern Fonts -->
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="dns-prefetch" href="https://fonts.gstatic.com">
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet"> 
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js" defer></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script>

<style><?php include __DIR__ . '/../assets/styles.css.php'; ?></style>

<!-- ── Firebase App (v9 compat) ── -->
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-auth-compat.js"></script>
<!-- ── Three.js (3D blood-drop hero) — pinned UMD build exposes global THREE ── -->
<script defer src="https://cdn.jsdelivr.net/npm/three@0.149.0/build/three.min.js"></script>
<script><?php include __DIR__ . '/../assets/head-init.js.php'; ?></script>
</head>
