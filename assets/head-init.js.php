// ── Firebase config (from config.php) ────────────────────
const _firebaseConfig = {
  apiKey:            "<?= FIREBASE['apiKey'] ?>",
  authDomain:        "<?= FIREBASE['authDomain'] ?>",
  projectId:         "<?= FIREBASE['projectId'] ?>",
  storageBucket:     "<?= FIREBASE['storageBucket'] ?>",
  messagingSenderId: "<?= FIREBASE['messagingSenderId'] ?>",
  appId:             "<?= FIREBASE['appId'] ?>",
  measurementId:     "<?= FIREBASE['measurementId'] ?>"
};
const _VAPID_KEY = "<?= FIREBASE['vapidKey'] ?>";

var _fbApp, _fbMessaging;
try {
  if (!firebase.apps.length) {
    _fbApp = firebase.initializeApp(_firebaseConfig);
  } else {
    _fbApp = firebase.app();
  }
  _fbMessaging = firebase.messaging();
} catch(e) {
  console.warn('[FCM] Init failed:', e);
}

// Save FCM token to server
function _saveFcmToken(token) {
  if (!token) return;
  try {
    // _AJAX_URL may not be defined yet (head script) — always compute directly
    var _url = window.location.origin + window.location.pathname;
    var fd = new FormData();
    fd.append('save_fcm_token', '1');
    fd.append('fcm_token', token);
    fd.append('device_id', (typeof getDeviceId === 'function') ? getDeviceId() : '');
    fd.append('csrf_token', (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '');
    fetch(_url, {method:'POST', body:fd}).catch(function(){});
  } catch(e) {}
}

// Get FCM token when permission granted
// ── FIX v4: firebase-messaging-sw.js কে নিজস্ব scope-এ register করা হয়।
// আগে scope '/' এ sw.js (cache SW) এর সাথে সংঘর্ষ হত — তাই app closed
// থাকলে background push আসত না। এখন আলাদা scope-এ bind করায় ঠিক হয়েছে।
function _initFcmToken() {
  if (!_fbMessaging) return;
  if (!navigator.serviceWorker) return;

  // ── FIX: firebase-messaging-sw.js কে নিজস্ব scope-এ register করো ──
  // আগে এটি scope '/' এ register হত — সেখানে sw.js (cache SW) ও বসে।
  // দুটো SW একই scope '/' দখলের চেষ্টা করায় শেষেরটা control নিত।
  // cache SW control নিলে FCM background handler বন্ধ → app/browser closed
  // থাকলে push আসত না। এখন আলাদা scope দেওয়ায় দুটোই পাশাপাশি বাঁচে এবং
  // background push সবসময় firebase-messaging-sw.js handle করে।
  var FCM_SCOPE = '/firebase-cloud-messaging-push-scope';
  navigator.serviceWorker.register('/firebase-messaging-sw.js', { scope: FCM_SCOPE })
    .then(function(swReg) {
      return _fbMessaging.getToken({
        vapidKey: _VAPID_KEY,
        serviceWorkerRegistration: swReg
      });
    })
    .then(function(token) {
      if (token) {
        console.log('[FCM] Token obtained ✅ bound to firebase-messaging-sw.js (dedicated scope)');
        _saveFcmToken(token);
      } else {
        console.warn('[FCM] No token — notification permission দিন');
      }
    })
    .catch(function(err) {
      console.warn('[FCM] getToken failed:', err);
    });
}

// Foreground message handler — same tag as PHP FCM push so browser deduplicates
if (_fbMessaging) {
  _fbMessaging.onMessage(function(payload) {
    try {
      var n = payload.notification || {};
      var d = payload.data || {};
      var url = (d.url) ? d.url : '<?= SITE_URL ?>/?tab=emergency';
      // Use same tag as PHP FCM V1 push ('blood-req-{id}') — browser replaces duplicate
      var notifTag = d.request_id ? ('blood-req-' + d.request_id) : (n.tag || 'blood-req-fcm');
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(function(reg) {
          reg.showNotification(n.title || '🆘 Blood Arena', {
            body:    n.body || '',
            icon:    n.icon || '/icon.png',
            badge:   '/?badge_icon=1',
            tag:     notifTag,
            renotify: false,
            requireInteraction: false,
            data:    { url: url }
          });
        }).catch(function(){});
      } else {
        try { new Notification(n.title || '🆘 Blood Arena', { body: n.body, icon: '/icon.png', tag: notifTag }); } catch(e){}
      }
    } catch(e) {}
  });
}

