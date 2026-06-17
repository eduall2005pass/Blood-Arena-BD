
<?php
// ── Social "Connect us on" bar — rendered above every page footer ──
if (!function_exists('render_social_bar')) {
    function render_social_bar() {
        $items = [
            ['url'=>SOCIAL_FACEBOOK,'cls'=>'sc-fb','label'=>'Facebook','svg'=>'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.4l.4-2.8h-2.8V9.4c0-.8.2-1.4 1.4-1.4h1.5V5.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2H8.2V14h2.7v7h2.6z"/></svg>'],
            ['url'=>SOCIAL_TELEGRAM,'cls'=>'sc-tg','label'=>'Telegram','svg'=>'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3l-3.3 15.5c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.2L6.2 13.5l-4.9-1.5c-1-.3-1-1 .2-1.5L20.6 2.8c.9-.3 1.6.2 1.3 1.5z"/></svg>'],
            ['url'=>SOCIAL_YOUTUBE,'cls'=>'sc-yt','label'=>'YouTube','svg'=>'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.5s-.2-1.6-.9-2.3c-.8-.9-1.8-.9-2.2-.9C16.8 4 12 4 12 4s-4.8 0-7.9.3c-.4 0-1.4.1-2.2.9C1.2 5.9 1 7.5 1 7.5S.8 9.4.8 11.3v1.3c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.8.9 1.9.8 2.4.9 1.7.2 7.7.3 7.7.3s4.8 0 7.9-.3c.4 0 1.4-.1 2.2-.9.7-.7.9-2.3.9-2.3s.2-1.9.2-3.8v-1.3c0-1.9-.2-3.8-.2-3.8zM9.8 15.1V8.9l5.4 3.1-5.4 3.1z"/></svg>'],
            ['url'=>SOCIAL_WHATSAPP,'cls'=>'sc-wa','label'=>'WhatsApp','svg'=>'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>'],
        ];
        echo '<div class="social-connect"><p class="social-connect-label">আমাদের সাথে যুক্ত থাকুন</p><div class="social-connect-row">';
        foreach ($items as $s) {
            echo '<a href="'.htmlspecialchars($s['url']).'" target="_blank" rel="noopener noreferrer" class="social-btn '.$s['cls'].'" aria-label="'.$s['label'].'" title="'.$s['label'].'">'.$s['svg'].'</a>';
        }
        echo '</div></div>';
    }
}
?>
<!-- ══ PWA SPLASH SCREEN ══ -->
<div id="pwaSplash">
    <img src="<?= htmlspecialchars(LOGO_PATH) ?>" alt="<?= htmlspecialchars(BRAND_SHORT) ?>" class="splash-logo" onerror="this.style.display='none'">
    <div class="splash-name">ব্লাড <span>অ্যারেনা</span></div>
    <div class="splash-tagline"><?= htmlspecialchars(BRAND_TAGLINE) ?></div>
    <div class="splash-spinner" id="splashGear"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
</svg></div>
    <div class="splash-reload-count" id="splashReloadCount" style="display:none;">
        <span class="splash-ping"></span>
        <span id="splashReloadLabel"></span>
    </div>
    <div class="splash-progress-wrap">
        <div class="splash-progress-fill" id="splashProgressFill"></div>
    </div>
    <div class="splash-percent" id="splashPercent">0%</div>
</div>

<!-- ══ PAGE TRANSITION LOADER (white flash fix) ══ -->
<div id="pageLoader">
    <div class="page-loader-gear"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
</svg></div>
</div>

<audio id="successSound" src="/success.mp3" preload="auto"></audio>

<div class="location-blocked-overlay" id="locationBlockedOverlay">
    <div class="location-blocked-box">
        <div class="icon">📍</div>
        <h2>লোকেশন অনুমতি আবশ্যক</h2>
        <p>রেজিস্ট্রেশন করতে অথবা কল করতে আপনার বর্তমান লোকেশনের অনুমতি প্রয়োজন।<br><br>নিচের বাটনে ক্লিক করে লোকেশন অন করুন।</p>
        <button onclick="requestLocationAgain()">📍 লোকেশন অন করুন</button>
    </div>
</div>

<div class="popup-overlay" id="popup">
    <div class="popup">
        <div id="popupIcon" class="tick"></div>
        <h2 id="popupTitle" style="color:var(--text-main); margin-bottom: 12px; font-family: var(--font-heading); font-weight: 600;"></h2>
        <p id="popupMsg" style="color:var(--text-muted); line-height:1.6; font-size: 0.95em;"></p>
        <div id="successNotice" style="display:none; margin:20px 0; padding:15px; background: rgba(245, 158, 11, 0.05); border-radius: var(--radius-md); border:1px solid rgba(245, 158, 11, 0.2); font-size:0.9em; color:var(--accent-orange); text-align: left;">
            <strong style="display:block; margin-bottom:5px; color:var(--text-main);">✅ গুরুত্বপূর্ণ নোটিশ:</strong>
            যেকোনো সময় Google বা ফোন নম্বর দিয়ে সাইন ইন করে আপনি আপনার তথ্য আপডেট করতে পারবেন।
        </div>
        <button id="popupOkBtn" onclick="closePopup()" class="countdown-btn" disabled>OK (5)</button>
    </div>
</div>

<div class="popup-overlay" id="callConfirmPopup">
    <div class="popup" id="callConfirmBox">
        <div class="tick" style="color:var(--info); font-size: 45px; margin-bottom: 5px;">📞</div>
        <h3>Call Confirmation</h3>
        <p style="font-size:0.9em; color:var(--text-muted); margin-bottom:20px;">আপনার তথ্য সিস্টেমে লগ করা হচ্ছে আইনি নিরাপত্তার স্বার্থে।</p>
        <div class="caller-info-item">
            <small>Donor Name</small>
            <p id="confDonorName"></p>
        </div>
        <div class="caller-info-item">
            <small>Blood Group & Location</small>
            <p id="confDonorLoc"></p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:20px;">
            <button onclick="document.getElementById('callConfirmPopup').classList.remove('active')" style="background:transparent;border:1px solid var(--border-color);color:var(--text-main);margin:0;box-shadow:none;font-size:0.88em;padding:11px 4px;">✕ Cancel</button>
            <button id="finalCallBtn" style="background:var(--success);color:#000;margin:0;font-size:0.88em;padding:11px 4px;">📞 Call</button>
            <button id="finalWaBtn" class="wa-btn" style="margin:0;font-size:0.88em;padding:11px 4px;">💬 WhatsApp</button>
        </div>
    </div>
</div>

<div class="popup-overlay" id="donorDetailPopup" onclick="if(event.target===this)closeDonorDetail()">
    <div class="popup donor-detail-box">
        <button class="dd-close" onclick="closeDonorDetail()" aria-label="Close">✕</button>
        <div class="dd-head">
            <span class="dd-badge" id="ddBlood"></span>
            <div class="dd-head-info">
                <h3 id="ddName"></h3>
                <span id="ddStatus" class="dd-status"></span>
            </div>
        </div>
        <div class="dd-rows">
            <div class="dd-row"><span class="dd-label">📍 Location</span><span class="dd-val" id="ddLoc"></span></div>
            <div class="dd-row"><span class="dd-label">🏅 Donor Level</span><span class="dd-val" id="ddBadge"></span></div>
            <div class="dd-row"><span class="dd-label">🩸 Total Donations</span><span class="dd-val" id="ddTotal"></span></div>
            <div class="dd-row"><span class="dd-label">🗓 Last Donation</span><span class="dd-val" id="ddLast"></span></div>
            <div class="dd-row"><span class="dd-label">📅 Member Since</span><span class="dd-val" id="ddSince"></span></div>
        </div>
        <div class="dd-actions">
            <button onclick="closeDonorDetail()" class="dd-btn-cancel">✕ Close</button>
            <button id="ddCallBtn" class="dd-btn-call">📞 Call Donor</button>
        </div>
    </div>
</div>

<div class="popup-overlay" id="reportPopup">
    <div class="popup">
        <h2 style="color:var(--danger); margin-bottom:10px; font-family:var(--font-heading);">Report Harassment</h2>
        <p style="font-size:0.9em; color:var(--text-muted); margin-bottom:20px;">দাতার সাথে অশালীন আচরণ বা হয়রানি করলে আইনি ব্যবস্থা নেওয়া হবে।</p>
        <input type="text" id="repDonorPhone" placeholder="দাতার ফোন নম্বরটি দিন (+8801XXXXXXXXX)" required>
        <input type="text" id="harasserInfo" placeholder="হয়রানিকারীর ফোন নম্বর ও নাম (যদি জানা থাকে)" required>
        <textarea id="reportComment" placeholder="অভিযোগটি বিস্তারিত লিখুন..." style="width:100%; height:100px; resize:none;" required></textarea>
        <div style="display:flex; gap:12px; margin-top:15px;">
            <button onclick="document.getElementById('reportPopup').classList.remove('active')" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-top:0; box-shadow:none;">Close</button>
            <button onclick="submitReport()" style="background:var(--danger); color:#fff; margin-top:0;">Send Report</button>
        </div>
    </div>
</div>

<div class="popup-overlay" id="warningPopupOverlay">
    <div class="popup">
        <div class="tick warning-tick">⚠️</div>
        <h2 style="color:var(--text-main); margin-bottom: 15px; font-family:var(--font-heading);">সতর্কবার্তা</h2>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom: 25px; line-height: 1.7;">
            ভুল তথ্য দিয়ে জীবনকে ঝুঁকির মুখে ফেলবেন না। মানুষ ইমার্জেন্সি মুহূর্তেই রক্তের খোঁজ করে, তাই আপনার ভুল তথ্য অন্যের মনে আশা সঞ্চার করলেও আপনার ভুল তথ্যটি অসুস্থ রোগীর জন্য ক্ষতির কারণ হবে।
        </p>
        <button onclick="showTerms()" style="background:var(--accent-orange); color:#000;">I have read and agree</button>
    </div>
</div>

<div class="popup-overlay" id="termsPopupOverlay">
    <div class="popup">
        <h2 style="color:var(--primary-red); margin-bottom: 10px; font-family:var(--font-heading);">শর্তাবলী ও নীতিমালা</h2>
        <div class="scroll-content">
            <p>এই পোর্টালে রক্তদাতা হিসেবে নিবন্ধিত হওয়ার পূর্বে দয়া করে নিচের শর্তাবলীগুলো মনোযোগ দিয়ে পড়ুন। নিবন্ধন সম্পন্ন করার অর্থ হলো আপনি এই নীতিমালের সাথে একমত পোষণ করেছেন।</p>
            
            <h4>১. তথ্যের সঠিকতা ও দায়বদ্ধতা</h4>
            <p><strong>সঠিক তথ্য প্রদান:</strong> রক্তদাতা হিসেবে আপনাকে অবশ্যই আপনার নাম, ফোন নম্বর, রক্ত গ্রুপ এবং সর্বশেষ রক্তদানের তারিখ সঠিকভাবে প্রদান করতে হবে।</p>
            <p><strong>সতর্কবার্তা:</strong> ভুল তথ্য প্রদান করে কোনো মুমূর্ষু রোগীর জীবনকে ঝুঁকির মুখে ফেলবেন না। আপনার দেওয়া ভুল তথ্যের কারণে জরুরি মুহূর্তে রক্ত সংগ্রহে বিলম্ব হলে তার দায়ভার আপনার ওপর বর্তাবে।</p>
            
            <h4>২. গোপনীয়তা ও যোগাযোগ</h4>
            <p><strong>ফোন নম্বর দৃশ্যমানতা:</strong> আপনি রক্তদাতা হিসেবে নিবন্ধিত হওয়ার সাথে সাথে আপনার ফোন নম্বরটি আমাদের ডাটাবেসে সাধারণ মানুষের জন্য উন্মুক্ত (Public) হবে।</p>
            <p><strong>অযাচিত কল:</strong> জনসমক্ষে নম্বর থাকায় কোনো অপ্রাসঙ্গিক বা বিরক্তিকর কলের জন্য পোর্টাল কর্তৃপক্ষ দায়ী থাকবে না।</p>
            
            <h4>৩. রক্তদান প্রক্রিয়া</h4>
            <p><strong>স্বেচ্ছাসেবী মনোভাব:</strong> এখানে নিবন্ধন করা মানে আপনি একজন স্বেচ্ছাসেবী রক্তদাতা। রক্তদানের বিনিময়ে কোনো আর্থিক লেনদেন বা অনৈতিক দাবি করা সম্পূর্ণ নিষিদ্ধ।</p>
            
            <h4>৪. ডাটাবেস পরিবর্তন</h4>
            <p>কর্তৃপক্ষ চাইলে যেকোনো সময় ভুল বা ভুয়া তথ্য ডিলিট করার অধিকার রাখে।</p>
        </div>
        <button onclick="dismissAllPopups()">Agree & Continue</button>
    </div>
</div>

<div class="popup-overlay" id="aboutUsPopupOverlay">
    <div class="popup" style="max-width: 550px;">
        <h2 style="color:var(--primary-red); margin-bottom: 10px; font-family:var(--font-heading);">আমাদের কথা (About Us)</h2>
        <div class="scroll-content">
            <p style="font-weight:600; color:var(--text-main); font-size:1.05em; margin-bottom:20px;">"রক্তের জন্য আর নয় অস্থিরতা " — এই সুদৃঢ় অঙ্গীকার নিয়ে Blood Arena-এর পথচলা শুরু হয়েছে।</p>
            
            <h4>আমাদের লক্ষ্য ও উদ্দেশ্য:</h4>
            <p>আমাদের মূল লক্ষ্য হলো জরুরি মুহূর্তে রক্তদাতার অভাবজনিত কারণে সৃষ্ট মানবিক সংকটের স্থায়ী সমাধান করা। একজন মুমূর্ষু রোগীর স্বজনরা যেন কোনো প্রকার বিড়ম্বনা ছাড়াই দ্রুততম সময়ে রক্তদাতার সন্ধান পান, সেটি নিশ্চিত করাই এই পোর্টালের প্রধান উদ্দেশ্য। আমাদের এই ক্ষুদ্র প্রয়াস বর্তমানে শহীদ সোহরাওয়ার্দী মেডিকেল কলেজ কেন্দ্রিক হলেও, আমাদের সুদূরপ্রসারী পরিকল্পনা হলো এই সেবামূলক প্ল্যাটফর্মটিকে বাংলাদেশের প্রতিটি মেডিকেল ক্যাম্পাস এবং প্রতিটি জেলা পর্যায়ে বিস্তৃত করা।</p>
            
            <h4>উদ্যোগের প্রেক্ষাপট:</h4>
            <p>শহীদ সোহরাওয়ার্দী মেডিকেল কলেজের চত্বরে অবস্থানকালীন সময়ে প্রতিনিয়ত অসংখ্য রোগীর রক্তের প্রয়োজনীয়তা ও তা সংগ্রহের প্রতিকূলতা আমাদের দৃষ্টিগোচর হয়েছে। সাধারণ মানুষের এই ভোগান্তি নিরসনে এবং সামাজিক দায়বদ্ধতা থেকে আমরা Blood Arena Team একটি আধুনিক ও স্বচ্ছ প্ল্যাটফর্ম তৈরির প্রয়োজনীয়তা অনুভব করি।</p>
            
            <h4>পোর্টালের প্রধান বৈশিষ্ট্যসমূহ:</h4>
            <p>• <strong>লাইভ স্ট্যাটাস:</strong> সিস্টেম স্বয়ংক্রিয়ভাবে রক্তদাতার বর্তমান প্রাপ্যতা প্রদর্শন করে।<br>
            • <strong>সরাসরি যোগাযোগ:</strong> রক্তগ্রহীতা সরাসরি দাতার সাথে যোগাযোগ করতে পারেন।<br>
            • <strong>নিরাপত্তা:</strong> আমরা তথ্যের নির্ভুলতার ওপর সর্বোচ্চ গুরুত্ব প্রদান করি।</p>
            
            <h4>সিয়াম ও রাফি-র বার্তা:</h4>
            <p style="font-style:italic; color:var(--text-muted); border-left: 3px solid var(--primary-red); padding-left: 15px; margin-top:15px; background: var(--input-bg); padding-top:10px; padding-bottom:10px; border-radius: 0 8px 8px 0;">“দীর্ঘ শ্রম এবং প্রচেষ্টার পর আমরা এই পোর্টালটি নির্মাণ করতে সক্ষম হয়েছি। আমাদের এই প্রযুক্তিগত উদ্যোগ যদি একজন মুমূর্ষু মানুষের জীবন রক্ষায় সামান্যতম অবদান রাখতে পারে, তবেই আমাদের পরিশ্রম সার্থক হবে। সোহরাওয়ার্দী ক্যাম্পাস থেকে শুরু হওয়া এই সেবা ইনশাআল্লাহ বাংলাদেশের প্রতিটি মেডিকেল ক্যাম্পাসে ছড়িয়ে যাবে।”</p>
        </div>
        <button onclick="closeAboutUs()" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); box-shadow:none;">Close</button>
    </div>
</div>

