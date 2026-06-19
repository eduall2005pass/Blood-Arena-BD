// ============================================================
// GLOBAL CONSTANTS — defined first, used everywhere
// ============================================================
const CSRF_TOKEN = '<?php echo $_SESSION["csrf_token"] ?? ""; ?>';
// Current Firebase-auth session state (from PHP) — null if logged out
const BA_AUTH = <?php echo json_encode(!empty($_SESSION['auth_uid']) ? [
    'provider' => $_SESSION['auth_provider'] ?? '',
    'email'    => $_SESSION['auth_email'] ?? null,
    'phone'    => $_SESSION['auth_phone'] ?? null,
    'name'     => $_SESSION['auth_name'] ?? null,
    'photo'    => $_SESSION['auth_photo'] ?? null,
    'verified' => _auth_is_verified(),
    'verify_channel' => $_SESSION['auth_verify_channel'] ?? null,
    'verify_phone' => $_SESSION['auth_verify_phone'] ?? ($_SESSION['auth_phone'] ?? null),
] : null, JSON_UNESCAPED_UNICODE); ?>;
// AJAX endpoint — always use pathname (strips ?query from URL bar)
const _AJAX_URL = window.location.origin + window.location.pathname;

// Modern animated tick / cross SVGs for popups (drawn via CSS stroke animation)
const TICK_OK = '<svg class="tick-svg" viewBox="0 0 52 52" aria-hidden="true"><circle class="tick-svg-circle" cx="26" cy="26" r="24" fill="none"/><path class="tick-svg-mark" fill="none" d="M14 27l8 8 16-18"/></svg>';
const TICK_NO = '<svg class="tick-svg" viewBox="0 0 52 52" aria-hidden="true"><circle class="tick-svg-circle" cx="26" cy="26" r="24" fill="none"/><path class="tick-svg-mark" fill="none" d="M17 17l18 18M35 17l-18 18"/></svg>';

// ============================================================
// safeJSON — InfinityFree HTML injection থেকে সুরক্ষা
// InfinityFree response-এর আগে বা পরে HTML inject করে।
// প্রথম { বা [ থেকে শেষ } বা ] পর্যন্ত extract করে parse করা হয়।
// ============================================================
function safeJSON(r) {
    return r.text().then(function(text) {
        // Direct parse — সবচেয়ে ভালো case
        try { return JSON.parse(text); }
        catch(e) {}

        // InfinityFree আগে বা পরে HTML inject করে।
        // প্রথম { খুঁজে শেষ } পর্যন্ত নাও
        var firstObj = text.indexOf('{');
        var lastObj  = text.lastIndexOf('}');
        if (firstObj !== -1 && lastObj > firstObj) {
            try { return JSON.parse(text.substring(firstObj, lastObj + 1)); }
            catch(e2) {}
        }

        // Array response — প্রথম [ থেকে শেষ ]
        var firstArr = text.indexOf('[');
        var lastArr  = text.lastIndexOf(']');
        if (firstArr !== -1 && lastArr > firstArr) {
            try { return JSON.parse(text.substring(firstArr, lastArr + 1)); }
            catch(e3) {}
        }

        // শেষ চেষ্টা — শেষ } পর্যন্ত (পুরনো logic, fallback)
        if (lastObj > 0) {
            try { return JSON.parse(text.substring(0, lastObj + 1)); }
            catch(e4) {}
        }

        // সব fail — raw text console-এ log করো debug-এর জন্য
        console.warn('[safeJSON] parse failed. Raw response:', text.substring(0, 300));
        return { status: 'error', msg: 'Response parse করা যায়নি। আবার চেষ্টা করুন।' };
    });
}

// ============================================================
// SCROLL LOCK — prevents background scroll when any popup/overlay is open
// ============================================================
let _scrollLockCount = 0;
function lockBodyScroll() {
    _scrollLockCount++;
    if (_scrollLockCount === 1) {
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + scrollY + 'px';
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.overflow = 'hidden';
        document.body.dataset.scrollY = scrollY;
    }
}
function unlockBodyScroll() {
    _scrollLockCount = Math.max(0, _scrollLockCount - 1);
    if (_scrollLockCount === 0) {
        const scrollY = parseInt(document.body.dataset.scrollY || '0', 10);
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.overflow = '';
        window.scrollTo(0, scrollY);
    }
}
function openOverlay(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('active');
    lockBodyScroll();
}
function closeOverlay(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const wasActive = el.classList.contains('active');
    el.classList.remove('active');
    if (wasActive) unlockBodyScroll();
}

// ── Global scroll-lock via MutationObserver on body ──
// Watches for any .popup-overlay or .settings-panel-overlay gaining/losing 'active'
(function() {
    function syncScrollLock() {
        // NOTE: #donorDetailPopup AND #callConfirmPopup are intentionally excluded —
        // locking the body (position:fixed + scrollTo-on-close) made the donor list
        // auto-scroll/jump when opening a card or tapping a card's 📞 call button.
        // Both are fixed/centered overlays, so they need no lock; leaving the page
        // unlocked keeps the clicked donor card exactly in place — no movement.
        const anyOpen = document.querySelector('.popup-overlay.active:not(#donorDetailPopup):not(#callConfirmPopup), .settings-panel-overlay.active');
        if (anyOpen) {
            if (document.body.dataset.scrollLocked !== '1') {
                document.body.dataset.scrollLocked = '1';
                const scrollY = window.scrollY;
                document.body.style.position = 'fixed';
                document.body.style.top = '-' + scrollY + 'px';
                document.body.style.left = '0';
                document.body.style.right = '0';
                document.body.style.overflow = 'hidden';
                document.body.dataset.scrollY = scrollY;
            }
        } else {
            if (document.body.dataset.scrollLocked === '1') {
                document.body.dataset.scrollLocked = '0';
                const scrollY = parseInt(document.body.dataset.scrollY || '0', 10);
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.overflow = '';
                window.scrollTo(0, scrollY);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const observer = new MutationObserver(syncScrollLock);
        observer.observe(document.body, { subtree: true, attributeFilter: ['class'], attributes: true });
        syncScrollLock();
    });
})();

// ── Clear App Data: strip ?_cache_bust= from URL after fresh reload ──
(function(){
    if (window.location.search.indexOf('_cache_bust=') !== -1) {
        var clean = window.location.origin + window.location.pathname;
        window.history.replaceState(null, '', clean);
    }
})();

// Prevent backdrop click closing the popup when secret code hasn't been copied yet
document.addEventListener('DOMContentLoaded', function() {
    var popupOverlay = document.getElementById('popup');
    if (popupOverlay) {
        popupOverlay.addEventListener('click', function(e) {
            if (e.target === popupOverlay) {
                // Only allow backdrop-close if: not a success popup, OR secret already copied
                if (lastStatus === 'success' && !secretCopied) {
                    // Shake the copy button to draw attention
                    var copyBtn = document.querySelector('.copy-btn');
                    if (copyBtn) {
                        copyBtn.style.animation = 'none';
                        copyBtn.offsetWidth; // reflow
                        copyBtn.style.animation = 'pulse-red 0.4s ease 3';
                        setTimeout(function() { copyBtn.style.animation = ''; }, 1200);
                    }
                    return; // Block close
                }
                closePopup();
            }
        });
    }
});

// === THEME TOGGLE LOGIC ===
// Search debounce — prevents server request on every keypress
let _searchTimer = null;
function debouncedSearch() {
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(() => fetchFilteredData(1), 400);
}

// ============================================================
// NAVIGATION — smooth scroll + active state + mobile menu
// ============================================================
function navGo(sectionId) {
    const el = document.getElementById(sectionId);
    if (!el) return;

    // Only header now (nav bar removed)
    const hdrH = (document.querySelector('header') || {offsetHeight:76}).offsetHeight;
    const top  = el.getBoundingClientRect().top + window.scrollY - hdrH - 8;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });

    // Donors section — always trigger fresh load
    if (sectionId === 'donorListSection') fetchFilteredData(1);
}



function toggleTheme() {
    const htmlObj = document.documentElement;
    const isLight = htmlObj.getAttribute('data-theme') === 'light';
    if (isLight) {
        // Light → Dark (dark = no data-theme attribute)
        htmlObj.removeAttribute('data-theme');
        localStorage.setItem('theme', 'dark');
    } else {
        htmlObj.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    }
    // Sync settings panel toggle state + icon
    if (typeof updateSettingsToggles === 'function') updateSettingsToggles();
    // Redraw badge donut for correct bg color
    if (typeof renderBadgeDonut === 'function' && window._lastBadgeData) renderBadgeDonut(window._lastBadgeData);
    // Update Leaflet map tile layer to match new theme
    if (leafletMap && window._mapTileLayer) {
        var _newTileUrl = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        leafletMap.removeLayer(window._mapTileLayer);
        window._mapTileLayer = L.tileLayer(_newTileUrl, {
            attribution: '© OpenStreetMap © CARTO',
            subdomains: 'abcd',
            maxZoom: 19
        });
        window._mapTileLayer.addTo(leafletMap);
        window._mapTileLayer.bringToBack();
        // Re-apply filters so markers use correct color after theme change
        if (_allMapMarkers.length > 0) setTimeout(function() { applyMapFilter(); }, 100);
        setTimeout(function() { if (leafletMap) leafletMap.invalidateSize(); }, 100);
    }
}
// Apply saved theme immediately on parse (light is the default)
(function(){
    if (localStorage.getItem('theme') !== 'dark') {
        document.documentElement.setAttribute('data-theme','light');
    }
})();
// Sync settings icon on load (in case page loads in light mode)
window.addEventListener('DOMContentLoaded', function() {
    if (typeof updateSettingsToggles === 'function') updateSettingsToggles();
});

// Smart location search with suggestion dropdown


document.addEventListener('mousedown', e => {
    const opt = e.target.closest('.sug-opt[data-v]');
    if (opt) {
        e.preventDefault();
        const v=opt.dataset.v, inp=opt.dataset.inp, sel=opt.dataset.sel;
        document.getElementById(inp).value = v;
        const s = document.getElementById(sel);
        if (s && s.options) { Array.from(s.options).forEach(o => { if(o.value===v) s.value=v; }); }
        else if (s) { s.value = v; }
        const sb = document.getElementById('sb_'+inp);
        if (sb) sb.classList.remove('on');
        if(sel==='locationFilter') fetchFilteredData(1);
    }
});
document.addEventListener('click', e => {
    document.querySelectorAll('.sug-list.on').forEach(b => {
        const inp = document.getElementById(b.id.replace('sb_',''));
        if(inp && !inp.contains(e.target) && !b.contains(e.target)) b.classList.remove('on');
    });
});

let locationPermissionGranted = false;
let currentLocData = "Not provided";
let tempDonorId = null;
let tempCallSourceEl = null;   // ← stores the VISIBLE donor element for auto-scroll
const _calledDonors = new Set(); // tracks donor IDs called this session
let tempName = "";
let tempLoc = "";
let lastStatus = "";
let warningAndTermsAccepted = false; 
let countdownInterval = null;
let secretCopied = false;
let countdownFinished = false;

// ── Mark a donor as called: green indicator but STILL clickable for re-call ──
function markDonorCalled(donorId) {
    if (!donorId) return;
    _calledDonors.add(String(donorId));

    // Style ALL buttons for this donor (desktop row + mobile card)
    // btn-called = green visual cue; button stays clickable so user can call again
    document.querySelectorAll(`button[onclick="prepCall('${donorId}')"]`).forEach(function(b) {
        b.classList.remove('btn-next-blink');
        b.classList.add('btn-called');
        // Red outline on the called/clicked donor card (or desktop row)
        var container = b.closest('.dc') || b.closest('.nearby-card') || b.closest('tr');
        if (container) container.classList.add('donor-called-outline');
        // Tick on left + call icon stays — user sees "called" but can still re-call
        if (b.closest('.dc')) {
            // Mobile card button: ✅ + 📞 stacked / side-by-side
            b.innerHTML = '<span style="font-size:0.65em;line-height:1;display:block;margin-bottom:1px;">✅</span>📞';
            b.title = t('আগে call করা হয়েছে — আবার tap করুন');
        } else {
            // Desktop table button: ✅ tick left, 📞 Call right
            b.innerHTML = '✅ 📞 Call';
            b.title = t('আগে call করা হয়েছে — আবার call করতে ক্লিক করুন');
        }
    });
}

// ── Find and blink the next AVAILABLE (not already called) donor button ──
function blinkNextAvailableDonor(sourceEl) {
    if (!sourceEl) return;
    // Remove old blink from any button still blinking
    document.querySelectorAll('.btn-next-blink').forEach(function(b) {
        b.classList.remove('btn-next-blink');
    });
    var next = sourceEl.nextElementSibling;
    // Walk siblings — skip hidden and already-called
    while (next) {
        if (next.style.display !== 'none' && next.offsetParent !== null) {
            // Find the call button inside this row/card
            var callBtn = next.querySelector('button[onclick^="prepCall("]');
            if (callBtn && !callBtn.classList.contains('btn-called') &&
                !callBtn.disabled && !callBtn.classList.contains('dc-call-btn-disabled')) {
                callBtn.classList.add('btn-next-blink');
                // Auto-remove blink after animation (4 repeats × 0.9s ≈ 3.7s)
                setTimeout(function() { callBtn.classList.remove('btn-next-blink'); }, 4000);
                return;
            }
        }
        next = next.nextElementSibling;
    }
}

// === TOGGLE FORM LOGIC ===
function toggleRegForm() {
    const form = document.getElementById('regForm');
    const btn = document.getElementById('toggleFormBtn');
    
    if(form.style.display === 'none') {
        form.style.display = 'block';
        setTimeout(() => {
            form.style.opacity = '1';
            form.style.transform = 'translateY(0)';
        }, 10);
        btn.innerHTML = "✖ Cancel Registration";
        btn.style.background = "var(--danger)";
        btn.style.color = "#fff";
        btn.style.boxShadow = "0 6px 20px rgba(239, 68, 68, 0.4)";
    } else {
        closeRegForm();
    }
}

function closeRegForm() {
    const form = document.getElementById('regForm');
    const btn = document.getElementById('toggleFormBtn');
    
    form.style.opacity = '0';
    form.style.transform = 'translateY(-15px)';
    
    setTimeout(() => {
        form.style.display = 'none';
    }, 400); 
    
    btn.innerHTML = "📝 Click Here to Register";
    btn.style.background = "var(--success)";
    btn.style.color = "#000";
    btn.style.boxShadow = "0 6px 20px rgba(16, 185, 129, 0.4)";
}

// NAME VALIDATION
function validateName(input) {
    input.value = input.value.replace(/[^a-zA-Z\u0980-\u09FF\s]/g, '');
}

function showValidationError(msg) {
    const overlay = document.getElementById("popup");
    const icon = document.getElementById("popupIcon");
    const title = document.getElementById("popupTitle");
    const popupMsg = document.getElementById("popupMsg");
    const notice = document.getElementById("successNotice");
    const okBtn = document.getElementById("popupOkBtn");

    icon.innerHTML = TICK_NO; 
    icon.className = "tick error-tick";
    title.innerText = "Validation Error";
    popupMsg.innerText = msg;
    notice.style.display = "none";
    okBtn.innerHTML = "OK";
    okBtn.disabled = false;
    okBtn.className = "countdown-btn active";
    okBtn.onclick = closePopup;
    if (!overlay.classList.contains('active')) { overlay.classList.add("active"); lockBodyScroll(); }
    else overlay.classList.add("active");
}

// REGISTRATION
function submitRegistration() {
    const name = document.querySelector('input[name="name"]').value.trim();
    const phone = document.querySelector('input[name="phone"]').value.trim();
    const locExact = document.getElementById('regExactLocation').value.trim();
    const group = document.querySelector('select[name="group"]').value;
    const lastDonation = document.getElementById('lastDonationHidden').value.trim();

    if (!name) return showValidationError("নাম দিতে হবে");
    if (/[^a-zA-Z\u0980-\u09FF\s]/.test(name)) return showValidationError("নামে শুধুমাত্র অক্ষর ও স্পেস থাকতে পারবে");
    if (!phone || !/^\+8801\d{9}$/.test(phone)) return showValidationError("সঠিক ফোন নম্বর দিন (+8801XXXXXXXXX)");
    if (!locExact) return showValidationError("Location লিখুন অথবা Map থেকে Pin করুন");
    if (!group) return showValidationError("রক্তের গ্রুপ নির্বাচন করুন");
    if (!lastDonation) return showValidationError("Last Blood Donation Date দিতে হবে");

    // Use exact location directly (no dropdown)
    const finalLocation = locExact;

    checkAndGetLocation(() => {
        const form = document.getElementById('regForm');
        const formData = new FormData(form);
        formData.append('ajax_submit', '1');
        formData.set('location', finalLocation);
        formData.append('device_id', (typeof getDeviceId === 'function') ? getDeviceId() : '');
        const donCount = parseInt(document.getElementById('regDonCountHidden').value)||0;
        formData.set('total_donations_reg', donCount);

        // Show loading state
        const btn = form.querySelector('button[type="button"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px;"><span style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;display:inline-block;animation:spin 0.7s linear infinite;"></span>অনুগ্রহ করে অপেক্ষা করুন...</span>';
        btn.disabled = true;

        fetch(_AJAX_URL, { method: 'POST', body: formData })
        .then(response => safeJSON(response))
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            const overlay = document.getElementById("popup");
            const icon = document.getElementById("popupIcon");
            const title = document.getElementById("popupTitle");
            const msg = document.getElementById("popupMsg");
            const notice = document.getElementById("successNotice");
            const okBtn = document.getElementById("popupOkBtn");

            if(data.status === 'success'){
                lastStatus = "";
                icon.innerHTML = TICK_OK; 
                icon.className = "tick success-tick";
                title.innerText = "✅ সফলভাবে Registered!";
                msg.innerHTML = `রক্তদান করে জীবন বাঁচানোর এই মহৎ উদ্যোগে শামিল হওয়ার জন্য আপনাকে ধন্যবাদ। আপনি Donor List-এ যুক্ত হয়ে গেছেন।
                <div style="margin-top:16px;padding:13px 15px;background:rgba(16,185,129,0.07);border:1px solid rgba(16,185,129,0.22);border-radius:12px;text-align:left;">
                  <strong style="color:var(--text-main);display:block;margin-bottom:7px;">✏️ তথ্য পরিবর্তন করতে চান?</strong>
                  <div style="font-size:0.84em;color:var(--text-muted);line-height:1.75;">
                    যেকোনো সময় <b style="color:var(--text-main);">Google</b> বা <b style="color:var(--text-main);">ফোন নম্বর</b> দিয়ে সাইন ইন করে
                    <b style="color:var(--info);">Update My Info</b> অথবা <b style="color:var(--info);">👤 আমার অ্যাকাউন্ট</b> থেকে নাম, location,
                    availability সব বদলাতে পারবেন। কোনো Secret Code মনে রাখার দরকার নেই!
                  </div>
                </div>`;
                
                notice.style.display = "none";
                document.getElementById("successSound").play();
                confetti({ particleCount: 200, spread: 120, origin: { y: 0.6 }, colors:['#dc2626', '#f59e0b', '#10b981'] });
                
                form.reset();
                document.getElementsByName('phone')[0].value = "+880";
                setDonationNever();
                closeRegForm();

                // OK immediately clickable — no countdown, no page reload
                okBtn.innerHTML = "✅ OK";
                okBtn.disabled = false;
                okBtn.className = "countdown-btn active";
                okBtn.onclick = function() {
                    document.getElementById("popup").classList.remove("active");
                    unlockBodyScroll();
                    fetchFilteredData(1);
                };
            } else {
                lastStatus = "error";
                icon.innerHTML = TICK_NO; 
                icon.className = "tick error-tick";
                title.innerText = "Registration Failed";
                msg.innerText = data.msg || "Something went wrong.";
                notice.style.display = "none";
                okBtn.innerHTML = "OK";
                okBtn.disabled = false;
                okBtn.className = "countdown-btn active";
                okBtn.onclick = closePopup;
            }
            overlay.classList.add("active");
        })
        .catch(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            const overlay = document.getElementById("popup");
            const icon = document.getElementById("popupIcon");
            const title = document.getElementById("popupTitle");
            const msg = document.getElementById("popupMsg");
            const okBtn = document.getElementById("popupOkBtn");
            icon.innerHTML = TICK_NO; 
            icon.className = "tick error-tick";
            title.innerText = "Network Error";
            msg.innerText = "Server connection failed. Please check your internet.";
            okBtn.innerHTML = "OK";
            okBtn.disabled = false;
            okBtn.className = "countdown-btn active";
            okBtn.onclick = closePopup;
            overlay.classList.add("active");
        });
    });
}

function startCountdown(btn, onDone) {
    if(countdownInterval) clearInterval(countdownInterval);
    let timeLeft = 5;
    btn.innerHTML = `OK (${timeLeft})`;
    btn.disabled = true;
    btn.className = "countdown-btn";
    
    countdownInterval = setInterval(() => {
        timeLeft--;
        btn.innerHTML = `OK (${timeLeft})`;
        if(timeLeft <= 0){
            clearInterval(countdownInterval);
            countdownFinished = true;
            btn.innerHTML = "OK";
            if(secretCopied) {
                btn.disabled = false;
                btn.className = "countdown-btn active";
                if (onDone) btn.onclick = onDone;
            }
        }
    }, 1000);
}

function closePopup(){
    const el = document.getElementById("popup");
    if (el && el.classList.contains('active')) { el.classList.remove("active"); unlockBodyScroll(); }
    lastStatus = ""; 
}

function closeAboutUs(){
    closeInfoPage();
}

// NOTE: These now route to the dedicated info pages in #infoPageOverlay.
// (The onboarding consent flow uses showTerms()/termsPopupOverlay directly and is unaffected.)
function openTermsModal() {
    openInfoPage('privacy');
}

function openAboutUsModal() {
    openInfoPage('about');
}

// DELETE DONOR
function toggleDeleteDonorSection() {
    var body  = document.getElementById('deleteDonorBody');
    var arrow = document.getElementById('deleteDonorArrow');
    if (!body) return;
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    if (arrow) arrow.style.transform = isOpen ? '' : 'rotate(90deg)';
    if (!isOpen) {
        var inp = document.getElementById('del_donor_confirm');
        if (inp) inp.value = '';
        var err = document.getElementById('del_donor_error');
        if (err) err.style.display = 'none';
    }
}

function submitDeleteDonor() {
    var confirm = document.getElementById('del_donor_confirm').value.trim();
    var errEl   = document.getElementById('del_donor_error');
    var btn     = document.getElementById('del_donor_btn');

    if (confirm !== 'DELETE') {
        errEl.textContent = '❌ নিশ্চিত করতে DELETE (বড় হাতে) লিখুন।';
        errEl.style.display = 'block'; return;
    }

    btn.disabled = true; btn.textContent = '⏳ মুছে ফেলা হচ্ছে...';
    errEl.style.display = 'none';

    var fd = new FormData();
    fd.append('delete_donor',  '1');
    fd.append('confirm',       confirm);
    fd.append('csrf_token',    CSRF_TOKEN);

    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(function(d){
        if (d.status === 'success') {
            // Reset the entire form
            document.getElementById('updateFields').style.display    = 'none';
            document.getElementById('donorBadgeCard').style.display  = 'none';
            document.getElementById('deleteDonorBody').style.display = 'none';
            showToast(d.msg || '✅ তথ্য মুছে ফেলা হয়েছে।', 'success');
        } else {
            btn.disabled = false; btn.textContent = '🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন';
            errEl.textContent = d.msg || '❌ ব্যর্থ হয়েছে।';
            errEl.style.display = 'block';
        }
    }).catch(function(){
        btn.disabled = false; btn.textContent = '🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন';
        errEl.textContent = '❌ Network error। আবার চেষ্টা করুন।';
        errEl.style.display = 'block';
    });
}