// ════════════════════════════════════════════════════════════
// 🔐 FIREBASE AUTH — Google sign-in + Phone OTP (Bangladeshi only)
// ════════════════════════════════════════════════════════════
var _fbAuth, _recaptchaVerifier, _otpConfirmation;
try { if (_fbApp && firebase.auth) _fbAuth = firebase.auth(); } catch(e) { console.warn('[Auth] init failed', e); }

// সার্ভারে ID token পাঠিয়ে session তৈরি করো
function _postAuthToServer(idToken) {
  var url = window.location.origin + window.location.pathname;
  var fd = new FormData();
  fd.append('firebase_auth', '1');
  fd.append('id_token', idToken);
  fd.append('device_id', (typeof getDeviceId === 'function') ? getDeviceId() : '');
  fd.append('csrf_token', (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '');
  return fetch(url, { method:'POST', body:fd })
    .then(function(r){ return (typeof safeJSON === 'function') ? safeJSON(r) : r.json(); });
}

function _authToast(msg, type) {
  if (typeof showToast === 'function') showToast(msg, type || 'info');
}

function _authBusy(on, btnId) {
  var b = document.getElementById(btnId);
  if (b) b.disabled = !!on;
}

// ── সব Google বাটনের (auth modal + registration page) busy state ──
function _googleBtnsBusy(on) {
  ['authGoogleBtn','regGoogleBtn'].forEach(function(id){
    var b = document.getElementById(id);
    if (b) b.disabled = !!on;
  });
}

// standalone PWA / iOS / in-app browser-এ popup ব্লক হয় — সেক্ষেত্রে redirect লাগে
function _isStandalonePWA() {
  try {
    return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
        || window.navigator.standalone === true;
  } catch(e) { return false; }
}

// ── Google Sign-in (popup; ব্যর্থ হলে redirect fallback) ──
function authGoogleSignIn() {
  if (!_fbAuth) { _authToast('Auth এখন উপলব্ধ নয়।', 'error'); return; }
  var provider = new firebase.auth.GoogleAuthProvider();
  provider.setCustomParameters({ prompt: 'select_account' });
  _googleBtnsBusy(true);

  // PWA standalone mode-এ popup কাজ করে না → সরাসরি redirect
  if (_isStandalonePWA()) {
    _fbAuth.signInWithRedirect(provider).catch(function(err){
      _googleBtnsBusy(false);
      console.warn('[Auth] Google redirect', err);
      _authToast('Google সাইন-ইন শুরু করা যায়নি।', 'error');
    });
    return;
  }

  _fbAuth.signInWithPopup(provider)
    .then(function(result){ return result.user.getIdToken(); })
    .then(function(idToken){ return _postAuthToServer(idToken); })
    .then(function(res){
      _googleBtnsBusy(false);
      if (res && res.status === 'success') _onAuthSuccess(res);
      else _authToast((res && res.msg) ? res.msg : 'সাইন-ইন ব্যর্থ।', 'error');
    })
    .catch(function(err){
      console.warn('[Auth] Google popup', err);
      if (err && err.code === 'auth/popup-closed-by-user') { _googleBtnsBusy(false); return; }
      // popup ব্লক / এই environment-এ unsupported হলে redirect-এ fallback করো
      var fallback = ['auth/popup-blocked','auth/cancelled-popup-request',
                      'auth/operation-not-supported-in-this-environment','auth/web-storage-unsupported'];
      if (err && fallback.indexOf(err.code) !== -1) {
        _fbAuth.signInWithRedirect(provider).catch(function(e2){
          _googleBtnsBusy(false);
          console.warn('[Auth] Google redirect fallback', e2);
          _authToast('Google সাইন-ইন ব্যর্থ হয়েছে।', 'error');
        });
        return;
      }
      _googleBtnsBusy(false);
      // এই domain Firebase Console-এ authorized না থাকলে এই error আসে
      if (err && err.code === 'auth/unauthorized-domain') {
        _authToast('এই ডোমেইনটি Firebase-এ অনুমোদিত নয়। Console → Authentication → Settings → Authorized domains-এ যোগ করুন।', 'error');
        return;
      }
      // অন্য যেকোনো error-এ আসল কারণ দেখাও যাতে নির্ণয় করা যায়
      _authToast('Google সাইন-ইন ব্যর্থ: ' + ((err && (err.code || err.message)) || 'unknown'), 'error');
    });
}

// ── redirect থেকে ফিরলে sign-in সম্পন্ন করো (page load-এ একবার চলে) ──
function _completeGoogleRedirect() {
  if (!_fbAuth || typeof _fbAuth.getRedirectResult !== 'function') return;
  _fbAuth.getRedirectResult()
    .then(function(result){
      if (!result || !result.user) return; // redirect থেকে আসেনি — কিছু করার নেই
      _googleBtnsBusy(true);
      return result.user.getIdToken()
        .then(function(idToken){ return _postAuthToServer(idToken); })
        .then(function(res){
          _googleBtnsBusy(false);
          if (res && res.status === 'success') _onAuthSuccess(res);
          else _authToast((res && res.msg) ? res.msg : 'সাইন-ইন ব্যর্থ।', 'error');
        });
    })
    .catch(function(err){
      _googleBtnsBusy(false);
      console.warn('[Auth] redirect result', err);
      if (err && err.code === 'auth/unauthorized-domain') {
        _authToast('এই ডোমেইনটি Firebase-এ অনুমোদিত নয়। Console → Authentication → Settings → Authorized domains-এ যোগ করুন।', 'error');
        return;
      }
      _authToast('Google সাইন-ইন সম্পন্ন হয়নি: ' + ((err && (err.code || err.message)) || 'unknown'), 'error');
    });
}
try { _completeGoogleRedirect(); } catch(e){ console.warn('[Auth] redirect init', e); }

// ── Firebase auth error code → বোধগম্য বাংলা বার্তা (diagnosis সহ) ──
function _authErrMsg(err) {
  var code = (err && err.code) ? err.code : '';
  switch (code) {
    case 'auth/invalid-phone-number':
      return 'ফোন নম্বরটি সঠিক নয় (+8801XXXXXXXXX দিন)।';
    case 'auth/missing-phone-number':
      return 'ফোন নম্বর দিন।';
    case 'auth/quota-exceeded':
      return 'আজকের OTP সীমা শেষ। কিছুক্ষণ পর আবার চেষ্টা করুন অথবা Google দিয়ে সাইন ইন করুন।';
    case 'auth/too-many-requests':
      return 'অনেকবার চেষ্টা হয়েছে — সাময়িকভাবে বন্ধ। কিছুক্ষণ পর আবার চেষ্টা করুন।';
    case 'auth/operation-not-allowed':
      return 'Phone sign-in এখনো চালু করা হয়নি (Firebase Console → Authentication → Sign-in method → Phone enable করুন)।';
    case 'auth/unauthorized-domain':
      return 'এই ডোমেইনটি Firebase-এ অনুমোদিত নয় (Console → Authentication → Settings → Authorized domains-এ যোগ করুন)।';
    case 'auth/captcha-check-failed':
    case 'auth/invalid-app-credential':
    case 'auth/missing-app-credential':
      return 'reCAPTCHA যাচাই ব্যর্থ। পেজ refresh করে আবার চেষ্টা করুন।';
    case 'auth/code-expired':
      return 'OTP-এর মেয়াদ শেষ। আবার নতুন OTP পাঠান।';
    case 'auth/invalid-verification-code':
      return 'ভুল OTP কোড। আবার দেখুন।';
    case 'auth/network-request-failed':
      return 'নেটওয়ার্ক সমস্যা। Internet connection চেক করুন।';
    default:
      return 'OTP সমস্যা: ' + (code || (err && err.message) || 'unknown') + '. না হলে Google দিয়ে সাইন ইন করুন।';
  }
}

// ── invisible reCAPTCHA তৈরি (phone OTP-এর জন্য আবশ্যক) ──
//  compat SDK: signature (container, parameters)। render() কল করায় হারিয়ে যাওয়া
//  বা hidden-modal verifier আগেই initialize হয়ে stable থাকে।
function _ensureRecaptcha() {
  if (_recaptchaVerifier) return _recaptchaVerifier;
  _recaptchaVerifier = new firebase.auth.RecaptchaVerifier('authRecaptcha', {
    size: 'invisible',
    callback: function(){}
  });
  try { _recaptchaVerifier.render(); } catch(e) { console.warn('[Auth] recaptcha render', e); }
  return _recaptchaVerifier;
}

// ── ব্যর্থ হলে reCAPTCHA verifier পরিষ্কার করো যাতে retry-তে নতুন করে তৈরি হয় ──
function _resetRecaptcha() {
  try { if (_recaptchaVerifier) { _recaptchaVerifier.clear(); } } catch(e){}
  _recaptchaVerifier = null;
}

// ── Phone OTP — Step 1: OTP পাঠাও ──
function authSendOtp() {
  if (!_fbAuth) { _authToast('Auth এখন উপলব্ধ নয়। পেজ refresh করুন।', 'error'); return; }
  var input = document.getElementById('authPhoneInput');
  var phone = (input ? input.value : '').trim();
  // Bangladeshi number normalize: 01XXXXXXXXX → +8801XXXXXXXXX
  if (/^01\d{9}$/.test(phone)) phone = '+88' + phone;
  if (!/^\+8801\d{9}$/.test(phone)) {
    _authToast('সঠিক বাংলাদেশি নম্বর দিন (+8801XXXXXXXXX)।', 'error');
    return;
  }
  _authBusy(true, 'authSendOtpBtn');
  var verifier;
  try {
    verifier = _ensureRecaptcha();
  } catch(e) {
    _authBusy(false, 'authSendOtpBtn');
    console.warn('[Auth] recaptcha init', e);
    _resetRecaptcha();
    _authToast('reCAPTCHA চালু করা যায়নি। পেজ refresh করে আবার চেষ্টা করুন।', 'error');
    return;
  }
  _fbAuth.signInWithPhoneNumber(phone, verifier)
    .then(function(confirmation){
      _otpConfirmation = confirmation;
      _authBusy(false, 'authSendOtpBtn');
      var step2 = document.getElementById('authOtpStep');
      if (step2) step2.style.display = 'block';
      _authToast('📲 OTP পাঠানো হয়েছে ' + phone + ' নম্বরে।', 'success');
    })
    .catch(function(err){
      _authBusy(false, 'authSendOtpBtn');
      console.warn('[Auth] OTP send', err);
      // verifier পরিষ্কার করো — না করলে পরের চেষ্টা একই stale verifier-এ আটকে যায়
      _resetRecaptcha();
      _authToast(_authErrMsg(err), 'error');
    });
}

// ── Phone OTP — Step 2: কোড verify করো ──
function authVerifyOtp() {
  if (!_otpConfirmation) { _authToast('আগে OTP পাঠান।', 'error'); return; }
  var codeEl = document.getElementById('authOtpInput');
  var code = (codeEl ? codeEl.value : '').trim();
  if (!/^\d{6}$/.test(code)) { _authToast('৬-সংখ্যার কোড দিন।', 'error'); return; }
  _authBusy(true, 'authVerifyOtpBtn');
  _otpConfirmation.confirm(code)
    .then(function(result){ return result.user.getIdToken(); })
    .then(function(idToken){ return _postAuthToServer(idToken); })
    .then(function(res){
      _authBusy(false, 'authVerifyOtpBtn');
      if (res && res.status === 'success') _onAuthSuccess(res);
      else _authToast((res && res.msg) ? res.msg : 'যাচাই ব্যর্থ।', 'error');
    })
    .catch(function(err){
      _authBusy(false, 'authVerifyOtpBtn');
      console.warn('[Auth] OTP verify', err);
      _authToast(_authErrMsg(err), 'error');
    });
}

// ── সফল login হলে ──
function _onAuthSuccess(res) {
  try { localStorage.setItem('ba_auth', JSON.stringify({
    provider: res.provider, email: res.email, phone: res.phone, name: res.name, photo: res.photo,
    verified: !!res.verified, verify_channel: res.verify_channel || null
  })); } catch(e){}
  if (typeof closeAuthModal === 'function') closeAuthModal();
  var who = res.name || res.email || res.phone || 'User';
  _authToast('✅ স্বাগতম, ' + who + '!', 'success');
  if (typeof _renderAuthState === 'function') _renderAuthState();
  // verified না হলে account bind করতে উৎসাহ দাও (call করতে লাগবে)
  if (!res.verified && typeof _promptBindIfUnverified === 'function') {
    setTimeout(function(){ _promptBindIfUnverified(); }, 900);
  }
  // donor হিসেবে register করা না থাকলে registration page-এ পাঠাও
  if (!res.linked_donor && res.phone && typeof appSwitchPage === 'function') {
    setTimeout(function(){ appSwitchPage('register'); }, 800);
  }
}

// ── ba_auth localStorage-এ verified flag আপডেট করো (bind সফল হলে) ──
function _setLocalVerified(channel, phone) {
  try {
    var a = JSON.parse(localStorage.getItem('ba_auth') || 'null') || {};
    a.verified = true; a.verify_channel = channel || a.verify_channel || null;
    if (phone) a.verify_phone = phone;
    localStorage.setItem('ba_auth', JSON.stringify(a));
  } catch(e){}
  try { if (typeof BA_AUTH !== 'undefined' && BA_AUTH) { BA_AUTH.verified = true; BA_AUTH.verify_channel = channel; if (phone) BA_AUTH.verify_phone = phone; } } catch(e){}
}

// ── Logout ──
function authLogout() {
  try { if (_fbAuth) _fbAuth.signOut(); } catch(e){}
  try { localStorage.removeItem('ba_auth'); } catch(e){}
  var url = window.location.origin + window.location.pathname;
  var fd = new FormData();
  fd.append('firebase_logout', '1');
  fd.append('csrf_token', (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '');
  fetch(url, { method:'POST', body:fd }).catch(function(){});
  _authToast('লগ-আউট হয়েছে।', 'info');
  if (typeof _renderAuthState === 'function') _renderAuthState();
}

// ════════════════════════════════════════════════════════════════════
// 🔗 ACCOUNT VERIFICATION — Telegram (প্রস্তাবিত) + WhatsApp bot OTP
//   দুটোই একই flow: নম্বর দাও → bot কোড পাঠায় → কোড বসাও → verified।
//   bind না করলে account unverified — blood request করা যাবে, call নয়।
// ════════════════════════════════════════════════════════════════════
function _verifyPost(field, extra) {
  var url = window.location.origin + window.location.pathname;
  var fd = new FormData();
  fd.append(field, '1');
  fd.append('csrf_token', (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '');
  if (extra) Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); });
  return fetch(url, { method:'POST', body:fd })
    .then(function(r){ return (typeof safeJSON === 'function') ? safeJSON(r) : r.json(); });
}