<header>
  <button class="ba-hamburger" id="baHamburger" onclick="openSideDrawer()" type="button" aria-label="Menu" title="Menu">
    <svg viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="6"  x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <img src="<?= htmlspecialchars(LOGO_PATH) ?>" alt="<?= htmlspecialchars(BRAND_SHORT) ?>" class="header-logo-left" loading="eager" decoding="sync" fetchpriority="high" onclick="appSwitchPage('home')" style="cursor:pointer;">
  <h1 onclick="appSwitchPage('home')" style="cursor:pointer;"><?= htmlspecialchars(BRAND_NAME) ?></h1>
  <div class="header-actions">
    <div class="notif-bell-wrap" id="nBellWrap">
      <button class="notif-bell" id="nBell" onclick="toggleNPanel()" title="Live Requests">
        🔔<span class="notif-badge" id="nBadge"></span>
      </button>
    </div>
    <button class="header-account-btn" id="headerAccountBtn" onclick="openAuthModal()" title="Account" aria-label="Account">
      <span class="header-account-fallback" id="headerAccountInit">👤</span>
    </button>
  </div>
</header>

<!-- ========== HAMBURGER SIDE DRAWER (mobile) ========== -->
<div class="side-drawer-overlay" id="sideDrawerOverlay" onclick="closeSideDrawer(event)">
  <aside class="side-drawer" id="sideDrawer" role="dialog" aria-label="Navigation menu">
    <div class="side-drawer-head">
      <img src="<?= htmlspecialchars(LOGO_PATH) ?>" alt="<?= htmlspecialchars(BRAND_SHORT) ?>" class="side-drawer-logo">
      <div class="side-drawer-brand">
        <span class="side-drawer-brand-name"><?= htmlspecialchars(BRAND_NAME) ?></span>
        <span class="side-drawer-brand-sub">রক্তের জন্য আর নয় অস্থিরতা</span>
      </div>
      <button class="side-drawer-close" onclick="closeSideDrawer()" type="button" aria-label="Close" title="Close">✕</button>
    </div>

    <nav class="side-drawer-nav">

      <!-- Install as App — সবার ১ম, ইতিমধ্যে install থাকলে JS hide করে দেয় -->
      <button class="sd-item sd-install" id="sdInstallItem" onclick="closeSideDrawer(); sidebarInstallApp();">
        <span class="sd-ic">📲</span>
        <span>Install as App</span>
      </button>

      <p class="side-drawer-group">নেভিগেশন</p>

      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('home')">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 13 15 13 15 21"/></svg></span>
        <span>Home</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('requests')">
        <span class="sd-ic">🆘</span>
        <span>Active Requests</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('donors')">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/></svg></span>
        <span>Donors</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('register')">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg></span>
        <span>Register</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('nearby')">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
        <span>Nearby & Map</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); appSwitchPage('more')">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
        <span>Analytics</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); openAccountDashboard()">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg></span>
        <span>Account Dashboard</span>
      </button>
      <button class="sd-item" onclick="closeSideDrawer(); openSettingsPanel()">
        <span class="sd-ic"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
        <span>Settings</span>
      </button>

      <p class="side-drawer-group">পেজ</p>

      <button class="sd-item" onclick="openInfoPage('about')">
        <span class="sd-ic">ℹ️</span>
        <span>আমাদের কথা (About Us)</span>
      </button>
      <button class="sd-item" onclick="openInfoPage('privacy')">
        <span class="sd-ic">🔒</span>
        <span>গোপনীয়তা ও নীতিমালা</span>
      </button>
      <button class="sd-item" onclick="openInfoPage('faq')">
        <span class="sd-ic">❓</span>
        <span>প্রশ্ন ও উত্তর (FAQ)</span>
      </button>
      <button class="sd-item" onclick="openInfoPage('sponsor')">
        <span class="sd-ic">🤝</span>
        <span>আমাদের স্পন্সর</span>
      </button>
      <button class="sd-item" onclick="openInfoPage('donate')">
        <span class="sd-ic">❤️</span>
        <span>Donate Us (সহযোগিতা করুন)</span>
      </button>

      <!-- Logout — sidebar-এর সবার শেষে, শুধু সাইন-ইন থাকলে দেখায় -->
      <div id="sdLogoutWrap" style="display:none;">
        <p class="side-drawer-group">অ্যাকাউন্ট</p>
        <button class="sd-item sd-logout" onclick="closeSideDrawer(); authLogout();">
          <span class="sd-ic">🚪</span>
          <span>লগ-আউট</span>
        </button>
      </div>
    </nav>

    <div class="side-drawer-foot">&copy; <?php echo date("Y"); ?> <?= htmlspecialchars(BRAND_NAME) ?></div>
  </aside>
</div>

<!-- ========== INFO PAGE OVERLAY (About / Privacy / FAQ / Sponsor) ========== -->
<div class="info-page-overlay" id="infoPageOverlay">
  <div class="info-page">
    <div class="info-page-bar">
      <button class="info-page-back" onclick="closeInfoPage()" type="button" aria-label="Back" title="Back">
        <svg viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <span class="info-page-title" id="infoPageTitle">Info</span>
    </div>
    <div class="info-page-body">

      <!-- About Us — content moved here from #aboutUsPopupOverlay on first open (see openInfoPage) -->
      <section class="info-panel" data-info="about" style="display:none;">
        <div class="scroll-content" id="infoAboutContent"></div>
      </section>

      <!-- Privacy & Policy -->
      <section class="info-panel" data-info="privacy" style="display:none;">
        <div class="scroll-content" id="infoPrivacyContent">
          <h2 style="color:var(--primary-red); margin-bottom: 10px; font-family:var(--font-heading);">গোপনীয়তা ও নীতিমালা</h2>
          <p>এই পোর্টালে রক্তদাতা হিসেবে নিবন্ধিত হওয়ার পূর্বে দয়া করে নিচের শর্তাবলীগুলো মনোযোগ দিয়ে পড়ুন। নিবন্ধন সম্পন্ন করার অর্থ হলো আপনি এই নীতিমালের সাথে একমত পোষণ করেছেন।</p>

          <h4>১. তথ্যের সঠিকতা ও দায়বদ্ধতা</h4>
          <p><strong>সঠিক তথ্য প্রদান:</strong> রক্তদাতা হিসেবে আপনাকে অবশ্যই আপনার নাম, ফোন নম্বর, রক্ত গ্রুপ এবং সর্বশেষ রক্তদানের তারিখ সঠিকভাবে প্রদান করতে হবে।</p>
          <p><strong>সতর্কবার্তা:</strong> ভুল তথ্য প্রদান করে কোনো মুমূর্ষু রোগীর জীবনকে ঝুঁকির মুখে ফেলবেন না। আপনার দেওয়া ভুল তথ্যের কারণে জরুরি মুহূর্তে রক্ত সংগ্রহে বিলম্ব হলে তার দায়ভার আপনার ওপর বর্তাবে।</p>

          <h4>২. গোপনীয়তা ও যোগাযোগ</h4>
          <p><strong>ফোন নম্বর দৃশ্যমানতা:</strong> আপনি রক্তদাতা হিসেবে নিবন্ধিত হওয়ার সাথে সাথে আপনার ফোন নম্বরটি আমাদের ডাটাবেসে সাধারণ মানুষের জন্য উন্মুক্ত (Public) হবে।</p>
          <p><strong>অযাচিত কল:</strong> জনসমক্ষে নম্বর থাকায় কোনো অপ্রাসঙ্গিক বা বিরক্তিকর কলের জন্য পোর্টাল কর্তৃপক্ষ দায়ী থাকবে না।</p>

          <h4>৩. রক্তদান প্রক্রিয়া</h4>
          <p><strong>স্বেচ্ছাসেবী মনোভাব:</strong> এখানে নিবন্ধন করা মানে আপনি একজন স্বেচ্ছাসেবী রক্তদাতা। রক্তদানের বিনিময়ে কোনো আর্থিক লেনদেন বা অনৈতিক দাবি করা সম্পূর্ণ নিষিদ্ধ।</p>

          <h4>৪. ডাটাবেস পরিবর্তন</h4>
          <p>কর্তৃপক্ষ চাইলে যেকোনো সময় ভুল বা ভুয়া তথ্য ডিলিট করার অধিকার রাখে।</p>
        </div>
      </section>

      <!-- FAQ -->
      <section class="info-panel" data-info="faq" style="display:none;">
        <div class="scroll-content" id="infoFaqContent"><!-- populated below --></div>
      </section>

      <!-- Our Sponsor -->
      <section class="info-panel" data-info="sponsor" style="display:none;">
        <div class="scroll-content">
          <h2 style="color:var(--primary-red); margin-bottom: 10px; font-family:var(--font-heading);">আমাদের স্পন্সর</h2>
          <p>আমাদের এই মহৎ উদ্যোগে স্পন্সর হিসেবে যুক্ত হতে আগ্রহী হলে, দয়া করে এই নাম্বারে যোগাযোগ করুন: <span class="highlight-number"><a href="tel:01518981827">০১৫১৮৯৮১৮২৭</a></span></p>
        </div>
      </section>

      <!-- Donate Us -->
      <section class="info-panel" data-info="donate" style="display:none;">
        <div class="scroll-content">
          <div class="donate-hero">
            <div class="donate-hero-ic">❤️</div>
            <h2 class="donate-hero-title">আমাদের সহযোগিতা করুন</h2>
            <p class="donate-hero-sub">Support our non-profit mission</p>
          </div>

          <p>আমরা একদল <strong>মেডিকেল শিক্ষার্থী</strong>, যারা সম্পূর্ণ অলাভজনকভাবে (non-profit) এই রক্তদান প্ল্যাটফর্মটি পরিচালনা করছি। আমাদের একমাত্র লক্ষ্য — জরুরি মুহূর্তে একজন রোগীর সাথে একজন রক্তদাতাকে দ্রুত সংযুক্ত করা এবং জীবন বাঁচানো।</p>

          <p>এই মহৎ কাজটিকে আরও উন্নত ও টেকসই করতে সার্ভার, ডোমেইন ও রক্ষণাবেক্ষণ বাবদ নিয়মিত খরচ বহন করতে হয়। আপনার <strong>ক্ষুদ্র সহযোগিতাও</strong> এই উদ্যোগকে বহুদূর এগিয়ে নিয়ে যেতে পারে। আসুন, একসাথে এই মানবিক কাজে অংশীদার হই।</p>

          <div class="donate-method">
            <span class="donate-method-label">bKash (পার্সোনাল)</span>
            <div class="donate-number-row">
              <span class="donate-number" id="donateBkashNum">01518981827</span>
              <button type="button" class="donate-copy-btn" onclick="copyDonateNumber()" aria-label="Copy number">📋 কপি</button>
            </div>
            <p class="donate-method-hint">Send Money অথবা Payment — উভয়ভাবেই অনুদান পাঠাতে পারেন।</p>
          </div>

          <p class="donate-thanks">আপনার পাশে থাকার জন্য আন্তরিক কৃতজ্ঞতা। 🩸<br><em>— Blood Arena Team (মেডিকেল শিক্ষার্থীবৃন্দ)</em></p>
        </div>
      </section>

    </div>
  </div>
</div>
<!-- Notif panel rendered at body level to escape header stacking context -->
<div class="notif-panel-anchor">
  <div class="notif-panel" id="nPanel">
    <!-- Tab header -->
    <div class="notif-tabs-hdr">
      <button class="notif-tab-btn active" id="nTabBlood" onclick="switchNTab('blood')">
        🆘 Blood Request<span class="notif-tab-badge" id="nTabBloodBadge" style="display:none;"></span>
      </button>
      <button class="notif-tab-btn" id="nTabSvc" onclick="switchNTab('service')">
        ⚙️ Services<span class="notif-tab-badge" id="nTabSvcBadge" style="display:none;"></span>
      </button>
    </div>
    <!-- Blood Request tab -->
    <div id="nTabBloodContent">
      <div class="notif-panel-subhdr"><span>🆘 Active Requests</span><span id="nCount" style="color:var(--text-muted);font-size:0.82em;"></span></div>
      <div id="nList"><div class="notif-empty">কোনো active request নেই</div></div>
    </div>
    <!-- Services tab -->
    <div id="nTabSvcContent" style="display:none;">
      <div class="notif-panel-subhdr">
        <span>⚙️ Service Notifications</span>
        <span id="nSvcCount" style="color:var(--text-muted);font-size:0.82em;"></span>
      </div>
      <div class="svc-notif-toolbar">
        <span class="svc-notif-hint">← swipe করে remove করুন</span>
        <button class="svc-delete-all-btn" onclick="deleteAllSvcNotifs()">🗑 সব মুছুন</button>
      </div>
      <div id="nSvcList"><div class="notif-empty">কোনো service notification নেই</div></div>
    </div>
  </div>
</div>

<!-- ========== HEADER ACCOUNT POPUP (signed-in quick menu) ========== -->
<div class="acct-pop-anchor">
  <div class="acct-pop" id="acctPop" role="menu" aria-label="Account menu">
    <button class="acct-pop-item" role="menuitem" onclick="closeAcctPop(); openAccountDashboard()">
      <span class="acct-pop-ic">📊</span>
      <span>Go to Dashboard</span>
    </button>
    <button class="acct-pop-item" role="menuitem" id="acctPopVerify" onclick="closeAcctPop(); openVerifyModal()" style="display:none;">
      <span class="acct-pop-ic">🔗</span>
      <span>Verify Now</span>
    </button>
    <button class="acct-pop-item acct-pop-danger" role="menuitem" onclick="closeAcctPop(); authLogout()">
      <span class="acct-pop-ic">🚪</span>
      <span>Logout</span>
    </button>
  </div>
</div>
<div id="toastWrap"></div>

<!-- ===== APP PAGE: HOME ===== -->
<div class="app-page page-active" id="page-home">
<!-- ══ 3D BLOOD-DROP HERO (WebGL via Three.js; CSS fallback when unavailable) ══ -->
<div id="heroFx" class="hero-fx" aria-hidden="true">
    <img src="bd-map.svg" alt="" class="hero-map-bg" loading="lazy" decoding="async">
    <canvas id="heroCanvas"></canvas>
    <div class="hero-fx-fallback">🩸</div>
</div>

<!-- HOME HERO: Total Summary -->
<div class="home-hero-bar">
    <div class="home-hero-stat">
        <span class="home-hero-num" id="heroTotalDonors"><?php echo $total_donors_count; ?></span>
        <span class="home-hero-lbl">মোট Donors</span>
    </div>
    <div class="home-hero-divider"></div>
    <div class="home-hero-stat">
        <span class="home-hero-num" id="heroAvailDonors" style="color:var(--success);"><?php echo array_sum($avail_counts); ?></span>
        <span class="home-hero-lbl">Available Now</span>
    </div>
    <div class="home-hero-divider"></div>
    <div class="home-hero-stat" onclick="appSwitchPage('register')" style="cursor:pointer;">
        <span class="home-hero-num" style="font-size:1.4rem;">📝</span>
        <span class="home-hero-lbl">Register</span>
    </div>
</div>
<div class="emergency-banner" id="requestSection">
    <div class="emergency-banner-left">
        <div class="emergency-banner-icon">🆘</div>
        <div class="emergency-banner-text">
            <h4>জরুরি রক্তের প্রয়োজন?</h4>
            <p>Emergency request করুন — সব donor দেখতে পাবে</p>
        </div>
    </div>
    <div class="emergency-banner-btns">
        <button class="btn-view-requests" onclick="appSwitchPage('requests')">📋 Active Requests দেখুন</button>
        <button class="btn-emergency" onclick="openBloodRequestModal()">🆘 Emergency Request</button>
    </div>
</div>

<!-- ACTIVE BLOOD REQUESTS SECTION -->
<!-- ==================== COMPACT LIVE STATS CARDS ==================== -->
<h3 class="quick-filter-title" style="text-align:center;font-family:var(--font-heading);font-weight:800;font-size:1.6rem;color:var(--text-main);margin:18px 0 12px;">Quick Filter</h3>
<div class="stats-container" id="statsSection">
    <?php 
    $__id_map = ['A+'=>'Aplus','A-'=>'Aminus','B+'=>'Bplus','B-'=>'Bminus','AB+'=>'ABplus','AB-'=>'ABminus','O+'=>'Oplus','O-'=>'Ominus'];
    foreach(["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"] as $g){
        $bg_id = $__id_map[$g];
        $color_class = "blood-" . $bg_id;
        echo "<div class='stat-card $color_class' role='button' tabindex='0' onclick=\"appSwitchPage('donors'); quickFilter('$g');\">
                <h4>$g</h4>
                <div class='count' id='count-$bg_id'>🩸 ".$avail_counts[$g]." Available</div>
                <span class='stat-tap-hint'>👆 তালিকা দেখুন</span>
              </div>";
    } 
    ?>
</div>


<!-- ===== DEVELOPER CARD (Home only — single horizontal card, divided) ===== -->
<div class="dev-section">
    <p class="dev-section-label">Developed By</p>

    <!-- Donate Us — interactive CTA opens the Donate Us page in the side drawer -->
    <button type="button" class="dev-donate-btn" onclick="openInfoPage('donate')" aria-label="Donate Us">
        <span class="dev-donate-ic">❤️</span>
        <span class="dev-donate-txt">Donate Us</span>
        <span class="dev-donate-arrow" aria-hidden="true">→</span>
    </button>

    <div class="dev-card dev-card-horizontal dev-card-min">

        <!-- Siam half -->
        <div class="dev-half">
            <img src="siam.jpg" alt="Siam" class="dev-avatar">
            <div class="dev-half-info">
                <p class="dev-name">Siam<span class="dev-batch">(Sh-20)</span></p>
                <span class="dev-role">Dev &amp; Planner</span>
            </div>
        </div>

        <div class="dev-divider"></div>

        <!-- Rafi half -->
        <div class="dev-half">
            <img src="rafi.jpg" alt="Rafi" class="dev-avatar">
            <div class="dev-half-info">
                <p class="dev-name">Rafi<span class="dev-batch">(Sh-20)</span></p>
                <span class="dev-role">Planner</span>
            </div>
        </div>
    </div>