// UPDATE FORM
function submitUpdate() {
    const name    = document.getElementById('u_name').value.trim();
    const locArea = document.getElementById('u_location').value;
    const last    = document.getElementById('u_last').value.trim();
    const regGeo       = (document.getElementById('u_reg_geo') || {value:''}).value.trim();
    
    if(!name)    return showValidationError("নাম দিতে হবে");
    if(/[^a-zA-Z\u0980-\u09FF\s]/.test(name)) return showValidationError("নামে শুধুমাত্র অক্ষর ও স্পেস থাকতে পারবে");
    if(!locArea  || !locArea.trim())  return showValidationError("লোকেশন লিখতে হবে");
    if(!last)    return showValidationError("Last Blood Donation Date দিতে হবে");

    const finalLocation = locArea.trim();
    const willing       = document.getElementById('u_willing').value;
    const justDonated   = document.getElementById('u_just_donated').value;

    const fd = new FormData();
    fd.append('ajax_update',      '1');
    fd.append('name',             name);
    fd.append('location',         finalLocation);
    fd.append('last_donation',    last);
    fd.append('willing_to_donate',willing);
    fd.append('just_donated',     justDonated);
    if(regGeo)     fd.append('reg_geo_update', regGeo);
    fd.append('device_id',        (typeof getDeviceId === 'function') ? getDeviceId() : '');
    fd.append('csrf_token',       CSRF_TOKEN);

    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(data => {
        const overlay = document.getElementById("popup");
        const icon    = document.getElementById("popupIcon");
        const title   = document.getElementById("popupTitle");
        const msg     = document.getElementById("popupMsg");
        const notice  = document.getElementById("successNotice");
        const okBtn   = document.getElementById("popupOkBtn");

        if(data.status === "success"){
            icon.innerHTML = TICK_OK;
            icon.className = "tick success-tick";
            title.innerText = "Update Successful";

            msg.innerText = data.msg;
            notice.style.display = "none";
            okBtn.innerHTML = "OK"; okBtn.disabled = false;
            okBtn.className = "countdown-btn active";
            okBtn.onclick = () => {
                document.getElementById("popup").classList.remove("active");
                unlockBodyScroll();
                location.reload();
            };

            // Update badge card live
            if(data.total_donations !== undefined) {
                updateBadgeCard(data.total_donations, data.badge_icon, data.badge_level);
            }
            // Reset just_donated flag
            document.getElementById('u_just_donated').value = '0';
        } else {
            icon.innerHTML = TICK_NO;
            icon.className = "tick error-tick";
            title.innerText = "Update Failed";
            msg.innerText = data.msg;
            notice.style.display = "none";
            okBtn.innerHTML = "OK"; okBtn.disabled = false;
            okBtn.className = "countdown-btn active";
            // Re-enable justDonatedBtn so user can retry
            const jdb = document.getElementById('justDonatedBtn');
            if(jdb && document.getElementById('u_just_donated').value === '1'){
                jdb.disabled = false; jdb.style.opacity = ''; jdb.style.cursor = '';
            }
            okBtn.onclick = () => {
                document.getElementById("popup").classList.remove("active");
                unlockBodyScroll();
            };
        }
        overlay.classList.add("active");
    })
    .catch(() => {
        const jdb = document.getElementById('justDonatedBtn');
        if(jdb && document.getElementById('u_just_donated').value === '1'){
            jdb.disabled = false; jdb.style.opacity = ''; jdb.style.cursor = '';
        }
        showValidationError("Network error। Internet connection চেক করুন।");
    });
}

// CALLER & REPORT

function submitReport() {
    const phone = document.getElementById('repDonorPhone').value.trim();
    const hInfo = document.getElementById('harasserInfo').value.trim();
    const comment = document.getElementById('reportComment').value.trim();

    if(!phone) return showValidationError("দাতার ফোন নম্বর দিন");
    if(!hInfo) return showValidationError("হয়রানিকারীর তথ্য দিন");
    if(!comment) return showValidationError("অভিযোগ লিখুন");

    const formData = new FormData();
    formData.append('submit_report', '1');
    formData.append('donor_phone', phone);
    formData.append('harasser_info', hInfo);
    formData.append('report_comment', comment);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL, { method: 'POST', body: formData })
    .then(r => r.text())
    .then(res => {
        const raw = res.trim();
        if (raw === 'success') {
            // Close report popup properly
            const rp = document.getElementById('reportPopup');
            if (rp && rp.classList.contains('active')) {
                rp.classList.remove('active');
                unlockBodyScroll();
            }
            // Show success using the standard popup (no ugly alert)
            const overlay = document.getElementById("popup");
            const icon    = document.getElementById("popupIcon");
            const title   = document.getElementById("popupTitle");
            const msg     = document.getElementById("popupMsg");
            const notice  = document.getElementById("successNotice");
            const okBtn   = document.getElementById("popupOkBtn");
            icon.innerHTML = TICK_OK; icon.className = "tick success-tick";
            title.innerText = "রিপোর্ট সফলভাবে জমা হয়েছে";
            msg.innerText   = "আপনার অভিযোগটি গ্রহণ করা হয়েছে। অ্যাডমিন দ্রুত ব্যবস্থা নেবেন।";
            notice.style.display = "none";
            okBtn.innerHTML = "OK"; okBtn.disabled = false;
            okBtn.className = "countdown-btn active";
            okBtn.onclick = closePopup;
            overlay.classList.add("active");
            // Clear the report form
            document.getElementById('repDonorPhone').value = '';
            document.getElementById('harasserInfo').value  = '';
            document.getElementById('reportComment').value = '';
        } else {
            // Server returned a JSON error
            try {
                const d = JSON.parse(raw);
                showValidationError(d.msg || "রিপোর্ট পাঠাতে সমস্যা হয়েছে।");
            } catch(e) {
                showValidationError("রিপোর্ট পাঠাতে সমস্যা হয়েছে। আবার চেষ্টা করুন।");
            }
        }
    })
    .catch(() => showValidationError("Network error। Internet connection চেক করুন।"));
}

// ============================================================
// GPS PERMISSION — soft prompt, non-blocking
// ============================================================
let _gpsCallback = null;
let _gpsContext  = 'general';

function gpsAllow() {
    document.getElementById('gpsPermPrompt').classList.remove('active');
    _saveDeviceId('loc_allow');
    if (!navigator.geolocation) { const cb = _gpsCallback; _gpsCallback = null; if (cb) cb(); return; }
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            currentLocData = 'Lat: ' + pos.coords.latitude + ', Lon: ' + pos.coords.longitude;
            locationPermissionGranted = true;
            const geoEl = document.getElementById('reg_geo_location');
            if (geoEl) geoEl.value = currentLocData;
            const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
        },
        function() {
            currentLocData = 'Not provided';
            const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
        },
        { timeout: 12000, enableHighAccuracy: true }
    );
}

function gpsSkip() {
    document.getElementById('gpsPermPrompt').classList.remove('active');
    _saveDeviceId('loc_deny');
    currentLocData = 'Not provided';
    const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
}

function requestGPSWithPrompt(context, callback) {
    _gpsCallback = callback;
    _gpsContext  = context;
    const msgs = {
        call:      'কল করার আগে আপনার Location log করা হবে — জালিয়াতি প্রতিরোধের জন্য।',
        register:  'Registration-এর সময় আপনার GPS Location সংরক্ষণ করা হবে।',
        emergency: 'Emergency request-এর সাথে আপনার Location log করা হবে।',
        general:   'আপনার location log করা হবে — শুধুমাত্র নিরাপত্তার জন্য।'
    };
    if (!navigator.geolocation) { const cb = _gpsCallback; _gpsCallback = null; if (cb) cb(); return; }
    
    // Try permissions API first (modern browsers)
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({name:'geolocation'}).then(function(r) {
            if (r.state === 'granted') {
                // Already granted — get location silently
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        currentLocData = 'Lat: ' + pos.coords.latitude + ', Lon: ' + pos.coords.longitude;
                        locationPermissionGranted = true;
                        const geoEl = document.getElementById('reg_geo_location');
                        if (geoEl) geoEl.value = currentLocData;
                        const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
                    },
                    function() { const cb = _gpsCallback; _gpsCallback = null; if (cb) cb(); },
                    { timeout: 8000, enableHighAccuracy: false }
                );
                return;
            }
            if (r.state === 'denied') {
                // Denied — collect device ID silently, skip and proceed
                _saveDeviceId('loc_deny');
                currentLocData = 'Not provided';
                const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
                return;
            }
            // 'prompt' — show our friendly UI first
            const msgEl = document.getElementById('gpsPromptMsg');
            if (msgEl) msgEl.textContent = msgs[context] || msgs.general;
            document.getElementById('gpsPermPrompt').classList.add('active');
        }).catch(function() {
            // permissions API not supported — try directly
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    currentLocData = 'Lat: ' + pos.coords.latitude + ', Lon: ' + pos.coords.longitude;
                    locationPermissionGranted = true;
                    const geoEl = document.getElementById('reg_geo_location');
                    if (geoEl) geoEl.value = currentLocData;
                    const cb = _gpsCallback; _gpsCallback = null; if (cb) cb();
                },
                function() { currentLocData = 'Not provided'; const cb = _gpsCallback; _gpsCallback = null; if (cb) cb(); },
                { timeout: 8000, enableHighAccuracy: false }
            );
        });
    } else {
        // No permissions API — show prompt anyway if not yet asked
        const msgEl = document.getElementById('gpsPromptMsg');
        if (msgEl) msgEl.textContent = msgs[context] || msgs.general;
        document.getElementById('gpsPermPrompt').classList.add('active');
    }
}

function requestLocationAgain() {
    requestGPSWithPrompt('general', function() {
        const overlay = document.getElementById('locationBlockedOverlay');
        if (overlay) { overlay.style.display = 'none'; document.body.style.overflow = 'auto'; }
        if (tempDonorId) prepCall(tempDonorId);
    });
}

function checkAndGetLocation(callback) {
    requestGPSWithPrompt('register', callback);
}

// ============================================================
// LEAFLET MAP PICKER (replaces broken Google Maps iframe)
// ============================================================
let _mapPickerMap = null;
let _mapPickerMarker = null;

function openMapPicker() {
    const modal   = document.getElementById('mapPickerModal');
    const loading = document.getElementById('mapPickerLoading');
    const resultEl = document.getElementById('mapPickerResult');
    modal.classList.add('active');
    loading.style.display = 'flex';
    resultEl.value = '';

    // Wait for Leaflet to be ready
    function initPickerMap() {
        if (typeof L === 'undefined') { setTimeout(initPickerMap, 400); return; }
        loading.style.display = 'none';

        const mapDiv = document.getElementById('leafletMapPicker');
        if (!_mapPickerMap) {
            // Default center: Dhaka, Bangladesh
            _mapPickerMap = L.map('leafletMapPicker', { zoomControl: true }).setView([23.7735, 90.3742], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap © CARTO',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(_mapPickerMap);

            // Click handler — drop a pin and reverse geocode
            _mapPickerMap.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);

                if (_mapPickerMarker) {
                    _mapPickerMarker.setLatLng(e.latlng);
                } else {
                    _mapPickerMarker = L.marker(e.latlng, { draggable: true }).addTo(_mapPickerMap);
                    _mapPickerMarker.on('dragend', function() {
                        const p = _mapPickerMarker.getLatLng();
                        doReverseGeocode(p.lat.toFixed(6), p.lng.toFixed(6));
                    });
                }
                doReverseGeocode(lat, lng);
            });
        } else {
            // Reset marker on re-open
            if (_mapPickerMarker) { _mapPickerMap.removeLayer(_mapPickerMarker); _mapPickerMarker = null; }
        }
        // Force resize in case modal was hidden
        setTimeout(() => { if (_mapPickerMap) _mapPickerMap.invalidateSize(); }, 400);

        // Try to centre on user location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                _mapPickerMap.setView([pos.coords.latitude, pos.coords.longitude], 15);
            }, null, { timeout: 5000 });
        }
    }
    setTimeout(initPickerMap, 150);
}

