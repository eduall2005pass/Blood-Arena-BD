/* === MODERN MEDICAL DESIGN TOKENS ===
   Default (no data-theme attr) = DARK. The app sets data-theme="light"
   by default, so LIGHT is what new users see. Both blocks share names —
   only values differ, keeping every var(--…) usage working. */
:root {
    /* ── Dark theme · PREMIUM GLASS (applies when data-theme attr is absent) ── */
    --bg-main: <?= COLOR_BG_MAIN ?>;
    --bg-card: #161d2b;                     /* opaque surface (cards/lists — fast, no bleed) */
    --bg-card-solid: #161d2b;               /* explicit opaque alias */
    --bg-glass: rgba(22, 29, 43, 0.72);     /* translucent — frosted fixed surfaces only */
    --primary-red: #f2555a;                 /* refined crimson */
    --primary-red-hover: #d83f48;
    --primary-red-soft: rgba(242, 85, 90, 0.14);
    --text-main: #e9eef7;
    --text-muted: #9aa7bd;
    --border-color: rgba(255, 255, 255, 0.08);
    --glass-border: rgba(255, 255, 255, 0.10);
    --glass-highlight: rgba(255, 255, 255, 0.06);
    --glass-blur: 14px;                      /* single blur value — whitelisted surfaces only */
    --input-bg: rgba(29, 38, 54, 0.72);
    --accent-orange: #f59e0b;
    --success: #10b981;
    --danger: #ef4444;
    --info: #3b82f6;
    --radius-sm: 10px;
    --radius-md: 14px;
    --radius-lg: 22px;
    --font-heading: 'Poppins', sans-serif;
    --font-body: 'Inter', 'Roboto', sans-serif;
    --shadow-glass: 0 1px 1px rgba(0,0,0,0.5), 0 10px 30px rgba(0,0,0,0.5), inset 0 1px 0 var(--glass-highlight);
    --glow-red: 0 6px 22px var(--primary-red-soft);
    /* Gradient-mesh background stops (static) */
    --mesh-1: rgba(210, 45, 54, 0.16);
    --mesh-2: rgba(99, 102, 241, 0.14);
    --mesh-3: rgba(14, 165, 233, 0.08);
    --btn-text: #ffffff;
    --footer-bg: #0a0f18;
    --footer-card-bg: rgba(255,255,255,0.05);
    --footer-card-border: rgba(255,255,255,0.1);
    --footer-text: #ffffff;
    --dc-zoom: 1;
}

[data-theme="light"] {
    /* ── Light theme · PREMIUM GLASS (DEFAULT for new users) ── */
    --bg-main: #eef2f9;
    --bg-card: #ffffff;                     /* opaque surface (cards/lists — fast, no bleed) */
    --bg-card-solid: #ffffff;               /* explicit opaque alias */
    --bg-glass: rgba(255, 255, 255, 0.72);  /* translucent — frosted fixed surfaces only */
    --text-main: #14233b;
    --text-muted: #5d6b82;
    --border-color: #e3e8f0;
    --glass-border: rgba(255, 255, 255, 0.55);
    --glass-highlight: rgba(255, 255, 255, 0.80);
    --glass-blur: 14px;
    --input-bg: rgba(241, 244, 249, 0.85);
    --shadow-glass: 0 1px 2px rgba(16,24,40,0.06), 0 8px 28px rgba(16,24,40,0.10), inset 0 1px 0 var(--glass-highlight);
    --glow-red: 0 6px 22px var(--primary-red-soft);
    --primary-red: <?= COLOR_PRIMARY ?>;
    --primary-red-hover: <?= COLOR_PRIMARY_HOVER ?>;
    --primary-red-soft: rgba(220, 39, 67, 0.12);
    /* Gradient-mesh background stops (static) */
    --mesh-1: rgba(220, 39, 67, 0.10);
    --mesh-2: rgba(99, 102, 241, 0.10);
    --mesh-3: rgba(56, 189, 248, 0.08);
    --btn-text: #ffffff;
    --accent-orange: #d97706;
    --success: #0f9d6e;
    --info: #2563eb;
    --danger: #e1322f;
    --footer-bg: #0f1b2d;
    --footer-card-bg: rgba(255,255,255,0.08);
    --footer-card-border: rgba(255,255,255,0.15);
    --footer-text: #ffffff;
}

*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;text-size-adjust:100%;}
body{overflow-anchor:none;}
body{font-family:var(--font-body);background:var(--bg-main);color:var(--text-main);line-height:1.6;overflow-x:hidden;overscroll-behavior-y:none;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeSpeed;}  

/* Static gradient-mesh background (premium glass depth, no animation/repaint) */
body::before{
    content:"";
    position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
        radial-gradient(60% 50% at 12% 8%, var(--mesh-1) 0%, transparent 60%),
        radial-gradient(55% 45% at 88% 18%, var(--mesh-2) 0%, transparent 62%),
        radial-gradient(70% 55% at 50% 100%, var(--mesh-3) 0%, transparent 60%);
}

header{ 
    background: linear-gradient(135deg, #0d0f18 0%, #12091a 40%, #1a0a0a 100%);
    color:white; padding:8px 18px; display:flex; align-items:center; justify-content:space-between;
    flex-wrap:nowrap;
    border-bottom: 1px solid transparent;
    border-image: linear-gradient(90deg, transparent, rgba(220,38,38,0.55) 30%, rgba(251,113,133,0.4) 60%, transparent) 1;
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 50; 
    box-shadow: 0 1px 0 rgba(220,38,38,0.18), 0 4px 32px rgba(0,0,0,0.65), 0 0 0 1px rgba(220,38,38,0.06);
    height: 76px;
    /* NOTE: NO contain here — it creates a stacking context that traps the notification panel */
}
[data-theme="light"] header {
    background: linear-gradient(135deg, #ffffff 0%, #fff5f5 50%, #fef2f2 100%);
    border-image: linear-gradient(90deg, transparent, rgba(220,38,38,0.3) 30%, rgba(220,38,38,0.5) 60%, transparent) 1;
    box-shadow: 0 1px 0 rgba(220,38,38,0.12), 0 4px 24px rgba(0,0,0,0.08), 0 0 0 1px rgba(220,38,38,0.05);
}
[data-theme="light"] header h1 {
    background: linear-gradient(90deg, #7f1d1d 0%, #dc2626 45%, #b71d38 80%, #9f1239 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    text-shadow: none !important;
    filter: drop-shadow(0 1px 3px rgba(220,38,38,0.25)) !important;
}
@media(min-width: 651px) {
    header { left: 230px !important; }
}
/* Compensate for fixed header only (nav bar removed) */
body { padding-top: 76px !important; }
@media(max-width: 650px) { body { padding-top: 76px !important; } }

header img{height: 52px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));}
.header-logo-left { height: 60px !important; width: 60px !important; border-radius: 14px; object-fit: contain; flex-shrink: 0; }
header h1{
    font-family: var(--font-heading); font-weight:900; font-size:2.9rem; letter-spacing:0.5px;
    flex: 1 1 auto; text-align: center; margin: 0 10px;
    background: linear-gradient(90deg, #fff 0%, #fda4af 35%, #fb7185 60%, #f9a8d4 85%, #fff 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 0 12px rgba(220,38,38,0.35));
    text-shadow: none;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;
}

/* Right-side header cluster: bell + account avatar */
.header-actions {
    display: inline-flex; align-items: center; gap: 8px;
    flex-shrink: 0;
}
.header-account-btn {
    background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.12);
    cursor: pointer; border-radius: 50%; width: 42px; height: 42px;
    /* min-height:unset — keep the avatar a perfect circle; the global mobile
       rule `button{min-height:44px}` would otherwise stretch it into an oval */
    min-height: unset;
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
    color: var(--text-main); padding: 0; margin: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2); position: relative;
    overflow: hidden; flex-shrink: 0;
}
.header-account-btn:hover { transform: scale(1.08); box-shadow: 0 4px 14px rgba(220,38,38,0.4); border-color: var(--primary-red); }
.header-account-btn img {
    width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;
}
.header-account-fallback {
    width: 100%; height: 100%; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; font-weight: 800; font-family: var(--font-heading);
    background: linear-gradient(135deg, var(--primary-red), #f59e0b); color: #fff;
}

/* Theme Toggle Button */
.theme-toggle { 
    background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.12); 
    font-size: 1.3rem; cursor: pointer; border-radius: 50%; width: 42px; height: 42px; 
    display: flex; align-items: center; justify-content: center; 
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease; 
    color: var(--text-main); margin:0; padding:0; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.theme-toggle:hover { transform: scale(1.12) rotate(20deg); box-shadow: 0 4px 14px rgba(224,36,36,0.3); border-color: var(--primary-red); }

.container{width:95%; max-width:1200px; margin:auto; padding: 0 10px;}  

form{ 
    background: var(--bg-card); padding:30px; border-radius: var(--radius-lg); 
    border: 1px solid var(--border-color); box-shadow: var(--shadow-glass); 
    transition: border-color 0.3s ease;
}
form h2{text-align:center; margin-bottom:25px; color: var(--primary-red); font-family: var(--font-heading); font-weight: 700; font-size: 1.8rem;}  

.input-group { display: flex; flex-direction: column; gap: 15px; }
@media(min-width: 768px) {
    .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
}

input, select, textarea{ 
    width:100%; padding:13px 16px; margin: 8px 0; border-radius: var(--radius-md); 
    border: 1px solid var(--border-color); font-size:1rem; outline:none; 
    background: var(--input-bg); color: var(--text-main); font-family: var(--font-body); 
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; 
    cursor: text; -webkit-appearance: none; appearance: none; 
}  
input::placeholder, textarea::placeholder { color: var(--text-muted); }
input:focus, select:focus, textarea:focus {
    border-color: var(--primary-red);
    background: var(--primary-red-soft);
    box-shadow: 0 0 0 3px var(--primary-red-soft);
}
select option { background: var(--bg-main); color: var(--text-main); font-weight:500; }
select optgroup { color: var(--primary-red); font-weight: bold; background: var(--bg-main); font-style: normal; }
select { cursor: pointer; }


button{
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary-red) 88%, #fff) 0%, var(--primary-red-hover) 100%);
    color:var(--btn-text); border:none; padding: 13px 24px; border-radius: var(--radius-md);
    cursor:pointer; font-weight:600; font-size: 1rem; font-family: var(--font-heading);
    transition: all 0.22s ease; width: 100%; margin-top: 15px; letter-spacing: 0.3px;
    box-shadow: var(--glow-red), inset 0 1px 0 rgba(255,255,255,0.18);
}
button:hover{
    background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-red-hover) 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 28px var(--primary-red-soft), 0 4px 10px rgba(0,0,0,0.18);
}
button:active { transform: translateY(0); box-shadow: 0 2px 8px var(--primary-red-soft); }

/* ── Override: compact inline buttons that must NOT be full-width ── */
.req-tab-btn, .req-bg-chip, .req-bg-clear,
.btn-deny-notif, .btn-emergency, .btn-view-requests,
.shift-btn, .sd-toggle-btn, .willing-btn,
.phone-link, .phone-link-disabled, .dc-call-btn, .dc-call-btn-disabled {
    width: auto !important;
    margin-top: 0 !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.note{
    font-size:0.85em; color: var(--accent-orange); margin-bottom:20px; display:block; 
    text-align:center; background: rgba(245, 158, 11, 0.08); padding: 12px 16px; 
    border-radius: var(--radius-sm); border-left: 3px solid var(--accent-orange);
    line-height: 1.6;
}

/* Real Button Look for Call */
.phone-link { 
    background: linear-gradient(135deg, var(--info), #2563eb); color: #fff !important; 
    padding: 10px 16px; border-radius: 8px; font-weight: 700; font-family: var(--font-heading);
    cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; border: none;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); font-size: 0.95em; width: 100%; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;
} 
.phone-link:hover { transform: scale(1.05) translateY(-2px); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.6); }
.unselectable { -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }

/* Highly Interactive Report Button */
.report-btn-footer { background: var(--input-bg); color: var(--danger); padding: 16px 30px; font-size: 1.05em; font-family: var(--font-heading); font-weight: 700; border-radius: 40px; width: auto; margin: 30px auto; display: flex; align-items: center; justify-content: center; gap: 10px; border: 2px solid var(--danger); cursor: pointer; transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);}
.report-btn-footer:hover { background: var(--danger); color: #fff; transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 25px rgba(239, 68, 68, 0.5);}

.call-notice-wrapper { overflow: hidden; white-space: nowrap; margin-top: 25px; background: rgba(220, 38, 38, 0.05); padding: 12px 0; border-radius: var(--radius-md); border: 1px solid rgba(220, 38, 38, 0.2); box-shadow: inset 0 0 10px rgba(0,0,0,0.05);}
.call-notice-text { display: inline-block; padding-left: 100%; animation: marquee-call 15s linear infinite; color: var(--accent-orange); font-size: 0.95em; font-weight: 500; font-family: var(--font-body); will-change: transform; }
@keyframes marquee-call { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

.donor-table-wrapper{overflow-x:auto; margin-top:15px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-glass); background: var(--bg-card);}  
.donor-table-wrapper::-webkit-scrollbar { height: 6px; }
.donor-table-wrapper::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
.donor-table-wrapper::-webkit-scrollbar-thumb { background: rgba(224,36,36,0.3); border-radius: 10px; }
.donor-table{ width:100%; border-collapse:collapse; min-width: 700px; }  
.donor-table th, .donor-table td{padding:13px 16px; text-align:center; border-bottom:1px solid var(--border-color); font-size:0.92em;}  
.donor-table th{background: rgba(224,36,36,0.07); color: var(--text-main); font-family: var(--font-heading); font-weight: 600; letter-spacing: 0.5px; white-space: nowrap;}  
.donor-table tr:hover { background: rgba(255,255,255,0.03); transition: background 0.15s ease; }
.donor-table tr:last-child td { border-bottom: none; }

/* Blood group badge */
.blood-badge { display:inline-block; font-weight:800; font-size:0.95em; padding:3px 10px; border-radius:20px; letter-spacing:0.5px; }
.bgApos  { background:rgba(231,76,60,0.15);  color:#e74c3c; border:1px solid rgba(231,76,60,0.3); }
.bgAneg  { background:rgba(192,57,43,0.15);  color:#c0392b; border:1px solid rgba(192,57,43,0.3); }
.bgBpos  { background:rgba(52,152,219,0.15); color:#3498db; border:1px solid rgba(52,152,219,0.3); }
.bgBneg  { background:rgba(41,128,185,0.15); color:#2980b9; border:1px solid rgba(41,128,185,0.3); }
.bgABpos { background:rgba(155,89,182,0.15); color:#9b59b6; border:1px solid rgba(155,89,182,0.3); }
.bgABneg { background:rgba(142,68,173,0.15); color:#8e44ad; border:1px solid rgba(142,68,173,0.3); }
.bgOpos  { background:rgba(243,156,18,0.15); color:#f39c12; border:1px solid rgba(243,156,18,0.3); }
.bgOneg  { background:rgba(230,126,34,0.15); color:#e67e22; border:1px solid rgba(230,126,34,0.3); }

.label-icon { margin-right:3px; }
.serial-num { color:var(--text-muted); font-size:0.85em; font-weight:600; }
.available{color: var(--success); font-weight:600; background: rgba(16, 185, 129, 0.1); padding: 6px 12px; border-radius: 20px; display: inline-block; border: 1px solid rgba(16, 185, 129, 0.2);}  
.notavailable{color: var(--danger); font-weight:600; background: rgba(239, 68, 68, 0.1); padding: 6px 12px; border-radius: 20px; display: inline-block; border: 1px solid rgba(239, 68, 68, 0.2);}  
.no-data { padding: 40px; text-align: center; color: var(--text-muted); font-weight: 500; }

.quick-shift-container { 
    display: flex; 
    overflow-x: auto; 
    gap: 10px; 
    padding: 10px 12px; 
    scrollbar-width: none; 
    -ms-overflow-style: none; 
    scroll-behavior: smooth;
    position: sticky;
    top: 118px; /* header(76) + app-page-header(42) */
    z-index: 28;
    background: var(--bg-main);
    border-bottom: 1px solid var(--border-color);
    margin: 0 -10px;
}
@media(min-width: 651px) {
    .quick-shift-container {
        top: 118px;
        margin: 0;
        border-radius: 0;
    }
}
.quick-shift-container::-webkit-scrollbar { display: none; }

.shift-btn { flex: 0 0 auto; background: var(--input-bg); color: var(--text-main); border: 2px solid var(--border-color); padding: 10px 24px; border-radius: 30px; cursor: pointer; font-weight: 600; font-family: var(--font-heading); transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; text-align: center; min-width: 80px; width: auto; margin: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05);}
.shift-btn:hover { background: rgba(128,128,128,0.2); box-shadow: 0 3px 10px rgba(0,0,0,0.12); border-color: var(--primary-red);}
.shift-btn.active { background: var(--primary-red); color: white; border-color: var(--primary-red); box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5); font-weight: 700;}
.shift-btn.active::after { content: "✓"; margin-left: 6px; font-size: 0.9em; }

.filter-container { 
    background: var(--bg-card); padding: 22px; border-radius: var(--radius-lg); margin-top: 20px; 
    border: 1px solid var(--border-color); box-shadow: var(--shadow-glass);
}
.filter-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
@media(min-width: 768px) {
    .filter-grid { grid-template-columns: 2fr 1fr 1fr 1fr; }
}

/* ====================== COMPACT BEAUTIFUL STATS CARDS ====================== */
/* Blood-group cards mirror the analytics KPI cards (rich card, glowing top bar) */
.stats-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin: 25px auto 35px; padding: 0 10px; max-width: 640px; }
.stat-card {
    background: var(--bg-card); padding: 18px 12px; border-radius: 16px; text-align: center;
    border: 1px solid var(--border-color);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    cursor: pointer; position: relative; overflow: hidden; box-shadow: var(--shadow-glass);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    transform: translateZ(0);
    will-change: transform;
}
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 14px 32px rgba(0,0,0,0.2); }
.stat-card:active { transform: scale(0.97); }
/* Always-visible "tappable" cue — mobile has no hover, so make clickability explicit */
.stat-tap-hint {
    margin-top: 8px; z-index: 1;
    font-size: 0.6em; font-weight: 700; letter-spacing: 0.3px; white-space: nowrap;
    color: var(--primary-red);
    background: rgba(220,38,38,0.10);
    border: 1px solid rgba(220,38,38,0.22);
    border-radius: 20px; padding: 3px 11px;
}

.stat-card h4 { font-size: 3rem; font-weight: 900; margin-bottom: 4px; letter-spacing: 0.5px; font-family: var(--font-heading); line-height: 1.1; z-index: 1;}
.stat-card .count { font-size: 1.1em; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 4px; z-index: 1;}

/* Colors for Stats — glowing top accent bars (like KPI cards) */
.blood-Aplus::before { background: linear-gradient(90deg,#e74c3c,#ff6b5e); box-shadow: 0 0 8px rgba(231,76,60,0.5); } .blood-Aplus h4 { color: #e74c3c; }
.blood-Aminus::before { background: linear-gradient(90deg,#c0392b,#e05545); box-shadow: 0 0 8px rgba(192,57,43,0.5); } .blood-Aminus h4 { color: #c0392b; }
.blood-Bplus::before { background: linear-gradient(90deg,#3498db,#5dade2); box-shadow: 0 0 8px rgba(52,152,219,0.5); } .blood-Bplus h4 { color: #3498db; }
.blood-Bminus::before { background: linear-gradient(90deg,#2980b9,#4a9fd4); box-shadow: 0 0 8px rgba(41,128,185,0.5); } .blood-Bminus h4 { color: #2980b9; }
.blood-ABplus::before { background: linear-gradient(90deg,#9b59b6,#b87fd0); box-shadow: 0 0 8px rgba(155,89,182,0.5); } .blood-ABplus h4 { color: #9b59b6; }
.blood-ABminus::before{ background: linear-gradient(90deg,#8e44ad,#a865c4); box-shadow: 0 0 8px rgba(142,68,173,0.5); } .blood-ABminus h4 { color: #8e44ad; }
.blood-Oplus::before { background: linear-gradient(90deg,#f39c12,#f7b541); box-shadow: 0 0 8px rgba(243,156,18,0.5); } .blood-Oplus h4 { color: #f39c12; }
.blood-Ominus::before { background: linear-gradient(90deg,#e67e22,#f0974a); box-shadow: 0 0 8px rgba(230,126,34,0.5); } .blood-Ominus h4 { color: #e67e22; }

.pagination{text-align:center; margin-top:30px; display: flex; justify-content: center; flex-wrap:wrap; gap: 8px; margin-bottom: 40px;}  
.pagination a{display:inline-flex; align-items:center; justify-content:center; min-width: 40px; height: 40px; padding: 0 12px; background: var(--input-bg); color: var(--text-main); border-radius: var(--radius-sm); text-decoration:none; font-size:0.95em; transition: background 0.2s ease, transform 0.2s ease; border: 1px solid var(--border-color); font-weight: 500;}  
.pagination a:hover{ background: rgba(128,128,128,0.2); transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.1); }
.pagination .active-page { background: var(--primary-red) !important; color: #fff !important; border-color: var(--primary-red); box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);}

/* FOOTER STYLES (Light & Dark Support) */
footer{ background: var(--footer-bg); color: var(--footer-text); padding: 50px 20px 30px; text-align:center; display:flex; flex-direction:column; gap:30px; align-items:center; border-top: 1px solid var(--border-color); margin-top: 50px;}  

.footer-card-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; width: 100%;}
.footer-card{background: var(--footer-card-bg); padding:25px; border-radius: var(--radius-lg); width:260px; border: 1px solid var(--footer-card-border); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;}  
.footer-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); border-color: var(--primary-red);}
.footer-card img{width:100px; height:100px; object-fit:cover; border-radius:50%; border:3px solid var(--primary-red); margin-bottom:15px; padding: 3px; background: #000;}  
.footer-card .developed-by{font-size:0.85em; color: var(--text-muted); margin-bottom:8px; text-transform: uppercase; letter-spacing: 1px;}  
.footer-card span{font-weight:600; font-family: var(--font-heading); color: var(--footer-text); font-size:1.1em; display: block;}  
.footer-card p { text-align: center; word-break: break-word; font-size: 0.85em; color: var(--text-muted); margin-top: 12px; font-style: italic; line-height: 1.5; }

/* Interactive Footer Links */
.footer-links { margin-top: 10px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; width: 100%; }
.footer-links a { 
    background: var(--footer-card-bg); color: var(--footer-text); text-decoration: none; 
    font-weight: 600; font-family: var(--font-heading); font-size: 1.05em; 
    padding: 12px 25px; border-radius: 30px; border: 2px solid var(--footer-card-border);
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    display: inline-flex; align-items: center; gap: 8px;
}
.footer-links a:hover { background: var(--info); color: #ffffff; border-color: var(--info); transform: translateY(-5px) scale(1.05); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4); }

/* ===== PAGE FOOTER (all navigation tabs) ===== */
.page-footer-bar {
    text-align: center;
    padding: 18px 16px 28px;
    margin-top: 30px;
    border-top: 1px solid var(--border-color);
    font-size: 0.78em;
    color: var(--text-muted);
    letter-spacing: 0.3px;
    font-family: var(--font-body);
    background: transparent;
}
.page-footer-bar span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 6px 16px;
    font-weight: 500;
}
[data-theme="light"] .page-footer-bar span {
    background: rgba(80,110,200,0.06);
    border-color: rgba(80,110,200,0.14);
    color: #4b5680;
}

/* === MODALS & POPUPS === */
.popup-overlay{ 
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%; 
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center; 
    visibility: hidden;
    opacity: 0; 
    transition: opacity 0.25s ease, visibility 0.25s ease; 
    z-index: 10100; /* above mobile-bottom-nav(9999) and settings(9990) */
}
@media(min-width: 651px) {
    .popup-overlay { left: 230px !important; width: calc(100% - 230px) !important; }
}

/* ── Auth wait overlay — Google সাইন-ইন প্রসেস হওয়ার সময় "অপেক্ষা করুন" ── */
.auth-wait-overlay {
    position: fixed; inset: 0;
    display: none; align-items: center; justify-content: center;
    z-index: 10600; /* above auth modal (10100) */
    background: rgba(10,14,22,0.82);
    padding: 24px;
}
.auth-wait-overlay.show { display: flex; }
.auth-wait-card {
    background: var(--card-bg, #141b2a);
    border: 1px solid var(--border-color, rgba(255,255,255,0.12));
    border-radius: 18px; padding: 30px 26px; max-width: 320px; width: 100%;
    text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.5);
}
.auth-wait-spinner {
    width: 44px; height: 44px; display: inline-block;
    border: 4px solid rgba(255,255,255,0.18);
    border-top-color: var(--primary-red, #dc2743);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
.auth-wait-title {
    margin: 16px 0 6px; font-family: var(--font-heading);
    font-weight: 700; font-size: 1.05em; color: var(--text-main, #fff);
}
.auth-wait-sub {
    margin: 0; font-size: 0.82em; line-height: 1.6; color: var(--text-muted, #94a3b8);
}

/* Validation/result popup must always render above all other popups */
#popup {
    z-index: 10200;
}
.popup{
    background: var(--bg-glass);
    backdrop-filter: blur(var(--glass-blur)); -webkit-backdrop-filter: blur(var(--glass-blur));
    padding: 32px 24px;
    border-radius: var(--radius-lg);
    text-align: center;
    transform: scale(0.92) translateY(16px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    width: 90%; max-width: 460px;
    box-shadow: var(--shadow-glass);
    border: 1px solid var(--glass-border);
    max-height: 88vh;
    overflow-y: auto;
}
.popup-overlay.active { visibility: visible; opacity: 1; }
.popup-overlay.active .popup { transform: scale(1) translateY(0); }
.tick{font-size:55px; margin-bottom:15px; line-height: 1;}
.success-tick{color: var(--success); filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.4));}
.error-tick{color: var(--danger); filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4));}
.warning-tick{color: var(--accent-orange); filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.4));}

/* ── Modern animated tick / cross SVG (popup success & error icons) ── */
.tick-svg { width: 78px; height: 78px; display: block; margin: 0 auto; }
.tick-svg-circle {
    stroke: currentColor;
    stroke-width: 2.5;
    stroke-dasharray: 151;
    stroke-dashoffset: 151;
    opacity: 0.85;
    animation: tickDrawCircle 0.55s cubic-bezier(0.65,0,0.45,1) forwards;
}
.tick-svg-mark {
    stroke: currentColor;
    stroke-width: 4.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 64;
    stroke-dashoffset: 64;
    animation: tickDrawMark 0.4s 0.45s cubic-bezier(0.65,0,0.45,1) forwards;
}
@keyframes tickDrawCircle { to { stroke-dashoffset: 0; } }
@keyframes tickDrawMark  { to { stroke-dashoffset: 0; } }
@media (prefers-reduced-motion: reduce) {
    .tick-svg-circle, .tick-svg-mark { animation: none !important; stroke-dashoffset: 0 !important; }
}

/* ── Social "Connect us on" footer buttons ── */
.social-connect { text-align: center; margin: 30px auto 6px; }
.social-connect-label {
    font-size: 0.82em; color: var(--text-muted); font-weight: 600;
    margin-bottom: 12px; letter-spacing: 0.3px;
}
.social-connect-row { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; }
.social-btn {
    width: 44px; height: 44px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}
.social-btn svg { width: 22px; height: 22px; }
.social-btn:hover { transform: translateY(-4px) scale(1.08); filter: brightness(1.08); }
.social-btn:active { transform: translateY(-1px) scale(1.02); }
.sc-fb { background: #1877f2; box-shadow: 0 4px 12px rgba(24,119,242,0.4); }
.sc-tg { background: #0088cc; box-shadow: 0 4px 12px rgba(0,136,204,0.4); }
.sc-yt { background: #ff0000; box-shadow: 0 4px 12px rgba(255,0,0,0.4); }
.sc-wa { background: #25d366; box-shadow: 0 4px 12px rgba(37,211,102,0.4); }

.scroll-content { text-align: left; max-height: 400px; overflow-y: auto; background: var(--input-bg); padding: 20px; border-radius: var(--radius-md); margin: 20px 0; font-size: 0.95em; color: var(--text-muted); border: 1px solid var(--border-color); }
.scroll-content::-webkit-scrollbar { width: 6px; }
.scroll-content::-webkit-scrollbar-track { background: transparent; }
.scroll-content::-webkit-scrollbar-thumb { background: rgba(128,128,128,0.4); border-radius: 10px; }
.scroll-content h4 { color: var(--text-main); margin-top: 15px; margin-bottom: 8px; font-family: var(--font-heading); font-weight: 600;}
.scroll-content p { margin-bottom: 12px; line-height: 1.7; }
.scroll-content strong { color: var(--text-main); }

.sponsor-banner { background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(0,0,0,0) 100%); color: var(--text-main); padding: 16px 20px; text-align: center; border-left: 4px solid var(--primary-red); border-right: 4px solid var(--primary-red); font-family: var(--font-body); border-radius: var(--radius-md); margin: 20px auto; max-width: 1200px; width: 95%; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);}
.sponsor-banner p { font-size: 0.95em; margin: 0; line-height: 1.6; font-weight: 500;}
.sponsor-banner .highlight-number { display: inline-block; margin-top: 5px;}
.sponsor-banner .highlight-number a { color: var(--accent-orange); font-weight: 700; font-size: 1.1em; padding: 4px 12px; background: rgba(245, 158, 11, 0.1); border-radius: 20px; border: 1px dashed rgba(245, 158, 11, 0.3); text-decoration: none; transition: background 0.2s ease;}
.sponsor-banner .highlight-number a:hover { background: rgba(245, 158, 11, 0.2); transform: scale(1.05); display: inline-block;}

/* ===== Donate Us info page ===== */
.donate-hero { text-align: center; margin-bottom: 18px; }
.donate-hero-ic { font-size: 2.6rem; line-height: 1; display: block; margin-bottom: 6px; filter: drop-shadow(0 4px 10px rgba(220,38,38,0.35)); }
.donate-hero-title { color: var(--primary-red); font-family: var(--font-heading); font-weight: 800; margin: 0 0 2px; font-size: 1.35em; }
.donate-hero-sub { color: var(--text-muted); font-size: 0.82em; margin: 0; letter-spacing: 0.3px; }
.donate-method { background: var(--input-bg); border: 1px dashed var(--primary-red); border-radius: var(--radius-md); padding: 16px 14px; margin: 18px 0; text-align: center; }
.donate-method-label { display: inline-block; font-size: 0.74em; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--primary-red); margin-bottom: 10px; }
.donate-number-row { display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; }
.donate-number { font-family: monospace; font-size: 1.5em; font-weight: 700; letter-spacing: 2px; color: var(--text-main); }
.donate-copy-btn { background: rgba(220,38,38,0.10); color: var(--primary-red); border: 1px solid rgba(220,38,38,0.35); border-radius: 20px; padding: 7px 16px; font-weight: 700; font-size: 0.82em; cursor: pointer; transition: background 0.18s ease, transform 0.18s ease; }
.donate-copy-btn:hover { background: var(--primary-red); color: #fff; transform: scale(1.05); }
.donate-copy-btn:active { transform: scale(0.97); }
.donate-method-hint { color: var(--text-muted); font-size: 0.8em; margin: 12px 0 0; }
.donate-thanks { text-align: center; color: var(--text-muted); font-size: 0.9em; margin-top: 18px; line-height: 1.7; }
.donate-thanks em { color: var(--text-main); font-style: normal; font-weight: 600; }

#callConfirmBox h3 { color: var(--text-main); margin-bottom: 20px; font-family: var(--font-heading); font-weight: 600; font-size: 1.5rem;}
.caller-info-item { background: var(--input-bg); padding: 15px; border-radius: var(--radius-md); margin-bottom: 15px; text-align: left; border-left: 3px solid var(--info); }
.caller-info-item small { color: var(--text-muted); font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;}
.caller-info-item p { font-weight: 500; color: var(--text-main); margin-top: 5px; font-size: 1.05em;}

.location-blocked-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000001; }
.location-blocked-box { background: var(--bg-card); border: 1px solid var(--border-color); border-top: 4px solid var(--primary-red); border-radius: var(--radius-lg); padding: 40px 30px; max-width: 460px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.5); width: 90%;}
.location-blocked-box .icon { font-size: 70px; margin-bottom: 20px; filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.4)); }
.location-blocked-box h2 { color: var(--text-main); font-family: var(--font-heading); margin-bottom: 15px; font-size: 1.6rem; font-weight: 600;}
.location-blocked-box p { color: var(--text-muted); line-height: 1.6; margin-bottom: 30px; font-size: 0.95rem; }

/* Highly Interactive Tab Header */
.tab-header { 
    display:flex; background: var(--input-bg); border-radius: var(--radius-lg); padding: 6px; 
    margin: 25px 0 15px; border: 1px solid var(--border-color); position: relative; z-index: 1; 
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.15);
}
.tab-btn { 
    flex:1; padding: 14px 10px; background: transparent; border: 2px solid transparent; 
    color: var(--text-muted); font-weight: 700; border-radius: var(--radius-md); cursor:pointer; 
    transition: transform 0.2s cubic-bezier(0.34,1.1,0.64,1), background 0.15s ease; font-size: 1em; margin: 0; 
    font-family: var(--font-heading); text-transform: uppercase; letter-spacing: 0.5px;
}
.tab-btn:hover { color: var(--text-main); background: rgba(255,255,255,0.05); transform: translateY(-2px); }
.tab-btn.active { 
    background: var(--bg-card); color: var(--primary-red); 
    box-shadow: 0 4px 16px rgba(0,0,0,0.2); 
    border: 2px solid rgba(224,36,36,0.2); 
    border-bottom: 3px solid var(--primary-red);
}
.tab-content { display:none; animation: fadeIn 0.22s ease; }
.tab-content.active { display:block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

.secret-box { background: var(--input-bg); padding: 18px; border-radius: var(--radius-md); text-align:center; border: 1px dashed var(--accent-orange); margin: 20px 0; font-size: 1.4em; font-weight: 700; letter-spacing: 2px; color: var(--text-main); font-family: monospace; box-shadow: inset 0 0 10px rgba(0,0,0,0.1);}
.copy-btn { background: rgba(245, 158, 11, 0.1); color: var(--accent-orange); padding: 12px 24px; border: 2px solid var(--accent-orange); border-radius: var(--radius-md); font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin: 0 auto; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;}
.copy-btn:hover { background: var(--accent-orange); color: #000; transform: scale(1.05); box-shadow: 0 6px 15px rgba(245, 158, 11, 0.4);}

.countdown-btn { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); font-weight: 600; box-shadow: none;}
.countdown-btn:disabled { opacity: 0.6; cursor: not-allowed; border-color: transparent; color: var(--text-muted); background: var(--input-bg);}
.countdown-btn.active, .countdown-btn:not(:disabled) { background: var(--success); color: #000; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);}

/* Skeleton Loading */
.skeleton { background: linear-gradient(90deg, rgba(128,128,128,0.1) 25%, rgba(128,128,128,0.2) 50%, rgba(128,128,128,0.1) 75%); background-size: 200% 100%; animation: skeleton-blink 1.5s infinite; height: 24px; border-radius: 6px; width: 100%; }
@keyframes skeleton-blink { to { background-position-x: -200%; } }
.skeleton-row td { padding: 18px 16px !important; }

/* ============================================================
   MOBILE CARD STYLES
   ============================================================ */
.donor-cards-container { display: none; margin-top: 10px; }
@media(max-width:767px) {
    .donor-cards-container { display: block !important; }
}

.dc-skeleton { padding: 10px; min-height: 54px; }

/* ============================================================
   LIGHT MODE — RICH COLORFUL DESIGN OVERRIDES
   ============================================================ */

/* Page background — base color; depth comes from the static mesh in body::before */
[data-theme="light"] body {
    background: var(--bg-main);
}

/* Header — refined crimson gradient */
[data-theme="light"] header {
    background: linear-gradient(135deg, #b71d38 0%, #dc2743 50%, #9f1239 100%) !important;
    color: #ffffff !important;
    border-bottom: none !important;
    box-shadow: 0 4px 22px rgba(220, 39, 67, 0.38), inset 0 1px 0 rgba(255,255,255,0.18) !important;
}
[data-theme="light"] header h1 {
    background: linear-gradient(90deg, #fff, #fecdd3) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
}
[data-theme="light"] header img { filter: drop-shadow(0 2px 6px rgba(0,0,0,0.25)) brightness(1.1) !important; }
[data-theme="light"] .theme-toggle {
    background: rgba(255,255,255,0.2) !important;
    border-color: rgba(255,255,255,0.4) !important;
    color: #ffffff !important;
}
[data-theme="light"] .theme-toggle:hover {
    background: rgba(255,255,255,0.35) !important;
    border-color: #ffffff !important;
}

/* Sponsor banner */
[data-theme="light"] .sponsor-banner {
    background: linear-gradient(135deg, rgba(225,29,72,0.08), rgba(99,102,241,0.06)) !important;
    border-left-color: #dc2743 !important;
    border-right-color: #6366f1 !important;
}

/* Stat cards — colorful gradient backgrounds */
[data-theme="light"] .stat-card {
    background: linear-gradient(145deg, #ffffff, #f8f5ff) !important;
    box-shadow: 0 4px 16px rgba(99,102,241,0.12) !important;
    border-color: rgba(99,102,241,0.15) !important;
}
[data-theme="light"] .stat-card:hover {
    box-shadow: 0 10px 28px rgba(99,102,241,0.22) !important;
}

/* Tab header — indigo gradient accent */
[data-theme="light"] .tab-header {
    background: linear-gradient(135deg, #ede9fe, #e0e7ff) !important;
    border-color: rgba(99,102,241,0.2) !important;
}
[data-theme="light"] .tab-btn { color: #4338ca !important; }
[data-theme="light"] .tab-btn:hover { background: rgba(99,102,241,0.12) !important; color: #1d4ed8 !important; }
[data-theme="light"] .tab-btn.active {
    background: #ffffff !important;
    color: #dc2743 !important;
    border-color: rgba(99,102,241,0.2) !important;
    border-bottom-color: #dc2743 !important;
    box-shadow: 0 6px 20px rgba(225,29,72,0.15) !important;
}

/* Forms — white card with colored left border accent */
[data-theme="light"] form {
    background: rgba(255,255,255,0.95) !important;
    border-color: rgba(99,102,241,0.15) !important;
    border-left: 4px solid #dc2743 !important;
    box-shadow: 0 8px 30px rgba(99,102,241,0.1) !important;
}
[data-theme="light"] .filter-container {
    background: rgba(255,255,255,0.9) !important;
    border-color: rgba(99,102,241,0.15) !important;
    border-top: 3px solid #6366f1 !important;
    box-shadow: 0 6px 20px rgba(99,102,241,0.1) !important;
}

/* Inputs — indigo tinted bg */
[data-theme="light"] input,
[data-theme="light"] select,
[data-theme="light"] textarea {
    background: #eef2ff !important;
    border-color: rgba(99,102,241,0.25) !important;
    color: #0f172a !important;
}
[data-theme="light"] input:focus,
[data-theme="light"] select:focus,
[data-theme="light"] textarea:focus {
    border-color: #6366f1 !important;
    background: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
}
[data-theme="light"] input::placeholder,
[data-theme="light"] textarea::placeholder { color: #94a3b8 !important; }
[data-theme="light"] select option,
[data-theme="light"] select optgroup { background: #ffffff !important; }

/* Note strip */
[data-theme="light"] .note {
    background: linear-gradient(135deg, rgba(217,119,6,0.1), rgba(245,158,11,0.06)) !important;
    border-left-color: #d97706 !important;
    color: #92400e !important;
}

/* Quick filter buttons */
[data-theme="light"] .shift-btn {
    background: linear-gradient(135deg, #ffffff, #f0f4ff) !important;
    border-color: rgba(99,102,241,0.25) !important;
    color: #3730a3 !important;
    box-shadow: 0 2px 8px rgba(99,102,241,0.1) !important;
}
[data-theme="light"] .shift-btn:hover {
    background: linear-gradient(135deg, #ede9fe, #e0e7ff) !important;
    border-color: #6366f1 !important;
}
[data-theme="light"] .shift-btn.active {
    background: linear-gradient(135deg, #dc2743, #b71d38) !important;
    border-color: #dc2743 !important;
    color: #ffffff !important;
    box-shadow: 0 6px 20px rgba(225,29,72,0.35) !important;
}

/* Donor table */
[data-theme="light"] .donor-table-wrapper {
    background: rgba(255,255,255,0.95) !important;
    border-color: rgba(99,102,241,0.15) !important;
    box-shadow: 0 6px 20px rgba(99,102,241,0.1) !important;
}
[data-theme="light"] .donor-table th {
    background: linear-gradient(135deg, rgba(225,29,72,0.08), rgba(99,102,241,0.06)) !important;
    color: #1e1b4b !important;
}
[data-theme="light"] .donor-table tr:hover { background: rgba(99,102,241,0.05) !important; }
[data-theme="light"] .donor-table td { border-bottom-color: rgba(99,102,241,0.08) !important; }

/* Mobile donor cards */
[data-theme="light"] .dc {
    background: rgba(255,255,255,0.97) !important;
    border-color: rgba(99,102,241,0.14) !important;
    box-shadow: 0 3px 12px rgba(99,102,241,0.1) !important;
}
[data-theme="light"] .dc-body { border-top-color: rgba(99,102,241,0.1) !important; }

/* Popups */
[data-theme="light"] .popup {
    background: rgba(255,255,255,0.98) !important;
    border-color: rgba(99,102,241,0.2) !important;
    box-shadow: 0 30px 60px rgba(99,102,241,0.2) !important;
}
[data-theme="light"] .popup-overlay { background: rgba(30,27,75,0.55) !important; }

/* Caller info items */
[data-theme="light"] .caller-info-item {
    background: #eef2ff !important;
    border-left-color: #6366f1 !important;
}

/* Secret box */
[data-theme="light"] .secret-box {
    background: linear-gradient(135deg, #fef3c7, #fffbeb) !important;
    border-color: #d97706 !important;
    color: #78350f !important;
}

/* Pagination */
[data-theme="light"] .pagination a {
    background: rgba(255,255,255,0.9) !important;
    border-color: rgba(99,102,241,0.2) !important;
    color: #3730a3 !important;
}
[data-theme="light"] .pagination a:hover { background: #ede9fe !important; }
[data-theme="light"] .pagination .active-page {
    background: linear-gradient(135deg, #dc2743, #b71d38) !important;
    color: #fff !important;
    border-color: #dc2743 !important;
}

/* Skeleton */
[data-theme="light"] .skeleton {
    background: linear-gradient(90deg, rgba(99,102,241,0.08) 25%, rgba(99,102,241,0.15) 50%, rgba(99,102,241,0.08) 75%) !important;
    background-size: 200% 100% !important;
}

/* Location blocked overlay */
[data-theme="light"] .location-blocked-overlay { background: rgba(30,27,75,0.6) !important; }
[data-theme="light"] .location-blocked-box {
    background: #ffffff !important;
    border-top-color: #dc2743 !important;
    border-color: rgba(99,102,241,0.2) !important;
}

/* Scroll content (terms/about) */
[data-theme="light"] .scroll-content {
    background: #f0f4ff !important;
    border-color: rgba(99,102,241,0.15) !important;
}

/* ============================================================
   MOBILE  ≤767px
   ============================================================ */
@media(max-width:767px){

  /* --- Header --- */
  header { padding: 8px 12px; }
  header img { height: 38px; }
  .header-logo-left { height: 52px !important; width: 52px !important; border-radius: 11px; }
  header h1 { font-size: 1.85rem; line-height: 1.3; margin: 0 6px; font-weight: 800; }
  .header-actions { gap: 6px; }
  .header-account-btn, .notif-bell { width: 38px; height: 38px; }
  .header-account-fallback { font-size: 0.95rem; }

  /* --- Stat cards (KPI-style, 2 per row) --- */
  .stats-container { grid-template-columns: repeat(2,1fr); gap:10px; margin:14px auto 22px; padding:0 10px; }
  .stat-card { padding:15px 10px; border-radius:14px; }
  .stat-card h4 { font-size:1.6rem; margin-bottom:3px; }
  .stat-card .count { font-size:0.7em; }

  /* --- Forms / filters --- */
  form { padding:18px 14px; }
  form h2 { font-size:1.3rem; margin-bottom:18px; }
  .filter-container { padding:14px; }
  .filter-grid { grid-template-columns:1fr 1fr; gap:10px; }
  .tab-btn { font-size:0.8em; padding:11px 5px; }

  /* --- Popups --- */
  .popup { padding:22px 16px; }
  .popup-overlay .popup { max-height:90vh; overflow-y:auto; }
  .footer-card { width:100%; max-width:300px; }

  /* --- Touch targets + iOS zoom --- */
  input, select, textarea { min-height:48px; font-size:16px !important; }
  button { min-height:44px; }
  .shift-btn { min-height:40px; padding:9px 16px; }

  /* --- Hide desktop table, show mobile cards --- */
  .donor-table-wrapper { display: none; }
  .donor-cards-container { display: block; }
}

/* ============================================================
   DONOR BADGE STYLES
   ============================================================ */
.donor-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.72em; font-weight: 700; padding: 2px 8px;
    border-radius: 20px; letter-spacing: 0.3px;
}
.unavailable { color: #6b7280; font-weight:600; background: rgba(107,114,128,0.1); padding: 6px 12px; border-radius: 20px; display: inline-block; border: 1px solid rgba(107,114,128,0.2); }
.dc-badge-inline { font-size:0.85em; }

/* Badge Card in Update Form */
.badge-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: var(--shadow-glass);
}
.badge-card-left { display:flex; align-items:center; gap:14px; }
.badge-icon-big { font-size: 2.8rem; line-height:1; }
.badge-level-name { font-size:1.15em; font-weight:800; font-family:var(--font-heading); color:var(--text-main); }
.badge-donations { font-size:0.82em; color:var(--text-muted); margin-top:3px; font-weight:500; }
.badge-progress-wrap { flex:1; min-width:0; }
.badge-progress-bar { background:var(--input-bg); border-radius:20px; height:8px; overflow:hidden; border:1px solid var(--border-color); }
.badge-progress-fill { height:100%; border-radius:20px; background: linear-gradient(90deg, var(--primary-red), #f59e0b); transition:width 0.8s cubic-bezier(0.34,1.56,0.64,1); }
.badge-next-label { font-size:0.75em; color:var(--text-muted); margin-top:5px; text-align:right; }

/* Just Donated button */
.just-donated-btn {
    width:100%; margin-top:12px;
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
    color:#fff !important;
    border-radius:14px !important;
    font-size:1em !important;
    padding:16px !important;
    box-shadow: 0 6px 20px rgba(220,38,38,0.4) !important;
    animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
    0%,100% { box-shadow: 0 6px 20px rgba(220,38,38,0.4); }
    50%      { box-shadow: 0 8px 28px rgba(220,38,38,0.7); }
}

/* ============================================================
   SECRET CODE CHANGE SECTION
   ============================================================ */
.secret-change-wrap {
    background: var(--input-bg);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: border-color 0.2s;
}
.secret-change-wrap:focus-within {
    border-color: var(--accent-orange);
}
.secret-change-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 16px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 700;
    color: var(--text-main);
    user-select: none;
    -webkit-user-select: none;
    transition: background 0.15s;
}
.secret-change-header:hover { background: rgba(245,158,11,0.06); }
.secret-change-arrow {
    font-size: 1.2em;
    color: var(--text-muted);
    transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), color 0.2s;
}
.secret-change-arrow.open {
    transform: rotate(90deg);
    color: var(--accent-orange);
}
.secret-change-body {
    padding: 0 16px 16px;
    border-top: 1px solid var(--border-color);
}
.secret-change-note {
    font-size: 0.78em;
    color: var(--accent-orange);
    background: rgba(245,158,11,0.08);
    border-left: 3px solid var(--accent-orange);
    padding: 8px 10px;
    border-radius: 0 6px 6px 0;
    margin: 12px 0 0;
    line-height: 1.6;
}
.secret-prefix-badge {
    background: rgba(245,158,11,0.12);
    border: 1.5px solid rgba(245,158,11,0.35);
    color: var(--accent-orange);
    font-family: monospace;
    font-weight: 800;
    font-size: 0.95em;
    padding: 10px 10px;
    border-radius: var(--radius-sm);
    white-space: nowrap;
    flex-shrink: 0;
    letter-spacing: 1px;
}
.secret-hint {
    font-size: 0.76em;
    margin: 6px 0 0;
    padding: 5px 8px;
    border-radius: 6px;
    font-weight: 500;
}
.secret-hint.ok  { color: var(--success); background: rgba(16,185,129,0.08); }
.secret-hint.err { color: var(--danger);  background: rgba(239,68,68,0.08); }
[data-theme="light"] .secret-change-wrap {
    background: #f8f9ff;
    border-color: rgba(99,102,241,0.18);
}
[data-theme="light"] .secret-change-header { color: #0b1120; }
[data-theme="light"] .secret-change-body { border-top-color: rgba(99,102,241,0.1); }

/* ============================================================
   WILLING TOGGLE
   ============================================================ */
.willing-toggle-wrap {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 16px;
}
.willing-toggle-row { display:flex; gap:10px; }
.willing-btn {
    flex:1; padding:12px 8px !important; border-radius:10px !important;
    font-size:0.88em !important; margin:0 !important; font-weight:600 !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease !important;
    border: 2px solid var(--border-color) !important;
    background: var(--bg-card) !important;
    color: var(--text-muted) !important;
    box-shadow: none !important;
}
.willing-btn.active.willing-yes { background: rgba(16,185,129,0.15) !important; color: #059669 !important; border-color: #059669 !important; }
.willing-btn.active.willing-no  { background: rgba(239,68,68,0.12) !important;  color: #ef4444 !important; border-color: #ef4444 !important; }
.willing-note { font-size:0.8em; color:var(--text-muted); margin-top:8px; margin-bottom:0; text-align:center; }

/* ============================================================
   ANALYTICS SECTION
   ============================================================ */
.analytics-section, .map-section {
    margin-top: 60px;
    padding-bottom: 20px;
}
.section-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 12px;
}
.section-title {
    margin:0; font-family:var(--font-heading);
    color:var(--text-main); font-size:1.8rem; font-weight:800;
}
.section-sub { margin:4px 0 0; color:var(--text-muted); font-size:0.88em; }
.analytics-refresh-btn {
    background: var(--input-bg) !important;
    color: var(--text-main) !important;
    border: 1px solid var(--border-color) !important;
    padding: 10px 18px !important;
    border-radius: 30px !important;
    font-size: 0.9em !important;
    font-weight: 600 !important;
    width: auto !important;
    margin: 0 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease !important;
    white-space: nowrap;
}
.analytics-refresh-btn:hover { transform:translateY(-2px) !important; border-color:var(--primary-red) !important; color:var(--primary-red) !important; }

/* KPI grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 18px 12px;
    text-align: center;
    box-shadow: var(--shadow-glass);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
    overflow: hidden;
    will-change: transform;
    transform: translateZ(0);
}
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-total::before  { background: linear-gradient(90deg,#6366f1,#8b5cf6); box-shadow: 0 0 8px rgba(99,102,241,0.5); }
.kpi-avail::before  { background: linear-gradient(90deg,#10b981,#059669); box-shadow: 0 0 8px rgba(16,185,129,0.5); }
.kpi-unav::before   { background: linear-gradient(90deg,#ef4444,#dc2626); box-shadow: 0 0 8px rgba(239,68,68,0.5); }
.kpi-calls::before  { background: linear-gradient(90deg,#3b82f6,#2563eb); box-shadow: 0 0 8px rgba(59,130,246,0.5); }
.kpi-req::before    { background: linear-gradient(90deg,#f59e0b,#d97706); box-shadow: 0 0 8px rgba(245,158,11,0.5); }
.kpi-donated::before{ background: linear-gradient(90deg,#e02424,#f87171); box-shadow: 0 0 8px rgba(220,36,36,0.5); }
.kpi-card { cursor:pointer; }
.kpi-card:hover { transform:translateY(-5px); box-shadow:0 14px 32px rgba(0,0,0,0.2); }
/* Non-interactive KPI (e.g. মোট Calls) — no pointer, no hover lift */
.kpi-static { cursor:default; }
.kpi-static:hover { transform:none; box-shadow:var(--shadow-glass); }
.kpi-icon { font-size:1.7rem; margin-bottom:8px; }
.kpi-val { font-size:2.2rem; font-weight:900; font-family:var(--font-heading); color:var(--text-main); line-height:1.1; }
.kpi-label { font-size:0.73em; color:var(--text-muted); margin-top:5px; font-weight:600; }

/* Charts grid */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--shadow-glass);
}
.chart-title { font-family:var(--font-heading); font-size:1em; font-weight:700; color:var(--text-main); margin:0 0 16px; }

/* Bar chart */
.bar-chart-wrap { display:flex; flex-direction:column; gap:8px; }
.bar-row { display:flex; align-items:center; gap:10px; }
.bar-label { font-size:0.78em; font-weight:700; min-width:36px; text-align:right; }
.bar-track { flex:1; background:var(--input-bg); border-radius:20px; height:20px; overflow:hidden; }
.bar-fill { height:100%; border-radius:20px; transition:width 0.9s cubic-bezier(0.34,1.56,0.64,1); display:flex; align-items:center; justify-content:flex-end; padding-right:6px; }
.bar-count { font-size:0.7em; font-weight:700; color:#fff; }

/* Badge donut */
.badge-donut-wrap { display:flex; align-items:center; justify-content:center; gap:20px; }
.badge-legend { display:flex; flex-direction:column; gap:8px; }
.badge-legend-item { display:flex; align-items:center; gap:8px; font-size:0.82em; font-weight:600; color:var(--text-main); }
.badge-legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }

/* Location chart */
.loc-chart-wrap { display:flex; flex-direction:column; gap:8px; }
.loc-row { display:flex; align-items:center; gap:10px; }
.loc-name { font-size:0.8em; font-weight:600; min-width:120px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.loc-bar-track { flex:1; background:var(--input-bg); border-radius:20px; height:18px; overflow:hidden; }
.loc-bar-fill { height:100%; border-radius:20px; background:linear-gradient(90deg,var(--primary-red),#f59e0b); display:flex; align-items:center; justify-content:flex-end; padding-right:6px; }
.loc-count { font-size:0.68em; font-weight:700; color:#fff; }

/* ============================================================
   MAP FILTER BAR — above the map
   ============================================================ */
.map-filter-bar {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 14px 16px;
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.map-filter-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.map-filter-label {
    font-size: 0.78em;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.map-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.map-pill {
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 0.78em;
    font-weight: 700;
    border: 1.5px solid var(--border-color);
    background: var(--input-bg);
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    margin: 0;
    width: auto;
    min-height: unset;
    box-shadow: none;
    font-family: var(--font-heading);
    letter-spacing: 0.2px;
}
.map-pill:hover {
    border-color: var(--primary-red);
    color: var(--primary-red);
    background: rgba(220,38,38,0.06);
    transform: translateY(-1px);
}
.map-pill.active {
    background: var(--primary-red);
    color: #fff;
    border-color: var(--primary-red);
    box-shadow: 0 3px 10px rgba(220,38,38,0.35);
}
.map-pill-avail.active  { background: #10b981; border-color: #10b981; box-shadow: 0 3px 10px rgba(16,185,129,0.35); }
.map-pill-notavail.active { background: #ef4444; border-color: #ef4444; box-shadow: 0 3px 10px rgba(239,68,68,0.35); }
.map-pill-unwill.active { background: #6b7280; border-color: #6b7280; box-shadow: 0 3px 10px rgba(107,114,128,0.35); }
.map-pill-avail:hover   { border-color: #10b981; color: #10b981; background: rgba(16,185,129,0.08); }
.map-pill-notavail:hover { border-color: #ef4444; color: #ef4444; background: rgba(239,68,68,0.08); }
.map-pill-unwill:hover  { border-color: #6b7280; color: #6b7280; background: rgba(107,114,128,0.08); }
.map-filter-info {
    font-size: 0.78em;
    color: var(--text-muted);
    padding: 6px 10px;
    background: rgba(59,130,246,0.07);
    border-radius: 8px;
    border: 1px solid rgba(59,130,246,0.15);
    font-weight: 500;
}
[data-theme="light"] .map-filter-bar {
    background: rgba(255,255,255,0.95);
    border-color: rgba(99,102,241,0.15);
}
[data-theme="light"] .map-pill {
    background: #f0f4ff;
    border-color: rgba(99,102,241,0.2);
    color: #4338ca;
}
[data-theme="light"] .map-pill:hover {
    background: rgba(225,29,72,0.06);
    border-color: #dc2743;
    color: #dc2743;
}
[data-theme="light"] .map-pill.active {
    background: #dc2743;
    border-color: #dc2743;
    color: #fff;
}
@media(max-width:767px) {
    .map-filter-bar { padding: 10px 12px; gap: 10px; }
    .map-pill { font-size: 0.72em; padding: 4px 10px; }
}

/* ============================================================
   MAP SECTION
   ============================================================ */
.map-container {
    width: 100%;
    height: 420px;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-glass);
    background: var(--input-bg);
    position: relative;
}
.map-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    gap: 8px;
}
.map-legend {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 12px;
}
.map-legend-item { display:flex; align-items:center; gap:6px; font-size:0.85em; font-weight:600; color:var(--text-main); }

/* ============================================================
   EMERGENCY BLOOD REQUEST STYLES
   ============================================================ */
.emergency-banner {
    background: linear-gradient(135deg, rgba(224,36,36,0.12), rgba(245,158,11,0.07));
    border: 1px solid rgba(224,36,36,0.35);
    border-radius: var(--radius-lg);
    padding: 16px 22px;
    margin: 20px auto;
    width: 95%; max-width: 1200px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
    box-shadow: 0 4px 24px rgba(224,36,36,0.12), inset 0 1px 0 rgba(255,255,255,0.05);
    animation: pulse-border 2.5s ease-in-out infinite;
}
@keyframes pulse-border {
    0%,100%{box-shadow:0 4px 20px rgba(220,38,38,0.15);}
    50%{box-shadow:0 4px 30px rgba(220,38,38,0.35);}
}
.emergency-banner-left { display:flex; align-items:center; gap:12px; }
.emergency-banner-icon { font-size:2rem; animation: pulse-icon 1s ease-in-out infinite alternate; }
@keyframes pulse-icon { from{transform:scale(1);}to{transform:scale(1.15);} }
.emergency-banner-text h4 { color:var(--danger); font-family:var(--font-heading); font-size:1.05rem; margin-bottom:2px; }
.emergency-banner-text p  { color:var(--text-muted); font-size:0.82em; }
.emergency-banner-btns { display:flex; gap:8px; flex-wrap:wrap; }
.btn-emergency { background:var(--danger); color:#fff; padding:10px 18px; border-radius:25px; font-size:0.9em; font-weight:700; cursor:pointer; border:none; transition:all 0.2s; width:auto; margin:0; }
.btn-emergency:hover { background:#b91c1c; transform:translateY(-2px); box-shadow:0 6px 15px rgba(220,38,38,0.4); }
.btn-view-requests { background:transparent; color:var(--accent-orange); border:1.5px solid var(--accent-orange); padding:9px 16px; border-radius:25px; font-size:0.9em; font-weight:700; cursor:pointer; transition:all 0.2s; width:auto; margin:0; }
.btn-view-requests:hover { background:var(--accent-orange); color:#000; }

/* Request cards */
.req-section { background:var(--bg-card); border-radius:var(--radius-lg); border:1px solid var(--border-color); padding:20px; margin:20px auto; width:95%; max-width:1200px; display:none; }
.req-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; margin-top:14px; }
/* ── Filter rows ── */
.req-filter-row { display:flex; flex-wrap:wrap; gap:7px; align-items:center; }
.req-tab-btn {
    width:auto !important; min-height:unset !important; margin:0 !important; box-shadow:none !important;
    padding:7px 16px; border-radius:20px; font-size:0.83em; font-weight:700; cursor:pointer;
    border:1.5px solid var(--border-color); background:transparent; color:var(--text-muted);
    transition:all 0.18s; letter-spacing:0.2px;
}
.req-tab-btn.req-tab-active {
    background:var(--danger) !important; color:#fff !important; border-color:var(--danger) !important;
    box-shadow:0 2px 10px rgba(220,38,38,0.3) !important;
}
.req-bg-chip {
    width:auto !important; min-height:unset !important; margin:0 !important; box-shadow:none !important;
    padding:5px 10px; border-radius:16px; font-size:0.76em; font-weight:800; cursor:pointer;
    border:1.5px solid var(--border-color); background:var(--bg-main); color:var(--text-muted);
    transition:all 0.15s; letter-spacing:0.3px;
}
.req-bg-chip.chip-active {
    background:rgba(220,38,38,0.12) !important; color:var(--danger) !important;
    border-color:rgba(220,38,38,0.5) !important; transform:scale(1.08);
}
.req-bg-clear {
    width:auto !important; min-height:unset !important; margin:0 !important; box-shadow:none !important;
    padding:4px 10px; border-radius:14px; font-size:0.74em; font-weight:700; cursor:pointer;
    border:1px solid rgba(220,38,38,0.35); background:rgba(220,38,38,0.08); color:var(--danger);
    transition:all 0.15s;
}
.req-card { background:var(--bg-main); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:16px; position:relative; overflow:hidden; }
.req-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.req-card.critical::before { background:var(--danger); }
.req-card.high::before { background:var(--accent-orange); }
.req-card.medium::before { background:var(--info); }
.req-card-urgency { font-size:0.72em; font-weight:700; text-transform:uppercase; letter-spacing:1px; padding:3px 8px; border-radius:20px; display:inline-block; margin-bottom:8px; }
.req-card-urgency.critical { background:rgba(239,68,68,0.15); color:var(--danger); }
.req-card-urgency.high     { background:rgba(245,158,11,0.15); color:var(--accent-orange); }
.req-card-urgency.medium   { background:rgba(59,130,246,0.15); color:var(--info); }
.req-card-group { font-size:2rem; font-weight:800; color:var(--primary-red); font-family:var(--font-heading); line-height:1; margin-bottom:4px; }
.req-card-name  { font-weight:600; font-size:0.95em; color:var(--text-main); }
.req-card-hosp  { color:var(--text-muted); font-size:0.82em; margin:4px 0; }
.req-card-meta  { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
.req-tag { font-size:0.78em; padding:3px 8px; border-radius:12px; background:var(--input-bg); color:var(--text-muted); }
.req-call-btn { background:linear-gradient(135deg,var(--success),#059669); color:#fff; border:none; padding:9px 14px; border-radius:8px; font-size:0.88em; font-weight:700; cursor:pointer; width:100%; margin-top:10px; transition:all 0.2s; }
.req-call-btn:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(16,185,129,0.4); }

/* Nearby donor section */
.nearby-section { background:var(--bg-card); border-radius:var(--radius-lg); border:1px solid var(--border-color); padding:20px; margin:20px auto; width:95%; max-width:1200px; }
.nearby-controls { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px; }
.nearby-controls select, .nearby-controls input { margin:0; flex:1; min-width:130px; }
.nearby-results { display:block; }
.nearby-results.donor-cards-container { display:block !important; }
.nearby-card { background:var(--bg-main); border-radius:var(--radius-md); border:1px solid var(--border-color); padding:14px; display:flex; flex-direction:column; gap:6px; min-width:0; box-sizing:border-box; }
@media(max-width:650px){
    .nearby-section { padding: 14px; }
}
.nearby-dist { font-size:0.78em; color:var(--info); font-weight:700; background:rgba(59,130,246,0.1); padding:3px 8px; border-radius:12px; display:inline-block; }
.nearby-empty { text-align:center; padding:40px; color:var(--text-muted); }

/* Push notification prompt — iOS-style */
.notif-prompt {
    position: fixed; bottom: 20px; left: 50%;
    transform: translateX(-50%) translateY(30px);
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px; padding: 18px 18px 16px;
    box-shadow: var(--shadow-glass);
    z-index: 10050; max-width: 360px; width: calc(100% - 32px);
    opacity: 0; pointer-events: none;
    transition: opacity 0.35s ease, transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
    will-change: opacity, transform;
}
.notif-prompt.np-show {
    opacity: 1; pointer-events: auto;
    transform: translateX(-50%) translateY(0);
}
@media(max-width:650px){
    .notif-prompt {
        bottom: calc(82px + env(safe-area-inset-bottom, 0px));
        width: calc(100% - 24px); max-width: none; left: 50%;
        border-radius: 18px;
    }
}
@keyframes slide-up { from{transform:translateX(-50%) translateY(30px);opacity:0;} to{transform:translateX(-50%) translateY(0);opacity:1;} }
.notif-prompt-icon { font-size:2.2rem; flex-shrink:0; }
.notif-prompt-text h4 { color:var(--text-main); font-size:0.95em; font-weight:700; margin-bottom:3px; }
.notif-prompt-text p  { color:var(--text-muted); font-size:0.8em; }
.notif-prompt-btns { display:flex; gap:8px; margin-top:8px; }
.btn-allow-notif {
    background: var(--primary-red); color: #fff; border: none;
    padding: 9px 0; border-radius: 10px; font-size: 0.87em;
    font-weight: 700; cursor: pointer; flex: 1; margin: 0;
    transition: opacity 0.15s;
    width: auto !important; display: inline-flex !important;
    align-items: center; justify-content: center;
}
.btn-allow-notif:hover { background: var(--primary-red) !important; transform: none !important; box-shadow: none !important; }
.btn-allow-notif:active { opacity: 0.82; transform: none !important; }
.btn-deny-notif {
    background: rgba(128,128,128,0.14); color: var(--text-muted);
    border: 1px solid var(--border-color);
    padding: 9px 0; border-radius: 10px; font-size: 0.87em;
    font-weight: 600; cursor: pointer; flex: 1; margin: 0;
    transition: opacity 0.15s;
}
.btn-deny-notif:hover { background: rgba(128,128,128,0.2) !important; transform: none !important; box-shadow: none !important; }
.btn-deny-notif:active { opacity: 0.7; transform: none !important; }
/* np-btn-row: force equal-width flex buttons, override global button width:100% */
.np-btn-row { display: flex; gap: 8px; }
.np-btn-row button,
.np-btn-row .btn-allow-notif,
.np-btn-row .btn-deny-notif {
    flex: 1 !important;
    width: 0 !important; /* flex-basis 0 so both grow equally */
    min-width: 0 !important;
    margin-top: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
/* Notif prompt app-icon style row */
.np-app-row {
    display: flex; align-items: flex-start; gap: 13px;
}
.np-app-icon {
    width: 50px; height: 50px; flex-shrink: 0;
    background: linear-gradient(135deg, #dc2626, #9f1239);
    border-radius: 12px; display: flex; align-items: center;
    justify-content: center; font-size: 1.55rem;
    box-shadow: 0 4px 12px rgba(220,38,38,0.4);
}
.np-text-wrap { flex: 1; min-width: 0; }
.np-app-name { font-weight: 800; font-size: 0.93em; color: var(--text-main); margin-bottom: 2px; line-height: 1.2; }
.np-msg { font-size: 0.81em; color: var(--text-muted); line-height: 1.45; margin-bottom: 11px; }

/* Admin link in footer */
.admin-link { color:var(--text-muted); font-size:0.75em; text-decoration:none; opacity:0.4; transition:opacity 0.2s; }
.admin-link:hover { opacity:1; color:var(--primary-red); }

@media(max-width:767px){
    .req-grid,.nearby-results { grid-template-columns:1fr; }
    .emergency-banner { flex-direction:column; align-items:flex-start; }
}
@media(max-width:767px){
    .kpi-grid { grid-template-columns: repeat(2,1fr); gap:8px; }
    .kpi-val { font-size:1.5rem; }
    .kpi-icon { font-size:1.2rem; }
    .charts-grid { grid-template-columns:1fr; }
    .badge-donut-wrap { flex-direction:column; }
    .map-container { height:320px; }
    .badge-card { flex-direction:column; align-items:flex-start; }
    .badge-progress-wrap { width:100%; }
    .willing-toggle-row { flex-direction:column; }
    .section-title { font-size:1.4rem; }
    .loc-name { min-width:90px; }
}


/* ============================================================
   DEVELOPER SECTION — Cards + AI Logos
   ============================================================ */
.dev-section {
    padding: 18px 14px 14px;
    max-width: 560px;
    margin: 0 auto;
}
.dev-section-label {
    text-align:center;
    font-size:0.62em;
    text-transform:uppercase;
    letter-spacing:2.5px;
    color:var(--text-muted);
    font-weight:700;
    margin-bottom:12px;
}
/* Single horizontal dev card split into two equal halves by a divider */
.dev-card.dev-card-horizontal {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    padding: 22px 10px;
}
.dev-half {
    flex: 1;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 13px;
    padding: 8px 10px;
    min-width: 0;
}
.dev-half .dev-avatar { width: 64px; height: 64px; margin: 0; flex-shrink: 0; }
.dev-half .dev-half-info { text-align: left; min-width: 0; }
.dev-half .dev-name { white-space: nowrap; margin: 0 0 5px; line-height: 1.25; }
/* Batch tag (Sh-20) always sits on its own line under the name, identical on every device */
.dev-name .dev-batch { display: block; font-size: 0.78em; font-weight: 600; color: var(--text-muted); white-space: nowrap; margin-top: 1px; }

/* Donate Us — interactive CTA above the developer card */
.dev-donate-btn {
    display: flex; align-items: center; justify-content: center; gap: 9px;
    width: 100%; margin: 0 0 12px; padding: 12px 16px;
    border: none; border-radius: 12px; cursor: pointer;
    font-family: var(--font-heading); font-weight: 800; font-size: 0.95em;
    color: #fff; letter-spacing: 0.3px;
    background: linear-gradient(135deg, var(--primary-red), #b91c1c);
    box-shadow: 0 6px 16px rgba(220,38,38,0.30);
    transition: transform 0.18s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.18s ease;
}
.dev-donate-btn:hover { transform: translateY(-2px) scale(1.015); box-shadow: 0 10px 22px rgba(220,38,38,0.42); }
.dev-donate-btn:active { transform: translateY(0) scale(0.99); }
.dev-donate-ic { font-size: 1.12em; line-height: 1; animation: devDonatePulse 1.8s ease-in-out infinite; }
.dev-donate-arrow { font-size: 1.05em; line-height: 1; transition: transform 0.18s ease; }
.dev-donate-btn:hover .dev-donate-arrow { transform: translateX(4px); }
@keyframes devDonatePulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.18); } }
.dev-divider {
    width: 1px;
    align-self: stretch;
    background: var(--border-color);
    margin: 2px 0;
    flex-shrink: 0;
}
@media(max-width: 767px) {
    .dev-card-horizontal { padding: 18px 6px; }
    .dev-half { padding: 6px 6px; gap: 10px; }
    .dev-half .dev-avatar { width: 54px; height: 54px; }
}
.dev-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 12px 10px 10px;
    text-align: center;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.22s ease;
}
.dev-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 14px 32px rgba(0,0,0,0.3);
}
.dev-card-bar {
    position:absolute; top:0; left:0; right:0; height:2.5px;
}
/* Minimalist developer card — no top bar, no hover lift, neutral avatars */
.dev-card-min { border-radius:12px; }
.dev-card-min:hover { transform:none; box-shadow:var(--shadow-glass); }
.dev-card-min .dev-avatar { box-shadow:none; border-color:var(--border-color); }
.dev-card-min .dev-name { font-size:0.86em; margin:0 0 3px; }
.dev-card-min .dev-role { margin:0; }
.dev-avatar {
    width:46px; height:46px; border-radius:50%; object-fit:cover;
    border: 2px solid transparent;
    margin: 4px auto 6px; display:block;
    box-shadow: 0 3px 8px rgba(0,0,0,0.22);
}
.dev-avatar-svg {
    width:58px; height:58px; border-radius:50%;
    border: 2.5px solid #cc785c;
    margin: 6px auto 8px; display:flex;
    align-items:center; justify-content:center;
    background: radial-gradient(circle at 30% 30%, rgba(204,120,92,0.15), rgba(0,0,0,0.3));
    box-shadow: 0 4px 12px rgba(204,120,92,0.25), inset 0 0 0 1px rgba(204,120,92,0.1);
}
.dev-name {
    font-weight:800; font-family:var(--font-heading);
    color:var(--text-main); font-size:0.83em; margin:0 0 2px;
}
.dev-role { font-size:0.67em; color:var(--text-muted); margin:0 0 9px; }
.dev-btn {
    display:inline-flex; align-items:center; gap:4px;
    border-radius:20px; padding:5px 12px;
    font-size:0.72em; font-weight:700; text-decoration:none;
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dev-btn:hover { opacity:0.85; transform:scale(1.06); }
.dev-btn-red   { background:rgba(220,38,38,0.12);  color:var(--primary-red); }
.dev-btn-claude{ background:rgba(204,120,92,0.12); color:#cc785c; }
.dev-btn-si    { background:rgba(59,130,246,0.12); color:#3b82f6; }
.dev-badge {
    display:inline-flex; align-items:center; gap:4px;
    border-radius:20px; padding:5px 11px;
    font-size:0.70em; font-weight:700;
    border: 1px solid transparent;
    letter-spacing:0.2px;
}
.dev-badge-red   { background:rgba(220,38,38,0.10); color:var(--primary-red); border-color:rgba(220,38,38,0.22); }
.dev-badge-green { background:rgba(16,185,129,0.10); color:#10b981; border-color:rgba(16,185,129,0.22); }
.dev-badge-orange{ background:rgba(245,158,11,0.10); color:#f59e0b; border-color:rgba(245,158,11,0.22); }
.dev-badge-purple{ background:rgba(139,92,246,0.10); color:#8b5cf6; border-color:rgba(139,92,246,0.22); }

/* Blood Arena logo avatar — contain fit so logo isn't cropped */
.dev-avatar-logo {
    object-fit: contain !important;
    background: #0a0e1a;
    padding: 6px;
}
[data-theme="light"] .dev-avatar-logo {
    background: #0d1a35;
}

/* Claude chip special accent */
.ai-logo-chip-claude {
    border-color: rgba(204,120,92,0.35) !important;
    color: #cc785c !important;
}
.ai-logo-chip-claude:hover {
    background: rgba(204,120,92,0.12) !important;
    border-color: #cc785c !important;
    color: #d4956a !important;
}

/* AI Tools Row */
.ai-tools-row {
    margin-top: 12px;
    text-align: center;
}
.ai-tools-label {
    font-size:0.60em; text-transform:uppercase; letter-spacing:1.5px;
    color:var(--text-muted); font-weight:600; margin-bottom:8px;
    opacity:0.7;
}
.ai-tools-logos {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}
.ai-logo-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    font-size: 0.67em;
    font-weight: 600;
    color: var(--text-muted);
    text-decoration: none;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    font-family: var(--font-heading);
    white-space: nowrap;
}
.ai-logo-chip:hover {
    background: rgba(255,255,255,0.08);
    color: var(--text-main);
    border-color: rgba(255,255,255,0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
[data-theme="light"] .ai-logo-chip:hover {
    background: rgba(99,102,241,0.08);
    color: #1e1b4b;
    border-color: rgba(99,102,241,0.25);
}

/* ============================================================
   GLOBAL UI POLISH — stat cards, hero bar, donor cards
   ============================================================ */

/* Smoother hero bar */
.home-hero-bar {
    background: linear-gradient(135deg, var(--bg-card) 0%, rgba(224,36,36,0.04) 100%) !important;
    border: 1px solid var(--border-color) !important;
}
.home-hero-num { text-shadow: 0 2px 8px rgba(224,36,36,0.2); }

/* Stat cards — glassy shimmer edge */
.stat-card::after {
    background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, transparent 50%) !important;
}

/* Donor cards — slightly elevated feel */
.dc {
    transition: box-shadow 0.2s ease, transform 0.2s ease !important;
}
.dc:active {
    transform: scale(0.985) !important;
}

/* Scrollbar polish */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(220,38,38,0.25); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(220,38,38,0.4); }

/* Light mode dev card */
[data-theme="light"] .dev-card {
    background: #fff !important;
    border-color: rgba(99,102,241,0.14) !important;
    box-shadow: 0 3px 14px rgba(99,102,241,0.08) !important;
}
[data-theme="light"] .ai-logo-chip {
    background: #f0f4ff !important;
    border-color: rgba(99,102,241,0.18) !important;
    color: #4338ca !important;
}

/* ============================================================
   SMART DATE PICKER
   ============================================================ */
.smart-date-wrap { }
.smart-date-toggle { display:flex; gap:8px; margin-bottom:4px; }
.sd-toggle-btn {
    flex:1; padding:10px 8px !important; border-radius:10px !important;
    font-size:0.88em !important; margin:0 !important; font-weight:600 !important;
    border: 2px solid var(--border-color) !important;
    background: var(--bg-card) !important; color: var(--text-muted) !important;
    box-shadow: none !important; cursor:pointer; transition:all 0.2s ease !important;
    width:auto !important; min-height:40px;
}
.sd-toggle-btn.sd-active {
    background: rgba(224,36,36,0.12) !important;
    color: var(--primary-red) !important;
    border-color: var(--primary-red) !important;
}

/* ============================================================
   CALL BUTTON — DISABLED STATE FOR NON-AVAILABLE DONORS
   ============================================================ */
/* ── Called donor button states ── */
.phone-link.btn-called {
    background: linear-gradient(135deg, #065f46, #047857) !important;
    color: #6ee7b7 !important;
    box-shadow: 0 4px 10px rgba(5,150,105,0.35) !important;
    cursor: pointer;
    opacity: 0.88;
    font-size: 0.85em;
    letter-spacing: 0.3px;
    /* pointer-events kept ON — user can call again */
}
.dc-call-btn.btn-called {
    background: linear-gradient(180deg, #047857 0%, #065f46 100%) !important;
    color: #6ee7b7 !important;
    border-left: 1px solid rgba(255,255,255,0.12) !important;
    opacity: 0.88;
    font-size: 0.75em;
    cursor: pointer;
    /* pointer-events kept ON — user can call again */
}
/* ── Next donor blink on the call button ── */
@keyframes nextCallBlink {
    0%   { box-shadow: 0 0 0 0 rgba(220,38,38,0.85), 0 4px 12px rgba(220,38,38,0.5); transform: scale(1); }
    30%  { box-shadow: 0 0 0 8px rgba(220,38,38,0), 0 4px 12px rgba(220,38,38,0.5); transform: scale(1.08); }
    50%  { box-shadow: 0 0 0 0 rgba(220,38,38,0), 0 4px 12px rgba(220,38,38,0.5); transform: scale(1); }
    75%  { box-shadow: 0 0 0 5px rgba(220,38,38,0), 0 4px 12px rgba(220,38,38,0.5); transform: scale(1.05); }
    100% { box-shadow: 0 0 0 0 rgba(220,38,38,0), 0 4px 12px rgba(220,38,38,0.5); transform: scale(1); }
}
.phone-link.btn-next-blink {
    animation: nextCallBlink 0.42s ease 9 !important;
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
}
.dc-call-btn.btn-next-blink {
    animation: nextCallBlink 0.42s ease 9 !important;
    background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%) !important;
}
.phone-link-disabled {
    background: rgba(107,114,128,0.18) !important;
    color: #6b7280 !important;
    padding: 10px 16px; border-radius: 8px; font-weight: 700;
    font-family: var(--font-heading); cursor: not-allowed;
    border: 1.5px solid rgba(107,114,128,0.25) !important;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: none !important; font-size: 0.9em; width: 100%; margin: 0;
    text-transform: uppercase; letter-spacing: 0.5px; pointer-events:none;
}
.dc-call-btn-disabled {
    background: rgba(107,114,128,0.15) !important;
    color: #6b7280 !important; border: 1.5px solid rgba(107,114,128,0.2) !important;
    border-radius: 10px; padding: 8px 10px; font-size: 1rem; cursor: not-allowed;
    pointer-events:none; margin:0; box-shadow:none !important;
    min-width:38px; min-height:38px; display:flex; align-items:center; justify-content:center;
}

/* ============================================================
   NOTIFICATION BELL — LIVE ANIMATION
   ============================================================ */
@keyframes bellShake {
    0%   { transform: rotate(0deg) scale(1); }
    10%  { transform: rotate(-15deg) scale(1.15); }
    20%  { transform: rotate(15deg) scale(1.18); }
    30%  { transform: rotate(-12deg) scale(1.12); }
    40%  { transform: rotate(12deg) scale(1.15); }
    50%  { transform: rotate(-8deg) scale(1.1); }
    60%  { transform: rotate(8deg) scale(1.08); }
    70%  { transform: rotate(-4deg) scale(1.04); }
    80%  { transform: rotate(4deg) scale(1.02); }
    90%  { transform: rotate(-2deg) scale(1.01); }
    100% { transform: rotate(0deg) scale(1); }
}
@keyframes badgePulse {
    0%,100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0.7); }
    50%     { transform: scale(1.25); box-shadow: 0 0 0 5px rgba(239,68,68,0); }
}
.notif-bell.live-ring {
    animation: bellShake 0.7s ease forwards;
    border-color: var(--accent-orange) !important;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.3), 0 4px 14px rgba(245,158,11,0.4) !important;
}
.notif-badge.on {
    animation: badgePulse 1.4s ease infinite;
}
/* When panel opens — themed header glow */
.notif-panel.show {
    border-color: rgba(245,158,11,0.4) !important;
    box-shadow: 0 18px 45px rgba(0,0,0,0.5), 0 0 0 1.5px rgba(245,158,11,0.3) !important;
}
.notif-panel.show .notif-panel-hdr {
    background: linear-gradient(90deg, rgba(220,38,38,0.12), rgba(245,158,11,0.08));
    border-radius: 8px 8px 0 0;
}



/* Light theme table & card text fix */
[data-theme="light"] .donor-table td { color: #0b1120 !important; }
[data-theme="light"] .dc-name { color: #0b1120 !important; }
[data-theme="light"] .dc-loc, [data-theme="light"] .dc-last { color: #2e4060 !important; }
[data-theme="light"] input, [data-theme="light"] select, [data-theme="light"] textarea {
    color: #0b1120 !important; background: #e8eeff !important;
}
[data-theme="light"] input::placeholder, [data-theme="light"] textarea::placeholder { color: #2e4060 !important; }

/* Notification Bell */
.notif-bell-wrap { position:relative; display:inline-flex; align-items:center; }
.notif-bell {
    background:rgba(255,255,255,0.07); border:1.5px solid rgba(255,255,255,0.12);
    font-size:1.2rem; cursor:pointer; border-radius:50%; width:42px; height:42px;
    min-height:unset; /* stay a circle — see .header-account-btn note */
    display:flex; align-items:center; justify-content:center;
    transition:transform 0.3s,box-shadow 0.3s,border-color 0.3s;
    color:var(--text-main); padding:0; margin:0 2px;
    box-shadow:0 2px 8px rgba(0,0,0,0.2); position:relative;
}
.notif-bell:hover { transform:scale(1.1) rotate(12deg); box-shadow:0 4px 14px rgba(245,158,11,0.4); border-color:var(--accent-orange); }
.notif-bell.ring { animation:bRing 0.4s ease 0s 5 alternate; }
@keyframes bRing { 0%{transform:rotate(-12deg);}100%{transform:rotate(12deg);} }
.notif-badge {
    position:absolute; top:-5px; right:-5px;
    background:var(--danger); color:#fff; font-size:0.55em; font-weight:800;
    min-width:17px; height:17px; border-radius:50%;
    display:none; align-items:center; justify-content:center;
    border:2px solid var(--bg-main);
}
.notif-badge.on { display:flex; }

/* Notification Panel anchor — sits at body level to escape stacking context */
.notif-panel-anchor {
    position: fixed;
    top: 76px;
    right: 0;
    z-index: 10050; /* above mobile-bottom-nav(9999) */
    pointer-events: none;
}
@media(min-width: 651px) {
    .notif-panel-anchor { left: 230px !important; }
}
.notif-panel-anchor .notif-panel {
    position: relative;
    top: 0;
    right: 0;
    pointer-events: all;
    margin: 4px 12px 0 0;
}

/* ── Header account quick popup ── */
.acct-pop-anchor {
    position: fixed;
    top: 76px;
    right: 0;
    z-index: 10051; /* above notif-panel-anchor */
    pointer-events: none;
}
.acct-pop {
    pointer-events: all;
    margin: 4px 12px 0 0;
    background: var(--bg-glass); border: 1px solid var(--glass-border);
    backdrop-filter: blur(var(--glass-blur)); -webkit-backdrop-filter: blur(var(--glass-blur));
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-glass);
    width: 220px; padding: 6px; display: none;
}
.acct-pop.show { display: block; animation: fadeIn 0.18s ease; }
.acct-pop-item {
    display: flex; align-items: center; gap: 10px;
    width: 100%; margin: 0; padding: 11px 12px;
    background: transparent; border: none; box-shadow: none;
    color: var(--text-main); font-size: 0.9em; font-weight: 600;
    text-align: left; cursor: pointer; border-radius: 10px;
    min-height: unset; transition: background 0.15s;
}
.acct-pop-item:hover { background: rgba(255,255,255,0.07); }
.acct-pop-ic { font-size: 1.05em; width: 22px; text-align: center; flex-shrink: 0; }
.acct-pop-danger { color: var(--danger); }
.acct-pop-danger:hover { background: rgba(220,38,38,0.1); }

/* Notification Panel */
.notif-panel {
    background:var(--bg-glass); border:1px solid var(--glass-border);
    backdrop-filter:blur(var(--glass-blur)); -webkit-backdrop-filter:blur(var(--glass-blur));
    border-radius:var(--radius-lg); width:290px; max-height:420px; overflow-y:auto;
    overflow-x: hidden;
    box-shadow:var(--shadow-glass); z-index:9100; display:none; padding:10px;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: rgba(220,38,38,0.4) transparent;
}
.notif-panel::-webkit-scrollbar { width: 4px; }
.notif-panel::-webkit-scrollbar-track { background: transparent; }
.notif-panel::-webkit-scrollbar-thumb { background: rgba(220,38,38,0.4); border-radius: 4px; }
.notif-panel.show { display:block; animation:fadeIn 0.2s ease; }
.notif-panel-hdr { font-weight:700; font-size:0.85em; color:var(--text-main);
    padding:5px 8px 10px; border-bottom:1px solid var(--border-color); margin-bottom:6px;
    display:flex; justify-content:space-between; }
/* ── Notification 2-tab system ── */
.notif-tabs-hdr {
    display:flex; border-bottom:1px solid var(--border-color); flex-shrink:0;
}
.notif-tab-btn {
    flex:1; padding:9px 4px 8px; background:transparent; border:none;
    font-size:0.78em; font-weight:700; color:var(--text-muted); cursor:pointer;
    border-bottom:2px solid transparent; transition:color 0.15s, border-color 0.15s;
    position:relative; white-space:nowrap; min-height:unset; box-shadow:none; margin:0;
    border-radius:0 !important;
}
.notif-tab-btn.active { color:var(--primary-red); border-bottom-color:var(--primary-red); }
.notif-tab-badge {
    display:inline-block; background:var(--primary-red); color:#fff;
    border-radius:10px; font-size:0.72em; padding:1px 5px; margin-left:4px;
    font-weight:800; vertical-align:middle;
}
.notif-panel-subhdr {
    font-weight:700; font-size:0.82em; color:var(--text-main);
    padding:7px 8px 8px; border-bottom:1px solid var(--border-color); margin-bottom:4px;
    display:flex; justify-content:space-between;
}
/* Service notification rows — modern swipeable */
.svc-notif-row {
    position:relative; overflow:hidden;
    padding:10px 12px; border-radius:12px; margin-bottom:6px;
    border:1px solid var(--border-color); background:var(--input-bg);
    display:flex; align-items:flex-start; gap:10px;
    transition:transform 0.25s ease, opacity 0.25s ease, max-height 0.3s ease;
    max-height:200px; touch-action:pan-y;
}
.svc-notif-row.unread {
    border-color:rgba(59,130,246,0.35);
    background:rgba(59,130,246,0.06);
    box-shadow:0 0 0 1px rgba(59,130,246,0.15);
}
.svc-notif-row.swiping-out {
    transform:translateX(110%);
    opacity:0;
    max-height:0;
    padding:0;
    margin:0;
    border-width:0;
    pointer-events:none;
}
.svc-notif-icon { font-size:1.25em; flex-shrink:0; line-height:1.4; margin-top:1px; }
.svc-notif-body { flex:1; min-width:0; }
.svc-notif-msg { font-size:0.82em; color:var(--text-main); line-height:1.55; word-break:break-word; white-space:pre-line; }
.svc-notif-time { font-size:0.7em; color:var(--text-muted); margin-top:4px; }
.svc-notif-actions { display:flex; flex-direction:column; gap:4px; flex-shrink:0; align-self:center; }
.svc-notif-read-btn {
    background:transparent; border:1px solid var(--border-color);
    color:var(--text-muted); font-size:0.65em; font-weight:600; border-radius:8px;
    padding:4px 8px; cursor:pointer; min-height:unset; box-shadow:none; margin:0;
    white-space:nowrap; transition:all 0.15s; line-height:1.4;
}
.svc-notif-read-btn:hover { color:var(--success); border-color:var(--success); }
.svc-notif-del-btn {
    background:transparent; border:1px solid rgba(220,38,38,0.2);
    color:rgba(220,38,38,0.5); font-size:0.65em; border-radius:8px;
    padding:4px 8px; cursor:pointer; min-height:unset; box-shadow:none; margin:0;
    white-space:nowrap; transition:all 0.15s; line-height:1.4;
}
.svc-notif-del-btn:hover { color:var(--danger); border-color:var(--danger); }
/* Delete all + swipe hint bar */
.svc-notif-toolbar {
    display:flex; align-items:center; justify-content:space-between;
    padding:4px 2px 8px;
}
.svc-notif-hint { font-size:0.7em; color:var(--text-muted); }
.svc-delete-all-btn {
    background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.2);
    color:var(--danger); font-size:0.72em; font-weight:700; border-radius:20px;
    padding:4px 12px; cursor:pointer; min-height:unset; box-shadow:none; margin:0;
    transition:all 0.15s;
}
.svc-delete-all-btn:hover { background:rgba(220,38,38,0.15); }
.notif-row { padding:9px; border-radius:10px; cursor:pointer; transition:background 0.12s; margin-bottom:3px; display:flex; align-items:flex-start; justify-content:space-between; gap:6px; }
.notif-row:hover { background:rgba(220,38,38,0.08); }
.notif-row-grp { font-size:1.3em; font-weight:900; color:var(--primary-red); font-family:var(--font-heading); }
.notif-row-info { font-size:0.78em; color:var(--text-muted); margin-top:2px; line-height:1.45; }
.notif-row-left { flex:1; min-width:0; }
.notif-mark-btn {
    flex-shrink:0;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    font-size: 0.68em;
    font-weight: 600;
    border-radius: 8px;
    padding: 4px 8px;
    cursor: pointer;
    white-space: nowrap;
    min-height: unset;
    width: auto;
    box-shadow: none;
    margin: 0;
    line-height: 1.4;
    transition: all 0.15s;
}
.notif-mark-btn:hover { background: rgba(16,185,129,0.15); border-color: #10b981; color: #10b981; }
.notif-panel-mark-all {
    width: 100%;
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    font-size: 0.75em;
    font-weight: 600;
    border-radius: 8px;
    padding: 7px;
    cursor: pointer;
    margin-top: 8px;
    min-height: unset;
    box-shadow: none;
    transition: all 0.15s;
}
.notif-panel-mark-all:hover { background: rgba(16,185,129,0.1); border-color: #10b981; color: #10b981; }
.notif-empty { text-align:center; padding:25px; color:var(--text-muted); font-size:0.84em; }

/* Live Toast */
#toastWrap { position:fixed; bottom:18px; right:16px; z-index:99999;
    display:flex; flex-direction:column; gap:8px;
    max-width:320px; width:calc(100% - 32px); pointer-events:none; }
.toast-item {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-left:4px solid var(--danger); border-radius:14px;
    padding:12px 13px; box-shadow:var(--shadow-glass);
    pointer-events:all; display:flex; align-items:flex-start; gap:10px;
    animation:tIn 0.32s cubic-bezier(0.34,1.5,0.64,1);
}
.toast-item.bye { animation:tOut 0.26s ease forwards; }
@keyframes tIn  { from{transform:translateX(110%);opacity:0;} to{transform:translateX(0);opacity:1;} }
@keyframes tOut { to{transform:translateX(110%);opacity:0;} }
.toast-ico { font-size:1.6rem; flex-shrink:0; line-height:1; }
.toast-bd { flex:1; min-width:0; }
.toast-ttl { font-weight:700; font-size:0.86em; color:var(--danger); margin-bottom:2px; font-family:var(--font-heading); }
.toast-sub { font-size:0.77em; color:var(--text-muted); line-height:1.4; }
.toast-x { background:none; border:none; color:var(--text-muted); font-size:1rem; cursor:pointer;
    padding:0; margin:0; width:auto; min-height:unset; flex-shrink:0; line-height:1; }
.toast-x:hover { color:var(--text-main); transform:none; box-shadow:none; }
@media(max-width:767px){
    #toastWrap { bottom:82px; right:8px; max-width:calc(100% - 16px); }
    .notif-panel { width: calc(100vw - 24px); }
    .notif-panel-anchor { right: 0; }
}

/* Smart Suggestion Box */
.sug-wrap { position:relative; }
.sug-list {
    position:absolute; top:100%; left:0; right:0; z-index:300;
    background:var(--bg-card); border:1px solid var(--border-color); border-top:none;
    border-radius:0 0 var(--radius-md) var(--radius-md);
    max-height:200px; overflow-y:auto;
    box-shadow:var(--shadow-glass); display:none;
}
.sug-list.on { display:block; }
.sug-opt {
    padding:8px 12px; cursor:pointer; font-size:0.85em; color:var(--text-main);
    border-bottom:1px solid var(--border-color);
    display:flex; justify-content:space-between; align-items:center; gap:8px;
    transition:background 0.1s;
}
.sug-opt:last-child { border-bottom:none; }
.sug-opt:hover, .sug-opt.act { background:rgba(220,38,38,0.08); color:var(--primary-red); }
.sug-opt mark { background:transparent; color:var(--primary-red); font-weight:800; }
.sug-cat { font-size:0.68em; color:var(--text-muted); background:var(--input-bg); padding:1px 6px; border-radius:8px; white-space:nowrap; flex-shrink:0; }
[data-theme="light"] .sug-list { background:#fff; }
[data-theme="light"] .sug-opt { color:#0b1120; }

/* WhatsApp button */
.wa-btn {
    background:linear-gradient(135deg,#25D366,#0f9e4e) !important;
    color:#fff !important; border:none !important;
    display:inline-flex !important; align-items:center; justify-content:center; gap:5px;
}
.wa-btn:hover { box-shadow:0 6px 20px rgba(37,211,102,0.5) !important; transform:translateY(-2px) !important; }


/* ============================================================
   PERFORMANCE: containment, GPU promotion, paint isolation
   ============================================================ */
.stats-grid, .cards-grid, .req-grid, .kpi-grid, .charts-grid { contain: layout style; }
.tab-content { contain: layout; }
.dc { contain: layout style; }
.stat-card { contain: layout style; }
.kpi-card { contain: layout style; }
img { decoding: async; }

/* ── SCROLL & LAYOUT PERFORMANCE ── */
html { scrollbar-gutter: stable; scroll-padding-top: 130px; }
.quick-shift-container, .donor-table-wrapper, .scroll-content {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}
.tab-btn:not(.active):hover { opacity: 0.85; }

/* ============================================================
   FIXED HEADER ONLY — nav bar removed, header is the only fixed element
   ============================================================ */

/* ── GPU & PAINT ISOLATION ── */
.dc, .stat-card, .kpi-card, .req-card, .nearby-card, .footer-card {
    contain: layout style;
    transform: translateZ(0);
}
/* Below-fold sections — skip rendering until near viewport (desktop only) */
@media(min-width: 651px) {
#analyticsSection, #mapSection {
    content-visibility: auto;
    contain-intrinsic-size: 0 600px;
}
}
/* Compositor layer for fixed header */
header { isolation: isolate; }
/* Prevent subpixel jank on text during scroll */
.section-title, h1, h2, h3 { text-rendering: optimizeSpeed; }
html { scroll-padding-top: 92px; }

/* ============================================================
   APP-MODE: PAGE SWITCHING SYSTEM with smooth animations
   ============================================================ */
.app-page {
    display: none;
    min-height: calc(100vh - 110px);
    opacity: 0;
}
.app-page.page-active {
    display: flex;
    flex-direction: column;
    animation: pageSlideIn 0.28s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}
/* Pin the "Connect with us" bar + copyright line to the very bottom of the
   page: the social bar soaks up any spare vertical space so it and the footer
   that follows always sit at the bottom, even on short pages. */
.app-page.page-active > .social-connect { margin-top: auto; }
.app-page.page-exit {
    animation: pageSlideOut 0.2s ease forwards;
}
@keyframes pageSlideIn {
    from { opacity: 0; transform: translateY(12px) scale(0.99); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes pageSlideOut {
    from { opacity: 1; transform: translateY(0); }
    to   { opacity: 0; transform: translateY(-8px); }
}

/* On desktop: left sidebar navigation */
@media(min-width: 651px) {
    .app-page { display: block !important; opacity: 1 !important; animation: none !important; }
    .app-page-header { display: none !important; }

    /* ── Sidebar base ── */
    .mobile-bottom-nav {
        display: flex !important;
        position: fixed !important;
        left: 0 !important; top: 0 !important; bottom: 0 !important;
        right: auto !important; width: 230px !important;
        flex-direction: column !important;
        border-top: none !important;
        border-right: 1px solid rgba(255,255,255,0.07) !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.35) !important;
        padding: 0 10px 20px !important;
        overflow-y: auto; overflow-x: hidden;
        z-index: 900 !important;
    }
    .mobile-bottom-nav::before {
        content: '🩸 Blood Arena';
        display: block;
        font-size: 1.05rem; font-weight: 800;
        color: var(--primary-red);
        padding: 22px 10px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin: 0 -10px 10px;
        background: var(--bg-card);
        letter-spacing: 0.3px;
        flex-shrink: 0;
        position: sticky; top: 0; z-index: 2;
    }
    .mobile-bottom-nav-inner {
        flex-direction: column !important;
        height: auto !important;
        gap: 3px !important;
        width: 100% !important;
        padding: 0 !important;
        flex: 1;
    }
    .mbn-item {
        flex: none !important;
        width: 100% !important;
        flex-direction: row !important;
        justify-content: flex-start !important;
        gap: 12px !important;
        padding: 11px 14px !important;
        font-size: 0.87rem !important;
        font-weight: 600 !important;
        border-radius: 12px !important;
        margin: 1px 0 !important;
        text-align: left !important;
    }
    .mbn-pill {
        width: 34px !important; height: 34px !important;
        border-radius: 10px !important; flex-shrink: 0 !important;
    }
    .mbn-item .mbn-icon { width: 18px !important; height: 18px !important; }
    .mbn-item span:last-child { font-size: 0.87rem !important; white-space: nowrap; }
    .mbn-item.mbn-active {
        background: rgba(220,38,38,0.13) !important;
        color: var(--primary-red) !important;
    }
    .mbn-item.mbn-active .mbn-pill {
        background: rgba(220,38,38,0.18) !important;
        box-shadow: none !important;
    }
    .mbn-item.mbn-active .mbn-icon { stroke: var(--primary-red) !important; }
    /* ── Notification badge on sidebar item ── */
    .mbn-item .mbn-badge {
        margin-left: auto !important;
        font-size: 0.68rem !important;
    }
    /* Push all body content to the right of the sidebar */
    body { padding-left: 230px !important; padding-bottom: 0 !important; }
    /* Header also shifts right */
    header, .site-header { left: 230px !important; }
    /* Toast above bottom right */
    #toastWrap { bottom: 20px !important; left: calc(230px + 12px) !important; }
    /* Notification prompt */
    #notifPrompt { left: calc(230px + 16px) !important; right: 16px !important; bottom: 20px !important; }
    /* Offline alert */
    #offlineAlert { left: 230px !important; }
    /* PWA install overlay — show over content area only */
    #pwaInstallOverlay { left: 230px !important; }
}
/* On mobile: show the bottom nav bar (FIXED, never scrolls away) */
@media(max-width: 650px) {
    .mobile-bottom-nav { display: flex !important; }
    /* Push page content up so it never hides behind the bottom nav */
    body { padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px)); }
    .app-page { min-height: calc(100vh - 110px - 64px); padding-bottom: 8px; }
    /* Toast should sit above bottom nav */
    #toastWrap { bottom: calc(80px + env(safe-area-inset-bottom, 0px)) !important; }
    /* Notification prompt above bottom nav */
    /* notif-prompt: own media query above */
    /* Footer only shows on desktop; developer cards are inside page-home on mobile */
    .site-footer { display: none !important; }
}

/* ============================================================
   APP PAGE HEADER (title bar for each page on mobile)
   ============================================================ */
.app-page-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-main);
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-main);
    position: sticky;
    top: 76px; /* sticks just under the fixed header */
    z-index: 30; /* below header(50) and notif panel(9100) */
    margin-bottom: 0;
}
.app-page-header .ph-icon { font-size: 1.25rem; }

/* ── Home page modern banner ── */
.app-page-header.home-banner {
    background: linear-gradient(135deg, rgba(220,38,38,0.06) 0%, rgba(99,102,241,0.04) 100%);
    border-bottom: 1px solid rgba(220,38,38,0.16);
    padding: 10px 16px;
    gap: 12px;
    min-height: 66px;
}
.home-banner-logo {
    height: 44px;
    width: 44px;
    object-fit: contain;
    border-radius: 10px;
    flex-shrink: 0;
    filter: drop-shadow(0 2px 8px rgba(220,38,38,0.3));
}
.home-banner-text {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}
.home-banner-title {
    font-family: var(--font-heading);
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.2;
}
.home-banner-tagline {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 500;
    line-height: 1.3;
}
.home-banner-tagline-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 2px;
}
.online-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--success);
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 20px;
    padding: 2px 7px 2px 5px;
    white-space: nowrap;
    flex-shrink: 0;
}
.online-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--success);
    flex-shrink: 0;
    animation: onlinePulse 2s ease-in-out infinite;
}
@keyframes onlinePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.5); }
    50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0); }
}
[data-theme="light"] .app-page-header.home-banner {
    background: linear-gradient(135deg, rgba(220,38,38,0.08), rgba(99,102,241,0.05));
    border-bottom-color: rgba(220,38,38,0.2);
}
.app-version-badge {
    margin-left: auto;
    font-size: 0.58em;
    font-weight: 600;
    color: var(--text-muted);
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 2px 8px;
    letter-spacing: 0.5px;
    font-family: var(--font-body);
    opacity: 0.7;
}

/* ============================================================
   COMPACT DONOR CARDS — grid layout, big blood badge, all info
   ============================================================ */
.dc {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    margin-bottom: 4px;
    overflow: hidden;
    position: relative;
    transform: translateZ(0);
    display: grid;
    grid-template-columns: 46px 1fr 40px;
    align-items: stretch;
    gap: 0;
    contain: layout style;
    transition: background 0.1s;
}
.dc:active { background: rgba(128,128,128,0.05); }
.dc-top { display: contents; }
.dc-top-left { display: contents; }
.dc-body { display: contents; }
.dc-meta { display: contents; }
.dc-top, .dc-body { padding: 0 !important; border: none !important; }

/* Left: blood group badge column */
.dc-badge-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    background: rgba(0,0,0,0.08);
    border-right: 1px solid var(--border-color);
    padding: 4px 2px;
    gap: 2px;
}
[data-theme="light"] .dc-badge-wrap { background: rgba(0,0,0,0.04); }

/* Serial number above blood group in card */
.dc-sn {
    font-size: 0.72em;
    font-weight: 800;
    color: var(--text-muted);
    line-height: 1;
    text-align: center;
    opacity: 0.9;
    display: block;
    letter-spacing: 0.5px;
}
.dc-badge {
    font-size: 0.72em !important;
    padding: 5px 2px !important;
    border-radius: 7px !important;
    font-weight: 900 !important;
    letter-spacing: 0.2px;
    display: block;
    width: 40px;
    text-align: center;
    line-height: 1.15;
    word-break: break-all;
}

/* Middle: info column */
.dc-serial { display: none; }
.dc-info {
    padding: 5px 7px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1px;
    min-width: 0;
}
.dc-name {
    font-weight: 700;
    font-size: calc(0.80em * var(--dc-zoom));
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.dc-status-badge {
    font-size: calc(0.56em * var(--dc-zoom)) !important;
    padding: 1px 5px !important;
    border-radius: 20px !important;
    display: inline-block !important;
    font-weight: 600 !important;
    white-space: nowrap;
    align-self: flex-start;
    margin-top: 1px;
}
.dc-loc {
    font-size: calc(0.64em * var(--dc-zoom));
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.15;
}
.dc-last {
    font-size: calc(0.59em * var(--dc-zoom));
    color: var(--text-muted);
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Right: call button column — full height tap target */
.dc-call-btn {
    position: static !important;
    transform: none !important;
    align-self: stretch;
    width: 40px !important;
    height: auto !important;
    min-height: 52px !important;
    border-radius: 0 10px 10px 0 !important;
    background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #fff !important;
    font-size: 1.1em;
    display: flex !important;
    align-items: center;
    justify-content: center;
    border: none !important;
    border-left: 1px solid rgba(255,255,255,0.08) !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    cursor: pointer;
    transition: filter 0.1s;
    line-height: 1;
    -webkit-tap-highlight-color: transparent;
    -webkit-appearance: none;
}
.dc-call-btn:active { filter: brightness(0.85) !important; }

/* Skeleton card */
.dc-skeleton { min-height: 52px; }

/* ============================================================
   MOBILE APP-LIKE BOTTOM NAVIGATION BAR
   ============================================================ */
/* ── Bottom Navigation Bar ── */
.mobile-bottom-nav {
    display: none;
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    top: auto !important;
    z-index: 9999 !important;
    background: var(--bg-card);
    border-top: 1px solid rgba(255,255,255,0.06);
    box-shadow: 0 -8px 32px rgba(0,0,0,0.5), 0 -1px 0 rgba(255,255,255,0.04);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    transform: none !important;
    will-change: auto;
    -webkit-transform: translateZ(0);
    backface-visibility: hidden;
}
.mobile-bottom-nav-inner {
    display: flex;
    align-items: center;
    height: 64px;
    padding: 0 4px;
    gap: 2px;
    width: 100%;
}
.mbn-item {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.68rem;
    font-weight: 600;
    padding: 5px 2px;
    min-height: unset;
    width: auto;
    box-shadow: none;
    border-radius: 14px;
    margin: 4px 0;
    transition: color 0.18s ease, background 0.18s ease;
    -webkit-tap-highlight-color: transparent;
    position: relative;
    letter-spacing: 0px;
    overflow: visible;
}
/* pill wrapper for icon */
.mbn-pill {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 26px;
    border-radius: 13px;
    background: transparent;
    transition: background 0.22s ease, box-shadow 0.22s ease;
    flex-shrink: 0;
}
.mbn-item .mbn-icon {
    width: 20px;
    height: 20px;
    display: block;
    flex-shrink: 0;
    transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1);
    stroke: currentColor;
    fill: none;
}
.mbn-item span:last-child {
    font-size: 1em;
    display: block;
    white-space: nowrap;
    max-width: 100%;
    line-height: 1;
    text-align: center;
}
/* ── Active state ── */
.mbn-item.mbn-active {
    color: #16a34a;
}
.mbn-item.mbn-active .mbn-pill {
    background: rgba(22,163,74,0.13);
    box-shadow: 0 0 12px rgba(22,163,74,0.25), inset 0 0 0 1px rgba(22,163,74,0.18);
}
.mbn-item.mbn-active .mbn-icon {
    transform: scale(1.12);
    stroke: #16a34a;
}
.mbn-item.mbn-active::before { display: none; }
.mbn-item.mbn-active::after  { display: none; }
/* ── Hover / tap feedback ── */
.mbn-item:active .mbn-pill {
    background: rgba(22,163,74,0.1);
    transform: scale(0.95);
}
/* ── Blood Request bottom sheet animation ── */
#bloodReqModal { transition: opacity 0.15s ease, visibility 0.15s ease !important; }
#bloodReqModal.active #bloodReqSheet { transform: translateY(0) !important; }
.req-group-btn.selected {
    background: rgba(220,38,38,0.15) !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
    box-shadow: 0 0 0 2px rgba(220,38,38,0.2) !important;
}
[data-theme="light"] .req-group-btn.selected {
    background: rgba(220,38,38,0.08) !important;
}

/* ── Center emergency button — REMOVED ── */

/* Settings panel */
/* ============================================================
   FAQ ACCORDION STYLES
   ============================================================ */
.faq-item { border:1px solid var(--border-color); border-radius:12px; margin-bottom:8px; overflow:hidden; transition:border-color 0.2s; }
.faq-q { display:flex; align-items:center; justify-content:space-between; padding:13px 16px; cursor:pointer; font-size:0.88em; font-weight:600; color:var(--text-main); background:var(--input-bg); gap:10px; user-select:none; -webkit-user-select:none; }
.faq-q:active { opacity:0.8; }
.faq-arrow { font-size:1.2em; color:var(--text-muted); transition:transform 0.25s cubic-bezier(0.34,1.56,0.64,1); flex-shrink:0; font-weight:400; }
.faq-a { display:none; padding:0 16px; background:var(--bg-card); }
.faq-a.open { display:block; padding:12px 16px 14px; }
.faq-a p { font-size:0.83em; color:var(--text-muted); line-height:1.65; margin:0 0 6px; }
.faq-a p:last-child { margin-bottom:0; }
.faq-a strong { color:var(--text-main); }
.faq-open .faq-arrow { transform:rotate(90deg); color:var(--primary-red); }

.settings-panel-overlay {
    position: fixed; inset: 0; z-index: 9990;
    bottom: calc(64px + env(safe-area-inset-bottom, 0px));
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0; visibility: hidden;
    transition: opacity 0.2s, visibility 0.2s;
}
.settings-panel-overlay.active { opacity: 1; visibility: visible; }
.settings-panel {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: var(--bg-glass);
    backdrop-filter: blur(var(--glass-blur)); -webkit-backdrop-filter: blur(var(--glass-blur));
    border-radius: 20px 20px 0 0;
    padding: 0 0 0;
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.34,1.1,0.64,1);
    max-height: calc(100vh - 64px - env(safe-area-inset-bottom, 0px));
    display: flex;
    flex-direction: column;
}
/* Desktop: settings panel as right-side drawer */
@media(min-width: 651px) {
    .settings-panel-overlay {
        left: 230px !important;
        bottom: 0 !important;
    }
    .settings-panel {
        position: absolute;
        top: 76px !important; bottom: 0 !important;
        left: auto !important; right: 0 !important;
        width: 380px !important;
        border-radius: 0 !important;
        transform: translateX(100%) !important;
        max-height: none !important;
        border-left: 1px solid var(--border-color);
    }
    .settings-panel-overlay.active .settings-panel {
        transform: translateX(0) !important;
    }
    .settings-panel-handle { display: none !important; }
}
/* The scrollable content area inside settings */
.settings-panel .settings-list {
    flex: 1 1 auto;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(220,38,38,0.3) transparent;
    padding: 8px 0 20px;
}
.settings-panel .settings-list::-webkit-scrollbar { width: 3px; }
.settings-panel .settings-list::-webkit-scrollbar-thumb { background: rgba(220,38,38,0.3); border-radius: 4px; }
.settings-panel-overlay.active .settings-panel {
    transform: translateY(0);
}
.settings-panel-handle {
    width: 40px; height: 4px;
    background: rgba(128,128,128,0.3);
    border-radius: 4px;
    margin: 12px auto 0;
}
.settings-panel-title {
    font-family: var(--font-heading);
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-main);
    padding: 14px 20px 10px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.settings-title-actions {
    display: flex; align-items: center; gap: 8px;
}
.settings-close-btn, .settings-reload-btn {
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.15);
    color: var(--text-muted);
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, transform 0.2s;
    flex-shrink: 0;
    min-height: unset !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    line-height: 1;
}
.settings-close-btn:hover {
    background: rgba(220,38,38,0.15);
    border-color: rgba(220,38,38,0.4);
    color: var(--primary-red);
    transform: rotate(90deg);
}
.settings-reload-btn:hover {
    background: rgba(59,130,246,0.15);
    border-color: rgba(59,130,246,0.4);
    color: #3b82f6;
    transform: rotate(360deg);
}
.settings-reload-btn.spinning { animation: spinOnce 0.5s linear; }
@keyframes spinOnce { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
[data-theme="light"] .settings-close-btn,
[data-theme="light"] .settings-reload-btn {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.12);
}
/* ── PWA Install Prompt ── */
/* ── PWA Install Banner (Top) ── */
#pwaInstallOverlay {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 10500;
    display: flex;
    justify-content: center;
    transform: translateY(-110%);
    transition: transform 0.4s cubic-bezier(0.34,1.26,0.64,1);
    pointer-events: none;
}
#pwaInstallOverlay.show {
    transform: translateY(0);
    pointer-events: auto;
}
#pwaInstallBox {
    background: #13161f;
    border: 1px solid var(--border-color);
    border-top: none;
    border-radius: 0 0 20px 20px;
    width: 100%; max-width: 540px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.75);
    overflow: hidden;
}
[data-theme="light"] #pwaInstallBox {
    background: #ffffff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.pwa-handle { display: none; }
.pwa-install-inner {
    padding: 14px 18px 16px;
}
/* Compact single-row layout */
.pwa-top-row {
    display: flex; align-items: center; gap: 12px;
}
.pwa-app-icon {
    width: 44px; height: 44px; border-radius: 11px;
    object-fit: cover;
    box-shadow: 0 3px 10px rgba(0,0,0,0.25);
    flex-shrink: 0;
}
.pwa-install-titles { flex: 1; min-width: 0; }
.pwa-install-titles strong {
    display: block; font-size: 0.92rem; font-weight: 700;
    color: var(--text-main); font-family: var(--font-heading);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pwa-install-titles span {
    font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 1px;
}
.pwa-top-btns {
    display: flex; gap: 8px; flex-shrink: 0;
}
.pwa-install-btn {
    background: linear-gradient(135deg, #e02424, #b91c1c);
    color: #fff; border: none; border-radius: 10px;
    padding: 9px 16px; font-size: 0.85rem; font-weight: 700;
    cursor: pointer; font-family: var(--font-heading);
    box-shadow: 0 3px 10px rgba(220,38,38,0.35);
    transition: transform 0.15s, box-shadow 0.15s;
    white-space: nowrap;
}
.pwa-install-btn:active { transform: scale(0.96); }
.pwa-dismiss-btn {
    background: transparent;
    color: var(--text-muted); border: 1px solid var(--border-color);
    border-radius: 10px; padding: 9px 12px;
    font-size: 0.82rem; font-weight: 600; cursor: pointer;
    white-space: nowrap;
}
/* Features pills — below the row */
.pwa-install-desc {
    font-size: 0.8rem; color: var(--text-muted);
    margin: 8px 0 0; line-height: 1.5;
}
.pwa-features {
    display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;
}
.pwa-feat-pill {
    background: rgba(220,38,38,0.1);
    border: 1px solid rgba(220,38,38,0.2);
    color: var(--primary-red);
    border-radius: 20px; padding: 3px 9px;
    font-size: 0.72rem; font-weight: 600;
}
/* iOS steps */
.pwa-ios-steps {
    background: rgba(59,130,246,0.08);
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 10px; padding: 10px 12px;
    font-size: 0.8rem; color: var(--text-muted);
    line-height: 1.65; margin-top: 10px;
}
.pwa-ios-steps strong { color: var(--text-main); }
.pwa-btn-row { display: flex; gap: 8px; margin-top: 10px; }
.pwa-btn-row .pwa-dismiss-btn { flex: 1; }
@media (max-width: 650px) {
    .pwa-install-inner { padding: 12px 14px 14px; }
    .pwa-app-icon { width: 38px; height: 38px; }
    .pwa-install-btn { padding: 8px 13px; font-size: 0.82rem; }
    .pwa-dismiss-btn { padding: 8px 10px; }
}
/* ── Offline Alert Banner ── */
#offlineAlert {
    position: fixed; top: 0; left: 0; right: 0; z-index: 10600;
    background: linear-gradient(90deg, #dc2626, #b91c1c);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: center;
    padding: 8px 16px;
    display: none;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 12px rgba(220,38,38,0.4);
}
#offlineAlert.show { display: flex; animation: slideDownAlert 0.3s ease; }
@keyframes slideDownAlert {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.offline-retry-btn {
    background: rgba(255,255,255,0.25);
    border: 1px solid rgba(255,255,255,0.5);
    color: #fff;
    border-radius: 12px;
    padding: 2px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    min-height: unset !important;
    margin: 0 !important;
    transition: background 0.15s;
}
.offline-retry-btn:hover { background: rgba(255,255,255,0.4); }

/* ── Pull to Refresh ── */
#ptrIndicator {
    position: fixed; top: -72px; left: 50%; transform: translateX(-50%);
    z-index: 10500; width: 46px; height: 46px;
    background: #1a1a2e; border-radius: 50%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5), 0 0 0 1.5px rgba(239,68,68,0.3);
    display: flex; align-items: center; justify-content: center;
    transition: top 0.22s cubic-bezier(0.34,1.4,0.64,1), opacity 0.2s;
    pointer-events: none; opacity: 0;
}
#ptrIndicator.ptr-visible { top: 16px; opacity: 1; }
#ptrIndicator svg { width: 22px; height: 22px; }
#ptrIndicator.ptr-spinning svg { animation: gearSpin 0.5s linear infinite; }
[data-theme="light"] #ptrIndicator {
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 1.5px rgba(239,68,68,0.2);
}

/* ── Network Live Status ── */
#netStatusDot {
    position: fixed; bottom: 90px; right: 12px;
    z-index: 9990;
    display: flex; align-items: center; gap: 5px;
    background: rgba(0,0,0,0.55); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px; padding: 4px 9px 4px 6px;
    font-size: 0.65rem; font-weight: 700; color: #10b981;
    pointer-events: none;
    transition: color 0.3s;
}
#netStatusDot::before {
    content: '';
    width: 7px; height: 7px; border-radius: 50%;
    background: #10b981;
    animation: netPing 2s ease-in-out infinite;
    flex-shrink: 0;
    transition: background 0.3s;
}
.live-online-count { font-weight: 600; opacity: 0.9; white-space: nowrap; }
#netStatusDot.net-offline { color: #6b7280; }
#netStatusDot.net-offline::before { background: #6b7280; animation: none; }
#netStatusDot.net-offline .live-online-count { display: none; }
@keyframes netPing {
    0%   { box-shadow: 0 0 0 0   rgba(16,185,129,0.55); }
    60%  { box-shadow: 0 0 0 6px rgba(16,185,129,0);    }
    100% { box-shadow: 0 0 0 0   rgba(16,185,129,0);    }
}
[data-theme="light"] #netStatusDot {
    background: rgba(255,255,255,0.9); border-color: rgba(0,0,0,0.08); color: #059669;
}
[data-theme="light"] #netStatusDot::before { background: #059669; }
[data-theme="light"] #netStatusDot.net-offline { color: #9ca3af; }
[data-theme="light"] #netStatusDot.net-offline::before { background: #9ca3af; }
@media(min-width: 651px) { #netStatusDot { bottom: 18px; right: 18px; } }

/* ── Vibration settings icon ── */
.si-vibr .settings-item-icon { background: rgba(168,85,247,0.15); }
.settings-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(128,128,128,0.08);
    cursor: pointer;
    transition: background 0.1s;
    -webkit-tap-highlight-color: transparent;
}
.settings-item:active { background: rgba(128,128,128,0.07); }
.settings-item:last-child { border-bottom: none; }
.settings-item-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.settings-item-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.si-theme .settings-item-icon { background: rgba(245,158,11,0.15); }
.si-notif .settings-item-icon  { background: rgba(59,130,246,0.15); }
.si-sound .settings-item-icon  { background: rgba(16,185,129,0.15); }
.si-loc .settings-item-icon    { background: rgba(220,38,38,0.15); }
.si-about .settings-item-icon  { background: rgba(139,92,246,0.15); }
.si-terms .settings-item-icon  { background: rgba(107,114,128,0.15); }
.si-faq .settings-item-icon    { background: rgba(234,179,8,0.15); }
.si-install .settings-item-icon { background: rgba(220,38,38,0.15); }
.si-faq    .settings-item-icon { background: rgba(234,179,8,0.15); }
.si-clear  .settings-item-icon { background: rgba(239,68,68,0.12); }
.si-zoom .settings-item-icon   { background: rgba(6,182,212,0.15); }

/* Zoom stepper widget in settings */
.zoom-stepper {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 24px;
    overflow: hidden;
}
.zoom-btn {
    width: 34px; height: 30px;
    display: flex; align-items: center; justify-content: center;
    background: transparent;
    border: none; cursor: pointer;
    font-size: 1.1em; font-weight: 700;
    color: var(--text-main);
    transition: background 0.12s;
    flex-shrink: 0;
    min-height: unset !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}
.zoom-btn:active { background: rgba(6,182,212,0.15); }
.zoom-val {
    font-size: 0.72em; font-weight: 800;
    color: #06b6d4;
    min-width: 38px; text-align: center;
    font-family: var(--font-heading);
    pointer-events: none;
    border-left: 1px solid var(--border-color);
    border-right: 1px solid var(--border-color);
    height: 30px; line-height: 30px;
}
.settings-item-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.settings-item-label {
    font-size: 0.92em;
    font-weight: 700;
    color: var(--text-main);
}
.settings-item-sub {
    font-size: 0.74em;
    color: var(--text-muted);
}
.settings-item-right {
    color: var(--text-muted);
    font-size: 0.85em;
}
/* Toggle switch */
.settings-toggle {
    width: 46px; height: 26px;
    background: rgba(128,128,128,0.25);
    border-radius: 13px;
    position: relative;
    transition: background 0.2s;
    flex-shrink: 0;
}
.settings-toggle::after {
    content: '';
    position: absolute;
    width: 20px; height: 20px;
    background: #fff;
    border-radius: 50%;
    top: 3px; left: 3px;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
}
.settings-toggle.on {
    background: var(--primary-red);
}
.settings-toggle.on::after {
    transform: translateX(20px);
}

/* On mobile: show bottom nav + use page system */
@media(max-width: 650px) {
    .mobile-bottom-nav { display: block; }
    body { padding-top: 122px; padding-bottom: calc(58px + env(safe-area-inset-bottom, 0px)); }

    /* Mobile navigation — bottom nav handles pages */

    /* Stats grid compact */
    .stats-container {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
        padding: 0 10px !important;
        margin: 12px auto 16px !important;
    }
    .stat-card { padding: 16px 10px !important; }
    .stat-card h4 { font-size: 2.25rem !important; }
    .stat-card .count { font-size: 1.08em !important; }

    /* Mobile kpi 2x2 */
    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .charts-grid { grid-template-columns: 1fr !important; }

    /* Pagination */
    .pagination a { min-width: 34px; height: 34px; font-size: 0.8em; padding: 0 8px; }

    /* Emergency banner */
    .emergency-banner { flex-direction: column; gap: 10px; }
    .emergency-banner-btns { width: 100%; flex-direction: row; }
    .btn-emergency, .btn-view-requests { flex: 1; text-align: center; font-size: 0.82em; padding: 8px 8px; }

    /* Donor cards list padding */
    .donor-cards-container { padding: 0 8px; }

    /* Hide desktop table on mobile */
    .donor-table-wrapper { display: none; }
}

/* Toast above bottom nav */
@media(max-width: 767px){
    #toastWrap { bottom: 82px; right: 8px; max-width: calc(100% - 16px); }
}

/* ============================================================
   HOME HERO BAR
   ============================================================ */
.home-hero-bar {
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: var(--bg-glass);
    backdrop-filter: blur(var(--glass-blur)); -webkit-backdrop-filter: blur(var(--glass-blur));
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    margin: 10px 12px 0;
    padding: 12px 8px;
    box-shadow: var(--shadow-glass);
}
.home-hero-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    flex: 1;
}
.home-hero-num {
    font-family: var(--font-heading);
    font-size: 1.6rem;
    font-weight: 900;
    color: var(--primary-red);
    line-height: 1.1;
}
.home-hero-lbl {
    font-size: 0.62em;
    color: var(--text-muted);
    font-weight: 600;
    text-align: center;
}
.home-hero-divider {
    width: 1px;
    height: 36px;
    background: var(--border-color);
    flex-shrink: 0;
}
[data-theme="light"] .home-hero-bar {
    background: #fff;
    box-shadow: 0 2px 12px rgba(99,102,241,0.10);
}

[data-theme="light"] .quick-shift-container {
    background: #f0f4ff;
    border-bottom: 1px solid rgba(99,102,241,0.15);
}

/* ============================================================
   LIGHT MODE FIXES FOR NEW ELEMENTS
   ============================================================ */
[data-theme="light"] .mobile-bottom-nav {
    background: rgba(255,255,255,0.97);
    border-top: 1px solid rgba(80,110,200,0.15);
    box-shadow: 0 -2px 16px rgba(80,110,200,0.10);
}
[data-theme="light"] .mbn-item { color: #7a8599; }
[data-theme="light"] .mbn-item.mbn-active { color: #16a34a; }
[data-theme="light"] .mbn-item.mbn-active .mbn-pill {
    background: rgba(22,163,74,0.1);
    box-shadow: 0 0 10px rgba(22,163,74,0.2), inset 0 0 0 1px rgba(22,163,74,0.15);
}
[data-theme="light"] .settings-panel {
    background: #fff;
    border-top: 1px solid rgba(80,110,200,0.15);
}
[data-theme="light"] .settings-item { border-bottom: 1px solid rgba(80,110,200,0.08); }
[data-theme="light"] .settings-panel-title { color: #0b1120; border-bottom: 1px solid rgba(80,110,200,0.12); }
[data-theme="light"] .app-page-header {
    background: rgba(240,244,255,0.99);
    border-bottom: 1px solid rgba(80,110,200,0.15);
    color: #0b1120;
}
[data-theme="light"] .app-version-badge {
    background: rgba(80,110,200,0.08);
    border-color: rgba(80,110,200,0.2);
    color: #4338ca;
}
[data-theme="light"] .dc {
    background: #fff;
    border: 1px solid rgba(80,110,200,0.14);
}
[data-theme="light"] .dc:active { background: rgba(80,110,200,0.04); }

/* ============================================================
   BOTTOM NAV — scroll hint fade on right edge
   ============================================================ */
.mobile-bottom-nav::after {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0;
    width: 24px;
    background: linear-gradient(to right, transparent, var(--bg-card));
    pointer-events: none;
    border-radius: 0 0 0 0;
}
[data-theme="light"] .mobile-bottom-nav::after {
    background: linear-gradient(to right, transparent, rgba(255,255,255,0.97));
}

/* ============================================================
   GPS PERMISSION PROMPT STYLE
   ============================================================ */
#gpsPermPrompt .popup { text-align: center; }
#gpsAllowBtn { font-size: 1em; font-weight: 700; }

/* ============================================================
   MAP PICKER MODAL
   ============================================================ */
#mapPickerModal .popup {
    padding: 0 !important;
    max-height: 90vh;
    width: 95%;
    max-width: 560px;
}

/* ============================================================
   DESKTOP IMPROVEMENTS
   ============================================================ */
@media(min-width: 651px) {
    /* Show last donation on all donors desktop */
    .dc-last { display: block !important; }
    
    /* Donor cards slightly bigger on desktop */
    .dc { grid-template-columns: 52px 1fr 44px; }
    .dc-badge { width: 46px; font-size: 0.8em !important; }
    .dc-call-btn { width: 44px !important; }

    /* Home hero bigger numbers on desktop */
    .home-hero-num { font-size: 2rem; }
    .home-hero-bar { margin: 20px auto 0; max-width: 1200px; }
    
    /* Quick filter pills bigger */
    .shift-btn { padding: 10px 22px; font-size: 0.9em; }
    
    /* Stats grid stay 2 cols */
    .stats-container { grid-template-columns: repeat(2, 1fr) !important; }
    
    /* Analytics charts better on desktop */
    .kpi-grid { grid-template-columns: repeat(3, 1fr) !important; }
    .charts-grid { grid-template-columns: 1fr 1fr !important; }
}

@media(min-width: 900px) {
    /* Wider donor cards layout on large screens */
    .donor-cards-container { display: none; }
    .donor-table-wrapper { display: block; }
}
@media(max-width: 899px) {
    .donor-table-wrapper { display: none; }
    .donor-cards-container { display: block; }
}


/* ============================================================
   PWA SPLASH SCREEN
   ============================================================ */
#pwaSplash {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: #08090f;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
    transition: opacity 0.45s ease, visibility 0.45s ease;
}
#pwaSplash.splash-hide {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.45s ease, visibility 0.45s ease;
}
#pwaSplash.splash-done {
    display: none !important; /* fully remove after fade so no z-index interference */
}
.splash-logo {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    object-fit: contain;
    box-shadow: 0 10px 36px rgba(220,38,38,0.5);
    animation: splashLogoPop 0.55s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes splashLogoPop {
    from { transform: scale(0.5); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.splash-name {
    margin-top: 18px;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #fff;
    animation: splashNameSlide 0.5s 0.2s cubic-bezier(0.34,1.4,0.64,1) both;
}
@keyframes splashNameSlide {
    from { transform: translateY(14px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.splash-name span { color: #ef4444; }
.splash-tagline {
    margin-top: 8px;
    font-size: 0.88rem;
    color: rgba(255,255,255,0.6);
    letter-spacing: 0.5px;
    animation: splashNameSlide 0.5s 0.35s cubic-bezier(0.34,1.4,0.64,1) both;
}
.splash-spinner {
    margin-top: 38px;
    width: 36px;
    height: 36px;
    animation: splashNameSlide 0.4s 0.5s ease both;
    opacity: 0.7;
    transition: animation-duration 0.15s linear;
}
/* Progress bar under gear */
.splash-progress-wrap {
    margin-top: 18px;
    width: 140px;
    height: 3px;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    overflow: hidden;
    animation: splashNameSlide 0.4s 0.6s ease both;
}
.splash-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #ef4444, #f59e0b);
    border-radius: 10px;
    transition: width 0.08s linear;
}
[data-theme="light"] .splash-progress-wrap { background: rgba(0,0,0,0.1); }
.splash-percent {
    margin-top: 10px;
    font-size: 0.72rem;
    font-weight: 700;
    color: rgba(239,68,68,0.85);
    letter-spacing: 1px;
    font-family: 'Segoe UI', monospace;
    animation: splashNameSlide 0.4s 0.65s ease both;
    min-width: 36px;
    text-align: center;
}
/* Reload count badge above progress bar */
.splash-reload-count {
    font-size: 0.65rem;
    font-weight: 700;
    color: rgba(255,255,255,0.35);
    letter-spacing: 2px;
    text-transform: uppercase;
    font-family: 'Segoe UI', monospace;
    margin-top: 14px;
    margin-bottom: -6px;
    animation: splashNameSlide 0.4s 0.55s ease both;
    display: flex;
    align-items: center;
    gap: 6px;
}
/* Pink live ping */
.splash-ping {
    width: 7px; height: 7px; border-radius: 50%;
    background: #ec4899;
    box-shadow: 0 0 0 0 rgba(236,72,153,0.6);
    animation: splashPing 1.4s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes splashPing {
    0%   { box-shadow: 0 0 0 0   rgba(236,72,153,0.6); }
    60%  { box-shadow: 0 0 0 7px rgba(236,72,153,0);   }
    100% { box-shadow: 0 0 0 0   rgba(236,72,153,0);   }
}
@keyframes gearSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* ── Page transition loader (white flash fix) ── */
#pageLoader {
    position: fixed;
    inset: 0;
    z-index: 99998;
    background: #08090f;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.15s ease, visibility 0.15s ease;
}
#pageLoader.loader-show {
    opacity: 1;
    visibility: visible;
}
.page-loader-gear {
    width: 44px;
    height: 44px;
    opacity: 0.7;
    animation: gearSpin 1s linear infinite;
}
[data-theme="light"] #pwaSplash,
[data-theme="light"] #pageLoader { background: #f8fafc; }
[data-theme="light"] .splash-name { color: #0b1120; }
[data-theme="light"] .splash-tagline { color: rgba(0,0,0,0.55); }

/* ════════════════════════════════════════════════════════════════════
   IMMERSIVE 3D FX  (gated by body.fx-on — body.fx-lite keeps it static)
   • All motion is opt-out via prefers-reduced-motion and the Settings
     "3D ও Animation" toggle (sets body.fx-lite). Functional pages keep
     their speed; heavy effects only attach under body.fx-on.
   ════════════════════════════════════════════════════════════════════ */

/* ── Global accessibility guard — kill motion for users who ask for it ── */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.001ms !important;
        scroll-behavior: auto !important;
    }
}

/* ── Animated "aurora" background (only when FX on) ──────────────────── */
/* fx-lite leaves the original static body::before from the top of the file. */
body.fx-on::before {
    animation: auroraDrift 38s ease-in-out infinite alternate;
    will-change: transform;
    transform: translateZ(0);
}
/* A second slow-moving blob layer for depth */
body.fx-on::after {
    content: "";
    position: fixed; inset: -20%; z-index: -1; pointer-events: none;
    background:
        radial-gradient(38% 38% at 25% 30%, var(--mesh-1) 0%, transparent 60%),
        radial-gradient(34% 34% at 78% 70%, var(--mesh-2) 0%, transparent 62%);
    filter: blur(8px);
    opacity: 0.9;
    animation: blobFloat 30s ease-in-out infinite alternate;
    will-change: transform;
}
@keyframes auroraDrift {
    0%   { transform: translate3d(0, 0, 0) scale(1); }
    50%  { transform: translate3d(-3%, 2%, 0) scale(1.08); }
    100% { transform: translate3d(3%, -2%, 0) scale(1.04); }
}
@keyframes blobFloat {
    0%   { transform: translate3d(0, 0, 0) rotate(0deg); }
    100% { transform: translate3d(4%, -3%, 0) rotate(8deg); }
}

/* ── 3D blood-drop hero stage ────────────────────────────────────────── */
.hero-fx {
    position: relative;
    width: 100%;
    height: clamp(190px, 36vh, 320px);
    margin: 4px 0 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: var(--radius-lg);
    isolation: isolate;
}
.hero-fx #heroCanvas {
    position: absolute; inset: 0;
    width: 100% !important; height: 100% !important;
    display: block;
}
/* Bangladesh map silhouette behind the 3D blood drop (z-index:-1 stays inside the
   isolated hero stacking context, so it sits under the canvas + fallback drop) */
.hero-map-bg {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    height: 90%;
    width: auto;
    max-width: 94%;
    object-fit: contain;
    z-index: -1;
    opacity: 0.22;
    pointer-events: none;
    filter: drop-shadow(0 0 20px rgba(0,106,78,0.55));
}
[data-theme="light"] .hero-map-bg { opacity: 0.18; }
/* Caption sits under the drop */
.hero-fx-caption {
    position: absolute;
    bottom: 10px; left: 0; right: 0;
    text-align: center;
    font-family: var(--font-heading);
    font-size: 0.82em;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    pointer-events: none;
    z-index: 2;
}
/* CSS fallback drop — shown when WebGL/FX unavailable (fx-lite or init fail) */
.hero-fx-fallback {
    font-size: clamp(72px, 22vw, 132px);
    line-height: 1;
    filter: drop-shadow(0 12px 24px var(--primary-red-soft));
    display: none;
}
/* When the canvas is active (fx-on + WebGL ok) JS hides the fallback by adding .webgl-live */
.hero-fx.webgl-live .hero-fx-fallback { display: none; }
.hero-fx:not(.webgl-live) #heroCanvas { display: none; }
.hero-fx:not(.webgl-live) .hero-fx-fallback {
    display: block;
    animation: dropFloat 4.5s ease-in-out infinite;
}
body.fx-lite .hero-fx { height: clamp(150px, 26vh, 230px); }
/* Compact floating-drop accent (e.g. Register header) — CSS-only, no WebGL */
.hero-fx-mini { height: clamp(96px, 16vh, 140px); margin: 2px 0 10px; }
.hero-fx-mini .hero-fx-fallback { font-size: clamp(48px, 13vw, 74px); }
body.fx-lite .hero-fx-mini { height: clamp(78px, 12vh, 110px); }
@keyframes dropFloat {
    0%, 100% { transform: translateY(0) rotate(-4deg); }
    50%      { transform: translateY(-14px) rotate(4deg); }
}

/* ── Animated donor illustration (Register tab) — CSS/SVG, no WebGL ── */
.donor-hero {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 6px auto 14px;
    padding: 6px 0 2px;
    isolation: isolate;
}
.donor-hero-svg {
    width: clamp(96px, 26vw, 134px);
    height: auto;
    overflow: visible;
    filter: drop-shadow(0 10px 22px var(--primary-red-soft, rgba(220,38,38,0.35)));
    animation: dhFloat 4.5s ease-in-out infinite;
}
.dh-heart {
    fill: var(--primary-red);
    stroke: rgba(255,255,255,0.18);
    stroke-width: 1.5;
    transform-box: fill-box;
    transform-origin: center;
    animation: dhBeat 1.5s ease-in-out infinite;
}
.dh-drop {
    fill: #fff;
    opacity: 0.96;
    transform-box: fill-box;
    transform-origin: center;
    animation: dhBeat 1.5s ease-in-out infinite;
}
.dh-ecg {
    fill: none;
    stroke: rgba(255,255,255,0.92);
    stroke-width: 3;
    stroke-dasharray: 180;
    stroke-dashoffset: 180;
    animation: dhEcg 2.6s ease-in-out infinite;
}
.dh-plus circle { fill: var(--success, #10b981); stroke: var(--bg-card); stroke-width: 2.5; }
.dh-plus path  { stroke: #fff; stroke-width: 3.4; }
.dh-plus {
    transform-box: fill-box;
    transform-origin: center;
    animation: dhPop 3s ease-in-out infinite;
}
/* Radiating "join us" rings behind the heart */
.donor-hero-rings {
    position: absolute;
    top: 46%; left: 50%;
    width: clamp(96px, 26vw, 134px);
    height: clamp(96px, 26vw, 134px);
    transform: translate(-50%, -50%);
    z-index: -1;
    pointer-events: none;
}
.dh-ring {
    position: absolute; inset: 0; margin: auto;
    width: 60%; height: 60%; border-radius: 50%;
    border: 2px solid var(--primary-red);
    opacity: 0;
    animation: dhRing 3s ease-out infinite;
}
.dh-ring:nth-child(2) { animation-delay: 1s; }
.dh-ring:nth-child(3) { animation-delay: 2s; }
.donor-hero-text {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: clamp(0.92em, 3.4vw, 1.08em);
    color: var(--text-main);
    text-align: center;
    margin: 0;
}
@keyframes dhFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-9px); }
}
@keyframes dhBeat {
    0%, 100%   { transform: scale(1); }
    14%        { transform: scale(1.09); }
    28%        { transform: scale(1); }
    42%        { transform: scale(1.05); }
    56%        { transform: scale(1); }
}
@keyframes dhEcg {
    0%        { stroke-dashoffset: 180; }
    55%, 100% { stroke-dashoffset: -180; }
}
@keyframes dhPop {
    0%, 70%, 100% { transform: scale(1); }
    80%           { transform: scale(1.18); }
    90%           { transform: scale(0.96); }
}
@keyframes dhRing {
    0%   { transform: scale(0.55); opacity: 0.55; }
    100% { transform: scale(1.5); opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
    .donor-hero-svg, .dh-heart, .dh-drop, .dh-ecg, .dh-plus, .dh-ring {
        animation: none !important;
    }
    .dh-ecg { stroke-dashoffset: 0; }
}

/* ── Scroll-reveal (entrance on viewport) — FX-on only ───────────────── */
body.fx-on .reveal {
    opacity: 0;
    transform: translateY(26px) rotateX(7deg);
    transform-origin: center bottom;
    transition: opacity 0.6s cubic-bezier(.22,.61,.36,1),
                transform 0.6s cubic-bezier(.22,.61,.36,1);
    will-change: opacity, transform;
}
body.fx-on .reveal.in-view {
    opacity: 1;
    transform: none;
}
/* fx-lite: reveal classes are inert (always visible) */
body.fx-lite .reveal { opacity: 1 !important; transform: none !important; }

/* ── 3D pointer tilt (JS sets --rx/--ry) — FX-on, fine-pointer only ──── */
body.fx-on .tilt3d {
    transform: perspective(700px) rotateX(var(--ry, 0deg)) rotateY(var(--rx, 0deg));
    transition: transform 0.18s ease-out, box-shadow 0.18s ease-out;
    transform-style: preserve-3d;
    will-change: transform;
}

/* ── Button micro-interaction: depth press + glow (FX-on) ────────────── */
body.fx-on button:not(.settings-toggle):active {
    transform: translateY(1px) scale(0.985);
}

/* Honour reduced-motion even if body.fx-on slipped through */
@media (prefers-reduced-motion: reduce) {
    body.fx-on::before, body.fx-on::after { animation: none !important; }
    .hero-fx:not(.webgl-live) .hero-fx-fallback { animation: none !important; }
    body.fx-on .reveal { opacity: 1 !important; transform: none !important; }
}

/* ════════════════════════════════════════════════════════════════════
   PREMIUM IMMERSIVE CARDS
   Professional glass surface + depth on every card (all modes); richer
   "live" interaction (lift, glow, light-sheen) layered on under body.fx-on.
   Donor rows (.dc) stay light enough to keep list scrolling smooth.
   ════════════════════════════════════════════════════════════════════ */

/* ── Baseline professional surface + depth (applies in every mode) ── */
.stat-card, .nearby-card, .faq-item, .dc, .home-hero-bar, .emergency-banner {
    background-image: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0) 46%);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 2px 6px -3px rgba(6,10,20,0.45),
        0 14px 30px -18px rgba(6,10,20,0.60);
    border-radius: var(--radius-md);
}
[data-theme="light"] .stat-card,
[data-theme="light"] .nearby-card,
[data-theme="light"] .faq-item,
[data-theme="light"] .dc,
[data-theme="light"] .home-hero-bar,
[data-theme="light"] .emergency-banner {
    background-image: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.35) 48%);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.9),
        0 2px 6px -3px rgba(16,24,40,0.14),
        0 16px 32px -20px rgba(16,24,40,0.26);
}

/* ── "Live" interactive layer — only under body.fx-on ── */

/* Smooth, GPU-friendly transitions on the interactive cards */
body.fx-on .stat-card,
body.fx-on .nearby-card,
body.fx-on .faq-item,
body.fx-on .dc {
    transition: box-shadow .25s ease, transform .18s ease-out, border-color .25s ease;
    will-change: transform;
}

/* Hover glow (box-shadow survives the JS tilt's inline transform) */
body.fx-on .stat-card:hover,
body.fx-on .nearby-card:hover,
body.fx-on .faq-item:hover,
body.fx-on .dc:hover {
    border-color: var(--primary-red-soft);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.10),
        0 0 0 1px var(--primary-red-soft),
        0 10px 22px -10px rgba(0,0,0,0.55),
        0 18px 48px -18px var(--primary-red-soft);
}
/* Tilt brings the card toward the viewer; add a glow while it's "lifted" */
body.fx-on .tilt-live {
    box-shadow:
        0 0 0 1px var(--primary-red-soft),
        0 22px 50px -18px rgba(0,0,0,0.6),
        0 18px 60px -20px var(--primary-red-soft) !important;
    z-index: 3;
}

/* Diagonal light-sheen sweep on hover (hover-triggered → cheap) */
body.fx-on .stat-card::after,
body.fx-on .nearby-card::after,
body.fx-on .faq-item::after,
body.fx-on .dc::after {
    content: "" !important;
    position: absolute !important;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    border-radius: inherit;
    background: linear-gradient(115deg, transparent 32%, rgba(255,255,255,0.30) 48%, transparent 64%) !important;
    transform: translateX(-130%);
    transition: transform .8s cubic-bezier(.22,.61,.36,1);
}
body.fx-on .stat-card:hover::after,
body.fx-on .nearby-card:hover::after,
body.fx-on .faq-item:hover::after,
body.fx-on .dc:hover::after {
    transform: translateX(130%);
}
/* .dc is a CSS grid (display:contents children); keep the sheen out of grid flow */
body.fx-on .dc { position: relative; }
/* Ensure sheen is clipped to the rounded card and positioned correctly */
body.fx-on .nearby-card, body.fx-on .faq-item { position: relative; overflow: hidden; }

/* ── Hero / Emergency banner: gentle "live" brand glow pulse (few nodes) ── */
body.fx-on .emergency-banner { animation: cardLiveGlow 3.4s ease-in-out infinite; }
body.fx-on .home-hero-bar    { animation: cardLiveGlow 4.6s ease-in-out infinite; }
@keyframes cardLiveGlow {
    0%, 100% { box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 8px 24px -14px var(--primary-red-soft); }
    50%      { box-shadow: inset 0 1px 0 rgba(255,255,255,0.10), 0 14px 40px -12px var(--primary-red-soft),
                            0 0 0 1px var(--primary-red-soft); }
}

/* ── Settings rows: lighter "immersive" treatment (slide + icon pop + glow) ── */
body.fx-on .settings-item {
    transition: background .2s ease, transform .18s ease-out, box-shadow .2s ease;
    border-radius: var(--radius-md);
}
body.fx-on .settings-item:hover {
    transform: translateX(4px);
    box-shadow: inset 3px 0 0 var(--primary-red), 0 8px 22px -16px rgba(0,0,0,0.5);
}
body.fx-on .settings-item:hover .settings-item-icon {
    transform: scale(1.12) rotate(-4deg);
    transition: transform .2s cubic-bezier(.34,1.56,.64,1);
}

/* Reduced-motion: strip the "live" animations/sheens, keep the static polish */
@media (prefers-reduced-motion: reduce) {
    body.fx-on .emergency-banner, body.fx-on .home-hero-bar { animation: none !important; }
    body.fx-on .stat-card::after, body.fx-on .nearby-card::after,
    body.fx-on .faq-item::after, body.fx-on .dc::after { display: none !important; }
}

/* ============================================================
   HAMBURGER MENU + SIDE DRAWER (mobile) + INFO PAGES
   NOTE: no backdrop-filter on these fixed-over-scroll surfaces (perf).
   ============================================================ */

/* ── Hamburger button (header, mobile only) ── */
.ba-hamburger {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; flex-shrink: 0;
    background: transparent; border: none; border-radius: 10px;
    color: var(--text-main); cursor: pointer;
    padding: 0; margin: 0 6px 0 -4px;
    -webkit-tap-highlight-color: transparent;
}
.ba-hamburger svg { width: 24px; height: 24px; stroke: currentColor; fill: none; }
.ba-hamburger:active { background: rgba(220,38,38,0.14); }
@media (min-width: 651px) { .ba-hamburger { display: none !important; } }

/* ── Side drawer ── */
.side-drawer-overlay {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(0,0,0,0.55);
    opacity: 0; visibility: hidden;
    transition: opacity 0.25s, visibility 0.25s;
}
.side-drawer-overlay.active { opacity: 1; visibility: visible; }
.side-drawer {
    position: absolute; top: 0; left: 0; bottom: 0;
    width: 82%; max-width: 320px;
    background: var(--bg-card);
    border-right: 1px solid var(--border-color);
    box-shadow: 4px 0 32px rgba(0,0,0,0.45);
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.34,1.05,0.64,1);
    display: flex; flex-direction: column;
    padding-top: env(safe-area-inset-top, 0px);
}
.side-drawer-overlay.active .side-drawer { transform: translateX(0); }
.side-drawer-head {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 16px; border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
}
.side-drawer-logo { width: 42px; height: 42px; border-radius: 11px; object-fit: cover; flex-shrink: 0; }
.side-drawer-brand { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.side-drawer-brand-name {
    font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem;
    color: var(--primary-red); line-height: 1.1;
}
.side-drawer-brand-sub { font-size: 0.68rem; color: var(--text-muted); margin-top: 2px; }
.side-drawer-close {
    width: 34px; height: 34px; flex-shrink: 0;
    background: var(--input-bg); border: 1px solid var(--border-color);
    border-radius: 9px; color: var(--text-muted); font-size: 0.95rem;
    cursor: pointer; padding: 0; margin: 0; line-height: 1;
    display: flex; align-items: center; justify-content: center;
}
.side-drawer-nav {
    flex: 1 1 auto; overflow-y: auto;
    padding: 10px 10px 16px; -webkit-overflow-scrolling: touch;
}
.side-drawer-group {
    font-size: 0.66rem; text-transform: uppercase; letter-spacing: 2px;
    color: var(--text-muted); font-weight: 700; margin: 14px 8px 6px;
}
.side-drawer-group:first-child { margin-top: 4px; }
.sd-item {
    display: flex; align-items: center; gap: 13px;
    width: 100%; text-align: left;
    background: transparent; border: none; border-radius: 12px;
    padding: 11px 12px; margin: 1px 0;
    font-size: 0.9rem; font-weight: 600; font-family: inherit;
    color: var(--text-main); cursor: pointer;
    min-height: unset; box-shadow: none;
    -webkit-tap-highlight-color: transparent;
}
.sd-item:active { background: rgba(220,38,38,0.12); }
.sd-logout { color: var(--danger); }
.sd-logout .sd-ic { background: rgba(220,38,38,0.10); }
.sd-ic {
    width: 34px; height: 34px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: var(--input-bg); border-radius: 10px; font-size: 1.05rem;
}
.sd-ic svg { width: 18px; height: 18px; stroke: var(--text-main); fill: none; }
.side-drawer-foot {
    padding: 12px 16px; border-top: 1px solid var(--border-color);
    font-size: 0.72rem; color: var(--text-muted); text-align: center; flex-shrink: 0;
}
/* Desktop already has the persistent left sidebar — drawer is mobile-only */
@media (min-width: 651px) { .side-drawer-overlay { display: none !important; } }

/* ── Info pages (About / Privacy / FAQ / Sponsor) ── */
.info-page-overlay {
    position: fixed; inset: 0; z-index: 10001;
    background: var(--bg-main);
    opacity: 0; visibility: hidden;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.34,1.02,0.64,1), opacity 0.2s, visibility 0.28s;
    display: flex; flex-direction: column;
}
.info-page-overlay.active { opacity: 1; visibility: visible; transform: translateX(0); }
.info-page { display: flex; flex-direction: column; height: 100%; width: 100%; }
.info-page-bar {
    display: flex; align-items: center; gap: 10px;
    padding: calc(env(safe-area-inset-top, 0px) + 12px) 14px 12px;
    background: var(--bg-card); border-bottom: 1px solid var(--border-color);
    flex-shrink: 0; position: sticky; top: 0; z-index: 2;
}
.info-page-back {
    width: 38px; height: 38px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: var(--input-bg); border: 1px solid var(--border-color);
    border-radius: 10px; color: var(--text-main); cursor: pointer; padding: 0; margin: 0;
}
.info-page-back svg { width: 20px; height: 20px; stroke: currentColor; fill: none; }
.info-page-title { font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: var(--text-main); }
.info-page-body {
    flex: 1 1 auto; overflow-y: auto; -webkit-overflow-scrolling: touch;
    padding: 16px 18px calc(env(safe-area-inset-bottom, 0px) + 28px);
}
.info-panel .scroll-content { max-height: none !important; overflow: visible !important; padding: 0 !important; }
/* Desktop: open to the right of the 230px sidebar */
@media (min-width: 651px) { .info-page-overlay { left: 230px; } }