</div>
<?php render_social_bar(); ?>
<div class="page-footer-bar"><span>🩸 © 2026 <?= htmlspecialchars(BRAND_NAME) ?> — All Rights Reserved.</span></div>
</div><!-- end page-home -->

<!-- ===== APP PAGE: ACTIVE REQUESTS ===== -->
<div class="app-page" id="page-requests">
<div class="app-page-header"><span class="ph-icon">🆘</span> Active Requests</div>
<div class="container" id="reqSection">
    <div style="margin-bottom:14px;">
        <h3 style="color:var(--danger);font-family:var(--font-heading);font-size:1.2rem;margin:0;">🆘 Active Blood Requests</h3>
        <p style="color:var(--text-muted);font-size:0.8em;margin:2px 0 0;">রক্তের জন্য অপেক্ষা করছেন এমন রোগীরা</p>
    </div>

    <!-- Row 1: Main tabs -->
    <div class="req-filter-row">
        <button id="reqTab_all" class="req-tab-btn req-tab-active" onclick="setReqTab('all')">🩸 সব</button>
        <button id="reqTab_mine" class="req-tab-btn" onclick="setReqTab('mine')">👤 আমার Request</button>
    </div>

    <!-- Row 2: Blood group chips -->
    <div class="req-filter-row" style="margin-top:8px;gap:6px;">
        <span style="font-size:0.72em;color:var(--text-muted);font-weight:600;white-space:nowrap;align-self:center;">গ্রুপ:</span>
        <?php foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g): ?>
        <button class="req-bg-chip" data-group="<?= $g ?>" onclick="setReqGroupFilter('<?= $g ?>')"><?= $g ?></button>
        <?php endforeach; ?>
        <button id="reqBgFilterClear" class="req-bg-clear" onclick="clearReqGroupFilter()" style="display:none;">✕ Clear</button>
    </div>

    <div class="req-grid" id="reqGrid">
        <div style="text-align:center;padding:30px;color:var(--text-muted);grid-column:1/-1;">⏳ লোড হচ্ছে...</div>
    </div>
</div>
</div><!-- end page-requests -->

<!-- ===== APP PAGE: REGISTER ===== -->
<div class="app-page" id="page-register">
<div class="app-page-header"><span class="ph-icon">📝</span> রেজিস্ট্রেশন</div>
<div class="container" id="regSection">
<div class="tab-header">
    <button class="tab-btn active" onclick="switchTab(0)">➕ Donor Registration</button>
    <button class="tab-btn" onclick="switchTab(1)">✏️ Update My Info</button>
</div>

<!-- TAB 0: Register -->
<div id="tab0" class="tab-content active">

    <!-- ANIMATED DONOR ILLUSTRATION (CSS/SVG only — no WebGL) -->
    <div class="donor-hero" aria-hidden="true">
        <div class="donor-hero-rings">
            <span class="dh-ring"></span><span class="dh-ring"></span><span class="dh-ring"></span>
        </div>
        <svg class="donor-hero-svg" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Heart -->
            <path class="dh-heart" d="M60 104C60 104 16 76 16 46C16 30 28 20 41 20C50 20 57 25 60 32C63 25 70 20 79 20C92 20 104 30 104 46C104 76 60 104 60 104Z"/>
            <!-- ECG / heartbeat line across the heart -->
            <path class="dh-ecg" d="M24 60H44L50 44L58 76L66 52L72 60H96" stroke-linecap="round" stroke-linejoin="round"/>
            <!-- Blood drop -->
            <path class="dh-drop" d="M60 36C60 36 50 49 50 57C50 63 54 67 60 67C66 67 70 63 70 57C70 49 60 36 60 36Z"/>
            <!-- Plus / cross badge -->
            <g class="dh-plus">
                <circle cx="92" cy="30" r="14"/>
                <path d="M92 23V37M85 30H99" stroke-linecap="round"/>
            </g>
        </svg>
        <p class="donor-hero-text">🩸 একজন রক্তদাতা হোন — জীবন বাঁচান</p>
    </div>

    <!-- SIGN IN + VERIFY GATE — registration-এর আগে দুটোই বাধ্যতামূলক -->
    <div id="regAuthPrompt" style="margin-top:20px;padding:18px 16px;background:rgba(66,133,244,0.06);border:1px solid rgba(66,133,244,0.22);border-radius:0;text-align:center;">
        <!-- State A: signed out → Google sign-in -->
        <div id="regSigninBlock">
            <p style="color:var(--text-main);font-weight:600;font-size:1.0em;margin:0 0 4px;">🔐 শুরু করার আগে সাইন ইন করুন</p>
            <p style="color:var(--text-muted);font-size:0.8em;margin:0 0 14px;">রেজিস্ট্রেশন করতে প্রথমে আপনার Google অ্যাকাউন্ট দিয়ে সাইন ইন করুন</p>
            <button id="regGoogleBtn" onclick="authGoogleSignIn()" type="button"
                style="width:100%;max-width:340px;display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#1f2937;border:1.5px solid var(--border-color);border-radius:0;padding:13px;font-weight:600;font-size:0.95em;box-shadow:none;margin:0 auto;">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35 24 35c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 5.1 29.5 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 5.1 29.5 3 24 3 16.1 3 9.2 7.6 6.3 14.7z"/><path fill="#4CAF50" d="M24 45c5.2 0 10-2 13.6-5.2l-6.3-5.3C29.2 36 26.7 37 24 37c-5.3 0-9.7-2.6-11.3-7l-6.5 5C9.1 40.3 16 45 24 45z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4 5.5l6.3 5.3C39.9 36.6 45 31 45 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
                Google দিয়ে সাইন ইন করুন
            </button>
        </div>
        <!-- State B: signed in but phone NOT verified → verify gate -->
        <div id="regVerifyBlock" style="display:none;">
            <p style="color:var(--text-main);font-weight:600;font-size:1.0em;margin:0 0 4px;">📱 ফোন নম্বর verify করুন</p>
            <p style="color:var(--text-muted);font-size:0.8em;margin:0 0 14px;">রেজিস্ট্রেশন করতে হলে প্রথমে আপনার ফোন নম্বর verify করতে হবে। যে নম্বরটি verify করবেন, সেটিই রেজিস্ট্রেশন ফর্মে বসবে।</p>
            <button onclick="openVerifyModal()" type="button"
                style="width:100%;max-width:340px;display:inline-flex;align-items:center;justify-content:center;gap:10px;background:var(--success);color:#000;border:none;border-radius:0;padding:13px;font-weight:700;font-size:0.95em;box-shadow:none;margin:0 auto;">
                ✅ এখনই Verify করুন
            </button>
        </div>
    </div>

    <!-- REGISTRATION TOGGLE BUTTON -->
    <div id="regToggleContainer" style="text-align: center; margin-top: 20px;">
        <p style="color: var(--text-muted); margin-bottom: 12px; font-size: 1.05em; font-weight:600;">নতুন রক্তদাতা হিসেবে যুক্ত হতে নিচের বাটনে ক্লিক করুন</p>
        <button id="toggleFormBtn" onclick="toggleRegForm()" style="background: var(--success); color: #000; max-width: 320px; box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); font-size: 1.15em; display: inline-flex; justify-content: center; align-items: center; gap: 8px; margin:0 auto; padding: 18px; border-radius: 40px;">
            📝 Click Here to Register
        </button>
    </div>

    <!-- TOGGLEABLE FORM -->
    <form id="regForm" style="display:none; opacity: 0; transform: translateY(-15px); transition: opacity 0.4s ease, transform 0.4s ease; margin-top: 25px;">  
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="reg_geo_location" id="reg_geo_location" value="Not captured">
        
        <h2>Register as Blood Donor</h2>  
        
        <div class="input-group">
            <div class="input-row">
                <input type="text" name="name" placeholder="Full Name" onfocus="handleNameFocus()" required oninput="validateName(this)">
                <input type="tel" name="phone" value="+880" placeholder="Enter your number" required pattern="^\+8801\d{9}$" title="Must start with +8801 followed by 9 digits">  
            </div>
            
            <!-- NEW: Location with Map Picker Only (No dropdown) -->
            <div>
                <label style="font-size: 0.85em; font-weight: 500; color: var(--text-muted); margin-bottom: 4px; display: block; padding-left: 4px;">📍 Donor Location</label>  
                
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="regExactLocation" placeholder="✍️ Your Area, House, Road... অথবা 🗺️ Map থেকে Pin করুন" style="margin:0;flex:1;" required>
                    <button type="button" onclick="openMapPicker()" title="Google Map থেকে Location বেছে নিন" style="margin:0;padding:10px 13px;min-height:unset;width:auto;background:rgba(66,133,244,0.12);border:1.5px solid rgba(66,133,244,0.35);color:#4285f4;border-radius:10px;font-size:1.25rem;flex-shrink:0;box-shadow:none;cursor:pointer;" aria-label="Map Picker">🗺️</button>
                </div>
                <p style="font-size:0.71em;color:var(--text-muted);margin:4px 0 0;padding-left:2px;">💡 🗺️ বাটনে ক্লিক করে Map থেকে সরাসরি লোকেশন পিন করুন</p>
            </div>
            
            <div class="input-row">
                <div>
                    <label style="font-size: 0.85em; font-weight: 500; color: var(--text-muted); margin-bottom: 4px; display: block; padding-left: 4px;">Blood Group</label>  
                    <select name="group" required style="margin-top:0;">  
                        <option value="" style="color:var(--text-muted);" disabled selected>Select Group</option>  
                        <option>A+</option><option>A-</option>  
                        <option>B+</option><option>B-</option>  
                        <option>AB+</option><option>AB-</option>  
                        <option>O+</option><option>O-</option>  
                    </select>  
                </div>
                <div>  
                    <label style="font-size: 0.85em; font-weight: 500; color: var(--text-muted); margin-bottom: 4px; display: block; padding-left: 4px;">Last Blood Donation Date</label>  
                    <!-- Smart date picker: toggle between "Never" and date -->
                    <div class="smart-date-wrap" style="margin-top:0;">
                        <div class="smart-date-toggle">
                            <button type="button" id="sdNeverBtn" class="sd-toggle-btn sd-active" onclick="setDonationNever()">🚫 Never Donated</button>
                            <button type="button" id="sdDateBtn" class="sd-toggle-btn" onclick="setDonationDate()">📅 Pick a Date</button>
                        </div>
                        <input type="hidden" name="last_donation" id="lastDonationHidden" value="no" required>
                        <div id="sdDatePickerWrap" style="display:none;margin-top:8px;">
                            <input type="date" id="sdDateInput" style="margin:0;" max="" onchange="syncDonationDate(this.value)">
                            <p style="font-size:0.72em;color:var(--text-muted);margin:3px 0 0;padding-left:2px;">📅 তারিখ বেছে নিন (min: 1940-01-01 · max: আজ)</p>
                        </div>
                        <div id="sdNeverMsg" style="margin-top:8px;padding:9px 12px;background:rgba(239,68,68,0.07);border-radius:8px;font-size:0.82em;color:var(--text-muted);">আপনি আগে কখনো রক্তদান করেননি — স্বয়ংক্রিয়ভাবে "no" সেট হবে।</div>
                    </div>
                </div>  
            </div>

            <!-- How many times donated — optional -->
            <div id="regDonationCountWrap" style="display:none; margin-top:4px; padding:14px 16px; background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.18); border-radius:12px;">
                <label style="font-size:0.85em;font-weight:600;color:var(--text-muted);display:block;margin-bottom:8px;">🩸 এখন পর্যন্ত মোট কতবার রক্ত দিয়েছেন? <span style="font-weight:400;font-size:0.9em;">(Optional)</span></label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="button" onclick="regDonCountChange(-1)" style="width:38px;height:38px;border-radius:50%;background:rgba(239,68,68,0.12);border:1.5px solid rgba(239,68,68,0.3);color:var(--primary-red);font-size:1.3rem;font-weight:700;cursor:pointer;flex-shrink:0;padding:0;min-height:unset;">−</button>
                    <div style="flex:1;text-align:center;">
                        <span id="regDonCountDisplay" style="font-size:1.6rem;font-weight:800;color:var(--text-main);">0</span>
                        <span style="font-size:0.8em;color:var(--text-muted);margin-left:4px;">বার</span>
                    </div>
                    <button type="button" onclick="regDonCountChange(+1)" style="width:38px;height:38px;border-radius:50%;background:rgba(16,185,129,0.12);border:1.5px solid rgba(16,185,129,0.3);color:#10b981;font-size:1.3rem;font-weight:700;cursor:pointer;flex-shrink:0;padding:0;min-height:unset;">+</button>
                </div>
                <input type="hidden" id="regDonCountHidden" name="total_donations_reg" value="0">
                <div id="regBadgePreview" style="margin-top:10px;display:flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(16,185,129,0.08);border-radius:8px;">
                    <span id="regBadgeIcon" style="font-size:1.3rem;">🌱</span>
                    <div>
                        <div style="font-size:0.82em;font-weight:700;color:var(--text-main);" id="regBadgeName">New Donor</div>
                        <div style="font-size:0.72em;color:var(--text-muted);" id="regBadgeNote">১ম donation করলে progress শুরু হবে</div>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" onclick="submitRegistration()">Submit Registration</button>  
    </form>  
</div>