// সফল bind হলে সবখানে verified state ছড়িয়ে দাও
function _onVerifySuccess(channel, msg, phone) {
  _setLocalVerified(channel, phone);
  _authToast(msg || '✅ অ্যাকাউন্ট verified!', 'success');
  if (typeof _renderAuthState === 'function') _renderAuthState();
  // account dashboard খোলা থাকলে রিফ্রেশ করো যাতে badge আপডেট হয়
  var am = document.getElementById('accountModal');
  if (am && am.classList.contains('active') && typeof openAccountDashboard === 'function') openAccountDashboard();
  if (typeof closeAuthModal === 'function') closeAuthModal();
}

// ── WhatsApp — Step 1: কোড পাঠাও ──
function waSendOtp() {
  var input = document.getElementById('waPhoneInput');
  var phone = (input ? input.value : '').trim();
  if (/^01\d{9}$/.test(phone)) phone = '+88' + phone;
  if (!/^\+8801\d{9}$/.test(phone)) { _authToast('সঠিক বাংলাদেশি WhatsApp নম্বর দিন।', 'error'); return; }
  _authBusy(true, 'waSendOtpBtn');
  _verifyPost('wa_send_otp', { phone: phone }).then(function(res){
    _authBusy(false, 'waSendOtpBtn');
    if (res && res.status === 'success') {
      var step = document.getElementById('waOtpStep');
      if (step) step.style.display = 'block';
      _authToast(res.msg || '📲 কোড পাঠানো হয়েছে।', 'success');
    } else { _authToast((res && res.msg) ? res.msg : 'কোড পাঠানো যায়নি।', 'error'); }
  }).catch(function(){ _authBusy(false, 'waSendOtpBtn'); _authToast('নেটওয়ার্ক সমস্যা।', 'error'); });
}