function doReverseGeocode(lat, lng) {
    const resultEl = document.getElementById('mapPickerResult');
    resultEl.value = `Lat: ${lat}, Lon: ${lng} (লোড হচ্ছে...)`;
    // Use Nominatim (free, no key required)
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`, {
        headers: { 'Accept-Language': 'en' }
    })
    .then(r => r.json())
    .then(d => {
        const addr = d.address || {};
        const parts = [
            addr.road || addr.neighbourhood || addr.suburb,
            addr.city_district || addr.suburb || addr.town || addr.city,
            addr.city || addr.county
        ].filter(Boolean);
        const readable = parts.length ? parts.join(', ') : d.display_name;
        if (resultEl) resultEl.value = readable;
        if (_mapPickerMarker) _mapPickerMarker.bindPopup(`📍 ${readable}`).openPopup();
    })
    .catch(() => {
        if (resultEl) resultEl.value = `Lat: ${lat}, Lon: ${lng}`;
    });
}

function mapGoToMyLocation() {
    const btn = document.getElementById('mapMyLocBtn');
    if (!_mapPickerMap || !navigator.geolocation) {
        showToast('GPS পাওয়া যাচ্ছে না।', 'error'); return;
    }
    if (btn) btn.textContent = '⏳';
    navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        _mapPickerMap.setView([lat, lng], 16);
        const latlng = L.latLng(lat, lng);
        if (_mapPickerMarker) {
            _mapPickerMarker.setLatLng(latlng);
        } else {
            _mapPickerMarker = L.marker(latlng, { draggable: true }).addTo(_mapPickerMap);
            _mapPickerMarker.on('dragend', function() {
                const p = _mapPickerMarker.getLatLng();
                doReverseGeocode(p.lat.toFixed(6), p.lng.toFixed(6));
            });
        }
        doReverseGeocode(lat.toFixed(6), lng.toFixed(6));
        if (btn) btn.textContent = '📍';
    }, function() {
        if (btn) btn.textContent = '📍';
        showToast('Location পাওয়া যায়নি। Browser-এ Permission দিন।', 'warning');
    }, { timeout: 8000, enableHighAccuracy: true });
}

function doMapSearch() {
    const q = (document.getElementById('mapSearchInput') || {}).value;
    if (!q || !q.trim()) return;
    const searchBtn = document.querySelector('#mapPickerModal [onclick="doMapSearch()"]');
    if (searchBtn) { searchBtn.textContent = '⏳'; searchBtn.disabled = true; }
    // Use Nominatim geocoding
    fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q.trim() + ', Dhaka, Bangladesh') + '&limit=1&accept-language=en', {
        headers: { 'Accept-Language': 'en' }
    })
    .then(r => r.json())
    .then(results => {
        if (searchBtn) { searchBtn.textContent = '🔍 খুঁজুন'; searchBtn.disabled = false; }
        if (!results || !results.length) {
            // Try without Bangladesh filter
            return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q.trim()) + '&limit=1&accept-language=en', {
                headers: { 'Accept-Language': 'en' }
            })
                .then(r => r.json())
                .then(r2 => {
                    if (!r2 || !r2.length) { showToast('এলাকা খুঁজে পাওয়া যায়নি। অন্য নাম দিন।', 'warning'); return; }
                    _applyMapSearchResult(r2[0]);
                });
        }
        _applyMapSearchResult(results[0]);
    })
    .catch(() => {
        if (searchBtn) { searchBtn.textContent = '🔍 খুঁজুন'; searchBtn.disabled = false; }
        showToast('Search কাজ করছে না। Internet চেক করুন।', 'error');
    });
}

function _applyMapSearchResult(result) {
    if (!_mapPickerMap || !result) return;
    const lat = parseFloat(result.lat), lng = parseFloat(result.lon);
    _mapPickerMap.setView([lat, lng], 16);
    const latlng = L.latLng(lat, lng);
    if (_mapPickerMarker) {
        _mapPickerMarker.setLatLng(latlng);
    } else {
        _mapPickerMarker = L.marker(latlng, { draggable: true }).addTo(_mapPickerMap);
        _mapPickerMarker.on('dragend', function() {
            const p = _mapPickerMarker.getLatLng();
            doReverseGeocode(p.lat.toFixed(6), p.lng.toFixed(6));
        });
    }
    doReverseGeocode(lat.toFixed(6), lng.toFixed(6));
}

function closeMapPicker() {
    document.getElementById('mapPickerModal').classList.remove('active');
    // Invalidate size to prevent grey tiles next open
    if (_mapPickerMap) setTimeout(() => _mapPickerMap.invalidateSize(), 400);
}

function useMapPickerLocation() {
    const val = document.getElementById('mapPickerResult').value.trim();
    if (!val || val.includes('লোড হচ্ছে')) { showValidationError('Map-এ ক্লিক করুন অথবা location লিখুন।'); return; }
    const exactEl = document.getElementById('regExactLocation');
    if (exactEl) exactEl.value = val;
    closeMapPicker();
}


// CALL FUNCTIONS
function prepCall(donorId) {
    vibrateIfOn([30, 15, 30]);
    // ── Verification gate: সাইন ইন করা না থাকলে / unverified হলে call আটকাও ──
    //  (server-side get_phone/log_call এটিই enforce করে — এটি শুধু UX।)
    if (!_isSignedIn()) {
        showToast('📞 কল করতে আগে সাইন ইন করুন।', 'info');
        if (typeof openAuthModal === 'function') openAuthModal();
        return;
    }
    if (!_isVerified()) {
        showToast('🔒 কল করতে Telegram বা WhatsApp দিয়ে অ্যাকাউন্ট verify করুন।', 'info');
        if (typeof openVerifyModal === 'function') openVerifyModal();
        return;
    }
    tempDonorId = donorId;
    tempCallSourceEl = null;

    // ── FIX: Desktop <tr> ও Mobile .dc দুটোতেই একই onclick button থাকে।
    // querySelector সবসময় DOM-এ প্রথমটা (প্রায়ই hidden desktop row) নিত,
    // তাই auto-scroll সবসময় same donor-এ আটকে থাকত।
    // offsetParent চেক করে শুধু VISIBLE button খুঁজে নেওয়া হচ্ছে।
    const allBtns = document.querySelectorAll(`button[onclick="prepCall('${donorId}')"]`);
    let btn = null;
    for (const b of allBtns) {
        if (b.offsetParent !== null) { btn = b; break; }
    }

    if (btn) {
        const row  = btn.closest('tr');
        const card = btn.closest('.dc') || btn.closest('.nearby-card');
        if (row) {
            tempCallSourceEl = row;
            tempName = row.cells[1] ? row.cells[1].innerText.trim() : "Donor";
            tempLoc  = row.cells[4] ? row.cells[4].innerText.trim() : "N/A";
        } else if (card) {
            tempCallSourceEl = card;
            tempName = (card.querySelector('.dc-name') || {}).innerText || "Donor";
            tempLoc  = (card.querySelector('.dc-loc')  || {}).innerText || "N/A";
        }
    }

    // ── Caller identity = verified account (আর আলাদা popup-এ চাওয়া হয় না) ──
    //  সাইন-ইন + verified গেট উপরে পাস হয়েছে, তাই verified নম্বরই caller।
    //  log_call server-side session থেকে নম্বর নেয় — এখানে শুধু confirm popup UX।
    const _a = (typeof _authState === 'function') ? _authState() : null;
    const callerPhone = (_a && (_a.verify_phone || _a.phone)) || '';
    const callerName  = (_a && _a.name) || callerPhone;
    showConfirmPopup(callerName, callerPhone);
    // ── Pre-fetch phone number in background — ready before user taps Call ──
    // Without this: user taps Call → ⏳ wait for fetch → then dials (noticeable delay).
    // With this: fetch starts NOW during confirm popup display (~300-800ms head start).
    var _prefetchId = String(donorId);
    var _pd = new FormData();
    _pd.append('get_phone','1'); _pd.append('id', donorId);
    _pd.append('csrf_token', CSRF_TOKEN);
    window._prefetchedPhone = null;
    window._prefetchDonorId = _prefetchId;
    fetch(_AJAX_URL, {method:'POST', body:_pd})
        .then(function(r){ return r.text(); })
        .then(function(raw){
            var phone = raw.trim();
            if (/^\+8801\d{9}$/.test(phone) && window._prefetchDonorId === _prefetchId) {
                window._prefetchedPhone = phone;
            }
        }).catch(function(){});

    // GPS in background — ready by the time user taps Call button
    if (navigator.geolocation && !currentLocData) {
        navigator.geolocation.getCurrentPosition(
            function(p){ currentLocData = 'Lat:'+p.coords.latitude+',Lon:'+p.coords.longitude; },
            function(){},
            { timeout:5000, enableHighAccuracy:false, maximumAge:120000 }
        );
    }
}

function showConfirmPopup(callerName, callerPhone) {
    document.getElementById("confDonorName").innerText = tempName || "Donor";
    document.getElementById("confDonorLoc").innerText = tempLoc || "N/A";
    document.getElementById("callConfirmPopup").classList.add("active");

    function execContact(type) {
        const callBtn = document.getElementById("finalCallBtn");
        const waBtn   = document.getElementById("finalWaBtn");
        callBtn.innerHTML = "⏳"; callBtn.disabled = true;
        waBtn.innerHTML   = "⏳"; waBtn.disabled   = true;

        // ── Use pre-fetched phone if ready; otherwise fetch now ──
        var _cachedPhone = (window._prefetchedPhone && window._prefetchDonorId === String(tempDonorId))
                            ? window._prefetchedPhone : null;

        var _doCall = function(phone) {
            callBtn.innerHTML = "📞 Call"; callBtn.disabled = false;
            waBtn.innerHTML   = "💬 WhatsApp"; waBtn.disabled   = false;
            // server verification gate — stale client state হলে এখানে ধরা পড়ে
            if (phone === 'unverified') {
                document.getElementById("callConfirmPopup").classList.remove("active");
                showToast('🔒 কল করতে Telegram বা WhatsApp দিয়ে অ্যাকাউন্ট verify করুন।', 'info');
                if (typeof openVerifyModal === 'function') openVerifyModal();
                return;
            }
            if (!phone || !/^\+8801\d{9}$/.test(phone)) {
                showToast('দাতার তথ্য পাওয়া যায়নি। আবার চেষ্টা করুন।', 'error'); return;
            }

            // ── log_call fire-and-forget ──
            const ld = new FormData();
            ld.append('log_call','1'); ld.append('donor_id', tempDonorId);
            ld.append('caller_name', callerName); ld.append('caller_phone', callerPhone);
            ld.append('location_data', currentLocData);
            ld.append('csrf_token', CSRF_TOKEN);
            fetch(_AJAX_URL, {method:'POST', body:ld}).catch(function(){});

            // ── Mark this donor as called (green button) & blink next available ──
            //  NOTE: no auto-scroll here — clicking Call must NOT move the page.
            var _sourceEl = tempCallSourceEl; // save before cleanup
            markDonorCalled(tempDonorId);
            if (_sourceEl) blinkNextAvailableDonor(_sourceEl);

            // ── Close popup ──
            // callConfirmPopup is excluded from the body scroll-lock (see syncScrollLock),
            // so closing it does NOT scrollTo/jump — the clicked card stays exactly in place.
            document.getElementById("callConfirmPopup").classList.remove("active");
            tempCallSourceEl = null;

            // ── Dial LAST — after popup close so mobile suspension won't cancel it ──
            if (type === 'wa') {
                window.open("https://wa.me/" + phone.replace('+',''), "_blank");
            } else {
                window.location.href = "tel:" + phone;
            }
        }; // end _doCall

        // ── Use cache if phone already fetched, else fetch now ──
        if (_cachedPhone) {
            _doCall(_cachedPhone);
        } else {
            const pd = new FormData();
            pd.append('get_phone','1'); pd.append('id', tempDonorId);
            pd.append('csrf_token', CSRF_TOKEN);
            fetch(_AJAX_URL, {method:'POST', body:pd})
                .then(function(r){ return r.text(); })
                .then(function(raw){ _doCall(raw.trim()); })
                .catch(function(){
                    callBtn.innerHTML = "📞 Call"; callBtn.disabled = false;
                    waBtn.innerHTML   = "💬 WhatsApp"; waBtn.disabled = false;
                    showToast('Network error। Internet connection চেক করুন।', 'error');
                });
        }
    }

    document.getElementById("finalCallBtn").onclick = function(){ execContact('call'); };
    document.getElementById("finalWaBtn").onclick   = function(){ execContact('wa'); };
}

function openGeneralReportModal() {
    document.getElementById('repDonorPhone').value = "";
    document.getElementById('reportPopup').classList.add('active');
}

function handleNameFocus() {
    if (!warningAndTermsAccepted) {
        document.getElementById('warningPopupOverlay').classList.add('active');
        document.querySelector('input[name="name"]').blur();
    }
}

function showTerms() {
    document.getElementById('warningPopupOverlay').classList.remove('active');
    document.getElementById('termsPopupOverlay').classList.add('active');
}

function dismissAllPopups() {
    document.getElementById('termsPopupOverlay').classList.remove('active');
    document.getElementById('warningPopupOverlay').classList.remove('active');
    warningAndTermsAccepted = true; 
    document.querySelector('input[name="name"]').focus();
    
    // Automatically open form if not opened
    if(document.getElementById('regForm').style.display === 'none'){
        toggleRegForm();
    }
}

// QUICK FILTER + PAGINATION RESET
// RESET ALL FILTERS
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('groupFilter').value = 'All';
    document.getElementById('statusFilter').value = 'All';
    document.getElementById('locationFilter').value = 'All';
    const df = document.getElementById('donatedFilter');
    if (df) df.value = '0';
    const bf = document.getElementById('badgeFilter');
    if (bf) bf.value = 'All';
    // Reset quick filter buttons
    document.querySelectorAll('.shift-btn').forEach(b => b.classList.remove('active'));
    const allBtn = document.querySelector('.shift-btn');
    if (allBtn) allBtn.classList.add('active');
    fetchFilteredData(1);
}

function quickFilter(group) {
    const _df = document.getElementById('donatedFilter');
    if (_df) _df.value = '0';
    document.getElementById('groupFilter').value = group;
    if(group !== 'All') {
        document.getElementById('statusFilter').value = 'Available';
    } else {
        document.getElementById('statusFilter').value = 'All';
    }
    
    const buttons = document.querySelectorAll('.shift-btn');
    buttons.forEach(btn => {
        // FIX: exact match — আগে .includes() ছিল, তাই 'B+' সিলেক্ট করলে
        // 'AB+' ও active হয়ে যেত (substring match)। একইভাবে 'B-' → 'AB-'।
        const label = btn.innerText.trim();
        const isMatch = (group === 'All')
            ? (label === 'All Groups' || label === 'All')
            : (label === group);
        btn.classList.toggle('active', isMatch);
    });

    fetchFilteredData(1);
    
    // Scroll to table — instant to avoid janky smooth scroll on mobile
    const section = document.getElementById('donorListSection');
    const y = section.getBoundingClientRect().top + window.pageYOffset - 70;
    window.scrollTo({ top: y });
}

// ── Stats (Analytics) KPI cards → jump to the matching donor view ──
// total → all donors · available → Available only · unavailable → Not Willing
// donated → recently-donated list (sorted by last donation date) · requests → Active Requests
function kpiGoto(type) {
    if (type === 'requests') {
        appSwitchPage('requests');   // dedicated Active Requests page (loads its own data)
        return;
    }

    // Donor-list views — set filters first, then switch (appSwitchPage triggers the fetch)
    const g  = document.getElementById('groupFilter');
    const s  = document.getElementById('statusFilter');
    const b  = document.getElementById('badgeFilter');
    const se = document.getElementById('searchInput');
    const df = document.getElementById('donatedFilter');
    if (g)  g.value  = 'All';
    if (b)  b.value  = 'All';
    if (se) se.value = '';
    if (df) df.value = '0';
    if (s)  s.value  = 'All';

    if (type === 'available')        { if (s) s.value = 'Available'; }
    else if (type === 'unavailable') { if (s) s.value = 'Unavailable'; }
    else if (type === 'donated')     { if (df) df.value = '1'; }
    // 'total' → all defaults

    appSwitchPage('donors');
    fetchFilteredData(1);
}

// FULL AJAX WITH PAGINATION & LOCATION FILTER
// ── PERFORMANCE: abort previous filter requests ──
let _filterController = null;

function fetchFilteredData(page = 1, doScroll = false) {
    if (_filterController) _filterController.abort();
    _filterController = new AbortController();
    const group    = document.getElementById('groupFilter').value;
    const search   = document.getElementById('searchInput').value;
    const status   = document.getElementById('statusFilter').value;
    const location = document.getElementById('locationFilter').value;
    const badge    = document.getElementById('badgeFilter')?.value || 'All';
    const donated  = document.getElementById('donatedFilter')?.value || '0';

    const tableBody = document.getElementById('donorTableBody');
    const cardsBody = document.getElementById('donorCardsBody');

    // Skeleton for table
    let skeletonHtml = '';
    for (let i = 0; i < 5; i++) { 
        skeletonHtml += `<tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>`;
    }
    tableBody.innerHTML = skeletonHtml;

    // Skeleton for mobile cards
    let cardSkel = '';
    for (let i = 0; i < 4; i++) {
        cardSkel += `<div class="dc dc-skeleton"><div class="skeleton" style="height:18px;margin-bottom:8px;width:60%;"></div><div class="skeleton" style="height:14px;margin-bottom:8px;width:80%;"></div><div class="skeleton" style="height:44px;border-radius:10px;"></div></div>`;
    }
    cardsBody.innerHTML = cardSkel;

    const formData = new FormData();
    formData.append('ajax_filter', '1');
    formData.append('filter_group', group);
    formData.append('search_query', search);
    formData.append('filter_status', status);
    formData.append('filter_location', location);
    formData.append('filter_badge', badge);
    formData.append('filter_donated', donated);
    formData.append('page', page);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL, { method: 'POST', body: formData, signal: _filterController.signal })
    .then(response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return safeJSON(response);
    })
    .then(data => {
        tableBody.innerHTML  = data.table  || `<tr><td colspan='7' class='no-data'>✖ কোনো রক্তদাতা পাওয়া যায়নি।</td></tr>`;
        cardsBody.innerHTML  = data.cards  || `<div class='no-data' style='text-align:center;padding:30px;'>✖ কোনো রক্তদাতা পাওয়া যায়নি।</div>`;
        const pagEl = document.getElementById('paginationSection');
        if (pagEl && data.pagination) pagEl.innerHTML = data.pagination;

        // Update stat cards + hero bar with fresh global counts from server
        if (data.counts) {
            const groupMap = {'A+':'Aplus','A-':'Aminus','B+':'Bplus','B-':'Bminus','AB+':'ABplus','AB-':'ABminus','O+':'Oplus','O-':'Ominus'};
            for (const [g, id] of Object.entries(groupMap)) {
                const el = document.getElementById('count-' + id);
                if (el) {
                    const cnt = data.counts[g] || 0;
                    el.textContent = '🩸 ' + cnt + ' Available';
                }
            }
            // Also update hero bar available count
            if (typeof data.total_available !== 'undefined') {
                const heroAvail = document.getElementById('heroAvailDonors');
                if (heroAvail) heroAvail.textContent = data.total_available;
            }
        }

        // Update bottom nav active state — only if currently on donors page
        if (_currentPage === 'donors') updateBottomNav('donors');

        if (doScroll) {
            const target = document.getElementById('donorListSection');
            if (target) {
                const offset = target.getBoundingClientRect().top + window.scrollY - 72;
                window.scrollTo({ top: offset, behavior: 'smooth' });
            }
        }
    })
    .catch(e => {
        if (e && e.name === 'AbortError') return; // ignore aborted requests
        tableBody.innerHTML = `<tr><td colspan='7' class='no-data'>✖ লোড করতে সমস্যা হয়েছে। পেজ রিফ্রেশ করুন।</td></tr>`;
        cardsBody.innerHTML = `<div class='no-data' style='text-align:center;padding:30px;'>✖ লোড করতে সমস্যা হয়েছে। পেজ রিফ্রেশ করুন।</div>`;
    });
}

// TAB SWITCH
function switchTab(n) {
    vibrateIfOn([12]);
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('#regSection .tab-btn').forEach(b => b.classList.remove('active'));
    const tabEl = document.getElementById('tab'+n);
    if (tabEl) tabEl.classList.add('active');
    const btns = document.querySelectorAll('#regSection .tab-btn');
    if (btns[n]) btns[n].classList.add('active');
}

// সাইন-ইন করা account-এর নিজের donor record auto-load করে (secret code লাগে না)
function loadMyDonorInfo() {
    // সাইন ইন না থাকলে আগে auth modal খোলো
    if (!_isSignedIn()) {
        showToast('তথ্য দেখতে/বদলাতে আগে সাইন ইন করুন।', 'info');
        if (typeof openAuthModal === 'function') openAuthModal();
        return;
    }

    const fd = new FormData();
    fd.append('load_my_donor', '1');
    fd.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(data => {
        if (data && data.status === 'error') {
            if (data.code === 'auth_required') {
                if (typeof openAuthModal === 'function') openAuthModal();
                return;
            }
            if (data.code === 'no_donor') {
                // donor profile নেই — registration tab-এ পাঠাও
                showToast('আপনার কোনো donor profile নেই। আগে রেজিস্ট্রেশন করুন।', 'info');
                if (typeof switchTab === 'function') switchTab(0);
                return;
            }
            showValidationError(data.msg || 'তথ্য আনা যায়নি।');
            return;
        }
        try {
            _loadUpdateFields(data);
        } catch(jsErr) {
            console.error('[loadMyDonorInfo] JS error in _loadUpdateFields:', jsErr);
            showValidationError("কিছু একটা সমস্যা হয়েছে। পেজ Refresh করে আবার চেষ্টা করুন।");
        }
    })
    .catch(netErr => {
        console.error('[loadMyDonorInfo] fetch error:', netErr);
        showValidationError("Network error। Internet connection চেক করুন আবার চেষ্টা করুন।");
    });
}

function _loadUpdateFields(data) {
    if(data.status === "success"){
        document.getElementById('updateFields').style.display = 'block';
        document.getElementById('u_name').value = data.name;

        // Parse Location — defensive null check (uExactLocation may not exist in all builds)
        let fullLoc = data.location || '';
        let parts = fullLoc.split(" - ");
        let areaVal  = parts[0] ? parts[0].trim() : fullLoc;
        let exactVal = parts.length > 1 ? parts.slice(1).join(" - ").trim() : "";
        const uLocEl = document.getElementById('u_location');
        if (uLocEl) uLocEl.value = areaVal;
        const uExactEl = document.getElementById('uExactLocation');
        if (uExactEl) uExactEl.value = exactVal;

        // Smart date picker
        if(!data.last_donation || data.last_donation === 'no') {
            setUpdateDonationNever();
        } else {
            var parts2 = data.last_donation.split('/');
            if(parts2.length === 3) {
                setUpdateDonationDate(parts2[2]+'-'+parts2[1]+'-'+parts2[0]);
            } else {
                setUpdateDonationNever();
            }
        }

        // Willing toggle
        setWilling(data.willing || 'yes');

        // Badge card
        document.getElementById('donorBadgeCard').style.display = 'block';
        updateBadgeCard(data.total_donations || 0, data.badge_icon, data.badge_level);

        // 120-day lock
        checkJustDonatedLock(data.last_donation);

        document.getElementById('updateFields').scrollIntoView({ block: 'center' });
    } else {
        showValidationError(data.msg || "❔ সার্ট কোড সঠিক নয় বা খুঁজে পাওয়া যায়নি।");
    }
}

// ── Change 5: Just Donated 120-day lock ────────────────────
function checkJustDonatedLock(lastDonation) {
    const btn     = document.getElementById('justDonatedBtn');
    const lockMsg = document.getElementById('justDonatedLockMsg');
    if(!btn) return;

    if(!lastDonation || lastDonation === 'no') {
        // Never donated — unlock
        btn.disabled = false;
        btn.style.opacity = '';
        btn.style.cursor  = '';
        btn.innerHTML = '🩸 আমি এইমাত্র রক্ত দিয়েছি — Update করুন';
        lockMsg.style.display = 'none';
        return;
    }

    // Parse dd/mm/yyyy
    var parts = lastDonation.split('/');
    if(parts.length !== 3) { btn.disabled = false; lockMsg.style.display='none'; return; }
    var lastDate = new Date(parts[2], parts[1]-1, parts[0]);
    var now      = new Date();
    var diffDays = Math.floor((now - lastDate) / (1000*60*60*24));
    var remaining = 120 - diffDays;

    if(remaining > 0) {
        btn.disabled      = true;
        btn.style.opacity = '0.45';
        btn.style.cursor  = 'not-allowed';
        btn.innerHTML     = '⏳ রক্তদানের ১২০ দিন হয়নি';
        lockMsg.style.display = 'block';
        lockMsg.textContent   = '⚠️ শেষ রক্তদানের পর আরও ' + remaining + ' দিন বাকি। (' + lastDonation + ' থেকে ১২০ দিন)';
    } else {
        btn.disabled = false;
        btn.style.opacity = '';
        btn.style.cursor  = '';
        btn.innerHTML = '🩸 আমি এইমাত্র রক্ত দিয়েছি — Update করুন';
        lockMsg.style.display = 'none';
    }
}

function updateBadgeCard(total, icon, level) {
    document.getElementById('badgeIconBig').textContent = icon || '🌱';
    document.getElementById('badgeLevelName').textContent = (icon||'🌱') + ' ' + (level||'New') + ' Donor';
    document.getElementById('badgeDonations').textContent = total + ' টি রক্তদান';
    let next, needed, progressPct;
    if(total < 2)      { next='Active'; needed=2; progressPct=(total/2)*100; }
    else if(total < 5) { next='Hero';   needed=5; progressPct=(total/5)*100; }
    else if(total < 10){ next='Legend'; needed=10; progressPct=(total/10)*100; }
    else               { next='MAX';    needed=10; progressPct=100; }
    document.getElementById('badgeProgressFill').style.width = progressPct + '%';
    document.getElementById('badgeNextLabel').textContent = next === 'MAX' ? '🏆 সর্বোচ্চ স্তর!' : `পরের Badge: ${next} (${needed-total} আর দরকার)`;
}

// ── Change 4: Smart date picker for update form ─────────────
function setUpdateDonationNever() {
    document.getElementById('u_last').value = 'no';
    document.getElementById('uSdNeverBtn').classList.add('sd-active');
    document.getElementById('uSdDateBtn').classList.remove('sd-active');
    document.getElementById('uSdDatePickerWrap').style.display = 'none';
    document.getElementById('uSdNeverMsg').style.display = 'block';
}
function setUpdateDonationDate(presetISO) {
    document.getElementById('uSdNeverBtn').classList.remove('sd-active');
    document.getElementById('uSdDateBtn').classList.add('sd-active');
    document.getElementById('uSdDatePickerWrap').style.display = 'block';
    document.getElementById('uSdNeverMsg').style.display = 'none';
    var today = new Date().toISOString().split('T')[0];
    var inp = document.getElementById('uSdDateInput');
    inp.max = today; inp.min = '1940-01-01';
    if(presetISO) {
        inp.value = presetISO;
        syncUpdateDonationDate(presetISO);
    } else if(!inp.value) {
        inp.value = today;
        syncUpdateDonationDate(today);
    }
}
function syncUpdateDonationDate(val) {
    if(!val) return;
    var p = val.split('-');
    if(p.length === 3) {
        document.getElementById('u_last').value = p[2]+'/'+p[1]+'/'+p[0];
    }
}

// ── Change 4: Map picker for update form ────────────────────
let _updateMapPickerMap    = null;
let _updateMapPickerMarker = null;

function openUpdateMapPicker() {
    // Reuse the same modal — just swap the confirm handler
    const modal   = document.getElementById('mapPickerModal');
    const loading = document.getElementById('mapPickerLoading');
    const resultEl = document.getElementById('mapPickerResult');
    modal.classList.add('active');
    loading.style.display = 'flex';
    resultEl.value = '';

    // Override the confirm button to fill update form
    const confirmBtn = modal.querySelector('[onclick="useMapPickerLocation()"]');
    if(confirmBtn) confirmBtn.setAttribute('onclick','useUpdateMapPickerLocation()');

    function initUpdatePickerMap() {
        if(typeof L === 'undefined') { setTimeout(initUpdatePickerMap, 400); return; }
        loading.style.display = 'none';
        if(!_mapPickerMap) {
            _mapPickerMap = L.map('leafletMapPicker', {zoomControl:true}).setView([23.7735, 90.3742], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap © CARTO', subdomains:'abcd', maxZoom:19
            }).addTo(_mapPickerMap);
            _mapPickerMap.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(6), lng = e.latlng.lng.toFixed(6);
                if(_mapPickerMarker) { _mapPickerMarker.setLatLng(e.latlng); }
                else {
                    _mapPickerMarker = L.marker(e.latlng, {draggable:true}).addTo(_mapPickerMap);
                    _mapPickerMarker.on('dragend', function() {
                        const p = _mapPickerMarker.getLatLng();
                        doReverseGeocode(p.lat.toFixed(6), p.lng.toFixed(6));
                    });
                }
                doReverseGeocode(lat, lng);
            });
        } else {
            if(_mapPickerMarker) { _mapPickerMap.removeLayer(_mapPickerMarker); _mapPickerMarker = null; }
        }
        setTimeout(()=>{ if(_mapPickerMap) _mapPickerMap.invalidateSize(); }, 400);
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos){
                _mapPickerMap.setView([pos.coords.latitude, pos.coords.longitude], 15);
            }, null, {timeout:5000});
        }
    }
    setTimeout(initUpdatePickerMap, 150);
}

function useUpdateMapPickerLocation() {
    const val = document.getElementById('mapPickerResult').value.trim();
    if(!val || val.includes('লোড হচ্ছে')) { showValidationError('Map-এ ক্লিক করুন অথবা location লিখুন।'); return; }
    // Fill update form location field
    document.getElementById('u_location').value = val;
    // Save geo coordinates
    if(_mapPickerMarker) {
        const p = _mapPickerMarker.getLatLng();
        document.getElementById('u_reg_geo').value = 'Lat: '+p.lat.toFixed(6)+', Lon: '+p.lng.toFixed(6);
    }
    // Restore original confirm button handler
    const modal = document.getElementById('mapPickerModal');
    const confirmBtn = modal.querySelector('[onclick="useUpdateMapPickerLocation()"]');
    if(confirmBtn) confirmBtn.setAttribute('onclick','useMapPickerLocation()');
    closeMapPicker();
}

function setWilling(val) {
    document.getElementById('u_willing').value = val;
    const yBtn = document.getElementById('willingYesBtn');
    const nBtn = document.getElementById('willingNoBtn');
    const note = document.getElementById('willingNote');
    if(val === 'yes') {
        yBtn.classList.add('active'); nBtn.classList.remove('active');
        note.textContent = '✅ আপনি Available হিসেবে তালিকায় থাকবেন।';
        note.style.color = '#059669';
    } else {
        nBtn.classList.add('active'); yBtn.classList.remove('active');
        note.textContent = '⛔ আপনি Unavailable হিসেবে mark হবেন। যেকোনো সময় পরিবর্তন করা যাবে।';
        note.style.color = '#ef4444';
    }
}

function triggerJustDonated() {
    const btn = document.getElementById('justDonatedBtn');
    if(btn && btn.disabled) return; // Already triggered — prevent double click
    if(!confirm(t('আপনি কি নিশ্চিত যে এইমাত্র রক্ত দিয়েছেন? এতে আপনার donation count বাড়বে।'))) return;
    document.getElementById('u_just_donated').value = '1';
    const today = new Date();
    const dd = String(today.getDate()).padStart(2,'0');
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const yyyy = today.getFullYear();
    const isoToday = yyyy+'-'+mm+'-'+dd;
    setUpdateDonationDate(isoToday);
    setWilling('yes');
    if(btn){
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        btn.innerHTML = '✅ ধন্যবাদ! "Save Changes" করুন';
        btn.style.background = 'linear-gradient(135deg,#059669,#10b981)';
    }
}

// PAGE LOAD → AUTO FETCH FIRST PAGE (pagination always works)
window.onload = function() {
    // ── Splash screen — single-load progress (0 → 100% in one load) ──
    (function() {
        var splash   = document.getElementById('pwaSplash');
        var pl       = document.getElementById('pageLoader');
        if (pl) pl.classList.remove('loader-show');
        if (!splash) return;

        var gear    = document.getElementById('splashGear');
        var fill    = document.getElementById('splashProgressFill');
        var pct     = document.getElementById('splashPercent');
        var rlLabel = document.getElementById('splashReloadLabel');

        var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                        || window.navigator.standalone === true;

        // Clean up any leftover step key from the old 2-reload scheme
        try { sessionStorage.removeItem('_splash_step'); } catch(e){}

        // Single load: always 0 → 100%
        var startPct = 0;
        var endPct   = 100;

        if (rlLabel) rlLabel.textContent = '';

        // Pre-fill bar to startPct instantly
        if (fill) fill.style.transition = 'none';
        if (fill) fill.style.width = startPct + '%';
        if (pct)  pct.textContent = startPct + '%';

        var _animFrame;
        var _startTime = performance.now();
        var TOTAL_MS   = isStandalone ? 350 : 850;

        function easeInOut(t) {
            return t < 0.5 ? 2*t*t : -1+(4-2*t)*t;
        }

        function animProgress(now) {
            var elapsed  = now - _startTime;
            var t        = Math.min(elapsed / TOTAL_MS, 1);
            var progress = Math.round(startPct + easeInOut(t) * (endPct - startPct));
            if (fill) {
                fill.style.transition = 'none';
                fill.style.width = progress + '%';
            }
            if (pct) pct.textContent = progress + '%';

            if (gear) {
                var speed = 1.8 - 1.5 * easeInOut(t);
                gear.style.animation = 'splashNameSlide 0.4s 0.5s ease both, gearSpin ' + speed.toFixed(2) + 's linear infinite';
            }

            if (t < 1) {
                _animFrame = requestAnimationFrame(animProgress);
            } else {
                splash.classList.add('splash-hide');
                setTimeout(function() { splash.classList.add('splash-done'); }, 480);
            }
        }

        if (isStandalone) {
            if (fill) fill.style.width = '100%';
            if (pct)  pct.textContent  = '100%';
            if (rlLabel) rlLabel.textContent = '';
            splash.classList.add('splash-hide');
            setTimeout(function() { splash.classList.add('splash-done'); }, 360);
            return;
        }

        requestAnimationFrame(animProgress);
    })();

    fetchFilteredData(1);
    loadAnalytics();
    startAnalyticsAutoRefresh();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page') && parseInt(urlParams.get('page')) > 1) {
        document.getElementById("donorListSection").scrollIntoView();
    }
    
    // Mobile: always start on home page
    if (window.innerWidth <= 650) {
        document.querySelectorAll('.app-page').forEach(function(p){ p.classList.remove('page-active'); });
        var home = document.getElementById('page-home');
        if (home) home.classList.add('page-active');
        _currentPage = 'home';
        updateBottomNav('home');
    }
};

// ============================================================
// ANALYTICS
// ============================================================
const BLOOD_COLORS = {
    'A+':'#e74c3c','A-':'#c0392b','B+':'#3498db','B-':'#2980b9',
    'AB+':'#9b59b6','AB-':'#6c3483','O+':'#f39c12','O-':'#e67e22'
};
const BADGE_COLORS = { 'New':'#10b981','Active':'#3b82f6','Hero':'#8b5cf6','Legend':'#f59e0b' };

// ── refreshHomeCounts: lightweight hero bar + stat card refresh ──
// Called when user navigates back to Home tab — avoids showing stale 0 counts.
// Does NOT redraw charts (that's loadAnalytics). Just updates numbers.
function refreshHomeCounts() {
    const fd = new FormData();
    fd.append('get_analytics','1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(d => {
        const hTotal = document.getElementById('heroTotalDonors');
        const hAvail = document.getElementById('heroAvailDonors');
        if (hTotal) hTotal.textContent = d.total || 0;
        if (hAvail) hAvail.textContent = d.available || 0;
        if (d.by_group_avail) {
            const gm = {'A+':'Aplus','A-':'Aminus','B+':'Bplus','B-':'Bminus',
                        'AB+':'ABplus','AB-':'ABminus','O+':'Oplus','O-':'Ominus'};
            for (const [g, id] of Object.entries(gm)) {
                const el = document.getElementById('count-' + id);
                if (el) el.textContent = '🩸 ' + (d.by_group_avail[g] || 0) + ' Available';
            }
        }
    }).catch(function(){});
}

function loadAnalytics() {
    const fd = new FormData();
    fd.append('get_analytics','1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL,{method:'POST',body:fd})
    .then(safeJSON)
    .then(d => {
        // KPIs — updates every .analytics-section instance (home embed + standalone page)
        animateAn('kpiTotal', d.total);
        animateAn('kpiAvail', d.available);
        animateAn('kpiUnav',  d.unavailable);
        animateAn('kpiCalls', d.total_calls || 0);
        animateAn('kpiReq',       d.active_requests   || 0);
        animateAn('kpiFulfilled', d.fulfilled_requests || 0);
        // Update home hero bar
        const hTotal = document.getElementById('heroTotalDonors');
        const hAvail = document.getElementById('heroAvailDonors');
        if (hTotal) animateNum('heroTotalDonors', d.total);
        if (hAvail) animateNum('heroAvailDonors', d.available);
        // ── Update stat cards (live counts per blood group) ──
        if (d.by_group_avail) {
            const groupMap = {'A+':'Aplus','A-':'Aminus','B+':'Bplus','B-':'Bminus','AB+':'ABplus','AB-':'ABminus','O+':'Oplus','O-':'Ominus'};
            for (const [g, id] of Object.entries(groupMap)) {
                const el = document.getElementById('count-' + id);
                if (el) {
                    const cnt = d.by_group_avail[g] || 0;
                    el.textContent = '🩸 ' + cnt + ' Available';
                }
            }
        }
        // Blood group bar chart
        renderBarChart(d.by_group);
        // Badge donut
        renderBadgeDonut(d.by_badge);
        // Location chart
        renderLocChart(d.by_loc);
    }).catch((err)=>{
        console.error('Analytics error:', err);
    });
}

// Auto-refresh analytics + stat cards every 60 seconds
let _analyticsRefreshTimer = null;
function startAnalyticsAutoRefresh() {
    if (_analyticsRefreshTimer) clearInterval(_analyticsRefreshTimer);
    _analyticsRefreshTimer = setInterval(function() {
        if (!document.hidden) loadAnalytics();
    }, 60000);
}
// Restart timer when tab becomes visible again (prevents stale data)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && _analyticsRefreshTimer) {
        clearInterval(_analyticsRefreshTimer);
        _analyticsRefreshTimer = null;
        startAnalyticsAutoRefresh();
    }
});

function animateNum(id, target) {
    animateNumEl(document.getElementById(id), target);
}

// Animate a specific element's number (used for the data-an analytics instances).
function animateNumEl(el, target) {
    if(!el) return;
    let startTime = null, duration = 900;
    function step(ts) {
        if(!startTime) startTime = ts;
        let progress = Math.min((ts-startTime)/duration, 1);
        let ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(ease * target).toLocaleString();
        if(progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

// Animate the same KPI across every analytics instance (home embed + standalone page).
function animateAn(name, target) {
    document.querySelectorAll('.analytics-section [data-an="'+name+'"]').forEach(function(el){
        animateNumEl(el, target);
    });
}

function renderBarChart(byGroup) {
    const wraps = document.querySelectorAll('.analytics-section [data-an="bgChart"]');
    if(!wraps.length) return;
    const max = Math.max(...Object.values(byGroup), 1);
    const groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    const html = groups.map(g => {
        const cnt = byGroup[g] || 0;
        const pct = Math.round((cnt/max)*100);
        const col = BLOOD_COLORS[g] || '#6b7280';
        return `<div class="bar-row">
            <span class="bar-label" style="color:${col};">${g}</span>
            <div class="bar-track">
                <div class="bar-fill" style="width:${pct}%;background:${col};">
                    <span class="bar-count">${cnt}</span>
                </div>
            </div>
        </div>`;
    }).join('');
    wraps.forEach(w => { w.innerHTML = html; });
}

function renderBadgeDonut(byBadge) {
    window._lastBadgeData = byBadge; // cache for theme-switch redraw
    document.querySelectorAll('.analytics-section').forEach(function(root){
        const canvas = root.querySelector('[data-an="badgeDonut"]');
        const legend = root.querySelector('[data-an="badgeLegend"]');
        if(!canvas || !legend) return;
        _drawBadgeDonut(canvas, legend, byBadge);
    });
}

function _drawBadgeDonut(canvas, legend, byBadge) {
    const ctx = canvas.getContext('2d');
    const levels = ['New','Active','Hero','Legend'];
    const vals = levels.map(l => byBadge[l] || 0);
    const total = vals.reduce((a,b)=>a+b,0) || 1;
    const colors = levels.map(l => BADGE_COLORS[l]);
    const icons  = {'New':'🌱','Active':'⭐','Hero':'🦸','Legend':'👑'};

    // Draw donut
    let startAngle = -Math.PI/2;
    const cx=90, cy=90, outerR=80, innerR=50;
    ctx.clearRect(0,0,180,180);
    vals.forEach((v,i) => {
        const sweep = (v/total)*2*Math.PI;
        ctx.beginPath();
        ctx.moveTo(cx,cy);
        ctx.arc(cx,cy,outerR,startAngle,startAngle+sweep);
        ctx.closePath();
        ctx.fillStyle = colors[i];
        ctx.fill();
        startAngle += sweep;
    });
    // Inner circle (donut hole)
    ctx.beginPath();
    ctx.arc(cx,cy,innerR,0,2*Math.PI);
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bg-main').trim() || '#0f1115';
    ctx.fill();
    // Center text
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-main').trim() || '#fff';
    ctx.font = 'bold 22px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total, cx, cy);

    legend.innerHTML = levels.map((l,i) =>
        `<div class="badge-legend-item"><div class="badge-legend-dot" style="background:${colors[i]};"></div>${icons[l]} ${l} (${vals[i]})</div>`
    ).join('');
}

function renderLocChart(byLoc) {
    const wraps = document.querySelectorAll('.analytics-section [data-an="locChart"]');
    if(!wraps.length || !byLoc.length) return;
    const max = byLoc[0].cnt || 1;
    const html = byLoc.map(r => {
        const pct = Math.round((r.cnt/max)*100);
        return `<div class="loc-row">
            <span class="loc-name" title="${r.area}">${r.area}</span>
            <div class="loc-bar-track">
                <div class="loc-bar-fill" style="width:${pct}%;">
                    <span class="loc-count">${r.cnt}</span>
                </div>
            </div>
        </div>`;
    }).join('');
    wraps.forEach(w => { w.innerHTML = html; });
}

// ============================================================
// MAP (Leaflet.js — OpenStreetMap)
// ============================================================
let leafletMap = null;
let _allMapMarkers = []; // store all fetched markers for client-side filtering
let _mapFilterGroup  = 'All';
let _mapFilterStatus = 'All';

function setMapFilter(type, val, btn) {
    if (type === 'group') {
        _mapFilterGroup = val;
        document.querySelectorAll('#mapGroupPills .map-pill').forEach(function(b){ b.classList.remove('active'); });
    } else {
        _mapFilterStatus = val;
        document.querySelectorAll('#mapStatusPills .map-pill').forEach(function(b){ b.classList.remove('active'); });
    }
    if (btn) btn.classList.add('active');
    // If map already loaded, re-apply filter without re-fetching
    if (_allMapMarkers.length > 0) {
        applyMapFilter();
    }
}

function applyMapFilter() {
    if (!leafletMap) return;
    // Remove existing markers
    leafletMap.eachLayer(function(l) {
        if (l instanceof L.CircleMarker) leafletMap.removeLayer(l);
    });
    const filtered = _allMapMarkers.filter(function(m) {
        const groupOk  = _mapFilterGroup  === 'All' || m.group  === _mapFilterGroup;
        const statusOk = _mapFilterStatus === 'All' || m.status === _mapFilterStatus;
        return groupOk && statusOk;
    });
    const infoEl = document.getElementById('mapFilterInfo');
    if (infoEl) {
        if (_mapFilterGroup !== 'All' || _mapFilterStatus !== 'All') {
            infoEl.style.display = 'block';
            infoEl.textContent = '🔍 ' + filtered.length + ' জন donor দেখাচ্ছে (মোট ' + _allMapMarkers.length + ' জনের মধ্যে)';
        } else {
            infoEl.style.display = 'none';
        }
    }
    const bounds = [];
    filtered.forEach(function(m) {
        const color = m.status === 'Available' ? '#10b981' : m.status === 'Unavailable' ? '#6b7280' : '#ef4444';
        const circle = L.circleMarker([m.lat, m.lng], {
            radius: 9, fillColor: color, color: '#fff',
            weight: 2, opacity: 1, fillOpacity: 0.9
        }).addTo(leafletMap);
        circle.bindPopup(
            '<div style="font-family:sans-serif; min-width:160px;">' +
            '<strong style="font-size:1em;">' + m.name + '</strong><br>' +
            '<span style="color:' + color + '; font-weight:700;">🩸 ' + m.group + '</span>' +
            '<span style="float:right; font-size:0.85em; color:#888;">' + m.badge + '</span><br>' +
            '<small>📍 ' + m.loc + '</small><br>' +
            '<small style="color:' + color + ';">' + (m.status === 'Available' ? '✔ Available' : m.status === 'Unavailable' ? '⛔ Not Willing' : '✖ Not Available') + '</small>' +
            '</div>'
        );
        bounds.push([m.lat, m.lng]);
    });
    if (bounds.length && filtered.length !== _allMapMarkers.length) {
        leafletMap.fitBounds(bounds, {padding:[30,30], maxZoom:14});
    }
}

function loadMap() {
    const placeholder = document.getElementById('mapPlaceholder');
    const mapDiv = document.getElementById('leafletMap');
    const legend = document.getElementById('mapLegend');

    if(typeof L === 'undefined') {
        placeholder.innerHTML = '<div style="font-size:2rem;">⏳</div><p>Leaflet লোড হচ্ছে, একটু অপেক্ষা করুন...</p>';
        setTimeout(loadMap, 800);
        return;
    }

    placeholder.innerHTML = '<div style="font-size:2rem;">⏳</div><p>Map লোড হচ্ছে...</p>';

    const fd = new FormData();
    fd.append('get_map_data','1');
    fd.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL,{method:'POST',body:fd})
    .then(safeJSON)
    .then(markers => {
        if(!markers.length) {
            placeholder.innerHTML = '<div style="font-size:2rem;">😞</div><p>Location data সহ কোনো donor নেই।</p>';
            return;
        }
        placeholder.style.display = 'none';
        mapDiv.style.display = 'block';
        legend.style.display = 'flex';

        // Store all markers for client-side filtering
        _allMapMarkers = markers;

        // Init Leaflet map
        if(!leafletMap) {
            leafletMap = L.map('leafletMap', {zoomControl:true}).setView([23.8103, 90.4125], 10);
            window._mapTileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap © CARTO',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(leafletMap);
        } else {
            leafletMap.eachLayer(function(l) {
                if(l instanceof L.CircleMarker) leafletMap.removeLayer(l);
            });
        }

        // Fix blank map
        const _doInvalidate = () => { if(leafletMap) leafletMap.invalidateSize(); };
        if(typeof ResizeObserver !== 'undefined') {
            const _ro = new ResizeObserver((entries, obs) => {
                if(mapDiv.offsetWidth > 0 && mapDiv.offsetHeight > 0) {
                    _doInvalidate(); obs.disconnect();
                }
            });
            _ro.observe(mapDiv);
            setTimeout(_doInvalidate, 400);
        } else {
            setTimeout(_doInvalidate, 300);
            setTimeout(_doInvalidate, 500);
        }

        // Render all markers (respecting any pre-set filter)
        const bounds = [];
        markers.forEach(function(m) {
            const color = m.status === 'Available' ? '#10b981' : m.status === 'Unavailable' ? '#6b7280' : '#ef4444';
            const groupOk  = _mapFilterGroup  === 'All' || m.group  === _mapFilterGroup;
            const statusOk = _mapFilterStatus === 'All' || m.status === _mapFilterStatus;
            if (!groupOk || !statusOk) return;
            const circle = L.circleMarker([m.lat, m.lng], {
                radius: 9, fillColor: color, color: '#fff',
                weight: 2, opacity: 1, fillOpacity: 0.9
            }).addTo(leafletMap);
            circle.bindPopup(
                '<div style="font-family:sans-serif; min-width:160px;">' +
                '<strong style="font-size:1em;">' + m.name + '</strong><br>' +
                '<span style="color:' + color + '; font-weight:700;">🩸 ' + m.group + '</span>' +
                '<span style="float:right; font-size:0.85em; color:#888;">' + m.badge + '</span><br>' +
                '<small>📍 ' + m.loc + '</small><br>' +
                '<small style="color:' + color + ';">' + (m.status === 'Available' ? '✔ Available' : m.status === 'Unavailable' ? '⛔ Not Willing' : '✖ Not Available') + '</small>' +
                '</div>'
            );
            bounds.push([m.lat, m.lng]);
        });
        if(bounds.length) leafletMap.fitBounds(bounds, {padding:[30,30]});

        // Update filter info
        const infoEl = document.getElementById('mapFilterInfo');
        if (infoEl && (_mapFilterGroup !== 'All' || _mapFilterStatus !== 'All')) {
            infoEl.style.display = 'block';
            infoEl.textContent = '🔍 ' + bounds.length + ' জন donor দেখাচ্ছে (মোট ' + markers.length + ' জনের মধ্যে)';
        }
    }).catch(() => {
        placeholder.innerHTML = '<p style="color:#ef4444;">Map লোড করতে সমস্যা হয়েছে।</p>';
    });
}

// ══════════════════════════════════════════════════════════════
// NOTIFICATION PROMPT — global functions, no closure
// ══════════════════════════════════════════════════════════════
function notifWasDismissed() {
    try {
        var raw = localStorage.getItem('notif_dismissed');
        if (!raw) return false;
        // Legacy value '1' — clear and treat as not dismissed
        if (raw === '1') { localStorage.removeItem('notif_dismissed'); return false; }
        var data = JSON.parse(raw);
        if (data.until && Date.now() < data.until) return true;
        localStorage.removeItem('notif_dismissed'); // expired
        return false;
    } catch(e) { return false; }
}

var _notifRetryCount = 0;
function maybeShowNotifPrompt() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') return;
    if (Notification.permission === 'denied') return;
    if (notifWasDismissed()) return;
    // If PWA overlay visible, retry — but max 4 times (12s total), then show anyway
    var pwaOverlay = document.getElementById('pwaInstallOverlay');
    if (pwaOverlay && pwaOverlay.classList.contains('show') && _notifRetryCount < 4) {
        _notifRetryCount++;
        setTimeout(maybeShowNotifPrompt, 3000); return;
    }
    var p = document.getElementById('notifPrompt');
    if (!p) return;
    requestAnimationFrame(function() { p.classList.add('np-show'); });
}

// Trigger 4s after page loads — completely independent of DOMContentLoaded
setTimeout(maybeShowNotifPrompt, 4000);

// ══════════════════════════════════════════════════════════════
// POPUP STACKING FIX
// ══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const popupIds = [
        'callConfirmPopup',
        'reportPopup',
        'warningPopupOverlay',
        'termsPopupOverlay',
        'aboutUsPopupOverlay',
        'locationBlockedOverlay',
        'bloodReqModal',
        'requestSecretCodeModal', // new: request secret code
        'getSecretCodeModal',     // new: get secret code by ref
        'adminMsgModal',          // new: message to admin
        'mapPickerModal',
        'gpsPermPrompt',
        'faqModal',
        'popup'  // must be last
    ];
    popupIds.forEach(function(id) {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });

    // ── Notification permission prompt ──────────────────────────
    // Shows after 4s. Retries every 3s if PWA is currently open (non-blocking).
    // GPS prompt: 9s পরে — notification prompt settle হওয়ার পরে
    setTimeout(function() {
        if (navigator.geolocation && !localStorage.getItem('gps_prompted')) {
            var pwaOverlay = document.getElementById('pwaInstallOverlay');
            var pwaVisible = pwaOverlay && pwaOverlay.classList.contains('show');
            var notifP = document.getElementById('notifPrompt');
            var notifVisible = notifP && notifP.style.display !== 'none';
            if (!pwaVisible && !notifVisible) {
                showGpsPrompt();
            } else {
                setTimeout(showGpsPrompt, 5000);
            }
        }
    }, 9000);

    function showGpsPrompt() {
        if (navigator.geolocation && !localStorage.getItem('gps_prompted')) {
            localStorage.setItem('gps_prompted', '1');
            const msgEl = document.getElementById('gpsPromptMsg');
            if (msgEl) msgEl.textContent = 'Nearby Donors feature ও Call log-এর জন্য আপনার Location দরকার। Allow করলে কাছের রক্তদাতা খুঁজে পাবেন।';
            const el = document.getElementById('gpsPermPrompt');
            if (el) el.classList.add('active');
        }
    }
});

// ============================================================
// FEATURE: EMERGENCY BLOOD REQUESTS
// ============================================================
// ── PERFORMANCE: debounce utility ──
function _debounce(fn, ms) {
    let t;
    return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
}

// ── Offline / Online Alert ─────────────────────────────────
(function() {
    var _alert = null;
    function getAlert() {
        if (!_alert) _alert = document.getElementById('offlineAlert');
        return _alert;
    }
    function showOffline() {
        var el = getAlert();
        if (el) el.classList.add('show');
    }
    function showOnline() {
        var el = getAlert();
        if (el) {
            el.classList.remove('show');
            // Silently refresh donor list when connection restored
            setTimeout(function() {
                if (typeof fetchFilteredData === 'function') fetchFilteredData(1);
            }, 500);
        }
    }
    window.addEventListener('offline', showOffline);
    window.addEventListener('online',  showOnline);
    // Check on load
    if (!navigator.onLine) showOffline();

    // Smart retry — test connection before reloading
    window.offlineRetry = function(btn) {
        if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
        fetch(_AJAX_URL, { method: 'HEAD', cache: 'no-store' })
            .then(function(r) {
                if (r.ok) {
                    window.location.reload();
                } else {
                    if (btn) { btn.disabled = false; btn.textContent = '🔄 Retry'; }
                }
            })
            .catch(function() {
                if (btn) { btn.disabled = false; btn.textContent = '🔄 Retry'; }
            });
    };
})();

// ── Pull to Refresh — half-screen pull needed (THRESHOLD = window.innerHeight/2) ──
(function() {
    var ptr = null, startY = 0, pulling = false, triggered = false;
    function getPtr() { if (!ptr) ptr = document.getElementById('ptrIndicator'); return ptr; }
    var THRESHOLD = 0; // set on first touch

    document.addEventListener('touchstart', function(e) {
        if (window.scrollY > 4) return;
        startY    = e.touches[0].clientY;
        THRESHOLD = Math.round(window.innerHeight * 0.45); // half screen
        pulling   = true;
        triggered = false;
    }, {passive: true});

    document.addEventListener('touchmove', function(e) {
        if (!pulling) return;
        var dy = e.touches[0].clientY - startY;
        if (dy < 10) return;
        var el = getPtr();
        if (el) {
            var prog = Math.min(dy / THRESHOLD, 1);
            el.classList.add('ptr-visible');
            el.classList.remove('ptr-spinning');
            var svg = el.querySelector('svg');
            if (svg) svg.style.transform = 'rotate(' + Math.round(prog * 300) + 'deg)';
        }
        if (dy >= THRESHOLD && !triggered) {
            triggered = true;
            if (el) el.classList.add('ptr-spinning');
            vibrateIfOn([22]);
        }
    }, {passive: true});

    document.addEventListener('touchend', function() {
        if (!pulling) return;
        pulling = false;
        var el = getPtr();
        if (triggered) {
            setTimeout(function() { window.location.reload(); }, 280);
        } else {
            if (el) {
                el.classList.remove('ptr-visible');
                var svg = el.querySelector('svg');
                if (svg) svg.style.transform = '';
            }
        }
    });
})();

// ── Network Live Status Dot ────────────────────────────────
(function() {
    var _dot = null;
    function getDot() { if (!_dot) _dot = document.getElementById('netStatusDot'); return _dot; }
    function updateDot() {
        var el = getDot(); if (!el) return;
        if (navigator.onLine) {
            el.classList.remove('net-offline');
            el.innerHTML = 'LIVE';
            el.title = 'Network: Online';
        } else {
            el.classList.add('net-offline');
            el.innerHTML = 'OFF';
            el.title = 'Network: Offline';
        }
    }
    window.addEventListener('online',  updateDot);
    window.addEventListener('offline', updateDot);
    updateDot();
})();

// ── Settings Reload Button ─────────────────────────────────
function settingsReload() {
    var btn = document.querySelector('.settings-reload-btn');
    if (btn) {
        btn.classList.remove('spinning');
        void btn.offsetWidth; // reflow to restart animation
        btn.classList.add('spinning');
        setTimeout(function() { btn.classList.remove('spinning'); }, 500);
    }
    // Close settings then reload
    if (typeof closeSettingsPanel === 'function') closeSettingsPanel();
    setTimeout(function() { window.location.reload(true); }, 200);
}



function openBloodRequestModal(){
    document.getElementById('req_group').value = '';
    // Clear previously selected group button
    document.querySelectorAll('#reqGroupGrid .req-group-btn').forEach(function(b){ b.classList.remove('selected'); });
    document.getElementById('bloodReqModal').classList.add('active');
}

function closeBloodReqModal(){
    document.getElementById('bloodReqModal').classList.remove('active');
    // FIX: ensure scroll lock released when modal closes (MutationObserver may lag)
    _forceUnlockBodyScroll();
}

function selectReqGroup(btn, group){
    document.querySelectorAll('#reqGroupGrid button').forEach(function(b){ b.classList.remove('selected'); });
    btn.classList.add('selected');
    document.getElementById('req_group').value = group;
}

function submitBloodRequest(){
    // রক্তের অনুরোধ পাঠাতে সাইন ইন আবশ্যক (Google / ফোন)
    if (!_isSignedIn()) {
        showValidationError('রক্তের অনুরোধ পাঠাতে আগে সাইন ইন করুন (Google অথবা ফোন নম্বর দিয়ে)।');
        closeBloodReqModal();
        if (typeof openAuthModal === 'function') setTimeout(openAuthModal, 300);
        return;
    }
    const patient  = document.getElementById('req_patient').value.trim();
    const group    = document.getElementById('req_group').value;
    const hospital = document.getElementById('req_hospital').value.trim();
    const contact  = document.getElementById('req_contact').value.trim();
    const urgency  = document.getElementById('req_urgency').value;
    const bags     = document.getElementById('req_bags').value;
    const note     = document.getElementById('req_note').value.trim();
    if(!patient||!group||!hospital){ showValidationError('রোগীর নাম, blood group ও হাসপাতাল দিতে হবে।'); return; }
    if(!/^\+8801\d{9}$/.test(contact)){ showValidationError('সঠিক যোগাযোগ নম্বর দিন (+8801XXXXXXXXX)।'); return; }

    // ── FIX: GPS fire-and-forget — do NOT block submit on GPS ──
    // requestGPSWithPrompt caused a popup + wait before submit, making form feel frozen.
    // GPS captured silently in background; submit fires immediately.
    if (navigator.geolocation && (!currentLocData || currentLocData === 'Not provided')) {
        navigator.geolocation.getCurrentPosition(
            function(p){ currentLocData = 'Lat:'+p.coords.latitude+',Lon:'+p.coords.longitude; },
            function(){},
            { timeout:4000, enableHighAccuracy:false, maximumAge:120000 }
        );
    }

    const submitBtn = document.querySelector('#bloodReqSheet button[onclick="submitBloodRequest()"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '⏳ পাঠানো হচ্ছে...'; }

    const fd = new FormData();
    fd.append('submit_blood_request','1');
    fd.append('patient_name', patient);
    fd.append('req_blood_group', group);
    fd.append('hospital', hospital);
    fd.append('req_contact', contact);
    fd.append('urgency', urgency);
    fd.append('bags_needed', bags);
    fd.append('req_note', note);
    fd.append('req_location', currentLocData);
    fd.append('device_id', (typeof getDeviceId === 'function') ? getDeviceId() : '');
    fd.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(d=>{
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '🆘 Emergency Request পাঠান'; }
        closeBloodReqModal();
        if(d.status==='success'){
            document.getElementById('req_patient').value = '';
            document.getElementById('req_group').value = '';
            document.getElementById('req_hospital').value = '';
            document.getElementById('req_contact').value = '+8801';
            document.getElementById('req_urgency').value = 'High';
            document.getElementById('req_bags').value = '1';
            document.getElementById('req_note').value = '';
            document.querySelectorAll('#reqGroupGrid .req-group-btn').forEach(function(b){ b.classList.remove('selected'); });
            appSwitchPage('requests');   // jump to the Active Requests page (reloads the list)
            // Request is tied to the signed-in account — manage/delete it from the
            // "👤 আমার Request" tab here or the Account Dashboard. No token needed.
            showToast('✅ Emergency request পাঠানো হয়েছে! "👤 আমার Request" tab থেকে যেকোনো সময় মুছতে পারবেন।', 'success');
            if (typeof refreshMyReqIds === 'function') refreshMyReqIds();
        } else {
            showValidationError(d.msg||'ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
        }
    }).catch(function(){
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '🆘 Emergency Request পাঠান'; }
        showValidationError('Network error। Internet connection চেক করুন।');
    });
}

// Kept for backward-compat: Active Requests is now a dedicated page, not an
// inline toggle — route any caller to the page.
function toggleRequestSection(){
    appSwitchPage('requests');
}

// ── My Requests (account-owned) ───────────────────────────────
// Ownership is derived from the signed-in account (auth_uid) via the
// get_my_requests endpoint — no client-side token storage.
var _myReqIds = new Set();
function isMyRequest(id){ return _myReqIds.has(String(id)); }
// Refresh the set of request IDs owned by the signed-in account, then re-filter.
function refreshMyReqIds(cb){
    if (typeof _isSignedIn === 'function' && !_isSignedIn()) {
        _myReqIds = new Set();
        if (typeof applyReqFilter === 'function') applyReqFilter();
        if (cb) cb();
        return;
    }
    var fd = new FormData();
    fd.append('get_my_requests', '1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(res){
            var rows = (res && res.status === 'success' && Array.isArray(res.requests)) ? res.requests : [];
            _myReqIds = new Set(rows.map(function(r){ return String(r.id); }));
        })
        .catch(function(){ /* keep previous set on error */ })
        .then(function(){
            if (typeof applyReqFilter === 'function') applyReqFilter();
            if (cb) cb();
        });
}

// ── Active filter state ────────────────────────────────────────
var _reqAllData    = [];
var _reqTabMode    = 'all';
var _reqGroupFilter = '';

function setReqTab(mode){
    _reqTabMode = mode;
    var allBtn  = document.getElementById('reqTab_all');
    var mineBtn = document.getElementById('reqTab_mine');
    if(allBtn)  allBtn.classList.toggle('req-tab-active',  mode === 'all');
    if(mineBtn) mineBtn.classList.toggle('req-tab-active', mode === 'mine');
    if(mode === 'mine'){
        // Pull the latest account-owned request IDs; applyReqFilter runs inside.
        refreshMyReqIds();
    } else {
        applyReqFilter();
    }
}

function setReqGroupFilter(group){
    _reqGroupFilter = (_reqGroupFilter === group) ? '' : group;
    document.querySelectorAll('.req-bg-chip').forEach(function(b){
        b.classList.toggle('chip-active', b.dataset.group === _reqGroupFilter);
    });
    var clearBtn = document.getElementById('reqBgFilterClear');
    if(clearBtn) clearBtn.style.display = _reqGroupFilter ? 'inline-block' : 'none';
    applyReqFilter();
}

function clearReqGroupFilter(){
    _reqGroupFilter = '';
    document.querySelectorAll('.req-bg-chip').forEach(function(b){ b.classList.remove('chip-active'); });
    var clearBtn = document.getElementById('reqBgFilterClear');
    if(clearBtn) clearBtn.style.display = 'none';
    applyReqFilter();
}

function applyReqFilter(){
    var filtered = _reqAllData;
    if(_reqTabMode === 'mine') filtered = filtered.filter(function(r){ return isMyRequest(r.id); });
    if(_reqGroupFilter)        filtered = filtered.filter(function(r){ return r.blood_group === _reqGroupFilter; });
    renderReqGrid(filtered, _reqTabMode === 'mine');
}

function renderReqGrid(reqs, showDeleteBtns) {
    var grid = document.getElementById('reqGrid');
    if(!grid) return;
    if(!reqs.length){
        var signedIn = (typeof _isSignedIn !== 'function') || _isSignedIn();
        var emptyMsg = (_reqTabMode === 'mine')
            ? (signedIn
                ? '<div style="font-size:2.5rem;margin-bottom:10px;">📭</div>'
                  +'<p style="font-weight:700;color:var(--text-main);">আপনার কোনো active Request নেই</p>'
                  +'<p style="font-size:0.82em;margin-top:5px;color:var(--text-muted);">জরুরি প্রয়োজনে উপরের 🆘 বাটনে ক্লিক করে Request পাঠান।</p>'
                : '<div style="font-size:2.5rem;margin-bottom:10px;">🔒</div>'
                  +'<p style="font-weight:700;color:var(--text-main);">নিজের Request দেখতে সাইন ইন করুন</p>'
                  +'<p style="font-size:0.82em;margin-top:5px;color:var(--text-muted);">Google অথবা ফোন নম্বর দিয়ে সাইন ইন করলে আপনার পাঠানো Request এখানে দেখাবে।</p>'
                  +'<button onclick="if(typeof openAuthModal===\'function\')openAuthModal()" style="margin-top:14px;width:auto!important;min-height:unset!important;padding:9px 18px;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);color:var(--danger);border-radius:20px;font-size:0.82em;font-weight:700;box-shadow:none;">🔑 সাইন ইন করুন</button>')
            : '<div style="font-size:3rem;margin-bottom:10px;">🕊️</div><p style="font-weight:600;color:var(--text-main);">এখন কোনো active request নেই</p><p style="font-size:0.85em;margin-top:5px;color:var(--text-muted);">জরুরি প্রয়োজনে উপরের 🆘 বাটনে ক্লিক করুন</p>';
        grid.innerHTML = '<div style="text-align:center;padding:40px;grid-column:1/-1;">' + emptyMsg + '</div>';
        return;
    }
    var urgencyClass = {Critical:'critical', High:'high', Medium:'medium'};
    var urgencyIcon  = {Critical:'🔴', High:'🟠', Medium:'🔵'};
    var timeAgo = function(dt){
        var unix = parseInt(dt, 10);
        var ms   = isNaN(unix) ? new Date(dt).getTime() : unix * 1000;
        var diff = Math.floor((Date.now() - ms) / 60000);
        if (isNaN(diff) || diff < 1) return 'এইমাত্র';
        if (diff < 60)               return diff + 'মিনিট আগে';
        if (diff < 1440)             return Math.floor(diff / 60) + 'ঘণ্টা আগে';
        return Math.floor(diff / 1440) + 'দিন আগে';
    };
    var escHtml = function(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); };
    grid.innerHTML = reqs.map(function(r){
        var mine = isMyRequest(r.id);
        var deleteBtn = mine
            ? '<button onclick="deleteMyAccountRequest('+r.id+', this)" style="margin-top:8px;width:100%;padding:9px;background:rgba(220,38,38,0.07);border:1px solid rgba(220,38,38,0.35);color:var(--danger);border-radius:10px;font-size:0.82em;cursor:pointer;font-weight:700;min-height:unset;box-shadow:none;margin-top:8px;">🗑️ আমার Request মুছুন</button>'
            : '';
        var myBadge = mine ? '<span style="font-size:0.7em;background:rgba(220,38,38,0.12);color:var(--danger);border-radius:20px;padding:2px 8px;font-weight:700;margin-left:6px;">👤 আমার</span>' : '';
        return '<div class="req-card '+(urgencyClass[r.urgency]||'high')+'">'
            +'<div style="display:flex;justify-content:space-between;align-items:flex-start;">'
            +'<span class="req-card-urgency '+(urgencyClass[r.urgency]||'high')+'">'+(urgencyIcon[r.urgency]||'')+' '+escHtml(r.urgency)+'</span>'
            +'<span style="font-size:0.75em;color:var(--text-muted);">'+timeAgo(r.created_at)+'</span>'
            +'</div>'
            +'<div class="req-card-group">🩸 '+escHtml(r.blood_group)+myBadge+'</div>'
            +'<div class="req-card-name">👤 '+escHtml(r.patient_name)+'</div>'
            +'<div class="req-card-hosp">🏥 '+escHtml(r.hospital)+'</div>'
            +'<div class="req-card-meta">'
            +'<span class="req-tag">🩸 '+escHtml(r.bags_needed)+' ব্যাগ</span>'
            +(r.note ? '<span class="req-tag">📝 '+escHtml(r.note)+'</span>' : '')
            +'</div>'
            +'<button class="req-call-btn" onclick="window.location=\'tel:'+escHtml(r.contact)+'\'">📞 '+escHtml(r.contact)+' — এখনই Call করুন</button>'
            +deleteBtn
            +'</div>';
    }).join('');
}

function loadBloodRequests(){
    var fd = new FormData();
    fd.append('get_blood_requests','1');
    fd.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL,{method:'POST',body:fd})
    .then(safeJSON)
    .then(function(reqs){
        _reqAllData = reqs;
        // Refresh account ownership so "👤 আমার" badge + delete buttons show on every tab
        // (refreshMyReqIds calls applyReqFilter once it resolves).
        refreshMyReqIds();
    }).catch(function(){
        document.getElementById('reqGrid').innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger);grid-column:1/-1;">❌ লোড করতে সমস্যা</div>';
    });
}

// Safety: unlock body scroll if no popup-overlay or settings panel is open
function _forceUnlockBodyScroll() {
    setTimeout(function() {
        var anyOpen = document.querySelector('.popup-overlay.active, .settings-panel-overlay.active');
        if (!anyOpen) {
            // FIX: also reset _scrollLockCount so counter never stays stuck at >0
            _scrollLockCount = 0;
            document.body.dataset.scrollLocked = '0';
            var scrollY = parseInt(document.body.dataset.scrollY || '0', 10);
            document.body.style.position = '';
            document.body.style.top      = '';
            document.body.style.left     = '';
            document.body.style.right    = '';
            document.body.style.overflow = '';
            window.scrollTo(0, scrollY);
        }
    }, 50);
}

// ============================================================
// FEATURE: NEARBY DONORS (GPS)
// ============================================================
function loadNearbyDonors(){
    const btn = document.getElementById('nearbyLoadBtn');
    const results = document.getElementById('nearbyResults');
    btn.textContent = '⏳ Location নিচ্ছে...';
    btn.disabled = true;

    if(!navigator.geolocation){
        btn.textContent='📡 খুঁজুন'; btn.disabled=false;
        results.innerHTML='<div class="nearby-empty" style="grid-column:1/-1;"><div style="font-size:2.5rem;">😢</div><p>আপনার browser Geolocation সাপোর্ট করে না।</p></div>';
        return;
    }
    navigator.geolocation.getCurrentPosition(pos=>{
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        btn.textContent = '⏳ Donors খুঁজছে...';

        const fd = new FormData();
        fd.append('get_nearby_donors','1');
        fd.append('lat', lat);
        fd.append('lng', lng);
        fd.append('radius', document.getElementById('nearbyRadius').value);
        fd.append('filter_group', document.getElementById('nearbyGroupFilter').value);
        fd.append('filter_status', document.getElementById('nearbyStatusFilter') ? document.getElementById('nearbyStatusFilter').value : 'All');
        fd.append('csrf_token', CSRF_TOKEN);

        fetch(_AJAX_URL,{method:'POST',body:fd})
        .then(safeJSON)
        .then(d=>{
            btn.textContent='🔄 আবার খুঁজুন'; btn.disabled=false;
            if(d.status!=='success'){ results.innerHTML=`<div class="nearby-empty" style="grid-column:1/-1;">❌ ${d.msg}</div>`; return; }
            if(!d.donors.length){
                results.innerHTML=`<div class="nearby-empty" style="grid-column:1/-1;">
                    <div style="font-size:2.5rem;margin-bottom:10px;">🔍</div>
                    <p style="font-weight:600;">এই এলাকায় কোনো donor পাওয়া যায়নি</p>
                    <p style="font-size:0.85em;color:var(--text-muted);margin-top:5px;">Radius বাড়িয়ে আবার চেষ্টা করুন</p>
                </div>`; return;
            }
            const stCls = {Available:'available', Unavailable:'unavailable', 'Not Available':'notavailable'};
            const stIcon= {Available:'✔', Unavailable:'⛔', 'Not Available':'✖'};
            const bgMap = {'A+':'Aplus','A-':'Aminus','B+':'Bplus','B-':'Bminus','AB+':'ABplus','AB-':'ABminus','O+':'Oplus','O-':'Ominus'};
            results.innerHTML = d.donors.map(dn=>{
                const isAvail = dn.status === 'Available';
                const bgClass = 'blood-' + (bgMap[dn.group] || dn.group.replace(/[^a-zA-Z]/g,''));
                const callBtn = isAvail
                    ? `<button class="dc-call-btn unselectable" onclick="prepCall('${dn.id}')" aria-label="Call donor">📞</button>`
                    : `<button class="dc-call-btn-disabled" disabled title="দাতা এখন Available নেই" aria-label="Not available">🚫</button>`;
                const stText = dn.status === 'Available' ? 'Available' : dn.status === 'Unavailable' ? 'Not Willing' : 'Not Available';
                return `<div class="dc">
                    <div class="dc-badge-wrap">
                        <span class="dc-badge ${bgClass}">${dn.group}</span>
                    </div>
                    <div class="dc-info">
                        <div class="dc-name">${dn.name} <span style="font-size:0.85em;opacity:0.85;">${dn.badge_icon||''}</span></div>
                        <span class="${stCls[dn.status]||'available'} dc-status-badge">${stIcon[dn.status]||'✔'} ${stText}</span>
                        <div class="dc-loc">📍 ${dn.loc}</div>
                        <div class="dc-last">📍 ${dn.dist} km দূরে</div>
                    </div>
                    ${callBtn}
                </div>`;
            }).join('');
        }).catch(()=>{ results.innerHTML='<div class="nearby-empty" style="grid-column:1/-1;">❌ Network error. আবার চেষ্টা করুন।</div>'; btn.textContent='📡 খুঁজুন'; btn.disabled=false; });
    }, err=>{
        btn.textContent='📡 খুঁজুন'; btn.disabled=false;
        let msg = '📍 Location পাওয়া যায়নি।';
        if(err.code===1) msg = '📍 Location permission দিন। Settings এ গিয়ে Allow করুন।';
        results.innerHTML=`<div class="nearby-empty" style="grid-column:1/-1;">
            <div style="font-size:2.5rem;margin-bottom:10px;">📍</div>
            <p style="font-weight:600;">${msg}</p>
            <button class="req-call-btn" style="margin-top:12px;" onclick="loadNearbyDonors()">🔄 আবার চেষ্টা করুন</button>
        </div>`;
    },{timeout:15000, enableHighAccuracy:true});
}

// ============================================================
// LIVE NOTIFICATION SYSTEM — 30s polling, toast, bell
// ============================================================
let _lnTimer = null;
let _seenIds  = new Set();

function toggleNPanel() {
    const p = document.getElementById('nPanel');
    p.classList.toggle('show');
    if(p.classList.contains('show')) {
        document.getElementById('nBadge').classList.remove('on');
        // Clear PWA app icon badge when user opens the panel
        if ('clearAppBadge' in navigator && Notification.permission === 'granted') {
            navigator.clearAppBadge().catch(function(){});
        }
    }
}
document.addEventListener('click', function(e){
    // Close notification panel — check bell wrap AND panel itself
    const w = document.getElementById('nBellWrap');
    const p = document.getElementById('nPanel');
    if(p && p.classList.contains('show')) {
        if((!w || !w.contains(e.target)) && !p.contains(e.target)) {
            p.classList.remove('show');
        }
    }
    // Close mobile nav
    const nav = document.getElementById('siteNav');
    if (nav && !nav.contains(e.target)) {
        const links = document.getElementById('navLinks');
        if (links && links.classList.contains('open')) {
            links.classList.remove('open');
            document.body.style.overflow = '';
        }
    }
});

function showToast(r, type) {
    const wrap = document.getElementById('toastWrap');
    if(!wrap) return;
    // If called with a plain string (e.g. GPS error), show simple toast
    if (typeof r === 'string') {
        const el = document.createElement('div');
        el.className = 'toast-item';
        const ico = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
        el.innerHTML = '<div class="toast-ico">' + ico + '</div>'
            + '<div class="toast-bd"><div class="toast-sub" style="color:var(--text-main);font-size:0.88em;">' + r + '</div></div>'
            + '<button class="toast-x" onclick="var t=this.closest(\'.toast-item\');t.classList.add(\'bye\');setTimeout(function(){t.remove()},260)">✕</button>';
        wrap.appendChild(el);
        setTimeout(function(){ if(el.parentNode){el.classList.add('bye');setTimeout(function(){el.remove();},260);} }, 4000);
        return;
    }
    // Blood request object toast — শুধু in-app toast, system notification showSystemNotif() করে
    const icons={Critical:'🔴',High:'🟠',Medium:'🔵'};
    const el = document.createElement('div');
    el.className = 'toast-item';
    el.innerHTML = '<div class="toast-ico">🆘</div>'
        + '<div class="toast-bd">'
        + '<div class="toast-ttl">' + (icons[r.urgency]||'🟠') + ' ' + (r.urgency||'') + ' — ' + (r.blood_group||'') + ' রক্ত দরকার!</div>'
        + '<div class="toast-sub">🏥 ' + (r.hospital||'') + '<br>📞 ' + (r.contact||'') + '</div>'
        + '</div>'
        + '<button class="toast-x" onclick="var t=this.closest(\'.toast-item\');t.classList.add(\'bye\');setTimeout(function(){t.remove()},260)">✕</button>';
    wrap.appendChild(el);
    setTimeout(function(){ if(el.parentNode){el.classList.add('bye');setTimeout(function(){el.remove();},260);} }, 7000);
    // FIX: system notification সরানো হয়েছে — showSystemNotif() এখন এটা handle করে
    // Play notification sound for new blood requests
    if (localStorage.getItem('sound_off') !== '1') {
        try {
            const s = document.getElementById('successSound');
            if (s) { s.currentTime = 0; s.play().catch(function(){}); }
        } catch(e) {}
    }
}

// ── Mark-as-read — localStorage-এ read request IDs রাখি ──────
var _readIds = (function(){
    try { return new Set(JSON.parse(localStorage.getItem('notif_read_ids') || '[]')); }
    catch(e) { return new Set(); }
})();
function _saveReadIds() {
    try { localStorage.setItem('notif_read_ids', JSON.stringify([..._readIds])); } catch(e) {}
}
function markNotifRead(reqId) {
    _readIds.add(String(reqId));
    _saveReadIds();
    // Panel re-render — current data থেকে unread গুলো দেখাও
    refreshNPanel(_reqAllData || []);
}
function markAllNotifRead() {
    (_reqAllData || []).forEach(function(r){ _readIds.add(String(r.id)); });
    _saveReadIds();
    refreshNPanel(_reqAllData || []);
}

function refreshNPanel(reqs) {
    const list  = document.getElementById('nList');
    const count = document.getElementById('nCount');
    const badge = document.getElementById('nBadge');
    if(!list) return;

    // Read filter — read করা IDs বাদ দাও
    const unread = reqs.filter(function(r){ return !_readIds.has(String(r.id)); });

    if(!unread.length) {
        list.innerHTML = reqs.length && _readIds.size
            ? '<div class="notif-empty">✅ সব পড়া হয়েছে</div>'
            : '<div class="notif-empty">কোনো active request নেই</div>';
        if(count) count.textContent = ''; badge.classList.remove('on');
        if ('clearAppBadge' in navigator && Notification.permission === 'granted') {
            navigator.clearAppBadge().catch(function(){});
        }
        return;
    }

    if(count) count.textContent = unread.length + 'টি unread';
    const icons = {Critical:'🔴', High:'🟠', Medium:'🔵'};

    list.innerHTML = unread.map(function(r){
        return '<div class="notif-row">'
            + '<div class="notif-row-left" onclick="toggleRequestSection();document.getElementById(\'nPanel\').classList.remove(\'show\')">'
            + '<div class="notif-row-grp">' + r.blood_group + ' <span style="font-size:0.55em;font-weight:700;">' + (icons[r.urgency]||'') + ' ' + r.urgency + '</span></div>'
            + '<div class="notif-row-info">🏥 ' + r.hospital + '<br>📞 ' + r.contact + '</div>'
            + '</div>'
            + '<button class="notif-mark-btn" onclick="event.stopPropagation();markNotifRead(' + r.id + ')" title="Mark as read">✓ Read</button>'
            + '</div>';
    }).join('');

    // Mark All Read button
    list.innerHTML += '<button class="notif-panel-mark-all" onclick="markAllNotifRead()">✓ সব Mark as Read করুন</button>';

    badge.textContent = unread.length > 9 ? '9+' : unread.length;
    if(!document.getElementById('nPanel').classList.contains('show')) badge.classList.add('on');
    // Update blood tab badge
    var bloodTabBadge = document.getElementById('nTabBloodBadge');
    if (bloodTabBadge) {
        if (unread.length) {
            bloodTabBadge.textContent = unread.length;
            bloodTabBadge.style.display = '';
        } else {
            bloodTabBadge.style.display = 'none';
        }
    }
    if ('setAppBadge' in navigator && Notification.permission === 'granted') {
        navigator.setAppBadge(unread.length).catch(function(){});
    }
}

function startLiveNotif() {
    if(_lnTimer) return;
    function poll() {
        // Tab hidden থাকলে poll করব না — network save
        if (document.hidden) return;
        var fd = new FormData();
        fd.append('get_blood_requests','1');
        fd.append('csrf_token', CSRF_TOKEN);
        fetch(_AJAX_URL,{method:'POST',body:fd})
        .then(safeJSON)
        .then(function(reqs){
            var newOnes = reqs.filter(function(r){return !_seenIds.has(String(r.id));});
            if(_seenIds.size>0 && newOnes.length>0) {
                // FIX: showToast বদলে showSystemNotif — in-app toast বন্ধ,
                // শুধু phone notification panel-এ system notification যাবে
                newOnes.forEach(function(r){ showSystemNotif(r); });
                triggerBellRing();
                // নতুন request এলে read list থেকে সরাও — unread হিসেবে দেখাবে
                newOnes.forEach(function(r){ _readIds.delete(String(r.id)); });
                _saveReadIds();
            }
            reqs.forEach(function(r){_seenIds.add(String(r.id));});
            // _reqAllData সব সময় update রাখো — refreshNPanel এটা ব্যবহার করে
            _reqAllData = reqs;
            refreshNPanel(reqs);
            // Re-render the Active Requests grid only while its page is on screen
            // (mobile: page-active; desktop: all pages are always visible).
            var reqPage = document.getElementById('page-requests');
            var reqVisible = reqPage && (reqPage.classList.contains('page-active') || window.innerWidth > 650);
            if(reqVisible) {
                applyReqFilter();
            }
        }).catch(function(){});
    }
    poll();
    _lnTimer = setInterval(poll, 30000);
    // Tab visible হলে immediately poll করি
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) poll();
    });
}
window.addEventListener('load', startLiveNotif);
window.addEventListener('load', startSvcNotifPoll);

// ============================================================
// DEVICE ID — localStorage UUID, persistent per browser
// Save হয় exactly একবার — same ID থাকলে আর পাঠায় না।
// Cache clear বা নতুন browser = নতুন ID = আবার একবার save।
// ============================================================
function getDeviceId() {
    var id = localStorage.getItem('ba_device_id');
    if (!id) {
        id = 'dev_' + Math.random().toString(36).substr(2,9) + '_' + Date.now().toString(36);
        localStorage.setItem('ba_device_id', id);
    }
    return id;
}

(function() {
    window.addEventListener('load', function() {
        var currentId  = getDeviceId();
        var lastSavedId = localStorage.getItem('ba_device_saved_id');
        if (lastSavedId === currentId) return; // same ID — already saved, skip
        setTimeout(function() {
            _saveDeviceId('first_visit');
            localStorage.setItem('ba_device_saved_id', currentId);
        }, 800);
    });
})();
// ============================================================
// NOTIFICATION PANEL — 2-TAB SYSTEM
// ============================================================
var _currentNTab = 'blood'; // 'blood' | 'service'

function switchNTab(tab) {
    _currentNTab = tab;
    var bloodBtn  = document.getElementById('nTabBlood');
    var svcBtn    = document.getElementById('nTabSvc');
    var bloodCont = document.getElementById('nTabBloodContent');
    var svcCont   = document.getElementById('nTabSvcContent');
    if (tab === 'blood') {
        bloodBtn.classList.add('active');
        svcBtn.classList.remove('active');
        bloodCont.style.display = '';
        svcCont.style.display   = 'none';
    } else {
        svcBtn.classList.add('active');
        bloodBtn.classList.remove('active');
        svcCont.style.display   = '';
        bloodCont.style.display = 'none';
        // Load fresh service notifs when tab is opened
        _loadSvcNotifs();
    }
}

// Override toggleNPanel to support tab badge clearing
(function(){
    var _orig = window.toggleNPanel;
    window.toggleNPanel = function() {
        var p = document.getElementById('nPanel');
        p.classList.toggle('show');
        if (p.classList.contains('show')) {
            document.getElementById('nBadge').classList.remove('on');
            if ('clearAppBadge' in navigator && Notification.permission === 'granted') {
                navigator.clearAppBadge().catch(function(){});
            }
            // If services tab active, reload
            if (_currentNTab === 'service') _loadSvcNotifs();
        }
    };
})();

// ============================================================
// SERVICE NOTIFICATIONS — device-specific, polls every 30s
// ============================================================
var _svcNotifTimer = null;
var _svcNotifsData = [];

function startSvcNotifPoll() {
    if (_svcNotifTimer) return;
    _loadSvcNotifs();
    _svcNotifTimer = setInterval(function() {
        if (!document.hidden) _loadSvcNotifs();
    }, 30000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) _loadSvcNotifs();
    });
}

function _loadSvcNotifs() {
    var fd = new FormData();
    fd.append('get_service_notifs', '1');
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(function(notifs) {
        // Read notifications filter — server থেকে আসা read items render করে না
        _svcNotifsData = (notifs || []).filter(function(n){ return !n.is_read; });
        _renderSvcNotifs(_svcNotifsData);
        _updateSvcBadge(_svcNotifsData);
        // Trigger bell ring for new unread service notifs
        var unread = _svcNotifsData.filter(function(n){ return !n.is_read; });
        if (unread.length > 0) triggerBellRing();
    }).catch(function(){});
}

function _renderSvcNotifs(notifs) {
    var list = document.getElementById('nSvcList');
    if (!list) return;
    var countEl = document.getElementById('nSvcCount');
    var unread = notifs.filter(function(n){ return !n.is_read; });
    if (countEl) countEl.textContent = unread.length ? unread.length + 'টি unread' : '';

    if (!notifs.length) {
        list.innerHTML = '<div class="notif-empty">কোনো service notification নেই</div>';
        return;
    }

    var iconMap = {
        'secret_reset':'🔑', 'location_on':'📍', 'notif_on':'🔔',
        'secret_code_ready':'✅', 'info':'ℹ️', 'warning':'⚠️', 'admin_reply':'💬',
        'welcome':'🎉', 'donor_called':'📞', 'blood_request':'🆘'
    };

    list.innerHTML = notifs.map(function(n) {
        var icon = iconMap[n.type] || 'ℹ️';
        var ts   = n.ts ? new Date(n.ts * 1000).toLocaleString('bn-BD') : '';
        var unreadCls = !n.is_read ? ' unread' : '';
        var readBtn = !n.is_read
            ? '<button class="svc-notif-read-btn" onclick="event.stopPropagation();markSvcNotifRead(' + n.id + ')">✓ পড়েছি</button>'
            : '';
        return '<div class="svc-notif-row' + unreadCls + '" id="svcn_' + n.id + '">'
            + '<div class="svc-notif-icon">' + icon + '</div>'
            + '<div class="svc-notif-body">'
            + '<div class="svc-notif-msg">' + (n.message || '') + '</div>'
            + '<div class="svc-notif-time">' + ts + '</div>'
            + '</div>'
            + '<div class="svc-notif-actions">'
            + readBtn
            + '</div>'
            + '</div>';
    }).join('');

    // Attach swipe-to-dismiss handlers
    list.querySelectorAll('.svc-notif-row').forEach(function(row) {
        _attachSwipeDismiss(row);
    });

    if (unread.length) {
        list.innerHTML += '<button class="notif-panel-mark-all" onclick="markAllSvcNotifsRead()" style="margin-top:4px;">✓ সব Read করুন</button>';
    }
}

// Swipe to dismiss (touch + mouse)
function _attachSwipeDismiss(el) {
    var startX = 0, curX = 0, swiping = false;
    function onStart(e) {
        startX = (e.touches ? e.touches[0].clientX : e.clientX);
        swiping = true; curX = 0;
    }
    function onMove(e) {
        if (!swiping) return;
        curX = (e.touches ? e.touches[0].clientX : e.clientX) - startX;
        if (curX > 10) { // only right swipe
            el.style.transform = 'translateX(' + Math.min(curX, 120) + 'px)';
            el.style.opacity = String(1 - curX / 200);
        }
    }
    function onEnd() {
        swiping = false;
        if (curX > 80) {
            // Swipe to dismiss = mark as read + animate out
            var idMatch = el.id.match(/svcn_(\d+)/);
            if (idMatch) {
                var nid = parseInt(idMatch[1]);
                el.classList.add('swiping-out');
                setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 320);
                _svcNotifsData = (_svcNotifsData || []).filter(function(n){ return n.id != nid; });
                _updateSvcBadge(_svcNotifsData);
                _deleteSvcNotifServer(nid); // ← DB থেকে delete — পরের poll এ আর আসবে না
            }
        } else {
            el.style.transform = '';
            el.style.opacity = '';
        }
    }
    el.addEventListener('touchstart', onStart, {passive:true});
    el.addEventListener('touchmove',  onMove,  {passive:true});
    el.addEventListener('touchend',   onEnd);
    el.addEventListener('mousedown',  onStart);
    el.addEventListener('mousemove',  onMove);
    el.addEventListener('mouseup',    onEnd);
}

function deleteSvcNotif(id, skipConfirm) {
    var el = document.getElementById('svcn_' + id);
    if (el) {
        el.classList.add('swiping-out');
        setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 320);
    }
    _svcNotifsData = (_svcNotifsData || []).filter(function(n){ return n.id != id; });
    _updateSvcBadge(_svcNotifsData);
    _deleteSvcNotifServer(id); // ← permanently delete
}

function deleteAllSvcNotifs() {
    var list = document.getElementById('nSvcList');
    if (list) {
        list.querySelectorAll('.svc-notif-row').forEach(function(row) {
            row.classList.add('swiping-out');
        });
        setTimeout(function() {
            _svcNotifsData = [];
            _renderSvcNotifs([]);
            _updateSvcBadge([]);
        }, 320);
    }
    _deleteSvcNotifServer(0, true); // del_all=true
}

function _updateSvcBadge(notifs) {
    var badge = document.getElementById('nTabSvcBadge');
    var mainBadge = document.getElementById('nBadge');
    var unread = notifs.filter(function(n){ return !n.is_read; });
    if (!badge) return;
    if (unread.length) {
        badge.textContent = unread.length;
        badge.style.display = '';
        // Also update main bell badge to show combined
        var bloodUnread = (_reqAllData||[]).filter(function(r){ return !_readIds.has(String(r.id)); }).length;
        var total = bloodUnread + unread.length;
        if (mainBadge && !document.getElementById('nPanel').classList.contains('show')) {
            mainBadge.textContent = total > 9 ? '9+' : total;
            mainBadge.classList.add('on');
        }
    } else {
        badge.style.display = 'none';
    }
}

function _markSvcNotifReadServer(id) {
    var fd = new FormData();
    fd.append('mark_service_notif_read', '1');
    fd.append('notif_id', id);
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd}).catch(function(){});
}

// Bell থেকে মুছলে DB থেকে permanently delete — পরের poll এ আর আসবে না
function _deleteSvcNotifServer(id, delAll) {
    var fd = new FormData();
    fd.append('delete_service_notif', '1');
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);
    if (delAll) {
        fd.append('del_all', '1');
    } else {
        fd.append('notif_id', id);
    }
    fetch(_AJAX_URL, {method:'POST', body:fd}).catch(function(){});
}

function markSvcNotifRead(id) {
    // Optimistic UI — local state update, NO re-fetch
    // (re-fetch করলে server থেকে সব notification ফিরে আসে — এটাই bug ছিল)
    _svcNotifsData = (_svcNotifsData || []).map(function(n){
        return n.id == id ? Object.assign({}, n, {is_read: 1}) : n;
    });
    _renderSvcNotifs(_svcNotifsData);
    _updateSvcBadge(_svcNotifsData);
    _markSvcNotifReadServer(id);
}

function markAllSvcNotifsRead() {
    (_svcNotifsData || []).forEach(function(n) {
        if (!n.is_read) markSvcNotifRead(n.id);
    });
}

// Send a service push notification to a device (called from admin panel side via PHP)
// Also: show system browser notification for service notifs
function _showSvcSystemNotif(msg, type) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    var iconMap = {'secret_reset':'🔑','secret_code_ready':'✅','location_on':'📍','notif_on':'🔔'};
    var icon = iconMap[type] || 'ℹ️';
    var opts = {
        body: msg,
        icon: '/?badge_icon=1',
        badge: '/?badge_icon=1',
        tag: 'svc_' + type + '_' + Date.now(),
        vibrate: [100, 50, 100],
        requireInteraction: false
    };
    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.ready.then(function(reg) {
            reg.showNotification(icon + ' Blood Arena — Service', opts).catch(function(){});
        });
    }
}

// ============================================================
// ADMIN MESSAGE MODAL
// ============================================================
function openAdminMessageModal() {
    // signed-in হলে name/phone prefill করো (account dashboard থেকে খুললে সুবিধা)
    var _auth = null;
    try { _auth = (typeof BA_AUTH !== 'undefined' && BA_AUTH) ? BA_AUTH : JSON.parse(localStorage.getItem('ba_auth') || 'null'); } catch(e){}
    document.getElementById('adm_sender_name').value = (_auth && _auth.name) ? _auth.name : '';
    document.getElementById('adm_sender_phone').value = (_auth && _auth.phone && /^\+8801\d{9}$/.test(_auth.phone)) ? _auth.phone : '+8801';
    document.getElementById('adm_sender_msg').value = '';
    document.getElementById('adm_msg_error').style.display = 'none';
    document.getElementById('adm_msg_success').style.display = 'none';
    var btn = document.getElementById('adm_msg_btn');
    if (btn) { btn.disabled = false; btn.textContent = '📤 পাঠান'; }
    openOverlay('adminMsgModal');
}

function closeAdminMsgModal() {
    closeOverlay('adminMsgModal');
}

function submitAdminMessage() {
    var name  = document.getElementById('adm_sender_name').value.trim();
    var phone = document.getElementById('adm_sender_phone').value.trim();
    var msg   = document.getElementById('adm_sender_msg').value.trim();
    var errEl = document.getElementById('adm_msg_error');
    var sucEl = document.getElementById('adm_msg_success');
    var btn   = document.getElementById('adm_msg_btn');
    errEl.style.display = 'none';
    sucEl.style.display = 'none';

    if (!name) { errEl.textContent = '❌ নাম দিন।'; errEl.style.display = 'block'; return; }
    if (!/^\+8801\d{9}$/.test(phone)) { errEl.textContent = '❌ সঠিক ফোন নম্বর দিন (+8801XXXXXXXXX)।'; errEl.style.display = 'block'; return; }
    if (!msg || msg.length < 5) { errEl.textContent = '❌ Message লিখুন (কমপক্ষে ৫ অক্ষর)।'; errEl.style.display = 'block'; return; }

    btn.disabled = true; btn.textContent = '⏳ পাঠানো হচ্ছে...';
    var fd = new FormData();
    fd.append('submit_admin_message', '1');
    fd.append('sender_name', name);
    fd.append('sender_phone', phone);
    fd.append('message', msg);
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);

    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(function(d) {
        btn.disabled = false; btn.textContent = '📤 পাঠান';
        if (d.status === 'success') {
            sucEl.textContent = d.msg || '✅ ধন্যবাদ! বার্তা পাঠানো হয়েছে।';
            sucEl.style.display = 'block';
            btn.disabled = true; btn.textContent = '✅ পাঠানো হয়েছে';
            document.getElementById('adm_sender_msg').value = '';
            // Start polling for admin reply
            _startAdminReplyPoll();
        } else {
            errEl.textContent = d.msg || '❌ কিছু ভুল হয়েছে।';
            errEl.style.display = 'block';
        }
    }).catch(function() {
        btn.disabled = false; btn.textContent = '📤 পাঠান';
        errEl.textContent = '❌ Network error। আবার চেষ্টা করুন।';
        errEl.style.display = 'block';
    });
}

// ── Admin Reply Polling — device-specific ────────────────
var _adminReplyTimer = null;
var _adminReplySeen = (function(){
    try { return new Set(JSON.parse(localStorage.getItem('adm_reply_seen')||'[]')); }
    catch(e){ return new Set(); }
})();
function _saveAdminReplySeen() {
    try { localStorage.setItem('adm_reply_seen', JSON.stringify([..._adminReplySeen])); } catch(e){}
}

function _startAdminReplyPoll() {
    if (_adminReplyTimer) return;
    _pollAdminReplies();
    _adminReplyTimer = setInterval(function() {
        if (!document.hidden) _pollAdminReplies();
    }, 30000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) _pollAdminReplies();
    });
}

function _pollAdminReplies() {
    var fd = new FormData();
    fd.append('get_admin_messages', '1');
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
    .then(safeJSON)
    .then(function(replies) {
        if (!Array.isArray(replies) || !replies.length) return;
        var newReplies = replies.filter(function(r){ return !_adminReplySeen.has(String(r.id)); });
        if (newReplies.length) {
            newReplies.forEach(function(r) {
                // Insert into service_notifications UI as 'admin_reply' type
                var fakeNotif = {
                    id: 'amsg_'+r.id,
                    type: 'info',
                    message: '💬 Admin Reply: ' + (r.admin_reply||''),
                    is_read: 0,
                    ts: r.replied_ts || Math.floor(Date.now()/1000)
                };
                if (typeof _svcNotifsData !== 'undefined') {
                    _svcNotifsData.unshift(fakeNotif);
                    _renderSvcNotifs(_svcNotifsData);
                    _updateSvcBadge(_svcNotifsData);
                }
                triggerBellRing();
                // Mark as seen + read in DB
                _adminReplySeen.add(String(r.id));
                _saveAdminReplySeen();
                _markAdminMsgRead(r.id);
            });
        }
    }).catch(function(){});
}

function _markAdminMsgRead(msgId) {
    var fd = new FormData();
    fd.append('mark_admin_msg_read', '1');
    fd.append('msg_id', msgId);
    fd.append('device_id', getDeviceId());
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd}).catch(function(){});
}

// Start admin reply poll on load if device has sent messages before
window.addEventListener('load', function() {
    // Check localStorage for any previously sent message flag
    if (localStorage.getItem('adm_msg_sent')) {
        _startAdminReplyPoll();
    }
});
// Set flag after successful send (supplement the submitAdminMessage call)
var _origSubmitAdminMsg = window.submitAdminMessage;
window.submitAdminMessage = function() {
    localStorage.setItem('adm_msg_sent', '1');
    _origSubmitAdminMsg && _origSubmitAdminMsg();
};
function triggerBellRing() {
    var bell = document.getElementById('nBell');
    if (!bell) return;
    bell.classList.remove('ring', 'live-ring');
    // Force reflow to restart animation
    void bell.offsetWidth;
    bell.classList.add('live-ring');
    setTimeout(function() { bell.classList.remove('live-ring'); }, 800);
}

// ============================================================
// SYSTEM NOTIFICATION — phone notification panel-এ পাঠায়
// in-app toast দেখায় না, শুধু proper system notification
// ============================================================

// Android status bar badge — monochrome white SVG blood drop
// /?badge_icon=1 এ PHP থেকে serve হয়, sw.js-ও একই URL ব্যবহার করতে পারবে
var _NOTIF_BADGE_URL = '/?badge_icon=1';

function showSystemNotif(r) {
    if (!('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;
    var urgencyMap = {Critical:'🔴 Critical', High:'🟠 High', Medium:'🔵 Medium'};
    var title  = t('🆘 ' + (r.blood_group||'') + ' রক্ত দরকার! — ' + (urgencyMap[r.urgency]||'🟠 High'));
    var body   = '🏥 ' + (r.hospital||'') + '\n📞 ' + (r.contact||'');
    var opts   = {
        body:    body,
        icon:    '/icon.png',
        badge:   _NOTIF_BADGE_URL,
        // Same tag as PHP FCM V1 push — browser replaces instead of stacking
        tag:     'blood-req-' + r.id,
        renotify: false,
        vibrate: [200, 100, 200],
        requireInteraction: false,
        data: { url: window.location.href }
    };
    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.ready.then(function(reg) {
            reg.showNotification(title, opts).catch(function() {
                try { new Notification(title, opts); } catch(e) {}
            });
        }).catch(function() {
            try { new Notification(title, opts); } catch(e) {}
        });
    } else {
        try { new Notification(title, opts); } catch(e) {}
    }
    // Notification sound
    if (localStorage.getItem('sound_off') !== '1') {
        try {
            var s = document.getElementById('successSound');
            if (s) { s.currentTime = 0; s.play().catch(function(){}); }
        } catch(e) {}
    }
}

// ── Silent device ID saver — permission allow/deny উভয়েই call হয় ──
function _saveDeviceId(context) {
    try {
        var fd = new FormData();
        fd.append('save_device_id', '1');
        fd.append('device_id', getDeviceId());
        fd.append('context', context);
        fd.append('csrf_token', CSRF_TOKEN);
        fetch(_AJAX_URL, {method:'POST', body:fd}).catch(function(){});
    } catch(e) {}
}

function enableNotifications(){
    dismissNotifPrompt();
    // Collect device ID silently on prompt show regardless of decision
    _saveDeviceId('notif_prompt');
    if(!('Notification' in window)){
        showToast('এই browser-এ notification support নেই।', 'error');
        return;
    }
    Notification.requestPermission().then(function(p){
        if(p==='granted'){
            _saveDeviceId('notif_allow');
            // ── Firebase FCM token acquire — SW ready হওয়ার পর ──
            // firebase-messaging-sw.js register হতে সময় লাগে, তাই 2s delay দরকার
            if (typeof _initFcmToken === 'function') {
                setTimeout(function() { try { _initFcmToken(); } catch(e){} }, 2000);
            }
            showToast('✅ Notifications চালু হয়েছে! নতুন request এলে জানানো হবে।', 'success');
            if ('setAppBadge' in navigator) {
                var curCount = (_reqAllData || []).length;
                if (curCount > 0) {
                    navigator.setAppBadge(curCount).catch(function(){});
                }
            }
        } else if(p==='denied') {
            _saveDeviceId('notif_deny');
            showToast('Notification block করা আছে। Browser Settings থেকে Allow করুন।', 'error');
        } else {
            _saveDeviceId('notif_deny');
        }
    });
}
function dismissNotifPrompt(){
    _saveDeviceId('notif_deny'); // dismissed = treat as deny for device tracking
    try {
        var data = { until: Date.now() + (7 * 24 * 60 * 60 * 1000) };
        localStorage.setItem('notif_dismissed', JSON.stringify(data));
    } catch(e) {}
    var p = document.getElementById('notifPrompt');
    if (p) {
        p.classList.remove('np-show');
    }
}
var _notifPollTimer=null;
function startNotificationPolling(){}

// ============================================================
// APP-MODE PAGE SWITCHING SYSTEM
// ============================================================
let _currentPage = 'home';

let _switchLock = false;
function appSwitchPage(pageKey) {
    vibrateIfOn([18]); // haptic on nav tap
    // If settings panel is open, close it first (animated), then switch
    var _settingsOverlay = document.getElementById('settingsPanelOverlay');
    if (_settingsOverlay && _settingsOverlay.classList.contains('active')) {
        closeSettingsPanel();
        setTimeout(function() { appSwitchPage(pageKey); }, 320);
        return;
    }
    // Desktop/tablet and mobile share one true SPA page-switch (one view at a time).
    if (_switchLock) return;
    const prevKey = _currentPage;
    if (prevKey === pageKey) return;
    _currentPage = pageKey;
    updateBottomNav(pageKey);

    // Direct 1:1 page mapping — no more remapping nearby→more
    const prevEl = document.getElementById('page-' + prevKey);
    const nextEl = document.getElementById('page-' + pageKey);
    if (!nextEl) return;

    function showNext() {
        _switchLock = false;
        document.querySelectorAll('.app-page').forEach(p => {
            p.classList.remove('page-active', 'page-exit');
        });
        nextEl.classList.add('page-active');
        window.scrollTo(0, 0);
        if (pageKey === 'donors')  fetchFilteredData(1);
        if (pageKey === 'requests') loadBloodRequests();
        if (pageKey === 'more')    loadAnalytics();
        if (pageKey === 'home')    {
            refreshHomeCounts();   // FIX: refresh hero bar + stat cards on home return
            // Desktop/tablet: home embeds the analytics block — redraw its charts too.
            if (document.querySelector('#page-home .analytics-section')) loadAnalytics();
        }
        if (pageKey === 'nearby')  {
            // Only auto-search if GPS already granted (don't disrupt user)
            if (navigator.permissions) {
                navigator.permissions.query({name:'geolocation'}).then(function(r) {
                    if (r.state === 'granted') setTimeout(function() { loadNearbyDonors(); }, 250);
                }).catch(function(){});
            }
        }
    }

    if (prevEl && prevEl !== nextEl) {
        _switchLock = true;
        prevEl.classList.add('page-exit');
        setTimeout(showNext, 160);
    } else {
        showNext();
    }
}

function updateBottomNav(activeKey) {
    ['home','donors','register','nearby','more','settings','requests'].forEach(function(k) {
        var btn = document.getElementById('mbn-' + k);
        if (btn) btn.classList.toggle('mbn-active', k === activeKey);
        var sd = document.getElementById('sd-' + k);
        if (sd) sd.classList.toggle('sd-active', k === activeKey);
    });
}

// legacy compatibility
function toggleMobileNav() { /* legacy stub - mobile nav is always visible */ }

function mbnGo(sectionId, key) { appSwitchPage(key); }

// ============================================================
// SETTINGS PANEL
// ============================================================
function openSettingsPanel() {
    vibrateIfOn([15]);
    updateSettingsToggles();
    document.getElementById('settingsPanelOverlay').classList.add('active');
    // Mark settings button active without changing page
    document.querySelectorAll('.mbn-item').forEach(function(b){ b.classList.remove('mbn-active'); });
    document.querySelectorAll('.sd-item').forEach(function(b){ b.classList.remove('sd-active'); });
    var sb = document.getElementById('mbn-settings');
    if (sb) sb.classList.add('mbn-active');
    var sb2 = document.getElementById('sd-settings');
    if (sb2) sb2.classList.add('sd-active');
}
function closeSettingsPanel() {
    document.getElementById('settingsPanelOverlay').classList.remove('active');
    // Restore current page active state
    updateBottomNav(_currentPage);
}

// ── Header Quick Links dropdown (desktop/tablet) ──
function toggleHeaderQuick(e) {
    if (e) e.stopPropagation();
    var w = document.getElementById('headerQuickWrap');
    if (!w) return;
    var open = w.classList.toggle('open');
    var b = document.getElementById('headerQuickBtn');
    if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function closeHeaderQuick() {
    var w = document.getElementById('headerQuickWrap');
    if (w) w.classList.remove('open');
    var b = document.getElementById('headerQuickBtn');
    if (b) b.setAttribute('aria-expanded', 'false');
}
// close on outside click / Escape
document.addEventListener('click', function (ev) {
    var w = document.getElementById('headerQuickWrap');
    if (w && w.classList.contains('open') && !w.contains(ev.target)) closeHeaderQuick();
});
document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') closeHeaderQuick();
});

function clearAppData() {
    if (!confirm(t('⚠️ সব App Data মুছে যাবে এবং page reload হবে।\n\nনিশ্চিত?'))) return;

    // 1. Show loader immediately
    var pl = document.getElementById('pageLoader');
    if (pl) pl.classList.add('loader-show');

    // 2. Clear localStorage & sessionStorage
    try { localStorage.clear(); } catch(e){}
    try { sessionStorage.clear(); } catch(e){}

    // 3. Unregister Service Worker & clear all caches, then reload
    var doReload = function() {
        // Use location.href reload with cache-busting param, then strip it
        var url = window.location.origin + window.location.pathname + '?_cache_bust=' + Date.now();
        window.location.replace(url);
    };

    if ('serviceWorker' in navigator) {
        // Unregister all SW registrations
        navigator.serviceWorker.getRegistrations().then(function(regs) {
            var promises = regs.map(function(r){ return r.unregister(); });
            // Also clear all caches
            if ('caches' in window) {
                caches.keys().then(function(keys){
                    keys.forEach(function(k){ caches.delete(k); });
                });
            }
            return Promise.all(promises);
        }).catch(function(){}).finally(function(){ doReload(); });
    } else {
        doReload();
    }
}

// FAQ now opens as a dedicated info page (content is relocated into the panel on first open).
function openFAQModal() { openInfoPage('faq'); }
function closeFAQModal() { closeInfoPage(); }

// ── 🔐 Auth helpers ──
// ব্যবহারকারী সাইন ইন করা আছে কিনা (localStorage ba_auth অথবা server-injected BA_AUTH)
function _isSignedIn() {
    try {
        if (typeof BA_AUTH !== 'undefined' && BA_AUTH) return true;
        var a = JSON.parse(localStorage.getItem('ba_auth') || 'null');
        return !!a;
    } catch(e) { return false; }
}

// বর্তমান auth state object (server-injected অগ্রাধিকার, fallback localStorage)
function _authState() {
    try {
        if (typeof BA_AUTH !== 'undefined' && BA_AUTH) return BA_AUTH;
        return JSON.parse(localStorage.getItem('ba_auth') || 'null');
    } catch(e) { return null; }
}

// account verified (Telegram/WhatsApp bind করা) কিনা — call করতে লাগে
function _isVerified() {
    var a = _authState();
    return !!(a && a.verified);
}

// signed-in কিন্তু unverified হলে bind করতে উৎসাহ দাও
function _promptBindIfUnverified() {
    if (!_isSignedIn() || _isVerified()) return false;
    openVerifyModal();
    return true;
}

// ── 🔐 Auth modal controls ──
// signed-in অবস্থা অনুযায়ী verify বনাম sign-in অংশ দেখায়
function _syncAuthModalSections() {
    var signedIn = _isSignedIn();
    var verified = _isVerified();
    var verifySec = document.getElementById('authVerifySection');
    var signinSec = document.getElementById('authSigninSection');
    var title = document.getElementById('authModalTitle');
    var sub   = document.getElementById('authModalSub');
    // logged-in + unverified → verify দেখাও; নইলে sign-in দেখাও
    var showVerify = signedIn && !verified;
    if (verifySec) verifySec.style.display = showVerify ? '' : 'none';
    if (signinSec) signinSec.style.display = showVerify ? 'none' : '';
    if (title && sub) {
        if (showVerify) {
            title.textContent = '🔗 অ্যাকাউন্ট verify করুন';
            sub.textContent = 'Telegram (প্রস্তাবিত) বা WhatsApp দিয়ে';
        } else {
            title.textContent = '🔐 সাইন ইন';
            sub.textContent = 'Google দিয়ে';
        }
    }
}

function openAuthModal() {
    _syncAuthModalSections();
    var m = document.getElementById('authModal');
    if (m) m.classList.add('active');
}

// verify/bind অংশটি দেখিয়ে auth modal খোলো (unverified user-দের জন্য)
function openVerifyModal() {
    openAuthModal();
    var m = document.getElementById('authModal');
    if (m) {
        var vs = document.getElementById('authVerifySection');
        if (vs && vs.style.display !== 'none') try { vs.scrollIntoView({behavior:'smooth', block:'start'}); } catch(e){}
    }
}
function closeAuthModal() {
    var m = document.getElementById('authModal');
    if (m) m.classList.remove('active');
    // reset Telegram/WhatsApp verify steps
    ['waOtpStep','tgOtpStep','tgOpenBotDiv'].forEach(function(id){
        var e = document.getElementById(id); if (e) e.style.display = 'none';
    });
    ['waOtpInput','tgOtpInput'].forEach(function(id){
        var e = document.getElementById(id); if (e) e.value = '';
    });
}

// Render logged-in / logged-out state on the auth entry button
function _renderAuthState() {
    var loggedIn = false, label = '', auth = null;
    try {
        var a = JSON.parse(localStorage.getItem('ba_auth') || 'null');
        if (typeof BA_AUTH !== 'undefined' && BA_AUTH) a = BA_AUTH;
        if (a) { loggedIn = true; label = a.name || a.email || a.phone || 'Account'; auth = a; }
    } catch(e){}

    var btn = document.getElementById('authEntryBtn');
    if (btn) {
        if (loggedIn) {
            btn.innerHTML = '👤 ' + label;
            btn.onclick = openAccountDashboard;
        } else {
            btn.innerHTML = '🔐 সাইন ইন';
            btn.onclick = openAuthModal;
        }
    }

    // ── Header round account icon (Google-style avatar) ──
    _renderHeaderAccountBtn(loggedIn, auth);

    // ── Sidebar logout — শুধু সাইন-ইন থাকলে দেখাও ──
    var sdLogout = document.getElementById('sdLogoutWrap');
    if (sdLogout) sdLogout.style.display = loggedIn ? '' : 'none';

    // ── রেজিস্ট্রেশন ট্যাব — সাইন ইন + ফোন verify দুটোই বাধ্যতামূলক ──
    //  signed-out → sign-in prompt; signed-in কিন্তু unverified → verify prompt;
    //  verified হলেই register toggle দেখা যাবে।
    var verifiedNow = loggedIn && _isVerified();
    var regPrompt = document.getElementById('regAuthPrompt');
    if (regPrompt) regPrompt.style.display = verifiedNow ? 'none' : '';
    var regSigninBlk = document.getElementById('regSigninBlock');
    if (regSigninBlk) regSigninBlk.style.display = loggedIn ? 'none' : '';
    var regVerifyBlk = document.getElementById('regVerifyBlock');
    if (regVerifyBlk) regVerifyBlk.style.display = (loggedIn && !verifiedNow) ? '' : 'none';
    var regToggle = document.getElementById('regToggleContainer');
    if (regToggle) regToggle.style.display = verifiedNow ? '' : 'none';
    // verify না করে আগের থেকে form খোলা থাকলে বন্ধ করো
    if (!verifiedNow) {
        var rfx = document.getElementById('regForm');
        if (rfx && rfx.style.display !== 'none') { try { closeRegForm(); } catch(e){ rfx.style.display = 'none'; } }
    }

    // ── Update My Info ট্যাব — sign-in gate ──
    var upPanel  = document.getElementById('updateSignedInPanel');
    var upPrompt = document.getElementById('updateAuthPrompt');
    if (upPanel)  upPanel.style.display  = loggedIn ? 'flex' : 'none';
    if (upPrompt) upPrompt.style.display = loggedIn ? 'none' : '';
    if (loggedIn) {
        _prefillRegFromAuth(auth);
    } else {
        // সাইন আউট অবস্থায় খোলা form থাকলে বন্ধ করো
        var rf = document.getElementById('regForm');
        if (rf && rf.style.display !== 'none') { try { closeRegForm(); } catch(e){ rf.style.display = 'none'; } }
    }
}

// ── Header round account avatar (Google photo বা initial) ──
function _renderHeaderAccountBtn(loggedIn, auth) {
    var btn  = document.getElementById('headerAccountBtn');
    if (!btn) return;
    if (!loggedIn) {
        btn.onclick = openAuthModal;
        btn.title   = t('সাইন ইন');
        btn.innerHTML = '<span class="header-account-fallback" id="headerAccountInit">👤</span>';
        return;
    }
    // signed-in: tapping the avatar jumps straight to the Account Dashboard
    btn.onclick = openAccountDashboard;
    var nm    = (auth && (auth.name || auth.email || auth.phone)) || 'Account';
    btn.title = nm;
    var photo = auth && auth.photo;
    if (photo) {
        var init = (nm.trim()[0] || '👤').toUpperCase();
        btn.innerHTML = '<img src="' + _esc(photo) + '" alt="" referrerpolicy="no-referrer" ' +
            'onerror="this.outerHTML=\'<span class=&quot;header-account-fallback&quot;>' + _esc(init) + '</span>\'">';
    } else {
        var initial = (nm.trim()[0] || '👤').toUpperCase();
        btn.innerHTML = '<span class="header-account-fallback">' + _esc(initial) + '</span>';
    }
}

// ── Header account quick popup (signed-in: Dashboard / Verify / Logout) ──
function toggleAcctPop() {
    var p = document.getElementById('acctPop');
    if (!p) return;
    if (p.classList.contains('show')) { p.classList.remove('show'); return; }
    // Show "Verify Now" only when signed-in but not yet verified
    var vb = document.getElementById('acctPopVerify');
    if (vb) vb.style.display = (_isSignedIn() && !_isVerified()) ? '' : 'none';
    p.classList.add('show');
}
function closeAcctPop() {
    var p = document.getElementById('acctPop');
    if (p) p.classList.remove('show');
}
document.addEventListener('click', function(e){
    var p = document.getElementById('acctPop');
    if (!p || !p.classList.contains('show')) return;
    var btn = document.getElementById('headerAccountBtn');
    if ((!btn || !btn.contains(e.target)) && !p.contains(e.target)) {
        p.classList.remove('show');
    }
});

// ============================================================
// 👤 ACCOUNT DASHBOARD
// ============================================================
function openAccountDashboard() {
    openOverlay('accountModal');
    _accDashLoading();
    // Profile + linked donor
    var fd = new FormData();
    fd.append('account_info', '1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(res){
            if (res && res.status === 'success') _renderAccountDashboard(res);
            else {
                // session lost server-side — fall back to local cache, prompt re-login
                showToast((res && res.msg) ? res.msg : 'অ্যাকাউন্ট তথ্য আনা যায়নি।', 'error');
                closeAccountModal();
                if (typeof openAuthModal === 'function') openAuthModal();
            }
        })
        .catch(function(){ showToast('Network error। আবার চেষ্টা করুন।', 'error'); });
    // Messages thread (device-keyed, same as admin messaging)
    var mfd = new FormData();
    mfd.append('get_my_messages', '1');
    mfd.append('device_id', (typeof getDeviceId === 'function') ? getDeviceId() : '');
    mfd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:mfd})
        .then(safeJSON)
        .then(function(rows){ _renderMyMessages(Array.isArray(rows) ? rows : []); })
        .catch(function(){ _renderMyMessages([]); });
    // My account-owned blood requests (tokenless management)
    loadMyAccountRequests();
    // My donation history (dates)
    loadMyDonations();
    // reset delete-info section
    var db = document.getElementById('accDeleteInfoBody');
    if (db) db.style.display = 'none';
    var ar = document.getElementById('accDeleteInfoArrow');
    if (ar) ar.style.transform = '';
    var dc2 = document.getElementById('acc_del_confirm'); if (dc2) dc2.value = '';
    var de2 = document.getElementById('acc_del_error');   if (de2) de2.style.display = 'none';
}

// ── My account requests: load + render + delete (no token) ──
function loadMyAccountRequests() {
    var list = document.getElementById('accReqList');
    var cnt  = document.getElementById('accReqCount');
    if (cnt) cnt.textContent = '';
    if (list) list.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.82em;padding:10px;">⏳ লোড হচ্ছে...</div>';
    var fd = new FormData();
    fd.append('get_my_requests', '1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(res){
            var rows = (res && res.status === 'success' && Array.isArray(res.requests)) ? res.requests : [];
            _renderMyAccountRequests(rows);
        })
        .catch(function(){ _renderMyAccountRequests([]); });
}

function _renderMyAccountRequests(rows) {
    var list = document.getElementById('accReqList');
    var cnt  = document.getElementById('accReqCount');
    if (!list) return;
    if (cnt) cnt.textContent = rows.length ? (rows.length + ' টি active') : '';
    if (!rows.length) {
        list.innerHTML = '<div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;padding:14px;text-align:center;color:var(--text-muted);font-size:0.82em;">কোনো active request নেই</div>';
        return;
    }
    var urgBn = { Critical:'🔴 অতিজরুরি', High:'🟠 জরুরি', Medium:'🔵 প্রয়োজন' };
    var html = '';
    rows.forEach(function(r){
        var bgClass = 'bg' + String(r.blood_group || '').replace(/[^a-zA-Z]/g,'') + ((String(r.blood_group||'').indexOf('+') !== -1) ? 'pos' : 'neg');
        html +=
            '<div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;padding:12px 14px;margin-bottom:10px;">' +
              '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">' +
                '<span class="blood-badge ' + bgClass + '" style="font-size:0.95em;font-weight:800;">' + _esc(r.blood_group) + '</span>' +
                '<span style="font-size:0.72em;font-weight:700;color:var(--text-muted);">' + _esc(urgBn[r.urgency] || r.urgency || '') + '</span>' +
              '</div>' +
              '<div style="font-size:0.83em;color:var(--text-muted);line-height:1.8;">' +
                '👤 <strong style="color:var(--text-main);">' + _esc(r.patient_name) + '</strong><br>' +
                '🏥 ' + _esc(r.hospital) + '<br>' +
                '📞 ' + _esc(r.contact) +
                (r.created_at ? '<br>🗓️ ' + _esc(new Date(r.created_at * 1000).toLocaleString('bn-BD', {day:'numeric', month:'long', hour:'2-digit', minute:'2-digit'})) : '') +
              '</div>' +
              '<button onclick="deleteMyAccountRequest(' + (r.id|0) + ', this)" style="width:100%;margin-top:10px;padding:9px;background:rgba(220,38,38,0.07);border:1px solid rgba(220,38,38,0.35);color:var(--danger);border-radius:10px;font-size:0.82em;cursor:pointer;font-weight:700;min-height:unset;box-shadow:none;">🗑️ এই Request মুছুন</button>' +
            '</div>';
    });
    list.innerHTML = html;
}

function deleteMyAccountRequest(reqId, btn) {
    if (!reqId) return;
    if (!confirm(t('এই Request টি মুছে ফেলতে চান? এটি ফেরানো যাবে না।'))) return;
    if (btn) { btn.disabled = true; btn.textContent = '⏳ মুছছে...'; }
    var fd = new FormData();
    fd.append('delete_my_request', '1');
    fd.append('request_id', reqId);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(d){
            if (d && d.status === 'success') {
                showToast(d.msg || '✅ Request মুছে ফেলা হয়েছে।', 'success');
                // Drop from the account-owned ID set so the Emergency "mine" tab updates
                try { if (typeof _myReqIds !== 'undefined') _myReqIds.delete(String(reqId)); } catch(e){}
                loadMyAccountRequests();
                if (typeof loadBloodRequests === 'function') loadBloodRequests();
            } else {
                if (btn) { btn.disabled = false; btn.textContent = '🗑️ এই Request মুছুন'; }
                showToast((d && d.msg) ? d.msg : '❌ মুছতে ব্যর্থ হয়েছে।', 'error');
            }
        })
        .catch(function(){
            if (btn) { btn.disabled = false; btn.textContent = '🗑️ এই Request মুছুন'; }
            showToast('❌ Network error। আবার চেষ্টা করুন।', 'error');
        });
}

// ── Delete My Info — account dashboard section ──
function toggleAccDeleteInfo() {
    var body  = document.getElementById('accDeleteInfoBody');
    var arrow = document.getElementById('accDeleteInfoArrow');
    if (!body) return;
    var open = body.style.display === 'none' || !body.style.display;
    body.style.display = open ? 'block' : 'none';
    if (arrow) arrow.style.transform = open ? 'rotate(90deg)' : '';
}

function submitAccDeleteInfo() {
    var inp = document.getElementById('acc_del_confirm');
    var err = document.getElementById('acc_del_error');
    var btn = document.getElementById('acc_del_btn');
    var val = (inp ? inp.value : '').trim().toUpperCase();
    if (val !== 'DELETE') {
        if (err) { err.textContent = '❌ নিশ্চিত করতে DELETE লিখুন।'; err.style.display = 'block'; }
        return;
    }
    if (err) err.style.display = 'none';
    if (btn) { btn.disabled = true; btn.textContent = '⏳ মুছছে...'; }
    var fd = new FormData();
    fd.append('delete_donor', '1');
    fd.append('confirm', 'DELETE');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(d){
            if (btn) { btn.disabled = false; btn.textContent = '🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন'; }
            if (d && d.status === 'success') {
                showToast(d.msg || '✅ তথ্য মুছে ফেলা হয়েছে।', 'success');
                if (inp) inp.value = '';
                // donor card refresh — এখন আর donor নেই
                loadMyAccountRequests();
                openAccountDashboard();
            } else {
                if (err) { err.textContent = (d && d.msg) ? d.msg : '❌ মুছতে ব্যর্থ হয়েছে।'; err.style.display = 'block'; }
            }
        })
        .catch(function(){
            if (btn) { btn.disabled = false; btn.textContent = '🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন'; }
            if (err) { err.textContent = '❌ Network error। আবার চেষ্টা করুন।'; err.style.display = 'block'; }
        });
}

function closeAccountModal() { closeOverlay('accountModal'); }

function _accDashLoading() {
    var dc = document.getElementById('accDonorCard');
    if (dc) dc.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.85em;padding:14px;">⏳ লোড হচ্ছে...</div>';
    var ml = document.getElementById('accMsgList');
    if (ml) ml.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.82em;padding:10px;">⏳ লোড হচ্ছে...</div>';
    var rl = document.getElementById('accReqList');
    if (rl) rl.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.82em;padding:10px;">⏳ লোড হচ্ছে...</div>';
    var dl = document.getElementById('accDonationList');
    if (dl) dl.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.82em;padding:10px;">⏳ লোড হচ্ছে...</div>';
}

// ── My Donations: load + render donation history (with dates) ──
function loadMyDonations() {
    var fd = new FormData();
    fd.append('get_my_donations', '1');
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(res){
            if (res && res.status === 'success') _renderMyDonations(res);
            else _renderMyDonations({history:[], total_donations:0, last_donation:'no'});
        })
        .catch(function(){ _renderMyDonations({history:[], total_donations:0, last_donation:'no'}); });
}

function _renderMyDonations(res) {
    var list = document.getElementById('accDonationList');
    var cnt  = document.getElementById('accDonationCount');
    if (!list) return;
    var total   = res.total_donations || 0;
    var history = Array.isArray(res.history) ? res.history : [];
    if (cnt) cnt.textContent = total ? ('মোট ' + total + ' বার') : '';

    // Build the dated rows. Prefer recorded history; fall back to last_donation.
    var rows = '';
    if (history.length) {
        history.forEach(function(h){
            var dt = h.ts ? new Date(h.ts * 1000) : null;
            var ds = dt ? dt.toLocaleDateString('bn-BD', {year:'numeric', month:'long', day:'numeric'}) : '—';
            rows +=
                '<div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--input-bg);border:1px solid var(--border-color);border-radius:10px;margin-bottom:8px;">' +
                  '<span style="font-size:1.1em;">🩸</span>' +
                  '<div style="font-size:0.83em;line-height:1.5;">' +
                    '<strong style="color:var(--text-main);">' + _esc(ds) + '</strong><br>' +
                    '<span style="color:var(--text-muted);font-size:0.92em;">রক্তদান করেছেন</span>' +
                  '</div>' +
                '</div>';
        });
    } else if (res.last_donation && res.last_donation !== 'no') {
        rows =
            '<div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--input-bg);border:1px solid var(--border-color);border-radius:10px;margin-bottom:8px;">' +
              '<span style="font-size:1.1em;">🩸</span>' +
              '<div style="font-size:0.83em;line-height:1.5;">' +
                '<strong style="color:var(--text-main);">' + _esc(res.last_donation) + '</strong><br>' +
                '<span style="color:var(--text-muted);font-size:0.92em;">সর্বশেষ রক্তদান</span>' +
              '</div>' +
            '</div>';
    }

    if (!rows) {
        list.innerHTML = '<div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;padding:14px;text-align:center;color:var(--text-muted);font-size:0.82em;">এখনো কোনো রক্তদানের রেকর্ড নেই।<br>রক্ত দেওয়ার পর "আমি এইমাত্র রক্ত দিয়েছি 🩸" চেপে Save করুন।</div>';
        return;
    }
    list.innerHTML = rows;
}

function _renderAccountDashboard(res) {
    var a = res.auth || {};
    var name = a.name || a.email || a.phone || 'User';
    var setTxt = function(id, val){ var el = document.getElementById(id); if (el) el.textContent = val; };

    var av = document.getElementById('accAvatar');
    if (av) {
        if (a.photo) {
            var init = (name.trim()[0] || '?').toUpperCase();
            av.innerHTML = '<img src="' + _esc(a.photo) + '" alt="" referrerpolicy="no-referrer" ' +
                'style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" ' +
                'onerror="this.parentNode.textContent=\'' + _esc(init) + '\'">';
        } else {
            av.textContent = (name.trim()[0] || '?').toUpperCase();
        }
    }
    setTxt('accName', name);

    var prov = document.getElementById('accProvider');
    if (prov) {
        if (a.provider === 'phone') {
            prov.textContent = '📱 ফোন';
            prov.style.background = 'rgba(16,185,129,0.12)'; prov.style.color = '#10b981';
            prov.style.borderColor = 'rgba(16,185,129,0.25)';
        } else {
            prov.textContent = '🔵 Google';
            prov.style.background = 'rgba(59,130,246,0.12)'; prov.style.color = '#3b82f6';
            prov.style.borderColor = 'rgba(59,130,246,0.25)';
        }
    }
    setTxt('accEmail', a.email || '—');
    // Show the number used to VERIFY the account (Telegram/WhatsApp/phone-OTP),
    // falling back to the Firebase sign-in phone if not yet verified.
    setTxt('accPhone', a.verify_phone || a.phone || '—');
    setTxt('accMemberSince', a.member_since || '—');

    // ── Verified / unverified badge + bind banner ──
    var vb = document.getElementById('accVerifyBadge');
    if (vb) {
        if (a.verified) {
            var chLabel = (a.verify_channel === 'telegram') ? '✈️ Telegram'
                        : (a.verify_channel === 'whatsapp') ? '🟢 WhatsApp'
                        : (a.provider === 'phone') ? '📱 ফোন' : '';
            vb.textContent = '✅ Verified' + (chLabel ? ' · ' + chLabel : '');
            vb.style.background = 'rgba(16,185,129,0.12)'; vb.style.color = '#10b981';
            vb.style.borderColor = 'rgba(16,185,129,0.3)';
            vb.style.display = '';
        } else {
            vb.textContent = '⚠️ Unverified';
            vb.style.background = 'rgba(245,158,11,0.12)'; vb.style.color = '#f59e0b';
            vb.style.borderColor = 'rgba(245,158,11,0.3)';
            vb.style.display = '';
        }
    }
    var bn = document.getElementById('accVerifyBanner');
    if (bn) bn.style.display = a.verified ? 'none' : '';

    // Donor card
    var dc = document.getElementById('accDonorCard');
    if (!dc) return;
    var d = res.donor;
    if (d) {
        var notWilling = (d.willing === 'no');
        var avail = notWilling ? '<span style="color:var(--danger);font-weight:700;">⛔ এখন Unavailable</span>'
                               : '<span style="color:var(--success);font-weight:700;">✅ Available</span>';
        var lastDon = (d.last_donation && d.last_donation !== 'no') ? d.last_donation : 'এখনো রেকর্ড নেই';
        var willBtn = notWilling
            ? '<button id="accWillBtn" onclick="setMyWilling(\'yes\')" style="width:100%;margin:10px 0 0;background:var(--success);color:#000;border:none;border-radius:10px;padding:11px;font-weight:700;font-size:0.85em;box-shadow:none;">✅ আবার রক্তদানে ইচ্ছুক</button>'
            : '<button id="accWillBtn" onclick="setMyWilling(\'no\')" style="width:100%;margin:10px 0 0;background:rgba(220,38,38,0.1);color:var(--danger);border:1px solid rgba(220,38,38,0.3);border-radius:10px;padding:11px;font-weight:700;font-size:0.85em;box-shadow:none;">⛔ এখন রক্তদানে অনিচ্ছুক</button>';
        // server-এর মতো bg-class বানাও: "A+" → bgApos, "AB-" → bgABneg
        var bgClass = 'bg' + String(d.blood_group || '').replace(/[^a-zA-Z]/g,'') + ((String(d.blood_group||'').indexOf('+') !== -1) ? 'pos' : 'neg');
        dc.innerHTML =
            '<div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:14px;padding:14px;">' +
              '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">' +
                '<span class="blood-badge ' + bgClass + '" style="font-size:1.05em;font-weight:800;">' + _esc(d.blood_group) + '</span>' +
                '<span style="font-size:0.8em;font-weight:700;padding:3px 10px;border-radius:20px;background:' + d.badge_bg + ';color:' + d.badge_color + ';border:1px solid ' + d.badge_border + ';">' + d.badge_icon + ' ' + _esc(d.badge_level) + '</span>' +
              '</div>' +
              '<div style="font-size:0.84em;color:var(--text-muted);line-height:1.9;">' +
                '📍 ' + _esc(d.location || '—') + '<br>' +
                '🩸 মোট রক্তদান: <strong style="color:var(--text-main);">' + d.total_donations + '</strong> বার<br>' +
                '🗓️ শেষ রক্তদান: <strong style="color:var(--text-main);">' + _esc(lastDon) + '</strong><br>' +
                avail +
              '</div>' +
              willBtn +
              '<button onclick="closeAccountModal(); appSwitchPage(\'register\'); setTimeout(function(){ try{switchTab(1); loadMyDonorInfo();}catch(e){} },220);" style="width:100%;margin:8px 0 0;background:var(--info);color:#fff;border:none;border-radius:10px;padding:11px;font-weight:700;font-size:0.85em;box-shadow:none;">✏️ আমার তথ্য Update করুন</button>' +
            '</div>';
    } else {
        dc.innerHTML =
            '<div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.2);border-radius:14px;padding:16px;text-align:center;">' +
              '<p style="font-size:0.85em;color:var(--text-main);font-weight:600;margin:0 0 4px;">আপনি এখনো রক্তদাতা হিসেবে নিবন্ধিত নন</p>' +
              '<p style="font-size:0.76em;color:var(--text-muted);margin:0 0 12px;">রক্তদাতা হিসেবে যুক্ত হয়ে জীবন বাঁচাতে সাহায্য করুন</p>' +
              '<button onclick="closeAccountModal(); appSwitchPage(\'register\'); setTimeout(function(){ try{switchTab(0);}catch(e){} },220);" style="width:100%;background:var(--success);color:#000;border:none;border-radius:10px;padding:11px;font-weight:700;font-size:0.85em;box-shadow:none;margin:0;">📝 রক্তদাতা হিসেবে যুক্ত হোন</button>' +
            '</div>';
    }
}

// ── Account Dashboard থেকে এক ট্যাপে willing/not-willing toggle ──
function setMyWilling(val) {
    var btn = document.getElementById('accWillBtn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
    var fd = new FormData();
    fd.append('set_willing', '1');
    fd.append('willing', val);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch(_AJAX_URL, {method:'POST', body:fd})
        .then(safeJSON)
        .then(function(res){
            if (res && res.status === 'success') {
                showToast(val === 'no' ? '⛔ আপনি এখন রক্তদানে অনিচ্ছুক হিসেবে চিহ্নিত।'
                                       : '✅ আপনি আবার রক্তদানে ইচ্ছুক হিসেবে চিহ্নিত।', 'success');
                openAccountDashboard(); // refresh card with new state
            } else {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                showToast((res && res.msg) ? res.msg : 'পরিবর্তন করা যায়নি।', 'error');
            }
        })
        .catch(function(){
            if (btn) { btn.disabled = false; btn.style.opacity = ''; }
            showToast('Network error। আবার চেষ্টা করুন।', 'error');
        });
}

function _renderMyMessages(rows) {
    var ml = document.getElementById('accMsgList');
    if (!ml) return;
    if (!rows.length) {
        ml.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.8em;padding:14px;background:var(--input-bg);border:1px dashed var(--border-color);border-radius:12px;">এখনো কোনো message নেই। Admin-কে কিছু জানাতে "✚ নতুন" চাপুন।</div>';
        return;
    }
    var html = '';
    rows.forEach(function(r){
        html += '<div style="margin-bottom:12px;">';
        // user's own message (right-aligned bubble)
        html += '<div style="display:flex;justify-content:flex-end;">' +
                  '<div style="max-width:85%;background:var(--primary-red);color:#fff;padding:8px 12px;border-radius:14px 14px 4px 14px;font-size:0.82em;line-height:1.5;word-break:break-word;">' + _esc(r.message || '') + '</div>' +
                '</div>';
        if (r.admin_reply) {
            html += '<div style="display:flex;justify-content:flex-start;margin-top:5px;">' +
                      '<div style="max-width:85%;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-main);padding:8px 12px;border-radius:14px 14px 14px 4px;font-size:0.82em;line-height:1.5;word-break:break-word;">' +
                        '<span style="font-size:0.85em;font-weight:700;color:var(--info);">👨‍⚕️ Admin</span><br>' + _esc(r.admin_reply) +
                      '</div>' +
                    '</div>';
        } else {
            html += '<div style="display:flex;justify-content:flex-start;margin-top:4px;"><span style="font-size:0.72em;color:var(--text-muted);font-style:italic;">⏳ Reply-এর অপেক্ষায়...</span></div>';
        }
        html += '</div>';
    });
    ml.innerHTML = html;
}

// ছোট HTML-escape helper (XSS রোধে)
function _esc(s) {
    return String(s == null ? '' : s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// signed-in Google অ্যাকাউন্ট থেকে registration form prefill
function _prefillRegFromAuth(auth) {
    try {
        if (!auth) return;
        var form = document.getElementById('regForm');
        if (!form) return;
        var nameEl = form.querySelector('input[name="name"]');
        if (nameEl && !nameEl.value && auth.name) nameEl.value = auth.name;
        var phoneEl = form.querySelector('input[name="phone"]');
        if (!phoneEl) return;
        // verify করতে যে নম্বরটি ব্যবহার হয়েছে (Telegram/WhatsApp bind, নইলে phone-OTP)
        //  সেটিই বসাও ও lock করো — register করতে verify বাধ্যতামূলক, তাই নম্বর সবসময় locked।
        var verifiedPhone = auth.verify_phone || auth.phone || '';
        if (verifiedPhone && /^\+8801\d{9}$/.test(verifiedPhone)) {
            phoneEl.value = verifiedPhone;
            phoneEl.readOnly = true;
            phoneEl.setAttribute('readonly', 'readonly');
            phoneEl.classList.add('locked-field');
            phoneEl.title = 'এই নম্বরটি আপনার verify করা নম্বর — পরিবর্তন করা যাবে না';
        } else {
            // verified নম্বর না থাকলে editable (register gate তবু আটকাবে)
            phoneEl.readOnly = false;
            phoneEl.removeAttribute('readonly');
            phoneEl.classList.remove('locked-field');
            phoneEl.removeAttribute('title');
        }
    } catch(e){}
}
document.addEventListener('DOMContentLoaded', function(){ try { _renderAuthState(); } catch(e){} });
function toggleFaq(qEl) {
    vibrateIfOn([10]);
    const a = qEl.nextElementSibling;
    if (!a) return;
    const isOpen = a.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-a.open').forEach(function(el){ 
        el.classList.remove('open');
        const arrow = el.previousElementSibling && el.previousElementSibling.querySelector('.faq-arrow');
        if (arrow) { arrow.style.transform=''; arrow.style.color=''; }
    });
    if (!isOpen) {
        a.classList.add('open');
        const arrow = qEl.querySelector('.faq-arrow');
        if (arrow) { arrow.style.transform='rotate(90deg)'; arrow.style.color='var(--primary-red)'; }
    }
}
function closeSettings(e) {
    if (e.target === document.getElementById('settingsPanelOverlay')) closeSettingsPanel();
}

// ============================================================
// HAMBURGER SIDE DRAWER (mobile)
// ============================================================
function openSideDrawer() {
    vibrateIfOn([15]);
    openOverlay('sideDrawerOverlay');
}
function closeSideDrawer(e) {
    // When used as an overlay onclick handler, only close on scrim taps (not drawer-content taps)
    if (e && e.target && e.target !== document.getElementById('sideDrawerOverlay')) return;
    closeOverlay('sideDrawerOverlay');
}

// ============================================================
// INFO PAGES (About / Privacy & Policy / FAQ / Our Sponsor)
// Single full-screen overlay; the matching panel is revealed by key.
// ============================================================
var _infoTitles = {
    about:   'আমাদের কথা',
    privacy: 'গোপনীয়তা ও নীতিমালা',
    faq:     'প্রশ্ন ও উত্তর',
    sponsor: 'আমাদের স্পন্সর',
    donate:  'Donate Us'
};
function copyDonateNumber() {
    var el = document.getElementById('donateBkashNum');
    var num = el ? el.textContent.trim() : '01518981827';
    function done() { showToast('bKash number কপি হয়েছে: ' + num, 'info'); vibrateIfOn([15]); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(num).then(done).catch(function(){ done(); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = num; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta); done();
    }
}
// Relocate existing modal content into the panel on first open (single source of truth).
function _infoEnsureContent(key) {
    var dst, src;
    if (key === 'about') {
        dst = document.getElementById('infoAboutContent');
        if (dst && !dst.children.length) {
            src = document.querySelector('#aboutUsPopupOverlay .scroll-content');
            if (src) dst.appendChild(src);
        }
    } else if (key === 'faq') {
        dst = document.getElementById('infoFaqContent');
        if (dst && !dst.children.length) {
            src = document.querySelector('#faqModal .scroll-content');
            if (src) { src.style.maxHeight = ''; src.style.padding = ''; src.style.overflowY = ''; dst.appendChild(src); }
        }
    }
}
function openInfoPage(key) {
    vibrateIfOn([15]);
    // If the drawer is open, close it first (no scroll-unlock fight — info overlay re-locks)
    closeOverlay('sideDrawerOverlay');
    _infoEnsureContent(key);
    document.querySelectorAll('#infoPageOverlay .info-panel').forEach(function(p) {
        p.style.display = (p.getAttribute('data-info') === key) ? 'block' : 'none';
    });
    var title = document.getElementById('infoPageTitle');
    if (title) {
        var tt = _infoTitles[key] || 'Info';
        title.textContent = (typeof window.t === 'function') ? window.t(tt) : tt;
    }
    var ov = document.getElementById('infoPageOverlay');
    if (ov) {
        ov.classList.add('active');
        var body = ov.querySelector('.info-page-body');
        if (body) body.scrollTop = 0;
    }
    lockBodyScroll();
}
function closeInfoPage() {
    var ov = document.getElementById('infoPageOverlay');
    if (ov) ov.classList.remove('active');
    unlockBodyScroll();
}

// ============================================================
// DONOR DETAIL POPUP
// ============================================================
// Opens a read-only profile popup for a donor card. Phone number
// and email are intentionally NOT shown — calling goes through the
// existing verified prepCall() flow.
function openDonorDetail(card) {
    if (!card) return;
    vibrateIfOn([12]);
    var d = card.dataset;

    var nameEl = document.getElementById('ddName');
    nameEl.innerHTML = (d.name || 'Donor') +
        (d.badgeicon ? ' <span style="font-size:0.85em;opacity:0.85;">' + d.badgeicon + '</span>' : '');

    var blood = document.getElementById('ddBlood');
    blood.textContent = d.group || '';
    blood.className = 'dd-badge ' + (d.bgclass || '');

    var st = document.getElementById('ddStatus');
    st.textContent = (d.sticon || '') + ' ' + (d.status || '');
    st.className = 'dd-status ' + (d.stclass || '');

    document.getElementById('ddLoc').textContent   = d.loc || '—';
    document.getElementById('ddBadge').textContent = (d.badge || 'New') + ' Donor';
    document.getElementById('ddTotal').textContent = (d.total || '0') + ' বার';
    document.getElementById('ddLast').textContent  = d.last || '—';
    document.getElementById('ddSince').textContent = d.since || '—';

    var callBtn = document.getElementById('ddCallBtn');
    if (d.available === '1') {
        callBtn.style.display = '';
        callBtn.disabled = false;
        callBtn.onclick = function() {
            closeDonorDetail();
            prepCall(d.id);
        };
    } else {
        callBtn.style.display = 'none';
    }

    // ── Red outline on the clicked card; clear any previous selection ──
    //  Only the card currently opened in the detail popup stays highlighted.
    document.querySelectorAll('.donor-selected-outline').forEach(function(el) {
        el.classList.remove('donor-selected-outline');
    });
    card.classList.add('donor-selected-outline');

    // ── Open the popup WITHOUT touching window scroll ──
    //  The overlay is position:fixed/centered, so it needs no scroll lock. Locking
    //  the body (position:fixed) was yanking the list to the top on open — leave the
    //  page exactly where it is so the clicked card stays in place.
    document.getElementById('donorDetailPopup').classList.add('active');
}

function closeDonorDetail() {
    var p = document.getElementById('donorDetailPopup');
    if (p) p.classList.remove('active');
}

// ============================================================
// DONOR CARD ZOOM
// ============================================================
(function initDcZoom() {
    var saved = parseFloat(localStorage.getItem('dc_zoom') || '1.5');
    document.documentElement.style.setProperty('--dc-zoom', saved);
})();
function changeZoom(dir) {
    var steps = [0.75, 0.85, 1.0, 1.15, 1.3, 1.5];
    var cur = parseFloat(localStorage.getItem('dc_zoom') || '1.5');
    var idx = steps.findIndex(function(s){ return Math.abs(s-cur)<0.01; });
    if (idx === -1) idx = 2; // default to 100%
    idx = Math.max(0, Math.min(steps.length-1, idx+dir));
    var newVal = steps[idx];
    localStorage.setItem('dc_zoom', newVal);
    document.documentElement.style.setProperty('--dc-zoom', newVal);
    var zl = document.getElementById('zoomValLabel');
    if (zl) zl.textContent = Math.round(newVal*100)+'%';
}
function updateSettingsToggles() {
    var isLight = localStorage.getItem('theme') !== 'dark';
    var tt = document.getElementById('settingsThemeToggle');
    // Dark mode toggle = ON when dark mode is active
    if (tt) tt.classList.toggle('on', !isLight);
    // Update the icon in settings
    var si = document.querySelector('.si-theme .settings-item-icon');
    if (si) si.textContent = isLight ? '☀️' : '🌙';

    // ✨ 3D & Animation toggle — OFF by default; ON only when user opts in (fx_on)
    var fxt = document.getElementById('settingsFxToggle');
    if (fxt) fxt.classList.toggle('on', localStorage.getItem('fx_on') === '1');

    var soundOff = localStorage.getItem('sound_off') === '1';
    var st = document.getElementById('settingsSoundToggle');
    if (st) st.classList.toggle('on', !soundOff);

    var autoScrollOn = localStorage.getItem('auto_scroll_call') === '1';
    var ast = document.getElementById('settingsAutoScrollToggle');
    if (ast) ast.classList.toggle('on', autoScrollOn);

    var vibOff = localStorage.getItem('vibration_off') === '1';
    var vt = document.getElementById('settingsVibToggle');
    if (vt) vt.classList.toggle('on', !vibOff);

    // Update zoom label
    var zl = document.getElementById('zoomValLabel');
    if (zl) { var zv = parseFloat(localStorage.getItem('dc_zoom') || '1.5'); zl.textContent = Math.round(zv*100)+'%'; }

    // Notification status
    var nt = document.getElementById('notifStatusText');
    var nb = document.getElementById('notifStatusBadge');
    if ('Notification' in window) {
        if (Notification.permission === 'granted') {
            if (nt) nt.textContent = '✅ Notifications চালু আছে';
            if (nb) { nb.textContent = '✅'; nb.style.color = 'var(--success)'; }
        } else if (Notification.permission === 'denied') {
            if (nt) nt.textContent = '❌ Browser settings থেকে Allow করুন';
            if (nb) { nb.textContent = '❌'; nb.style.color = 'var(--danger)'; }
        } else {
            if (nt) nt.textContent = 'নতুন blood request এলে জানুন';
            if (nb) { nb.textContent = '›'; nb.style.color = ''; }
        }
    }

    // Install app status
    var installItem = document.getElementById('settingsInstallItem');
    var installText = document.getElementById('installStatusText');
    var installBadge = document.getElementById('installStatusBadge');
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;
    if (installItem) {
        if (isStandalone) {
            // Already installed
            if (installText) installText.textContent = '✅ ইতিমধ্যে Install করা আছে';
            if (installBadge) { installBadge.textContent = '✅'; installBadge.style.color = 'var(--success)'; }
            installItem.style.opacity = '0.55';
            installItem.style.pointerEvents = 'none';
        } else if (window._pwaPromptEvent) {
            if (installText) installText.textContent = 'Install করতে tap করুন';
            if (installBadge) { installBadge.textContent = '›'; installBadge.style.color = ''; }
        } else {
            if (installText) installText.textContent = 'Home Screen-এ Add করুন';
            if (installBadge) { installBadge.textContent = '›'; installBadge.style.color = ''; }
        }
    }

    // Location status
    var lt = document.getElementById('locStatusText');
    var lb = document.getElementById('locStatusBadge');
    if (navigator.permissions) {
        navigator.permissions.query({name:'geolocation'}).then(function(r) {
            if (r.state === 'granted') {
                if(lt) lt.textContent = '✅ Location চালু আছে';
                if(lb) { lb.textContent = '✅'; lb.style.color = 'var(--success)'; }
            } else if (r.state === 'denied') {
                if(lt) lt.textContent = '❌ Browser settings থেকে Allow করুন';
                if(lb) { lb.textContent = '❌'; lb.style.color = 'var(--danger)'; }
            } else {
                if(lt) lt.textContent = 'Nearby donors খুঁজতে দরকার';
                if(lb) { lb.textContent = '›'; lb.style.color = ''; }
            }
        });
    }
}
function toggleSoundSetting() {
    var isOff = localStorage.getItem('sound_off') === '1';
    if (isOff) localStorage.removeItem('sound_off'); else localStorage.setItem('sound_off','1');
    updateSettingsToggles();
}
function toggleAutoScrollSetting() {
    var isOn = localStorage.getItem('auto_scroll_call') === '1';
    if (isOn) localStorage.removeItem('auto_scroll_call'); else localStorage.setItem('auto_scroll_call','1');
    updateSettingsToggles();
}
function toggleVibrationSetting() {
    var isOff = localStorage.getItem('vibration_off') === '1';
    if (isOff) {
        localStorage.removeItem('vibration_off');
        if (navigator.vibrate) navigator.vibrate([30, 20, 60]); // preview when turning ON
    } else {
        localStorage.setItem('vibration_off', '1');
    }
    updateSettingsToggles();
}
// ── vibrateIfOn(pattern) — call everywhere instead of navigator.vibrate directly ──
function vibrateIfOn(pattern) {
    if (localStorage.getItem('vibration_off') === '1') return;
    try { if (navigator.vibrate) navigator.vibrate(pattern); } catch(e) {}
}
function settingsInstallApp() {
    closeSettingsPanel();
    setTimeout(function() {
        // Reset content to original
        var andEl = document.getElementById('pwaAndroidContent');
        if (andEl) {
            andEl.innerHTML =
                '<div class="pwa-top-row">'
              + '  <img src="icon.png" alt="Blood Arena" class="pwa-app-icon">'
              + '  <div class="pwa-install-titles"><strong>Blood Arena</strong><span>Home Screen-এ Add করুন</span></div>'
              + '  <div class="pwa-top-btns">'
              + '    <button class="pwa-install-btn" onclick="pwaDoInstall()">📲 Install</button>'
              + '    <button class="pwa-dismiss-btn" onclick="pwaDismiss()">✕</button>'
              + '  </div>'
              + '</div>'
              + '<div class="pwa-features">'
              + '  <span class="pwa-feat-pill">⚡ দ্রুত লোড</span>'
              + '  <span class="pwa-feat-pill">📵 Offline</span>'
              + '  <span class="pwa-feat-pill">🔔 Notification</span>'
              + '  <span class="pwa-feat-pill">📱 App Feel</span>'
              + '</div>';
        }
        var overlay = document.getElementById('pwaInstallOverlay');
        if (overlay) overlay.classList.add('show');
    }, 320);
}
// Sidebar "Install as App" — Settings item-এর মতোই overlay দেখায়
function sidebarInstallApp() {
    setTimeout(function() {
        var andEl = document.getElementById('pwaAndroidContent');
        if (andEl) {
            andEl.innerHTML =
                '<div class="pwa-top-row">'
              + '  <img src="icon.png" alt="Blood Arena" class="pwa-app-icon">'
              + '  <div class="pwa-install-titles"><strong>Blood Arena</strong><span>Home Screen-এ Add করুন</span></div>'
              + '  <div class="pwa-top-btns">'
              + '    <button class="pwa-install-btn" onclick="pwaDoInstall()">📲 Install</button>'
              + '    <button class="pwa-dismiss-btn" onclick="pwaDismiss()">✕</button>'
              + '  </div>'
              + '</div>'
              + '<div class="pwa-features">'
              + '  <span class="pwa-feat-pill">⚡ দ্রুত লোড</span>'
              + '  <span class="pwa-feat-pill">📵 Offline</span>'
              + '  <span class="pwa-feat-pill">🔔 Notification</span>'
              + '  <span class="pwa-feat-pill">📱 App Feel</span>'
              + '</div>';
        }
        var overlay = document.getElementById('pwaInstallOverlay');
        if (overlay) overlay.classList.add('show');
    }, 320);
}

// ইতিমধ্যে standalone হিসেবে চললে sidebar-এর Install item লুকাও
(function() {
    function _hideInstallIfStandalone() {
        var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                        || window.navigator.standalone === true;
        if (isStandalone) {
            ['sdInstallItem','headerInstallBtn'].forEach(function(id){
                var it = document.getElementById(id);
                if (it) it.style.display = 'none';
            });
        }
    }
    if (document.readyState !== 'loading') _hideInstallIfStandalone();
    else document.addEventListener('DOMContentLoaded', _hideInstallIfStandalone);
    window.addEventListener('appinstalled', function() {
        ['sdInstallItem','headerInstallBtn'].forEach(function(id){
            var it = document.getElementById(id);
            if (it) it.style.display = 'none';
        });
    });
})();

function requestBrowserNotif() {
    if (!('Notification' in window)) { showToast('এই browser notification সাপোর্ট করে না।', 'error'); return; }
    _saveDeviceId('notif_prompt');
    if (Notification.permission === 'granted') { _saveDeviceId('notif_allow'); showToast('✅ Notifications ইতিমধ্যে চালু আছে।', 'success'); return; }
    if (Notification.permission === 'denied') { _saveDeviceId('notif_deny'); showToast('❌ Browser URL bar-এ 🔒 icon → Site settings → Notifications → Allow করুন।', 'error'); return; }
    Notification.requestPermission().then(function(p) {
        if (p==='granted') { _saveDeviceId('notif_allow'); showToast('✅ Notifications চালু হয়েছে! নতুন request এলে জানানো হবে।', 'success'); }
        else { _saveDeviceId('notif_deny'); showToast('❌ Notification blocked। Browser settings থেকে Allow করুন।', 'error'); }
        updateSettingsToggles();
    });
}
function requestLocationSetting() {
    if (!navigator.geolocation) { showToast('এই browser geolocation সাপোর্ট করে না।', 'error'); return; }
    _saveDeviceId('loc_prompt');
    closeSettingsPanel();
    // Reset so prompt can show again
    localStorage.removeItem('gps_prompted');
    const msgEl = document.getElementById('gpsPromptMsg');
    if (msgEl) msgEl.textContent = 'Nearby Donors feature ও Call log-এর জন্য আপনার Location দরকার। Allow করলে কাছের রক্তদাতা খুঁজে পাবেন।';
    const el = document.getElementById('gpsPermPrompt');
    if (el) el.classList.add('active');
}

// Patch sound to respect sound setting
(function() {
    var origPlay = HTMLAudioElement.prototype.play;
    HTMLAudioElement.prototype.play = function() {
        if (localStorage.getItem('sound_off') === '1') return Promise.resolve();
        return origPlay.apply(this, arguments);
    };
})();

// ============================================================
// SMART DATE PICKER FOR REGISTRATION
// ============================================================
function setDonationNever() {
    document.getElementById('lastDonationHidden').value = 'no';
    document.getElementById('sdNeverBtn').classList.add('sd-active');
    document.getElementById('sdDateBtn').classList.remove('sd-active');
    document.getElementById('sdDatePickerWrap').style.display = 'none';
    document.getElementById('sdNeverMsg').style.display = 'block';
    // Hide donation count — if never donated, count stays 0
    var wrap = document.getElementById('regDonationCountWrap');
    if(wrap) wrap.style.display = 'none';
    document.getElementById('regDonCountHidden').value = '0';
    document.getElementById('regDonCountDisplay').textContent = '0';
    updateRegBadgePreview(0);
}
function setDonationDate() {
    document.getElementById('sdNeverBtn').classList.remove('sd-active');
    document.getElementById('sdDateBtn').classList.add('sd-active');
    document.getElementById('sdDatePickerWrap').style.display = 'block';
    document.getElementById('sdNeverMsg').style.display = 'none';
    // Show donation count field
    var wrap = document.getElementById('regDonationCountWrap');
    if(wrap) wrap.style.display = 'block';
    // Set today as max date
    var today = new Date().toISOString().split('T')[0];
    var inp = document.getElementById('sdDateInput');
    inp.max = today;
    inp.min = '1940-01-01';
    if (!inp.value) { inp.value = today; syncDonationDate(today); }
    // Default count to 1 if currently 0
    var cur = parseInt(document.getElementById('regDonCountHidden').value)||0;
    if(cur === 0) {
        document.getElementById('regDonCountHidden').value = '1';
        document.getElementById('regDonCountDisplay').textContent = '1';
        updateRegBadgePreview(1);
    }
}
function syncDonationDate(val) {
    if (!val) return;
    // Convert yyyy-mm-dd to dd/mm/yyyy for the backend
    var parts = val.split('-');
    if (parts.length === 3) {
        document.getElementById('lastDonationHidden').value = parts[2]+'/'+parts[1]+'/'+parts[0];
    }
}

// ── Registration donation count ──────────────────────────────
function regDonCountChange(delta) {
    var el  = document.getElementById('regDonCountHidden');
    var dis = document.getElementById('regDonCountDisplay');
    var cur = parseInt(el.value)||0;
    var next = Math.max(1, cur + delta); // min 1 when date is picked
    el.value = next;
    dis.textContent = next;
    updateRegBadgePreview(next);
}

function updateRegBadgePreview(n) {
    var icon, name, note;
    if(n >= 10)     { icon='👑'; name='Legend Donor'; note='সর্বোচ্চ স্তর! অসাধারণ!'; }
    else if(n >= 5) { icon='🦸'; name='Hero Donor';   note='আরও '+(10-n)+' donation করলে Legend হবেন'; }
    else if(n >= 2) { icon='⭐'; name='Active Donor'; note='আরও '+(5-n)+' donation করলে Hero হবেন'; }
    else            { icon='🌱'; name='New Donor';    note=n===0?'প্রথমবার হলে 0 রাখুন':'আরও '+(2-n)+' donation করলে Active হবেন'; }
    var ic = document.getElementById('regBadgeIcon');
    var nm = document.getElementById('regBadgeName');
    var nt = document.getElementById('regBadgeNote');
    if(ic) ic.textContent = icon;
    if(nm) nm.textContent = name;
    if(nt) nt.textContent = note;
}

// Init smart date on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    setDonationNever(); // default = never donated
    updateRegBadgePreview(0);
});

// scroll-padding = header height only
document.documentElement.style.scrollPaddingTop = '80px';

// ── Online visitor heartbeat ──────────────────────────────────
(function() {
    function pingOnline() {
        var fd = new FormData();
        fd.append('ping_online', '1');
        fd.append('visitor_token', getDeviceId());
        fetch(_AJAX_URL, {method:'POST', body:fd})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.online) return;
                var lc = document.getElementById('liveOnlineCount');
                if (lc) lc.textContent = ' · ' + d.online + ' online';
            })
            .catch(function(){});
    }
    window.addEventListener('load', function(){
        setTimeout(pingOnline, 1500);
        setInterval(pingOnline, 30000);
    });
})();



// ── Real-time Visitors card (Register page) ───────────────────
// Pings visitors_api.php every 30s: logs this visit + reads {live, total}.
// Runs site-wide so the all-time total counts every visitor; the card itself
// is only updated when present (Register page).
(function() {
    // visitors_api.php sits next to the app entry point (works in sub-dir installs too)
    var VIS_URL = (window.location.pathname.replace(/[^/]*$/, '') || '/') + 'visitors_api.php';

    var _lvShown = 0;          // currently displayed live number
    var _lvRAF   = null;       // running count-up animation frame
    var _lvSpark = [];         // recent live samples for the sparkline

    function animateCount(el, to) {
        if (!el) return;
        var from = _lvShown;
        to = Math.max(0, to | 0);
        if (from === to) { el.textContent = to; return; }
        if (_lvRAF) cancelAnimationFrame(_lvRAF);
        var start = null, dur = 800;
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min(1, (ts - start) / dur);
            var eased = 1 - Math.pow(1 - p, 3);          // easeOutCubic
            el.textContent = Math.round(from + (to - from) * eased);
            if (p < 1) { _lvRAF = requestAnimationFrame(step); }
            else { _lvShown = to; el.textContent = to; _lvRAF = null; }
        }
        _lvRAF = requestAnimationFrame(step);
    }

    function renderSpark(live) {
        var wrap = document.getElementById('lvSpark');
        if (!wrap) return;
        _lvSpark.push(Math.max(0, live | 0));
        if (_lvSpark.length > 14) _lvSpark.shift();
        var max = Math.max.apply(null, _lvSpark.concat([1]));
        var html = '';
        for (var i = 0; i < _lvSpark.length; i++) {
            var h = Math.round(4 + (_lvSpark[i] / max) * 22);   // 4–26px
            html += '<span class="lv-bar" style="height:' + h + 'px"></span>';
        }
        wrap.innerHTML = html;
    }

    function updateCard(live, total) {
        var countEl = document.getElementById('lvCount');
        var totalEl = document.getElementById('lvTotal');
        var dotEl   = document.getElementById('lvDot');
        if (countEl) animateCount(countEl, live);
        if (totalEl) totalEl.textContent = (total | 0).toLocaleString('en-US');
        if (dotEl) dotEl.classList.toggle('is-live', (live | 0) > 0);
        renderSpark(live);
    }

    function pingVisitors() {
        var fd = new FormData();
        fd.append('session_id', (typeof getDeviceId === 'function') ? getDeviceId() : 'anon');
        fd.append('page', (window.location.pathname || '/'));
        fetch(VIS_URL, { method: 'POST', body: fd, cache: 'no-store' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d) return;
                updateCard(d.live || 0, d.total || 0);
            })
            .catch(function(){ /* offline / endpoint missing → silent */ });
    }

    window.addEventListener('load', function() {
        setTimeout(pingVisitors, 1200);
        setInterval(pingVisitors, 30000);
    });
})();