<!-- TAB 1: Update Info -->
<div id="tab1" class="tab-content">
<form id="updateForm">
    <h2>Update Your Information</h2>
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
    <?php $__signedIn = !empty($_SESSION['auth_uid']); ?>
    <div id="updateSignedInPanel" style="<?= $__signedIn ? 'display:flex;' : 'display:none;' ?>flex-direction:column; align-items:center; max-width:500px; margin:0 auto;">
        <p style="color:var(--text-muted);font-size:0.88em;text-align:center;margin:0 0 12px;">✅ আপনি সাইন ইন করা আছেন। নিচের বাটনে চাপলে আপনার তথ্য লোড হবে।</p>
        <button type="button" onclick="loadMyDonorInfo()" style="background:var(--info);">🔄 আমার তথ্য লোড করুন</button>
    </div>
    <div id="updateAuthPrompt" style="<?= $__signedIn ? 'display:none;' : '' ?>max-width:500px;margin:0 auto;padding:18px 16px;background:rgba(66,133,244,0.06);border:1px solid rgba(66,133,244,0.22);border-radius:14px;text-align:center;">
        <p style="color:var(--text-main);font-weight:600;font-size:1.0em;margin:0 0 4px;">🔐 তথ্য আপডেট করতে সাইন ইন করুন</p>
        <p style="color:var(--text-muted);font-size:0.8em;margin:0 0 14px;">কোনো Secret Code মনে রাখার দরকার নেই — Google অথবা ফোন নম্বর দিয়ে সাইন ইন করুন</p>
        <button type="button" onclick="openAuthModal()" style="background:var(--info);width:100%;max-width:340px;margin:0 auto;">🔐 সাইন ইন করুন</button>
    </div>

    <!-- Donor Badge Display -->
    <div id="donorBadgeCard" style="display:none; margin:20px auto; max-width:500px;">
        <div class="badge-card">
            <div class="badge-card-left">
                <div id="badgeIconBig" class="badge-icon-big">🌱</div>
                <div>
                    <div class="badge-level-name" id="badgeLevelName">New Donor</div>
                    <div class="badge-donations" id="badgeDonations">0 donations</div>
                </div>
            </div>
            <div class="badge-progress-wrap">
                <div class="badge-progress-bar"><div class="badge-progress-fill" id="badgeProgressFill"></div></div>
                <div class="badge-next-label" id="badgeNextLabel"></div>
            </div>
        </div>
        <!-- Quick action: Just Donated button -->
        <button type="button" id="justDonatedBtn" onclick="triggerJustDonated()" class="just-donated-btn">
            🩸 আমি এইমাত্র রক্ত দিয়েছি — Update করুন
        </button>
        <p id="justDonatedLockMsg" style="display:none;text-align:center;font-size:0.82em;color:#f59e0b;margin-top:6px;padding:7px 12px;background:rgba(245,158,11,0.1);border-radius:8px;"></p>
    </div>

    <div id="updateFields" style="display:none; margin-top:20px; border-top:1px solid var(--border-color); padding-top:25px;">
        <div class="input-group">
            <input type="text" id="u_name" placeholder="Full Name" required oninput="validateName(this)">
            
            <div>
                <label style="font-size: 0.85em; font-weight: 500; color: var(--text-muted); margin-bottom: 4px; display: block;">Update Location</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="u_location" placeholder="✍️ Your Area, House, Road, Landmark..." required style="margin:0;flex:1;">
                    <button type="button" onclick="openUpdateMapPicker()" title="Map থেকে Location বেছে নিন" style="margin:0;padding:10px 13px;min-height:unset;width:auto;background:rgba(66,133,244,0.12);border:1.5px solid rgba(66,133,244,0.35);color:#4285f4;border-radius:10px;font-size:1.25rem;flex-shrink:0;box-shadow:none;cursor:pointer;" aria-label="Map Picker">🗺️</button>
                </div>
                <input type="hidden" id="u_reg_geo" value="">
                <p style="font-size:0.71em;color:var(--text-muted);margin:4px 0 0;padding-left:2px;">💡 🗺️ বাটনে ক্লিক করে Map থেকে সরাসরি লোকেশন পিন করুন</p>
            </div>

            <div>
                <label style="font-size: 0.85em; font-weight: 500; color: var(--text-muted); margin-bottom: 4px; display: block;">Last Blood Donation Date</label>
                <div class="smart-date-wrap" style="margin-top:0;">
                    <div class="smart-date-toggle">
                        <button type="button" id="uSdNeverBtn" class="sd-toggle-btn sd-active" onclick="setUpdateDonationNever()">🚫 Never / Reset</button>
                        <button type="button" id="uSdDateBtn"  class="sd-toggle-btn" onclick="setUpdateDonationDate()">📅 Pick a Date</button>
                    </div>
                    <input type="hidden" id="u_last" value="no">
                    <div id="uSdDatePickerWrap" style="display:none;margin-top:8px;">
                        <input type="date" id="uSdDateInput" style="margin:0;" max="" onchange="syncUpdateDonationDate(this.value)">
                        <p style="font-size:0.72em;color:var(--text-muted);margin:3px 0 0;padding-left:2px;">📅 তারিখ বেছে নিন (min: 1940-01-01 · max: আজ)</p>
                    </div>
                    <div id="uSdNeverMsg" style="margin-top:8px;padding:9px 12px;background:rgba(239,68,68,0.07);border-radius:8px;font-size:0.82em;color:var(--text-muted);">তারিখ নেই বা reset করতে চান — "no" সেট হবে।</div>
                </div>
            </div>

            <!-- Willing to Donate Toggle -->
            <div class="willing-toggle-wrap">
                <label style="font-size:0.95em; font-weight:600; color:var(--text-main); margin-bottom:10px; display:block;">🩸 রক্ত দিতে ইচ্ছুক?</label>
                <div class="willing-toggle-row">
                    <button type="button" id="willingYesBtn" class="willing-btn willing-yes active" onclick="setWilling('yes')">✅ হ্যাঁ, দিতে রাজি আছি</button>
                    <button type="button" id="willingNoBtn"  class="willing-btn willing-no"  onclick="setWilling('no')">⛔ এখন দিতে পারব না</button>
                </div>
                <input type="hidden" id="u_willing" value="yes">
                <p class="willing-note" id="willingNote">আপনি Available হিসেবে তালিকায় থাকবেন।</p>
            </div>

        </div>
        <input type="hidden" id="u_just_donated" value="0">
        <button type="button" onclick="submitUpdate()" style="background:var(--success); color:#000; margin-top:20px;">💾 Save Changes</button>

        <!-- ===== DELETE MY INFO SECTION ===== -->
        <div style="margin-top:28px;border-top:1px solid rgba(220,38,38,0.2);padding-top:20px;">
            <div onclick="toggleDeleteDonorSection()" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;padding:10px 14px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.2);border-radius:12px;user-select:none;">
                <span style="color:var(--danger);font-weight:700;font-size:0.9em;">🗑️ আমার সকল তথ্য মুছে ফেলুন</span>
                <span id="deleteDonorArrow" style="color:var(--danger);font-size:1.2em;transition:transform 0.2s;">›</span>
            </div>
            <div id="deleteDonorBody" style="display:none;margin-top:12px;padding:16px;background:rgba(220,38,38,0.04);border:1px solid rgba(220,38,38,0.15);border-radius:12px;">
                <p style="color:var(--danger);font-weight:700;font-size:0.88em;margin-bottom:6px;">⚠️ সতর্কতা — এই কাজ পূর্বাবস্থায় ফেরানো যাবে না!</p>
                <p style="color:var(--text-muted);font-size:0.83em;margin-bottom:14px;">আপনার নাম, ফোন নম্বর, রক্তের গ্রুপ, location সহ সকল তথ্য database থেকে <strong style="color:var(--danger);">চিরতরে মুছে যাবে।</strong></p>
                <label style="font-size:0.83em;color:var(--text-muted);display:block;margin-bottom:6px;">নিশ্চিত করতে নিচের বক্সে <strong style="color:var(--danger);">DELETE</strong> লিখুন:</label>
                <input type="text" id="del_donor_confirm" placeholder="DELETE" maxlength="6"
                    style="font-family:monospace;font-size:1.1em;letter-spacing:3px;text-transform:uppercase;margin-bottom:12px;"
                    oninput="this.value=this.value.toUpperCase()">
                <div id="del_donor_error" style="display:none;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);border-radius:8px;padding:8px 12px;color:var(--danger);font-size:0.82em;margin-bottom:10px;"></div>
                <button type="button" id="del_donor_btn" onclick="submitDeleteDonor()"
                    style="width:100%;background:var(--danger);color:#fff;border:none;border-radius:12px;padding:12px;font-size:0.92rem;font-weight:700;cursor:pointer;min-height:unset;box-shadow:none;margin:0;">
                    🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন
                </button>
            </div>
        </div>
    </div>
</form>
</div>
</div>
<?php render_social_bar(); ?>
<div class="page-footer-bar"><span>🩸 © 2026 <?= htmlspecialchars(BRAND_NAME) ?> — All Rights Reserved.</span></div>
</div><!-- end page-register -->

<!-- ===== APP PAGE: DONORS ===== -->
<div class="app-page" id="page-donors">
<div class="app-page-header"><span class="ph-icon">👥</span> রক্তদাতার তালিকা</div>
<div class="container" id="donorListSection">  
    
<!-- Database Header -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-top:20px; margin-bottom:12px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
    <h3 style="margin:0; font-family:var(--font-heading); color:var(--text-main); font-size:1.5rem; font-weight:800;">👥 Donor Database</h3>
    <button onclick="resetFilters()" style="background:rgba(128,128,128,0.12);border:1px solid var(--border-color);color:var(--text-muted);padding:6px 14px;border-radius:20px;font-size:0.8em;margin:0;box-shadow:none;width:auto;min-height:unset;" title="Reset all filters">🔄 Reset</button>
</div>

<div class="filter-container">
    <div class="filter-grid">
        <div>
            <label style="font-size: 0.85em; font-weight: 500; color:var(--text-muted); display:block; margin-bottom:6px;">Search by Name / Exact Place</label>
            <input type="text" id="searchInput" placeholder="Search name or exact location..." onkeyup="debouncedSearch()" style="margin:0;">
        </div>
        <!-- Location filter removed — always filters All Areas -->
        <input type="hidden" id="locationFilter" value="All">
        <!-- Recently-donated mode (set by Stats "Successfully Donated" card) -->
        <input type="hidden" id="donatedFilter" value="0">

        <div>
            <label style="font-size: 0.85em; font-weight: 500; color:var(--text-muted); display:block; margin-bottom:6px;">Filter by Group</label>
            <select id="groupFilter" onchange="fetchFilteredData(1)" style="margin:0;">  
                <option value="All">All Groups</option>  
                <?php foreach(["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"] as $g){ echo "<option value='$g'>$g</option>"; } ?>
            </select>
        </div>
        <div>
            <label style="font-size: 0.85em; font-weight: 500; color:var(--text-muted); display:block; margin-bottom:6px;">Live Status</label>
            <select id="statusFilter" onchange="fetchFilteredData(1)" style="margin:0;">  
                <option value="All">Show All</option>  
                <option value="Available">Available Only</option>  
                <option value="Unavailable">Not Willing (⛔)</option>
            </select>
        </div>
        <div>
            <label style="font-size: 0.85em; font-weight: 500; color:var(--text-muted); display:block; margin-bottom:6px;">🏅 Badge Level</label>
            <select id="badgeFilter" onchange="fetchFilteredData(1)" style="margin:0;">
                <option value="All">All Badges</option>
                <option value="New">🌱 New</option>
                <option value="Active">⭐ Active</option>
                <option value="Hero">🦸 Hero</option>
                <option value="Legend">👑 Legend</option>
            </select>
        </div>
    </div>
</div>

<div class="call-notice-wrapper">
    <div class="call-notice-text">
        👤রক্তদাতার সাথে যোগাযোগ করতে (📞Call) এ ক্লিক করুন।
    </div>
</div>

<!-- Desktop table (hidden on mobile) -->
<div class="donor-table-wrapper">
<table class="donor-table">  
<thead>
    <tr>
        <th>No.</th> 
        <th>Name</th> 
        <th>Blood Group</th> 
        <th>Status</th> 
        <th>Location</th> 
        <th>Last Donation</th> 
        <th>Phone</th>
    </tr>  
</thead>
<tbody id="donorTableBody"></tbody>
</table>  
</div>

<!-- Mobile cards (hidden on desktop) -->
<div id="donorCardsBody" class="donor-cards-container"></div>  

<div id="paginationSection" class="pagination"></div>  

<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:30px auto 0;max-width:500px;padding:0 12px;">
<button class="report-btn-footer" onclick="openGeneralReportModal()" style="flex:1;min-width:180px;">
    ⚠️ Report Harassment
</button>
<button class="report-btn-footer" onclick="openAdminMessageModal()" style="flex:1;min-width:180px;border-color:var(--info);color:var(--info);box-shadow:0 4px 15px rgba(59,130,246,0.2);">
    💬 Message to Admin
</button>
</div>

<!-- MODAL: MESSAGE TO ADMIN -->
<div class="popup-overlay" id="adminMsgModal" style="z-index:10050;" onclick="if(event.target===this)closeAdminMsgModal()">
  <div class="popup" style="max-width:400px;padding:24px 20px;">
    <h2 style="color:var(--info);margin-bottom:6px;font-family:var(--font-heading);">💬 Admin কে Message</h2>
    <p style="font-size:0.82em;color:var(--text-muted);margin-bottom:16px;">আপনার idea বা আমাদের ত্রুটি সম্পর্কে জানান। Admin reply করলে আপনার Services notification এ আসবে।</p>
    <input type="text" id="adm_sender_name" placeholder="আপনার নাম" maxlength="100" style="margin-bottom:10px;">
    <input type="tel" id="adm_sender_phone" placeholder="+8801XXXXXXXXX" value="+8801" maxlength="14" style="margin-bottom:10px;font-family:monospace;" oninput="if(!this.value.startsWith('+880'))this.value='+880'">
    <textarea id="adm_sender_msg" rows="4" placeholder="আপনার idea বা আমাদের ত্রুটি লিখুন..." maxlength="1000" style="width:100%;padding:11px 14px;background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;color:var(--text-main);font-size:0.9em;resize:none;font-family:var(--font-body);margin-bottom:10px;box-sizing:border-box;"></textarea>
    <div id="adm_msg_error" style="display:none;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);border-radius:10px;padding:9px 12px;color:var(--danger);font-size:0.82em;margin-bottom:10px;"></div>
    <div id="adm_msg_success" style="display:none;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:9px 12px;color:var(--success);font-size:0.82em;margin-bottom:10px;"></div>
    <div style="display:flex;gap:10px;margin-top:4px;">
      <button onclick="closeAdminMsgModal()" style="flex:1;padding:12px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);border-radius:12px;font-size:0.88rem;cursor:pointer;min-height:unset;box-shadow:none;margin:0;">বাতিল</button>
      <button id="adm_msg_btn" onclick="submitAdminMessage()" style="flex:2;padding:12px;background:var(--info);color:#fff;border:none;border-radius:12px;font-size:0.88rem;font-weight:700;cursor:pointer;min-height:unset;box-shadow:none;margin:0;">📤 পাঠান</button>
    </div>
  </div>
</div>

</div>
<?php render_social_bar(); ?>
<div class="page-footer-bar"><span>🩸 © 2026 <?= htmlspecialchars(BRAND_NAME) ?> — All Rights Reserved.</span></div>
</div><!-- end page-donors -->

<!-- ===== APP PAGE: NEARBY ===== -->
<div class="app-page" id="page-nearby">
<div class="app-page-header"><span class="ph-icon">📍</span> Nearby Donors & Map</div>

<!-- ==================== NEARBY DONORS SECTION ==================== -->
<div class="container nearby-section" id="nearbySection">
    <div class="section-header-row">
        <div>
            <h3 class="section-title">📍 আমার কাছের Donors</h3>
            <p class="section-sub">GPS দিয়ে কাছের রক্তদাতা খুঁজুন</p>
        </div>
        <button class="analytics-refresh-btn" id="nearbyLoadBtn" onclick="loadNearbyDonors()">📡 খুঁজুন</button>
    </div>
    <div class="nearby-controls">
        <div style="flex:1;min-width:120px;">
            <label style="font-size:0.8em;color:var(--text-muted);display:block;margin-bottom:4px;">🩸 Blood Group</label>
            <select id="nearbyGroupFilter" style="margin:0;" onchange="if(document.getElementById('nearbyResults').querySelector('.nearby-card')) loadNearbyDonors();">
                <option value="All">All Groups</option>
                <?php foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g) echo "<option>$g</option>"; ?>
            </select>
        </div>
        <div style="flex:1;min-width:120px;">
            <label style="font-size:0.8em;color:var(--text-muted);display:block;margin-bottom:4px;">🟢 Live Status</label>
            <select id="nearbyStatusFilter" style="margin:0;" onchange="if(document.getElementById('nearbyResults').querySelector('.nearby-card')) loadNearbyDonors();">
                <option value="All">সব দেখুন</option>
                <option value="Available">✔ Available</option>
                <option value="Not Available">✖ Not Available</option>
                <option value="Unavailable">⛔ Not Willing</option>
            </select>
        </div>
        <div style="flex:1;min-width:120px;">
            <label style="font-size:0.8em;color:var(--text-muted);display:block;margin-bottom:4px;">📍 Radius (km)</label>
            <select id="nearbyRadius" style="margin:0;">
                <option value="2">2 km</option>
                <option value="5" selected>5 km</option>
                <option value="10">10 km</option>
                <option value="20">20 km</option>
                <option value="50">50 km</option>
            </select>
        </div>
    </div>
    <div class="nearby-results donor-cards-container" id="nearbyResults">
        <div class="nearby-empty" style="grid-column:1/-1;">
            <div style="font-size:3rem;margin-bottom:10px;">📡</div>
            <p style="font-weight:600;margin-bottom:5px;">Location ব্যবহার করে কাছের donor খুঁজুন</p>
            <p style="font-size:0.85em;color:var(--text-muted);">উপরের বাটনে ক্লিক করুন</p>
        </div>
    </div>
</div>

<!-- ==================== MAP SECTION ==================== -->
<div class="container map-section" id="mapSection">
    <div class="section-header-row">
        <div>
            <h3 class="section-title">🗺️ Donor Map</h3>
            <p class="section-sub">রক্তদাতারা কোথায় আছেন</p>
        </div>
        <button class="analytics-refresh-btn" onclick="loadMap()">📍 Load Map</button>
    </div>

    <!-- MAP FILTERS -->
    <div class="map-filter-bar" id="mapFilterBar">
        <div class="map-filter-group">
            <label class="map-filter-label">🩸 Blood Group</label>
            <div class="map-filter-pills" id="mapGroupPills">
                <button class="map-pill active" data-val="All" onclick="setMapFilter('group','All',this)">All</button>
                <?php foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g): ?>
                <button class="map-pill" data-val="<?php echo $g; ?>" onclick="setMapFilter('group','<?php echo $g; ?>',this)"><?php echo $g; ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="map-filter-group">
            <label class="map-filter-label">🟢 Live Status</label>
            <div class="map-filter-pills" id="mapStatusPills">
                <button class="map-pill active" data-val="All" onclick="setMapFilter('status','All',this)">All</button>
                <button class="map-pill map-pill-avail" data-val="Available" onclick="setMapFilter('status','Available',this)">✔ Available</button>
                <button class="map-pill map-pill-notavail" data-val="Not Available" onclick="setMapFilter('status','Not Available',this)">✖ Not Available</button>
                <button class="map-pill map-pill-unwill" data-val="Unavailable" onclick="setMapFilter('status','Unavailable',this)">⛔ Not Willing</button>
            </div>
        </div>
        <div id="mapFilterInfo" class="map-filter-info" style="display:none;"></div>
    </div>

    <div id="mapContainer" class="map-container">
        <div class="map-placeholder" id="mapPlaceholder">
            <div style="font-size:3rem;">🗺️</div>
            <p style="font-weight:600; margin:10px 0 5px;">Map লোড করতে উপরের বাটনে ক্লিক করুন</p>
            <p style="font-size:0.82em; color:var(--text-muted);">শুধুমাত্র যেসব donors location permission দিয়েছেন তারা map-এ দেখাবে</p>
        </div>
        <div id="leafletMap" style="display:none; width:100%; height:100%; border-radius:16px;"></div>
    </div>
    <div id="mapLegend" class="map-legend" style="display:none;">
        <span class="map-legend-item"><span style="color:#10b981; font-size:1.2em;">●</span> Available</span>
        <span class="map-legend-item"><span style="color:#ef4444; font-size:1.2em;">●</span> Not Available</span>
        <span class="map-legend-item"><span style="color:#6b7280; font-size:1.2em;">●</span> Not Willing</span>
    </div>
</div>
<?php render_social_bar(); ?>
<div class="page-footer-bar"><span>🩸 © 2026 <?= htmlspecialchars(BRAND_NAME) ?> — All Rights Reserved.</span></div>
</div><!-- end page-nearby -->

<!-- ===== APP PAGE: ANALYTICS ===== -->
<div class="app-page" id="page-more">
<div class="app-page-header"><span class="ph-icon">📊</span> Analytics</div>