// ── WhatsApp — Step 2: কোড যাচাই ──
function waVerifyOtp() {
  var el = document.getElementById('waOtpInput');
  var code = (el ? el.value : '').trim();
  if (!/^\d{6}$/.test(code)) { _authToast('৬-সংখ্যার কোড দিন।', 'error'); return; }
  _authBusy(true, 'waVerifyOtpBtn');
  _verifyPost('wa_verify_otp', { code: code }).then(function(res){
    _authBusy(false, 'waVerifyOtpBtn');
    if (res && res.status === 'success') _onVerifySuccess('whatsapp', res.msg, res.phone);
    else _authToast((res && res.msg) ? res.msg : 'যাচাই ব্যর্থ।', 'error');
  }).catch(function(){ _authBusy(false, 'waVerifyOtpBtn'); _authToast('নেটওয়ার্ক সমস্যা।', 'error'); });
}

// ── Verify channel selector — একসাথে একটাই panel খোলে (Telegram বা WhatsApp) ──
function selectVerifyChannel(ch) {
  var tgP = document.getElementById('tgPanel');
  var waP = document.getElementById('waPanel');
  var tgB = document.getElementById('vchTgBtn');
  var waB = document.getElementById('vchWaBtn');
  if (tgP) tgP.style.display = (ch === 'tg') ? 'block' : 'none';
  if (waP) waP.style.display = (ch === 'wa') ? 'block' : 'none';
  if (ch === 'wa') {
    if (waB) { waB.style.borderColor = '#25D366'; waB.style.background = 'rgba(37,211,102,0.10)'; }
    if (tgB) { tgB.style.borderColor = 'var(--border-color)'; tgB.style.background = 'transparent'; }
  } else {
    if (tgB) { tgB.style.borderColor = '#229ED9'; tgB.style.background = 'rgba(34,158,217,0.10)'; }
    if (waB) { waB.style.borderColor = 'var(--border-color)'; waB.style.background = 'transparent'; }
  }
}