<!-- ==================== ANALYTICS SECTION ==================== -->
<div class="container analytics-section" id="analyticsSection">
    <div class="section-header-row">
        <div>
            <h3 class="section-title">📊 Data Analytics</h3>
            <p class="section-sub">Blood Arena-র সার্বিক পরিসংখ্যান</p>
        </div>
        <button class="analytics-refresh-btn" onclick="loadAnalytics()">🔄 Refresh</button>
    </div>
    <div class="kpi-grid" id="kpiGrid">
        <div class="kpi-card kpi-total" onclick="kpiGoto('total')"><div class="kpi-icon">👥</div><div class="kpi-val" id="kpiTotal">—</div><div class="kpi-label">মোট Donors</div></div>
        <div class="kpi-card kpi-avail" onclick="kpiGoto('available')"><div class="kpi-icon">✅</div><div class="kpi-val" id="kpiAvail">—</div><div class="kpi-label">Available</div></div>
        <div class="kpi-card kpi-unav" onclick="kpiGoto('unavailable')"> <div class="kpi-icon">⛔</div><div class="kpi-val" id="kpiUnav">—</div><div class="kpi-label">Not Willing</div></div>
        <div class="kpi-card kpi-calls kpi-static"><div class="kpi-icon">📞</div><div class="kpi-val" id="kpiCalls">—</div><div class="kpi-label">মোট Calls</div></div>
        <div class="kpi-card kpi-req" onclick="kpiGoto('requests')">  <div class="kpi-icon">🆘</div><div class="kpi-val" id="kpiReq">—</div><div class="kpi-label">Active Requests</div></div>
        <div class="kpi-card kpi-donated" onclick="kpiGoto('donated')"><div class="kpi-icon">🩸</div><div class="kpi-val" id="kpiFulfilled">—</div><div class="kpi-label">Successfully Donated</div></div>
    </div>
    <div class="charts-grid">
        <div class="chart-card">
            <h4 class="chart-title">🩸 Blood Group Distribution</h4>
            <div id="bgChartWrap" class="bar-chart-wrap"></div>
        </div>
        <div class="chart-card">
            <h4 class="chart-title">🏅 Donor Badge Levels</h4>
            <div class="badge-donut-wrap">
                <canvas id="badgeDonut" width="180" height="180"></canvas>
                <div id="badgeLegend" class="badge-legend"></div>
            </div>
        </div>
    </div>
    <div class="chart-card" style="margin-top:16px;">
        <h4 class="chart-title">📍 Top Donor Areas</h4>
        <div id="locChartWrap" class="loc-chart-wrap"></div>
    </div>
</div>
<?php render_social_bar(); ?>
<div class="page-footer-bar"><span>🩸 © 2026 <?= htmlspecialchars(BRAND_NAME) ?> — All Rights Reserved.</span></div>
</div><!-- end page-more -->

<!-- PWA INSTALL PROMPT -->
<div id="pwaInstallOverlay" role="dialog" aria-modal="true" aria-label="App Install Prompt">
  <div id="pwaInstallBox">
    <div class="pwa-install-inner">

      <!-- Android / Chrome: compact single row -->
      <div id="pwaAndroidContent">
        <div class="pwa-top-row">
          <img src="icon.png" alt="Blood Arena" class="pwa-app-icon">
          <div class="pwa-install-titles">
            <strong><?= htmlspecialchars(BRAND_NAME) ?></strong>
            <span>Home Screen-এ Add করুন</span>
          </div>
          <div class="pwa-top-btns">
            <button class="pwa-install-btn" onclick="pwaDoInstall()">📲 Install</button>
            <button class="pwa-dismiss-btn" onclick="pwaDismiss()">✕</button>
          </div>
        </div>
        <div class="pwa-features">
          <span class="pwa-feat-pill">⚡ দ্রুত লোড</span>
          <span class="pwa-feat-pill">📵 Offline</span>
          <span class="pwa-feat-pill">🔔 Notification</span>
          <span class="pwa-feat-pill">📱 App Feel</span>
        </div>
      </div>

      <!-- iOS Safari: step instructions -->
      <div id="pwaIOSContent" style="display:none;">
        <div class="pwa-top-row">
          <img src="icon.png" alt="Blood Arena" class="pwa-app-icon">
          <div class="pwa-install-titles">
            <strong>Home Screen-এ Add করুন</strong>
            <span>Blood Arena · iOS Safari</span>
          </div>
          <button class="pwa-dismiss-btn" onclick="pwaDismiss()" style="flex-shrink:0;">✕</button>
        </div>
        <div class="pwa-ios-steps">
          নিচের <strong>Share ⎋</strong> বাটন চাপুন →
          <strong>"Add to Home Screen"</strong> বেছে নিন →
          উপরে <strong>"Add"</strong> চাপুন
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ══ PULL TO REFRESH ══ -->
<div id="ptrIndicator">
  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
  </svg>
</div>
<!-- ══ NETWORK LIVE DOT ══ -->
<div id="netStatusDot">LIVE<span id="liveOnlineCount" class="live-online-count"></span></div>
<!-- OFFLINE ALERT BANNER -->
<div id="offlineAlert">
  <span>📵 ইন্টারনেট সংযোগ নেই — Cached content দেখাচ্ছে</span>
  <button class="offline-retry-btn" onclick="offlineRetry(this)">🔄 Retry</button>
</div>

<!-- SETTINGS PANEL OVERLAY (Bottom Sheet) -->
<div class="settings-panel-overlay" id="settingsPanelOverlay" onclick="closeSettings(event)">
  <div class="settings-panel" id="settingsPanel">
    <div class="settings-panel-handle"></div>
    <div class="settings-panel-title">
      <span>⚙️ Settings</span>
      <div class="settings-title-actions">
        <button onclick="settingsReload()" class="settings-reload-btn" title="Reload page">🔄</button>
        <button onclick="closeSettingsPanel()" class="settings-close-btn" title="Close">✕</button>
      </div>
    </div>
    <div class="settings-list">

      <!-- 🔐 Account / Sign-in -->
      <div class="settings-item si-account" style="margin-bottom:12px;">
        <div class="settings-item-left">
          <div class="settings-item-icon">🔐</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Account</span>
            <span class="settings-item-sub">Google / ফোন OTP দিয়ে সাইন ইন</span>
          </div>
        </div>
        <button id="authEntryBtn" onclick="openAuthModal()" type="button"
          style="width:auto;min-height:unset;margin:0;padding:8px 14px;border-radius:20px;font-size:0.78em;font-weight:700;background:var(--primary-red);color:#fff;box-shadow:none;white-space:nowrap;">🔐 সাইন ইন</button>
      </div>

      <!-- Donation reminder hint card -->
      <div style="margin:0 0 12px;padding:12px 14px;background:linear-gradient(135deg,rgba(220,38,38,0.10),rgba(245,158,11,0.08));border:1px solid rgba(220,38,38,0.22);border-radius:12px;cursor:pointer;" onclick="closeSettingsPanel(); setTimeout(()=>{ appSwitchPage('register'); setTimeout(()=>{ switchTab(1); },200); },300);">
        <div style="display:flex;align-items:flex-start;gap:10px;">
          <span style="font-size:1.4rem;flex-shrink:0;">🩸</span>
          <div>
            <div style="font-size:0.84em;font-weight:700;color:var(--text-main);margin-bottom:4px;" class="si-donation-reminder-title">রক্ত দিয়েছেন? এখনই Update করুন!</div>
            <div style="font-size:0.76em;color:var(--text-muted);line-height:1.6;" class="si-donation-reminder-body">রক্ত দেওয়ার <strong style="color:var(--text-main);">সাথে সাথে বা একই দিনের মধ্যে</strong> "Update My Info"-এ গিয়ে <strong style="color:var(--text-main);">"আমি এইমাত্র রক্ত দিয়েছি 🩸"</strong> বাটন চাপুন।<br>এতে আপনার donation count ও badge update হবে এবং অন্যরা জানবে আপনি এখন available নন।</div>
            <div style="margin-top:7px;display:inline-flex;align-items:center;gap:5px;font-size:0.72em;font-weight:700;color:var(--primary-red);background:rgba(220,38,38,0.08);padding:4px 10px;border-radius:20px;border:1px solid rgba(220,38,38,0.2);" class="si-donation-reminder-btn">✏️ Update My Info খুলুন →</div>
          </div>
        </div>
      </div>

      <div class="settings-item si-theme" onclick="toggleTheme(); updateSettingsToggles();">
        <div class="settings-item-left">
          <div class="settings-item-icon">🌙</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Dark / Light Mode</span>
            <span class="settings-item-sub">Night mode চালু/বন্ধ করুন</span>
          </div>
        </div>
        <div class="settings-toggle" id="settingsThemeToggle"></div>
      </div>
      <!-- Language Toggle -->
      <div class="settings-item si-lang" onclick="toggleAppLanguage()">
        <div class="settings-item-left">
          <div class="settings-item-icon">🌐</div>
          <div class="settings-item-text">
            <span class="settings-item-label" id="langSettingLabel">App Language</span>
            <span class="settings-item-sub" id="langSettingSubLabel">বাংলা / English</span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
          <span id="langCurrentBadge" style="font-size:0.72em;font-weight:800;background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.25);color:var(--primary-red);padding:3px 10px;border-radius:20px;white-space:nowrap;">বাংলা</span>
        </div>
      </div>
      <div class="settings-item si-sound" onclick="toggleSoundSetting()">
        <div class="settings-item-left">
          <div class="settings-item-icon">🔊</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Notification Sound</span>
            <span class="settings-item-sub">Registration ও notification sound</span>
          </div>
        </div>
        <div class="settings-toggle on" id="settingsSoundToggle"></div>
      </div>
      <div class="settings-item si-vibr" onclick="toggleVibrationSetting()">
        <div class="settings-item-left">
          <div class="settings-item-icon">📳</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Vibration</span>
            <span class="settings-item-sub">Button ও notification vibration</span>
          </div>
        </div>
        <div class="settings-toggle on" id="settingsVibToggle"></div>
      </div>
      <!-- Donor Card Zoom -->
      <div class="settings-item si-zoom" style="cursor:default;">
        <div class="settings-item-left">
          <div class="settings-item-icon">🔍</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Donor Card Text Size</span>
            <span class="settings-item-sub">Donor list এর লেখার সাইজ</span>
          </div>
        </div>
        <div class="zoom-stepper">
          <button class="zoom-btn" onclick="changeZoom(-1)" title="Smaller">−</button>
          <span class="zoom-val" id="zoomValLabel">150%</span>
          <button class="zoom-btn" onclick="changeZoom(1)" title="Larger">+</button>
        </div>
      </div>
      <div class="settings-item si-notif" onclick="requestBrowserNotif()">
        <div class="settings-item-left">
          <div class="settings-item-icon">🔔</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Browser Notifications</span>
            <span class="settings-item-sub" id="notifStatusText">নতুন blood request এলে জানুন</span>
          </div>
        </div>
        <div class="settings-item-right" id="notifStatusBadge">›</div>
      </div>
      <div class="settings-item si-loc" onclick="requestLocationSetting()">
        <div class="settings-item-left">
          <div class="settings-item-icon">📍</div>
          <div class="settings-item-text">
            <span class="settings-item-label">Location Permission</span>
            <span class="settings-item-sub" id="locStatusText">Nearby donors খুঁজতে দরকার</span>
          </div>
        </div>
        <div class="settings-item-right" id="locStatusBadge">›</div>
      </div>
      <div class="settings-item si-clear" onclick="clearAppData()">
        <div class="settings-item-left">
          <div class="settings-item-icon">🧹</div>
          <div class="settings-item-text">
            <span class="settings-item-label" style="color:var(--danger);">Clear App Data</span>
            <span class="settings-item-sub">Cache, token ও settings মুছে fresh reload নেবে</span>
          </div>
        </div>
        <div class="settings-item-right" style="color:var(--danger);">›</div>
      </div>
    </div>
  </div>
</div>

<!-- PUSH NOTIFICATION PROMPT — iOS-style -->
<div id="notifPrompt" class="notif-prompt">
  <div class="np-app-row">
    <div class="np-app-icon">🩸</div>
    <div class="np-text-wrap">
      <div class="np-app-name"><?= htmlspecialchars(BRAND_NAME) ?></div>
      <div class="np-msg">নতুন emergency blood request হলে সাথে সাথে notification পাঠাতে চায়</div>
      <div class="np-btn-row">
        <button class="btn-deny-notif" onclick="dismissNotifPrompt()">না থাক</button>
        <button class="btn-allow-notif" onclick="enableNotifications()">✅ Allow</button>
      </div>
    </div>
  </div>
</div>

<!-- BLOOD REQUEST MODAL -->
<div class="popup-overlay" id="bloodReqModal" style="align-items:flex-end;">
    <div style="
        width:100%; max-width:580px;
        background:var(--bg-card);
        border-radius:24px 24px 0 0;
        overflow:hidden;
        transform:translateY(100%);
        transition:transform 0.22s cubic-bezier(0.32,1.1,0.64,1);
        max-height:92vh;
        display:flex; flex-direction:column;
        box-shadow:0 -12px 48px rgba(0,0,0,0.5);
        border-top:1px solid rgba(255,255,255,0.08);
        padding-bottom:env(safe-area-inset-bottom,0px);
    " id="bloodReqSheet">

        <!-- Drag handle -->
        <div style="display:flex;justify-content:center;padding:12px 0 0;">
            <div style="width:40px;height:4px;background:rgba(128,128,128,0.3);border-radius:4px;"></div>
        </div>

        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px 12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;background:linear-gradient(135deg,#dc2626,#9f1239);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(220,38,38,0.4);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <div style="font-family:var(--font-heading);font-weight:800;color:var(--text-main);font-size:1.05rem;line-height:1.2;">Emergency Blood Request</div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:1px;">সব donors-কে notify করা হবে</div>
                </div>
            </div>
            <button onclick="closeBloodReqModal()" style="background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);width:34px;height:34px;border-radius:10px;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;min-height:unset;box-shadow:none;margin:0;flex-shrink:0;">✕</button>
        </div>

        <!-- Divider -->
        <div style="height:1px;background:var(--border-color);margin:0 20px;"></div>

        <!-- Scrollable form body -->
        <div style="overflow-y:auto;padding:18px 20px 8px;flex:1;">

            <!-- Blood Group — big tap targets -->
            <div style="margin-bottom:16px;">
                <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:8px;">Blood Group <span style="color:#ef4444;">*</span></label>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;" id="reqGroupGrid">
                    <?php foreach(["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $g): ?>
                    <button type="button" class="req-group-btn" onclick="selectReqGroup(this,'<?= $g ?>')"
                        style="height:44px;border-radius:10px;border:1.5px solid var(--border-color);background:var(--input-bg);color:var(--text-main);font-weight:700;font-size:0.9rem;cursor:pointer;transition:all 0.15s;box-shadow:none;margin:0;padding:0;"
                        data-group="<?= $g ?>"><?= $g ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="req_group">
            </div>

            <!-- Patient Name + Bags in one row -->
            <div style="display:grid;grid-template-columns:1fr 80px;gap:10px;margin-bottom:14px;">
                <div>
                    <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">রোগীর নাম <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="req_patient" placeholder="পুরো নাম লিখুন" autocomplete="off"
                        style="margin:0;height:46px;font-size:0.92rem;padding:0 14px;border-radius:12px;">
                </div>
                <div>
                    <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">ব্যাগ</label>
                    <input type="number" id="req_bags" value="1" min="1" max="10"
                        style="margin:0;height:46px;font-size:1rem;padding:0;text-align:center;border-radius:12px;">
                </div>
            </div>

            <!-- Hospital -->
            <div style="margin-bottom:14px;">
                <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">হাসপাতাল / Ward <span style="color:#ef4444;">*</span></label>
                <input type="text" id="req_hospital" placeholder="যেমন: DMCH, Ward 5" autocomplete="off"
                    style="margin:0;height:46px;font-size:0.92rem;padding:0 14px;border-radius:12px;">
            </div>

            <!-- Contact + Urgency -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div>
                    <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">যোগাযোগ <span style="color:#ef4444;">*</span></label>
                    <input type="tel" id="req_contact" placeholder="+8801XXXXXXXXX" value="+8801" autocomplete="tel"
                        style="margin:0;height:46px;font-size:0.88rem;padding:0 12px;border-radius:12px;">
                </div>
                <div>
                    <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">Urgency</label>
                    <select id="req_urgency" style="margin:0;height:46px;font-size:0.88rem;padding:0 10px;border-radius:12px;">
                        <option value="Critical">🔴 Critical</option>
                        <option value="High" selected>🟠 High</option>
                        <option value="Medium">🔵 Medium</option>
                    </select>
                </div>
            </div>

            <!-- Note -->
            <div style="margin-bottom:20px;">
                <label style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">অতিরিক্ত তথ্য <span style="color:var(--text-muted);font-weight:400;">(Optional)</span></label>
                <input type="text" id="req_note" placeholder="রোগের ধরন, patient condition ইত্যাদি"
                    style="margin:0;height:46px;font-size:0.88rem;padding:0 14px;border-radius:12px;">
            </div>
        </div>

        <!-- Sticky action buttons -->
        <div style="padding:12px 20px 16px;border-top:1px solid var(--border-color);display:grid;grid-template-columns:1fr 2.5fr;gap:10px;">
            <button onclick="closeBloodReqModal()" style="height:50px;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);border-radius:14px;font-size:0.9rem;font-weight:600;cursor:pointer;margin:0;box-shadow:none;">বাতিল</button>
            <button onclick="submitBloodRequest()" style="height:50px;background:linear-gradient(135deg,#dc2626,#9f1239);color:#fff;border:none;border-radius:14px;font-size:0.97rem;font-weight:800;cursor:pointer;margin:0;box-shadow:0 4px 16px rgba(220,38,38,0.4);font-family:var(--font-heading);letter-spacing:0.3px;">🆘 Send Request</button>
        </div>
    </div>
</div>

<!-- ========== MAP PICKER MODAL (Leaflet-based, no API key needed) ========== -->
<div class="popup-overlay" id="mapPickerModal">
    <div class="popup" style="max-width:560px;padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border-color);">
            <strong style="font-family:var(--font-heading);font-size:1em;">🗺️ Map থেকে Location বেছে নিন</strong>
            <button onclick="closeMapPicker()" style="background:none;border:none;color:var(--text-muted);font-size:1.2rem;cursor:pointer;width:auto;min-height:unset;margin:0;padding:4px 8px;box-shadow:none;">✕</button>
        </div>
        <!-- Map search bar -->
        <div style="padding:8px 12px;border-bottom:1px solid var(--border-color);display:flex;gap:6px;align-items:center;">
            <input type="text" id="mapSearchInput" placeholder="🔍 এলাকার নাম লিখুন... (e.g. Mirpur, Kafrul)" style="margin:0;flex:1;font-size:0.83em;padding:8px 12px;" autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault();doMapSearch();}">
            <button onclick="doMapSearch()" style="margin:0;width:auto;min-height:unset;padding:8px 14px;background:rgba(59,130,246,0.15);color:#3b82f6;border:1px solid rgba(59,130,246,0.3);border-radius:10px;font-size:0.82em;font-weight:700;flex-shrink:0;box-shadow:none;">🔍 খুঁজুন</button>
        </div>
        <div style="position:relative;height:330px;">
            <div id="leafletMapPicker" style="width:100%;height:100%;"></div>
            <!-- My Location button -->
            <button id="mapMyLocBtn" onclick="mapGoToMyLocation()" title="আমার Location" style="position:absolute;bottom:12px;right:12px;z-index:999;width:40px;height:40px;border-radius:50%;background:#fff;border:none;box-shadow:0 2px 10px rgba(0,0,0,0.35);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.25rem;padding:0;margin:0;min-height:unset;">📍</button>
            <div id="mapPickerLoading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:var(--bg-card);font-size:1.5rem;flex-direction:column;gap:8px;z-index:10;">
                <div style="font-size:2.5rem;">🗺️</div>
                <p style="font-size:0.85em;color:var(--text-muted);">Map লোড হচ্ছে...</p>
            </div>
        </div>
        <div style="padding:12px 18px;display:flex;align-items:center;gap:10px;border-top:1px solid var(--border-color);">
            <input type="text" id="mapPickerResult" placeholder="📍 Map-এ ক্লিক করুন অথবা এখানে লিখুন..." style="margin:0;flex:1;font-size:0.85em;" oninput="" autocomplete="off">
            <button onclick="useMapPickerLocation()" style="margin:0;width:auto;min-height:unset;padding:10px 16px;background:var(--success);color:#000;font-size:0.85em;font-weight:700;flex-shrink:0;">✅ ব্যবহার করুন</button>
        </div>
    </div>
</div>

<!-- ========== FAQ MODAL ========== -->
<!-- ══════════ 🔐 AUTH (Sign in) MODAL ══════════ -->
<div class="popup-overlay" id="authModal">
    <div class="popup" style="max-width:420px;padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color);">
            <div>
                <strong style="font-family:var(--font-heading);font-size:1.1em;color:var(--text-main);" id="authModalTitle">🔐 সাইন ইন</strong>
                <p style="font-size:0.75em;color:var(--text-muted);margin:2px 0 0;" id="authModalSub">Google দিয়ে</p>
            </div>
            <button onclick="closeAuthModal()" style="background:none;border:none;color:var(--text-muted);font-size:1.2rem;cursor:pointer;width:auto;min-height:unset;margin:0;padding:6px 10px;box-shadow:none;border-radius:8px;">✕</button>
        </div>
        <div style="padding:22px 20px;">

            <!-- ══════ Account verification (Telegram / WhatsApp) — signed-in users ══════ -->
            <!-- verify না করলে call করা যাবে না — শুধু blood request। openAuthModal()
                 visibility toggle করে: logged-out → hidden, logged-in+unverified → shown। -->
            <div id="authVerifySection" style="display:none;">
                <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:12px;padding:10px 13px;margin-bottom:16px;">
                    <p style="font-size:0.78em;color:#f59e0b;font-weight:700;margin:0 0 2px;">🔒 অ্যাকাউন্ট verify করুন</p>
                    <p style="font-size:0.72em;color:var(--text-muted);margin:0;line-height:1.6;">দাতাকে <strong>call</strong> করতে Telegram বা WhatsApp দিয়ে নম্বর verify করা বাধ্যতামূলক। (blood request verify ছাড়াও করা যায়)</p>
                </div>

                <!-- ── Channel selector (একসাথে একটাই খোলে) ── -->
                <div style="display:flex;gap:10px;margin-bottom:16px;">
                    <button id="vchTgBtn" type="button" onclick="selectVerifyChannel('tg')"
                        style="flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;padding:14px 8px;border-radius:14px;border:2px solid #229ED9;background:rgba(34,158,217,0.10);cursor:pointer;box-shadow:none;">
                        <svg width="28" height="28" viewBox="0 0 240 240" aria-hidden="true"><circle cx="120" cy="120" r="120" fill="#229ED9"/><path fill="#fff" d="M53.6 117.4c34.9-15.2 58.2-25.2 69.9-30.1 33.3-13.8 40.2-16.2 44.7-16.3 1 0 3.2.2 4.7 1.4 1.2 1 1.5 2.3 1.7 3.3.2 1 .4 3.1.2 4.8-1.8 19.4-9.8 66.3-13.9 88-1.7 9.2-5.1 12.3-8.4 12.6-7.1.7-12.6-4.7-19.5-9.2-10.8-7.1-16.9-11.5-27.4-18.4-12.1-8-4.3-12.4 2.7-19.6 1.8-1.9 33.4-30.6 34-33.2.1-.3.1-1.5-.6-2.1-.7-.6-1.7-.4-2.5-.2-1.1.2-18.1 11.5-51.3 33.8-4.9 3.3-9.3 5-13.2 4.9-4.3-.1-12.7-2.5-18.9-4.5-7.6-2.5-13.7-3.8-13.1-8 .3-2.2 3.3-4.4 9-6.7z"/></svg>
                        <span style="font-size:0.82em;font-weight:700;color:var(--text-main);">Telegram</span>
                    </button>
                    <button id="vchWaBtn" type="button" onclick="selectVerifyChannel('wa')"
                        style="flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;padding:14px 8px;border-radius:14px;border:2px solid var(--border-color);background:transparent;cursor:pointer;box-shadow:none;">
                        <svg width="28" height="28" viewBox="0 0 32 32" aria-hidden="true"><path fill="#25D366" d="M16 0C7.2 0 0 7.2 0 16c0 2.8.7 5.5 2.1 7.9L0 32l8.3-2.2C10.6 31.2 13.3 32 16 32c8.8 0 16-7.2 16-16S24.8 0 16 0z"/><path fill="#fff" d="M12.4 9.4c-.3-.7-.6-.7-.9-.7h-.8c-.3 0-.7.1-1.1.5s-1.4 1.4-1.4 3.4 1.5 3.9 1.7 4.2c.2.3 2.9 4.6 7.2 6.3 3.6 1.4 4.3 1.1 5.1 1s2.5-1 2.9-2 .4-1.8.3-2c-.1-.2-.4-.3-.9-.5s-2.7-1.3-3.1-1.5c-.4-.1-.7-.2-1 .2s-1.1 1.5-1.4 1.8c-.3.3-.5.3-.9.1s-1.9-.7-3.6-2.2c-1.3-1.2-2.2-2.6-2.5-3.1s0-.7.2-.9c.2-.2.4-.5.6-.8s.3-.4.4-.7.1-.5 0-.7-1-2.5-1.3-3.2z"/></svg>
                        <span style="font-size:0.82em;font-weight:700;color:var(--text-main);">WhatsApp</span>
                    </button>
                </div>

                <!-- Telegram panel -->
                <div id="tgPanel">
                    <p style="font-size:0.72em;color:var(--text-muted);margin:0 0 6px;line-height:1.6;">নম্বর দিন — Telegram-এ OTP আসবে।</p>
                    <input type="tel" id="tgPhoneInput" placeholder="+8801XXXXXXXXX" value="+880"
                        style="margin:0;width:100%;box-sizing:border-box;" pattern="^\+8801\d{9}$">
                    <button id="tgSendOtpBtn" onclick="tgSendOtp()" type="button"
                        style="width:100%;margin-top:10px;display:flex;align-items:center;justify-content:center;gap:8px;background:#229ED9;color:#fff;font-weight:700;">
                        <svg width="18" height="18" viewBox="0 0 240 240" aria-hidden="true"><circle cx="120" cy="120" r="120" fill="#fff"/><path fill="#229ED9" d="M53.6 117.4c34.9-15.2 58.2-25.2 69.9-30.1 33.3-13.8 40.2-16.2 44.7-16.3 1 0 3.2.2 4.7 1.4 1.2 1 1.5 2.3 1.7 3.3.2 1 .4 3.1.2 4.8-1.8 19.4-9.8 66.3-13.9 88-1.7 9.2-5.1 12.3-8.4 12.6-7.1.7-12.6-4.7-19.5-9.2-10.8-7.1-16.9-11.5-27.4-18.4-12.1-8-4.3-12.4 2.7-19.6 1.8-1.9 33.4-30.6 34-33.2.1-.3.1-1.5-.6-2.1-.7-.6-1.7-.4-2.5-.2-1.1.2-18.1 11.5-51.3 33.8-4.9 3.3-9.3 5-13.2 4.9-4.3-.1-12.7-2.5-18.9-4.5-7.6-2.5-13.7-3.8-13.1-8 .3-2.2 3.3-4.4 9-6.7z"/></svg>
                        OTP পাঠান
                    </button>
                    <div id="tgOpenBotDiv" style="display:none;margin-top:10px;">
                        <p style="font-size:0.7em;color:var(--text-muted);margin:0 0 6px;line-height:1.5;">Telegram খোলেনি? নিচের বাটনে চাপুন →</p>
                        <a id="tgOpenBotBtn" href="#" target="_blank" rel="noopener"
                            style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;box-sizing:border-box;padding:11px;background:#229ED9;color:#fff;font-weight:700;text-align:center;border-radius:10px;text-decoration:none;">
                            <svg width="18" height="18" viewBox="0 0 240 240" aria-hidden="true"><circle cx="120" cy="120" r="120" fill="#fff"/><path fill="#229ED9" d="M53.6 117.4c34.9-15.2 58.2-25.2 69.9-30.1 33.3-13.8 40.2-16.2 44.7-16.3 1 0 3.2.2 4.7 1.4 1.2 1 1.5 2.3 1.7 3.3.2 1 .4 3.1.2 4.8-1.8 19.4-9.8 66.3-13.9 88-1.7 9.2-5.1 12.3-8.4 12.6-7.1.7-12.6-4.7-19.5-9.2-10.8-7.1-16.9-11.5-27.4-18.4-12.1-8-4.3-12.4 2.7-19.6 1.8-1.9 33.4-30.6 34-33.2.1-.3.1-1.5-.6-2.1-.7-.6-1.7-.4-2.5-.2-1.1.2-18.1 11.5-51.3 33.8-4.9 3.3-9.3 5-13.2 4.9-4.3-.1-12.7-2.5-18.9-4.5-7.6-2.5-13.7-3.8-13.1-8 .3-2.2 3.3-4.4 9-6.7z"/></svg>
                            Telegram এ OTP নিন
                        </a>
                    </div>
                    <div id="tgOtpStep" style="display:none;margin-top:10px;">
                        <label style="font-size:0.78em;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;">🔢 Telegram-এ পাওয়া ৬-সংখ্যার কোড</label>
                        <input type="text" id="tgOtpInput" inputmode="numeric" maxlength="6" placeholder="••••••"
                            style="margin:0;width:100%;box-sizing:border-box;text-align:center;letter-spacing:6px;font-size:1.2em;font-family:monospace;">
                        <button id="tgVerifyBtn" onclick="tgVerifyOtp()" type="button"
                            style="width:100%;margin-top:10px;background:var(--success);color:#000;">✅ যাচাই করে verify করুন</button>
                    </div>
                </div>

                <!-- WhatsApp panel -->
                <div id="waPanel" style="display:none;">
                    <p style="font-size:0.72em;color:var(--text-muted);margin:0 0 6px;line-height:1.6;">নম্বর দিন — WhatsApp-এ OTP আসবে।</p>
                    <input type="tel" id="waPhoneInput" placeholder="+8801XXXXXXXXX" value="+880"
                        style="margin:0;width:100%;box-sizing:border-box;" pattern="^\+8801\d{9}$">
                    <button id="waSendOtpBtn" onclick="waSendOtp()" type="button"
                        style="width:100%;margin-top:10px;display:flex;align-items:center;justify-content:center;gap:8px;background:#25D366;color:#053d23;font-weight:700;">
                        <svg width="18" height="18" viewBox="0 0 32 32" aria-hidden="true"><path fill="#053d23" d="M16 0C7.2 0 0 7.2 0 16c0 2.8.7 5.5 2.1 7.9L0 32l8.3-2.2C10.6 31.2 13.3 32 16 32c8.8 0 16-7.2 16-16S24.8 0 16 0z"/><path fill="#25D366" d="M12.4 9.4c-.3-.7-.6-.7-.9-.7h-.8c-.3 0-.7.1-1.1.5s-1.4 1.4-1.4 3.4 1.5 3.9 1.7 4.2c.2.3 2.9 4.6 7.2 6.3 3.6 1.4 4.3 1.1 5.1 1s2.5-1 2.9-2 .4-1.8.3-2c-.1-.2-.4-.3-.9-.5s-2.7-1.3-3.1-1.5c-.4-.1-.7-.2-1 .2s-1.1 1.5-1.4 1.8c-.3.3-.5.3-.9.1s-1.9-.7-3.6-2.2c-1.3-1.2-2.2-2.6-2.5-3.1s0-.7.2-.9c.2-.2.4-.5.6-.8s.3-.4.4-.7.1-.5 0-.7-1-2.5-1.3-3.2z"/></svg>
                        WhatsApp-এ কোড পাঠান
                    </button>
                    <div id="waOtpStep" style="display:none;margin-top:10px;">
                        <label style="font-size:0.78em;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;">🔢 WhatsApp-এ পাওয়া ৬-সংখ্যার কোড</label>
                        <input type="text" id="waOtpInput" inputmode="numeric" maxlength="6" placeholder="••••••"
                            style="margin:0;width:100%;box-sizing:border-box;text-align:center;letter-spacing:6px;font-size:1.2em;font-family:monospace;">
                        <button id="waVerifyOtpBtn" onclick="waVerifyOtp()" type="button"
                            style="width:100%;margin-top:10px;background:var(--success);color:#000;">✅ যাচাই করে verify করুন</button>
                    </div>
                </div>

                <p style="font-size:0.72em;color:var(--text-muted);text-align:center;margin:16px 0 0;line-height:1.6;">
                    Verify না করলে account <strong style="color:#f59e0b;">unverified</strong> থাকবে — blood request করা যাবে, কিন্তু call নয়।
                </p>
            </div>

            <!-- ══════ Sign-in (Google) — logged-out users ══════ -->
            <div id="authSigninSection">

            <!-- Google -->
            <button id="authGoogleBtn" onclick="authGoogleSignIn()" type="button"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#1f2937;border:1.5px solid var(--border-color);border-radius:12px;padding:13px;font-weight:600;font-size:0.95em;box-shadow:none;margin:0;">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35 24 35c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 5.1 29.5 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 5.1 29.5 3 24 3 16.1 3 9.2 7.6 6.3 14.7z"/><path fill="#4CAF50" d="M24 45c5.2 0 10-2 13.6-5.2l-6.3-5.3C29.2 36 26.7 37 24 37c-5.3 0-9.7-2.6-11.3-7l-6.5 5C9.1 40.3 16 45 24 45z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4 5.5l6.3 5.3C39.9 36.6 45 31 45 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
                Google দিয়ে চালিয়ে যান
            </button>

            <p style="font-size:0.7em;color:var(--text-muted);text-align:center;margin:16px 0 0;line-height:1.6;">
                সাইন ইন করলে আপনি আমাদের শর্তাবলী মেনে নিচ্ছেন।
            </p>

            </div><!-- /authSigninSection -->
        </div>
    </div>
</div>

<!-- ══════════ ⏳ AUTH WAIT OVERLAY (Google সাইন-ইন প্রসেস হওয়ার সময়) ══════════ -->
<!-- redirect থেকে ফিরলে / popup-এর পর প্রোফাইল লোড না হওয়া পর্যন্ত "অপেক্ষা করুন" দেখায় -->
<div class="auth-wait-overlay" id="authWaitOverlay" role="alert" aria-live="assertive" aria-hidden="true">
  <div class="auth-wait-card">
    <span class="auth-wait-spinner" aria-hidden="true"></span>
    <p class="auth-wait-title" id="authWaitTitle">সাইন ইন হচ্ছে…</p>
    <p class="auth-wait-sub" id="authWaitSub">অনুগ্রহ করে অপেক্ষা করুন।</p>
  </div>
</div>

<!-- ══════════ 👤 ACCOUNT DASHBOARD MODAL ══════════ -->
<div class="popup-overlay" id="accountModal" onclick="if(event.target===this)closeAccountModal()">
    <div class="popup" style="max-width:440px;padding:0;overflow:hidden;max-height:88vh;display:flex;flex-direction:column;">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color);flex-shrink:0;">
            <strong style="font-family:var(--font-heading);font-size:1.1em;color:var(--text-main);">👤 আমার অ্যাকাউন্ট</strong>
            <button onclick="closeAccountModal()" style="background:none;border:none;color:var(--text-muted);font-size:1.2rem;cursor:pointer;width:auto;min-height:unset;margin:0;padding:6px 10px;box-shadow:none;border-radius:8px;">✕</button>
        </div>

        <div class="scroll-content" style="padding:18px 20px;overflow-y:auto;flex:1;">

            <!-- Profile -->
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
                <div id="accAvatar" style="width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,var(--primary-red),#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6em;font-weight:800;flex-shrink:0;font-family:var(--font-heading);">?</div>
                <div style="min-width:0;flex:1;">
                    <div id="accName" style="font-size:1.05em;font-weight:700;color:var(--text-main);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">—</div>
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:4px;">
                        <span id="accProvider" style="display:inline-block;font-size:0.7em;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(59,130,246,0.12);color:#3b82f6;border:1px solid rgba(59,130,246,0.25);">—</span>
                        <span id="accVerifyBadge" style="display:none;font-size:0.7em;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid;">—</span>
                    </div>
                </div>
            </div>

            <!-- Unverified bind prompt — verify না করলে call করা যাবে না -->
            <div id="accVerifyBanner" style="display:none;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.28);border-radius:12px;padding:12px 14px;margin-bottom:16px;">
                <p style="font-size:0.82em;color:#f59e0b;font-weight:700;margin:0 0 3px;">⚠️ আপনার অ্যাকাউন্ট unverified</p>
                <p style="font-size:0.76em;color:var(--text-muted);margin:0 0 10px;line-height:1.6;">দাতাকে <strong>call</strong> করতে Telegram বা WhatsApp দিয়ে নম্বর verify করুন। (blood request এখনই করা যাবে)</p>
                <button onclick="closeAccountModal(); openVerifyModal();" type="button"
                    style="width:100%;background:#229ED9;color:#fff;border:none;border-radius:10px;padding:11px;font-weight:700;font-size:0.85em;box-shadow:none;margin:0;">🔗 এখন verify করুন</button>
            </div>

            <!-- Account meta -->
            <div style="background:var(--input-bg);border:1px solid var(--border-color);border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:0.85em;">
                <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;">
                    <span style="color:var(--text-muted);">📧 Email</span>
                    <span id="accEmail" style="color:var(--text-main);font-weight:600;text-align:right;overflow:hidden;text-overflow:ellipsis;">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-top:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);">📱 ফোন</span>
                    <span id="accPhone" style="color:var(--text-main);font-weight:600;text-align:right;">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-top:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);">📅 যুক্ত হয়েছেন</span>
                    <span id="accMemberSince" style="color:var(--text-main);font-weight:600;text-align:right;">—</span>
                </div>
            </div>

            <!-- Donor profile link -->
            <p style="font-size:0.72em;text-transform:uppercase;letter-spacing:1.5px;color:var(--primary-red);font-weight:700;margin:0 0 8px;">🩸 রক্তদাতা প্রোফাইল</p>
            <div id="accDonorCard" style="margin-bottom:18px;"></div>

            <!-- My Donations / Donation History -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;">
                <p style="font-size:0.72em;text-transform:uppercase;letter-spacing:1.5px;color:var(--success);font-weight:700;margin:0;">🩸 আমার রক্তদান</p>
                <span id="accDonationCount" style="color:var(--text-muted);font-size:0.72em;"></span>
            </div>
            <div id="accDonationList" style="margin-bottom:18px;"></div>

            <!-- My Blood Requests (account-owned, tokenless delete) -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;">
                <p style="font-size:0.72em;text-transform:uppercase;letter-spacing:1.5px;color:var(--danger);font-weight:700;margin:0;">🆘 আমার Requests</p>
                <span id="accReqCount" style="color:var(--text-muted);font-size:0.72em;"></span>
            </div>
            <div id="accReqList" style="margin-bottom:18px;"></div>

            <!-- Messages -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;">
                <p style="font-size:0.72em;text-transform:uppercase;letter-spacing:1.5px;color:var(--info);font-weight:700;margin:0;">💬 Messages</p>
                <button onclick="openAdminMessageModal()" style="width:auto;min-height:unset;margin:0;padding:5px 12px;border-radius:20px;font-size:0.72em;font-weight:700;background:rgba(59,130,246,0.12);color:#3b82f6;border:1px solid rgba(59,130,246,0.25);box-shadow:none;">✚ নতুন</button>
            </div>
            <div id="accMsgList" style="margin-bottom:6px;"></div>

            <!-- Delete My Info (account dashboard থেকে সরাসরি) -->
            <div style="margin-top:18px;border-top:1px solid rgba(220,38,38,0.2);padding-top:16px;">
                <div onclick="toggleAccDeleteInfo()" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;padding:10px 14px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.2);border-radius:12px;user-select:none;">
                    <span style="color:var(--danger);font-weight:700;font-size:0.88em;">🗑️ আমার সকল তথ্য মুছে ফেলুন</span>
                    <span id="accDeleteInfoArrow" style="color:var(--danger);font-size:1.2em;transition:transform 0.2s;">›</span>
                </div>
                <div id="accDeleteInfoBody" style="display:none;margin-top:12px;padding:16px;background:rgba(220,38,38,0.04);border:1px solid rgba(220,38,38,0.15);border-radius:12px;">
                    <p style="color:var(--danger);font-weight:700;font-size:0.86em;margin-bottom:6px;">⚠️ সতর্কতা — এই কাজ পূর্বাবস্থায় ফেরানো যাবে না!</p>
                    <p style="color:var(--text-muted);font-size:0.82em;margin-bottom:14px;">আপনার নাম, ফোন নম্বর, রক্তের গ্রুপ, location সহ donor তথ্য database থেকে <strong style="color:var(--danger);">চিরতরে মুছে যাবে।</strong></p>
                    <label style="font-size:0.82em;color:var(--text-muted);display:block;margin-bottom:6px;">নিশ্চিত করতে নিচের বক্সে <strong style="color:var(--danger);">DELETE</strong> লিখুন:</label>
                    <input type="text" id="acc_del_confirm" placeholder="DELETE" maxlength="6"
                        style="font-family:monospace;font-size:1.1em;letter-spacing:3px;text-transform:uppercase;margin-bottom:12px;"
                        oninput="this.value=this.value.toUpperCase()">
                    <div id="acc_del_error" style="display:none;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);border-radius:8px;padding:8px 12px;color:var(--danger);font-size:0.82em;margin-bottom:10px;"></div>
                    <button type="button" id="acc_del_btn" onclick="submitAccDeleteInfo()"
                        style="width:100%;background:var(--danger);color:#fff;border:none;border-radius:12px;padding:12px;font-size:0.9rem;font-weight:700;cursor:pointer;min-height:unset;box-shadow:none;margin:0;">
                        🗑️ হ্যাঁ, আমার তথ্য সম্পূর্ণ মুছে দিন
                    </button>
                </div>
            </div>

        </div>

        <!-- Footer: logout -->
        <div style="padding:12px 20px;border-top:1px solid var(--border-color);flex-shrink:0;">
            <button onclick="authLogout(); closeAccountModal();" style="width:100%;background:rgba(220,38,38,0.1);color:var(--danger);border:1px solid rgba(220,38,38,0.3);border-radius:12px;padding:12px;font-weight:700;font-size:0.9em;box-shadow:none;margin:0;">🚪 লগ-আউট</button>
        </div>
    </div>