// ── Telegram — Step 1: OTP prepare করো, deep link দিয়ে bot খোলো ──
//  মোবাইলে async .then()-এর ভেতরে window.open() ব্লক হয় (user-gesture হারায়) →
//  তাই click-এর সাথে সাথেই একটা blank tab খুলে রাখি, server response এলে সেই
//  tab-টাকে bot link-এ পাঠাই। এতে মূল সাইটের tab reload হয় না।
function tgSendOtp() {
  var input = document.getElementById('tgPhoneInput');
  var phone = (input ? input.value : '').trim();
  if (/^01\d{9}$/.test(phone)) phone = '+88' + phone;
  if (!/^\+8801\d{9}$/.test(phone)) { _authToast('সঠিক বাংলাদেশি নম্বর দিন।', 'error'); return; }
  // click gesture-এর ভেতরেই blank tab খুলে রাখো (পরে location বসাবো)
  var botWin = window.open('', '_blank');
  _authBusy(true, 'tgSendOtpBtn');
  _verifyPost('tg_send_otp', { phone: phone }).then(function(res){
    _authBusy(false, 'tgSendOtpBtn');
    if (res && res.status === 'open_bot' && res.link) {
      // OTP বসানোর ঘর + manual link দেখাও (fallback)
      var div = document.getElementById('tgOpenBotDiv');
      if (div) {
        div.style.display = 'block';
        var a = document.getElementById('tgOpenBotBtn');
        if (a) a.href = res.link;
      }
      var step = document.getElementById('tgOtpStep');
      if (step) step.style.display = 'block';
      // pre-opened tab-টাকে bot-এ পাঠাও; না খুললে (popup-blocked) manual বাটন আছে
      if (botWin) { try { botWin.location = res.link; } catch(e){} }
      _authToast('Telegram-এ "START" চাপুন — OTP আসবে।', 'info');
    } else {
      if (botWin) { try { botWin.close(); } catch(e){} }
      _authToast((res && res.msg) ? res.msg : 'কোড পাঠানো যায়নি।', 'error');
    }
  }).catch(function(){
    if (botWin) { try { botWin.close(); } catch(e){} }
    _authBusy(false, 'tgSendOtpBtn');
    _authToast('নেটওয়ার্ক সমস্যা।', 'error');
  });
}

// ── Telegram — Step 2: কোড যাচাই ──
function tgVerifyOtp() {
  var el = document.getElementById('tgOtpInput');
  var code = (el ? el.value : '').trim();
  if (!/^\d{6}$/.test(code)) { _authToast('৬-সংখ্যার কোড দিন।', 'error'); return; }
  _authBusy(true, 'tgVerifyBtn');
  _verifyPost('tg_verify_otp', { code: code }).then(function(res){
    _authBusy(false, 'tgVerifyBtn');
    if (res && res.status === 'success') _onVerifySuccess('telegram', res.msg, res.phone);
    else _authToast((res && res.msg) ? res.msg : 'যাচাই ব্যর্থ।', 'error');
  }).catch(function(){ _authBusy(false, 'tgVerifyBtn'); _authToast('নেটওয়ার্ক সমস্যা।', 'error'); });
}