</div>

<div class="popup-overlay" id="faqModal">
    <div class="popup" style="max-width:580px;padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color);position:sticky;top:0;background:var(--bg-card);z-index:2;">
            <div>
                <strong style="font-family:var(--font-heading);font-size:1.1em;color:var(--text-main);">❓ প্রশ্ন ও উত্তর</strong>
                <p style="font-size:0.75em;color:var(--text-muted);margin:2px 0 0;">Blood Arena — FAQ</p>
            </div>
            <button onclick="closeFAQModal()" style="background:none;border:none;color:var(--text-muted);font-size:1.2rem;cursor:pointer;width:auto;min-height:unset;margin:0;padding:6px 10px;box-shadow:none;border-radius:8px;">✕</button>
        </div>
        <div class="scroll-content" style="padding:16px 20px;max-height:72vh;overflow-y:auto;">

            <!-- Category: Basic Usage -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:0 0 10px;">ব্যবহার পদ্ধতি</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📖 এই পোর্টাল কীভাবে ব্যবহার করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Blood Arena ব্যবহার করা অত্যন্ত সহজ:</p>
                    <p>• <strong>Donors দেখুন:</strong> নিচের Donors ট্যাবে যান। রক্তের গ্রুপ, Badge ও availability অনুযায়ী filter করুন।</p>
                    <p>• <strong>Register করুন:</strong> Register ট্যাবে গিয়ে আপনার তথ্য দিন — এটা সম্পূর্ণ বিনামূল্যে।</p>
                    <p>• <strong>Emergency Request:</strong> জরুরি রক্তের দরকার হলে SOS বাটন চেপে Emergency Request পাঠান। Request আপনার অ্যাকাউন্টের সাথে যুক্ত থাকে, তাই পরে "👤 আমার Request" tab থেকে যেকোনো সময় মুছতে পারবেন।</p>
                    <p>• <strong>Nearby Donors:</strong> কাছের donors খুঁজতে Location চালু রেখে Nearby ট্যাবে যান।</p>
                    <p>• <strong>তথ্য মুছুন:</strong> Update My Info → সাইন ইন → নিচে "🗑️ আমার সকল তথ্য মুছে ফেলুন" থেকে নিজেই account delete করতে পারবেন।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🩸 রক্তদাতা হিসেবে কীভাবে নিবন্ধন করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Register ট্যাবে গিয়ে আগে Google বা ফোন নম্বর দিয়ে সাইন ইন করুন, তারপর আপনার নাম, রক্তের গ্রুপ, মোবাইল নম্বর, এলাকা ও availability দিন। কোনো Secret Code মনে রাখার দরকার নেই — পরে সাইন ইন করেই তথ্য update করতে পারবেন।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>✏️ আমার তথ্য কীভাবে update করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Register → "Update My Info" ট্যাবে যান। Google বা ফোন নম্বর দিয়ে সাইন ইন করুন, তারপর তথ্য বদলান। Availability, ফোন নম্বর, এলাকা সব বদলানো যাবে।</p>
                    <p style="margin-top:8px;padding:8px 10px;background:rgba(220,38,38,0.08);border-left:3px solid var(--primary-red);border-radius:0 6px 6px 0;"><strong>⚠️ রক্ত দেওয়ার পর:</strong> রক্ত দেওয়ার <strong>সাথে সাথে বা একই দিনের মধ্যে</strong> update করুন — <strong>"আমি এইমাত্র রক্ত দিয়েছি 🩸"</strong> বাটন চেপে Save করুন। এতে আপনার donation count বাড়বে এবং আপনি ১২০ দিনের জন্য "Not Available" হবেন।</p>
                </div>
            </div>

            <!-- Category: Location -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Location ও Permission</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📍 Location কেন নেওয়া হয়?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Location দুটো কারণে ব্যবহার করা হয়:</p>
                    <p>• <strong>Nearby Donors:</strong> আপনার কাছাকাছি (নির্দিষ্ট km-এর মধ্যে) কোন donors আছেন তা খুঁজে বের করতে।</p>
                    <p>• <strong>নিরাপত্তা:</strong> Emergency Request বা Registration-এর সময় IP/Location log করা হয় — এটি জালিয়াতি ও স্প্যাম প্রতিরোধের জন্য।</p>
                    <p><em>আপনার location কখনো তৃতীয় পক্ষকে দেওয়া হয় না।</em></p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🚫 Location permission দিতে না পারলে কী করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Location ছাড়াও বেশিরভাগ feature কাজ করে। শুধু Nearby Donors কাজ করবে না।</p>
                    <p><strong>Browser-এ Permission চালু করতে:</strong></p>
                    <p>• Chrome: Address bar-এ 🔒 আইকনে ক্লিক → Site settings → Location → Allow</p>
                    <p>• Firefox: Address bar-এ 🔒 আইকনে ক্লিক → Connection secure → More info → Permissions → Access your location → Allow</p>
                    <p>• Safari (iOS): Settings → Safari → Location → Allow</p>
                    <p>• Chrome (Android): Settings → Site settings → Location → Allow</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🗺️ Map-এ location pick করবো কীভাবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Registration form-এ 🗺️ বাটন চেপে Map Picker খুলুন। Map-এ আপনার এলাকায় ক্লিক করুন অথবা নিচের 📍 বাটন চেপে GPS থেকে auto-detect করুন। Address confirm হলে "✅ ব্যবহার করুন" চাপুন।</p>
                </div>
            </div>

            <!-- Category: Notifications -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Notification ও Sound</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔔 Notification কীভাবে চালু করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Settings → Browser Notifications-এ ক্লিক করুন। Browser একটি permission popup দেখাবে — "Allow" চাপুন। এরপর নতুন Blood Request এলে সরাসরি phone-এ notification আসবে, এমনকি browser বন্ধ থাকলেও।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔇 Notification sound বন্ধ করবো কীভাবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Settings → Notification Sound toggle টি বন্ধ করুন। এরপর Registration এবং নতুন Blood Request — সব sound বন্ধ হয়ে যাবে।</p>
                </div>
            </div>

            <!-- Category: Privacy & Security -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Privacy ও নিরাপত্তা</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔒 আমার ফোন নম্বর কি সবার কাছে দেখা যাবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>হ্যাঁ। Donor list-এ আপনার নাম ও ফোন নম্বর দেখা যাবে — এটাই এই পোর্টালের উদ্দেশ্য, যাতে রোগীর স্বজনরা সরাসরি যোগাযোগ করতে পারেন। আপনার তথ্য শুধুমাত্র আপনি সাইন ইন করেই পরিবর্তন করতে পারবেন।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🗑️ আমার তথ্য কি মুছে ফেলা যাবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>হ্যাঁ, দুটো উপায়ে:</p>
                    <p>• <strong>Unavailable করুন:</strong> Update My Info-এ গিয়ে availability "⛔ এখন দিতে পারব না" করুন — এতে আপনি list-এ দেখাবেন না কিন্তু তথ্য থাকবে।</p>
                    <p>• <strong>সম্পূর্ণ মুছুন:</strong> Update My Info → সাইন ইন করুন → নিচে স্ক্রোল করুন → <strong>"🗑️ আমার সকল তথ্য মুছে ফেলুন"</strong> section খুলুন → DELETE লিখে confirm করুন। আপনার নাম, ফোন, রক্তের গ্রুপ সহ সকল তথ্য চিরতরে মুছে যাবে।</p>
                </div>
            </div>

            <!-- Category: Emergency Blood Request -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Emergency Blood Request</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🆘 Emergency Request পাঠানোর পর কীভাবে মুছব?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Emergency Request পাঠাতে আগে সাইন ইন করতে হয়, তাই প্রতিটি Request আপনার অ্যাকাউন্টের সাথে যুক্ত থাকে। কোনো Token মনে রাখার দরকার নেই।</p>
                    <p>পরে Request মুছতে:</p>
                    <p>• Home-এ "📋 Active Requests দেখুন" বাটনে ক্লিক করুন।</p>
                    <p>• <strong>"👤 আমার Request"</strong> tab-এ যান — নিজের card দেখতে পাবেন।</p>
                    <p>• <strong>"🗑️ আমার Request মুছুন"</strong> বাটনে ক্লিক করলেই সাথে সাথে মুছে যাবে।</p>
                    <p>• অথবা Account Dashboard → <strong>"🆘 আমার Requests"</strong> থেকেও মুছতে পারবেন।</p>
                    <p><em>⏳ Request ৭২ ঘণ্টা পর স্বয়ংক্রিয়ভাবে Expire হয়ে যায়।</em></p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🩸 Active Requests-এ কীভাবে filter করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Active Requests section-এ দুই ধরনের filter আছে:</p>
                    <p>• <strong>🩸 সব:</strong> সকল active request দেখাবে।</p>
                    <p>• <strong>👤 আমার Request:</strong> শুধু আপনার পাঠানো request দেখাবে — এখান থেকেই delete করা যাবে।</p>
                    <p>• <strong>Blood Group Filter (A+, B+, O+ ...):</strong> নির্দিষ্ট গ্রুপের request আলাদা করে দেখতে পারবেন। একটি group-এ ক্লিক করলে highlight হয়, আবার ক্লিক করলে clear হয়।</p>
                    <p><em>💡 Tab ও Blood Group filter একসাথে ব্যবহার করা যায়।</em></p>
                </div>
            </div>

            <!-- Category: Settings -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Settings</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>⚙️ Settings-এ কী কী option আছে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Settings panel-এ নিচের option গুলো পাবেন:</p>
                    <p>• <strong>🌙 Dark / Light Mode:</strong> রাতে পড়তে সুবিধার জন্য Dark mode চালু করুন।</p>
                    <p>• <strong>🔊 Notification Sound:</strong> Registration success ও নতুন blood request-এর sound চালু/বন্ধ করুন।</p>
                    <p>• <strong>🔍 Donor Card Text Size:</strong> Donor list-এর লেখা বড় বা ছোট করুন (+/− বাটন দিয়ে)।</p>
                    <p>• <strong>🔔 Browser Notifications:</strong> নতুন Emergency Blood Request এলে phone-এ notification পাঠাবে।</p>
                    <p>• <strong>📍 Location Permission:</strong> Nearby Donors feature-এর জন্য GPS চালু করুন।</p>
                    <p>• <strong>🧹 Clear App Data:</strong> Cache, token ও সব settings মুছে app fresh করে reload নেবে — কোনো সমস্যা হলে এটি ব্যবহার করুন।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🧹 Clear App Data কী করে? কখন ব্যবহার করব?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Settings → <strong>"🧹 Clear App Data"</strong> চাপলে নিচের সব কিছু একসাথে মুছে যাবে এবং app fresh হয়ে reload নেবে:</p>
                    <p>• <strong>LocalStorage ও SessionStorage</strong> — সেভ করা token, preferences, dismissed notifications সব</p>
                    <p>• <strong>Service Worker ও Cache</strong> — পুরনো cached files মুছে সার্ভার থেকে নতুন করে লোড হবে</p>
                    <p><strong>কখন ব্যবহার করবেন:</strong></p>
                    <p>• App আটকে গেলে বা সঠিকভাবে কাজ না করলে</p>
                    <p>• Update দেওয়ার পরেও পুরনো version দেখালে</p>
                    <p>• "আমার Request" tab-এ data না দেখালে</p>
                    <p>• যেকোনো অদ্ভুত সমস্যায়</p>
                    <p><em>⚠️ এটি আপনার donation তথ্য বা database-এর কিছু মুছবে না — শুধু browser-এ জমা local data clear হবে।</em></p>
                </div>
            </div>

            <!-- Category: Badge System -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Badge System</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🏅 Badge system কী? কীভাবে পাবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Badge হলো রক্তদানের অভিজ্ঞতার স্বীকৃতি। মোট রক্তদানের সংখ্যার উপর ভিত্তি করে badge নির্ধারিত হয়:</p>
                    <p>• <strong>🌱 New</strong> — ০–১ বার &nbsp;&nbsp;|&nbsp;&nbsp; নতুন donor হিসেবে স্বাগতম!</p>
                    <p>• <strong>⭐ Active</strong> — ২–৪ বার &nbsp;&nbsp;|&nbsp;&nbsp; নিয়মিত দাতা।</p>
                    <p>• <strong>🦸 Hero</strong> — ৫–৯ বার &nbsp;&nbsp;|&nbsp;&nbsp; সত্যিকারের রক্তবীর!</p>
                    <p>• <strong>👑 Legend</strong> — ১০+ বার &nbsp;&nbsp;|&nbsp;&nbsp; কিংবদন্তি দাতা!</p>
                    <p>Badge আপনার নামের পাশে donor list-এ সবার কাছে দেখা যায়।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔄 Badge কীভাবে update হবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p><strong>Register → Update My Info</strong>-এ যান। Google বা ফোন নম্বর দিয়ে সাইন ইন করুন, তারপর <strong>"আজ রক্ত দিয়েছি ✅"</strong> চেকবক্সটি tick করুন এবং Last Donation date দিন।</p>
                    <p>Save করলে donation count বাড়বে এবং প্রয়োজনীয় সংখ্যায় পৌঁছালে Badge স্বয়ংক্রিয়ভাবে upgrade হবে।</p>
                    <p><em>নতুন registration-এর সময়েও আগের donation count দেওয়া যায়।</em></p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>⏰ রক্ত দেওয়ার পর কতক্ষণের মধ্যে update করতে হবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>রক্ত দেওয়ার <strong>সাথে সাথে বা একই দিনের মধ্যে</strong> update করুন — এটাই সবচেয়ে ভালো।</p>
                    <p>কারণ:</p>
                    <p>• <strong>Availability সঠিক থাকে:</strong> রক্ত দেওয়ার পর আপনি ১২০ দিন পর্যন্ত "Not Available" — এটা system-এ আপডেট না হলে রোগীর স্বজনরা আপনাকে call করতে পারেন, কিন্তু আপনি দিতে পারবেন না।</p>
                    <p>• <strong>Donation count সঠিক থাকে:</strong> একই দিনে update করলেই system সঠিকভাবে count গণনা করতে পারে।</p>
                    <p>• <strong>Badge upgrade হয়:</strong> পরের দিন বা অনেক পরে update করলে count ঠিকমতো নাও হতে পারে।</p>
                    <p style="margin-top:8px;padding:8px 10px;background:rgba(220,38,38,0.08);border-left:3px solid var(--primary-red);border-radius:0 6px 6px 0;"><strong>💡 টিপস:</strong> রক্ত দিয়ে hospital থেকে বের হওয়ার আগেই phone খুলে Blood Arena-তে update করে নিন — মাত্র ১ মিনিটের কাজ।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📋 রক্ত দেওয়ার পর update করার ধাপগুলো কী?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>রক্ত দেওয়ার পর নিচের ধাপ অনুসরণ করুন:</p>
                    <p><strong>১।</strong> Register ট্যাব → <strong>"Update My Info"</strong> ট্যাবে যান।</p>
                    <p><strong>২।</strong> Google বা ফোন নম্বর দিয়ে <strong>সাইন ইন</strong> করুন (আগে থেকে করা থাকলে সরাসরি লোড হবে)।</p>
                    <p><strong>৩।</strong> <strong>🩸 "আমি এইমাত্র রক্ত দিয়েছি"</strong> বাটনে চাপুন — আজকের তারিখ ও Willing: Yes স্বয়ংক্রিয়ভাবে set হয়ে যাবে।</p>
                    <p><strong>৪।</strong> <strong>"Save Changes"</strong> চাপুন।</p>
                    <p>এতে আপনার donation count বাড়বে, badge update হবে এবং আপনি পরের ১২০ দিনের জন্য "Not Available" হিসেবে mark হবেন — যাতে এই সময়ে কেউ unnecessarily call না করে।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔍 Badge দিয়ে কি filter করা যায়?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>হ্যাঁ! Donors ট্যাবে Badge filter আছে। New, Active, Hero বা Legend — যেকোনো badge-এর donor আলাদা করে দেখা যাবে।</p>
                    <p>Hero বা Legend filter করলে অভিজ্ঞ donors পাবেন — তারা রক্তদানে অভ্যস্ত, তাই সফলভাবে দেওয়ার সম্ভাবনা বেশি।</p>
                </div>
            </div>

            <!-- Category: গোপনীয়তা ও Permission -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">গোপনীয়তা ও Permission</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔔 Notification Permission কেন চাওয়া হয়?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Blood Arena নতুন Emergency Blood Request আসলে আপনাকে সাথে সাথে phone notification পাঠাতে পারে — এর জন্য permission দরকার।</p>
                    <p><strong>Allow করলে:</strong> নতুন request এলে আপনার notification bar-এ alert আসবে।</p>
                    <p><strong>Deny করলেও:</strong> App সম্পূর্ণ কাজ করবে, শুধু push notification আসবে না। In-app notification panel থেকে দেখতে পারবেন।</p>
                    <p style="margin-top:8px;padding:8px 10px;background:rgba(245,158,11,0.08);border-left:3px solid var(--accent-orange);border-radius:0 6px 6px 0;"><strong>⚠️ নোট:</strong> Allow বা Deny — উভয় ক্ষেত্রেই আপনার Device ID সংরক্ষণ করা হয়, যাতে ভবিষ্যতে সব device-এ push notification পাঠানো যায়।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📍 Location Permission কেন চাওয়া হয়?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Location permission দুটি কাজে ব্যবহার হয়:</p>
                    <p>• <strong>Nearby Donors:</strong> আপনার কাছের রক্তদাতা GPS দিয়ে খুঁজে বের করতে।</p>
                    <p>• <strong>Registration location log:</strong> Donor হিসেবে register করার সময় আপনার approximate GPS position সংরক্ষণ হয় — যাতে ম্যাপে দেখানো যায়।</p>
                    <p><strong>Deny করলেও:</strong> App চলবে, শুধু Nearby Donors ও Map feature কাজ করবে না।</p>
                    <p style="margin-top:8px;padding:8px 10px;background:rgba(245,158,11,0.08);border-left:3px solid var(--accent-orange);border-radius:0 6px 6px 0;"><strong>⚠️ নোট:</strong> Location allow বা deny — উভয় ক্ষেত্রেই Device ID সংরক্ষণ হয়।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🆔 Device ID কী? কেন সংরক্ষণ হয়?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Device ID হলো আপনার browser-এ তৈরি একটি unique anonymous identifier। এটি কোনো personal তথ্য (নাম, ফোন নম্বর) ধারণ করে না।</p>
                    <p><strong>কেন দরকার:</strong></p>
                    <p>• Push notification পাঠাতে — যাতে Emergency request এলে সব device-এ alert যায়।</p>
                    <p>• Services notification (Admin reply ইত্যাদি) আপনার নির্দিষ্ট device-এ পৌঁছাতে।</p>
                    <p><strong>কখন সংরক্ষণ হয়:</strong> Page load হওয়ার সাথে সাথে এবং Notification / Location permission Allow বা Deny করার সময়।</p>
                    <p><strong>মুছতে চাইলে:</strong> Settings → <b>🧹 Clear App Data</b> চাপুন — এতে Device ID সহ সব local data মুছে যাবে।</p>
                </div>
            </div>

            <!-- Category: Technical সমস্যা -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">Technical সমস্যা</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>⚠️ পেজ লোড হচ্ছে না / কাজ করছে না?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>• Browser refresh করুন (Pull-to-refresh বা F5)</p>
                    <p>• Internet connection চেক করুন</p>
                    <p>• Browser cache clear করুন: Chrome → Settings → Privacy → Clear browsing data</p>
                    <p>• অন্য browser-এ try করুন (Chrome recommended)</p>
                    <p>• সমস্যা থাকলে উপরের যোগাযোগ নম্বরে call করুন</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📱 Mobile-এ ভালো কাজ করে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>হ্যাঁ, Blood Arena সম্পূর্ণ mobile-friendly। নিচের bottom navigation bar দিয়ে সব section-এ যাওয়া যাবে। Chrome বা Samsung Internet browser-এ সবচেয়ে ভালো কাজ করে। "Add to Home Screen" করলে app-এর মতো ব্যবহার করা যাবে।</p>
                </div>
            </div>

            <!-- Category: Sign-in & Account -->
            <p style="font-size:0.7em;text-transform:uppercase;letter-spacing:2px;color:var(--primary-red);font-weight:700;margin:18px 0 10px;">সাইন ইন ও অ্যাকাউন্ট</p>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔐 সাইন ইন কীভাবে করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>উপরের <strong>"🔐 সাইন ইন"</strong> বাটনে চাপুন। দুটি উপায় আছে:</p>
                    <p>• <strong>Google:</strong> "Google দিয়ে চালিয়ে যান" চাপলেই হবে — সবচেয়ে সহজ।</p>
                    <p>• <strong>ফোন নম্বর:</strong> আপনার বাংলাদেশি (+8801...) নম্বর দিন → OTP পাঠান → SMS-এ আসা ৬ সংখ্যার কোড দিয়ে লগইন করুন।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>🔑 আগের Secret Code-এর কী হবে?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>Secret Code পদ্ধতি আর নেই — কোনো কোড মনে রাখার দরকার নেই। এখন শুধু <strong>Google বা ফোন নম্বর</strong> দিয়ে সাইন ইন করলেই আপনার তথ্য নিরাপদে পাওয়া যায় ও আপডেট করা যায়।</p>
                    <p>আগে যাঁরা ফোন নম্বর দিয়ে register করেছিলেন, তাঁরা <strong>একই ফোন নম্বরে OTP দিয়ে সাইন ইন</strong> করলে পুরনো profile স্বয়ংক্রিয়ভাবে যুক্ত হয়ে যাবে।</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>📲 Phone OTP আসছে না — কী করবো?</span>
                    <span class="faq-arrow">›</span>
                </div>
                <div class="faq-a">
                    <p>• নম্বরটি সঠিক বাংলাদেশি ফরম্যাটে আছে কিনা দেখুন: <strong>+8801XXXXXXXXX</strong></p>
                    <p>• কিছুক্ষণ অপেক্ষা করুন — SMS আসতে ১-২ মিনিট লাগতে পারে।</p>
                    <p>• বারবার চেষ্টা করলে সাময়িকভাবে block হতে পারে — একটু পরে আবার চেষ্টা করুন।</p>
                    <p>• সমস্যা থাকলে <strong>Google দিয়ে সাইন ইন</strong> করুন — এটি দ্রুত ও নির্ভরযোগ্য।</p>
                </div>
            </div>


            <div style="text-align:center;padding:20px 0 5px;color:var(--text-muted);font-size:0.8em;">
                <p>আরও প্রশ্ন থাকলে: <a href="tel:01518981827" style="color:var(--primary-red);font-weight:700;">০১৫১৮৯৮১৮২৭</a></p>
                <p style="margin-top:4px;opacity:0.5;">Blood Arena — v2.7.0</p>
            </div>
        </div>
    </div>
</div>

<!-- ========== GPS PERMISSION PROMPT (soft, non-blocking) ========== -->
<div class="popup-overlay" id="gpsPermPrompt">
    <div class="popup" style="max-width:420px;">
        <div style="font-size:3rem;text-align:center;margin-bottom:10px;">📍</div>
        <h3 style="text-align:center;color:var(--text-main);font-family:var(--font-heading);margin-bottom:10px;">Location Permission</h3>
        <p id="gpsPromptMsg" style="text-align:center;color:var(--text-muted);font-size:0.9em;line-height:1.6;margin-bottom:20px;">আপনার location log করা হবে — এটি শুধুমাত্র নিরাপত্তা ও জালিয়াতি প্রতিরোধের জন্য।</p>
        <div style="display:flex;gap:10px;">
            <button id="gpsAllowBtn" onclick="gpsAllow()" style="flex:1;background:var(--primary-red);color:#fff;margin:0;">📍 Allow</button>
            <button onclick="gpsSkip()" style="flex:1;background:transparent;border:1px solid var(--border-color);color:var(--text-muted);margin:0;box-shadow:none;">এড়িয়ে যান</button>
        </div>
        <p style="text-align:center;font-size:0.72em;color:var(--text-muted);margin-top:12px;">Location দিলে Nearby Donors feature আরো ভালো কাজ করবে।</p>
    </div>
</div>

<footer class="site-footer">

<!-- ==================== SMART INTERACTIVE LINKS ==================== -->
<div class="footer-links">
    <a href="#" onclick="openTermsModal(); return false;">📄 শর্তাবলী ও নীতিমালা</a>
    <a href="#" onclick="openAboutUsModal(); return false;">ℹ️ আমাদের কথা (About Us)</a>
<?php /* Blood Arena — index_part2.php */ ?>
</div>
<div style="margin-top:25px; font-size: 0.85em; color: #64748b;">&copy; <?php echo date("Y"); ?> Blood Arena. All rights reserved. &nbsp;|&nbsp; <span style="opacity:0.5;font-size:0.9em;">v2.5.8</span></div>
</footer>
<?php /* Blood Arena — index_part2.php */ ?>

<script><?php include __DIR__ . '/../assets/app.js.php'; ?></script>
<script><?php include __DIR__ . '/../assets/fx-3d.js.php'; ?></script>

<!-- ========== BOTTOM NAVIGATION BAR ========== -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
  <div class="mobile-bottom-nav-inner">

    <!-- Home -->
    <button class="mbn-item mbn-active" id="mbn-home" onclick="appSwitchPage('home')">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9.5z"/>
          <polyline points="9 21 9 13 15 13 15 21"/>
        </svg>
      </span>
      <span>Home</span>
    </button>

    <!-- Donors -->
    <button class="mbn-item" id="mbn-donors" onclick="appSwitchPage('donors')">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="7" r="4"/>
          <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          <path d="M21 21v-2a4 4 0 0 0-3-3.87"/>
        </svg>
      </span>
      <span>Donors</span>
    </button>

    <!-- Register -->
    <button class="mbn-item" id="mbn-register" onclick="appSwitchPage('register')">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="19" y1="8" x2="19" y2="14"/>
          <line x1="16" y1="11" x2="22" y2="11"/>
        </svg>
      </span>
      <span>Register</span>
    </button>

    <!-- Nearby -->
    <button class="mbn-item" id="mbn-nearby" onclick="appSwitchPage('nearby')">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </span>
      <span>Nearby</span>
    </button>

    <!-- Analytics -->
    <button class="mbn-item" id="mbn-more" onclick="appSwitchPage('more')">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6"  y1="20" x2="6"  y2="14"/>
        </svg>
      </span>
      <span>Stats</span>
    </button>

    <!-- Settings -->
    <button class="mbn-item" id="mbn-settings" onclick="openSettingsPanel()">
      <span class="mbn-pill">
        <svg class="mbn-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </span>
      <span>Settings</span>
    </button>

  </div>
</nav>

<script><?php include __DIR__ . '/../assets/i18n-dict.js.php'; ?></script>
<script><?php include __DIR__ . '/../assets/boot.js.php'; ?></script>
