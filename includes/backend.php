<?php  
// ── PWA Manifest endpoint — no separate file needed ──────────
if (isset($_GET['manifest'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo json_encode([
        "name"                    => BRAND_NAME,
        "short_name"              => BRAND_SHORT,
        "description"             => APP_DESC,
        "start_url"               => "/",
        "scope"                   => "/",
        "display"                 => "standalone",
        "orientation"             => "portrait-primary",
        "background_color"        => SPLASH_BG,
        "theme_color"             => COLOR_THEME,
        "lang"                    => "bn",
        "categories"              => ["health","medical"],
        "prefer_related_applications" => false,
        "icons" => [
            ["src"=>"/icon-192.png","sizes"=>"192x192","type"=>"image/png","purpose"=>"any"],
            ["src"=>"/icon-192.png","sizes"=>"192x192","type"=>"image/png","purpose"=>"maskable"],
            ["src"=>"/icon-512.png","sizes"=>"512x512","type"=>"image/png","purpose"=>"any"],
            ["src"=>"/icon-512.png","sizes"=>"512x512","type"=>"image/png","purpose"=>"maskable"]
        ],
        "shortcuts" => [
            ["name"=>"রক্তদাতা খুঁজুন","short_name"=>"Donors","url"=>"/?tab=donors","icons"=>[["src"=>"/icon-192.png","sizes"=>"192x192","type"=>"image/png"]]],
            ["name"=>"Emergency Request","short_name"=>"Emergency","url"=>"/","icons"=>[["src"=>"/icon-192.png","sizes"=>"192x192","type"=>"image/png"]]]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Badge SVG endpoint — monochrome white blood drop for Android status bar ──
// Android notification status bar শুধু monochrome icon support করে।
// /icon.png colorful হওয়ায় white square দেখায়।
// এই endpoint থেকে proper monochrome blood drop SVG serve করা হয়।
if (isset($_GET['badge_icon'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=86400');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96">'
       . '<path fill="#ffffff" fill-rule="evenodd" d="'
       . 'M48 8 C48 8 18 46 18 62 a30 30 0 0 0 60 0 C78 46 48 8 48 8z '
       . 'M44 52 L44 74 L52 74 L52 52 Z '
       . 'M37 59 L59 59 L59 67 L37 67 Z'
       . '"/>'
       . '</svg>';
    exit;
}
// ─────────────────────────────────────────────────────────────
ob_start(); // Buffer output — prevents PHP warnings/notices from corrupting JSON responses
include __DIR__ . "/../db.php";

// === EXTREME SQL INJECTION PROTECTION ===
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// === SCHEMA MIGRATION — file-flag ensures this runs only ONCE ever, not every request ===
// Running ALTER TABLE + UPDATE on every request caused 2-3s delay on all AJAX calls
$_schema_flag = dirname(__DIR__) . '/.schema_v1_done';
if (!file_exists($_schema_flag)) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS total_donations INT DEFAULT 0");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS badge_level VARCHAR(10) DEFAULT 'New'");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS willing_to_donate VARCHAR(3) DEFAULT 'yes'");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS reg_geo VARCHAR(300) DEFAULT 'Not captured'");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS reg_ip VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS reg_device VARCHAR(300) DEFAULT NULL");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $conn->query("UPDATE donors SET badge_level = CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END WHERE badge_level IS NULL OR badge_level=''");
    // Also fix any badge_level that's out of sync with total_donations
    $conn->query("UPDATE donors SET badge_level = CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END");
    $conn->query("UPDATE donors SET willing_to_donate='yes' WHERE willing_to_donate IS NULL OR willing_to_donate=''");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_flag, date('Y-m-d H:i:s'));
}
// ── One-time badge sync fix (v2) ─────────────────────────────
$_schema_v2 = dirname(__DIR__) . '/.schema_v2_done';
if(!file_exists($_schema_v2) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("UPDATE donors SET badge_level = CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v2, date('Y-m-d H:i:s'));
}
// ── Schema v3: fix blood_requests table ──────────────────────
// Runs ONCE on first page load after deploy. Converts ENUM columns
// to VARCHAR for InfinityFree MySQL 5.7 compat.
$_schema_v3 = dirname(__DIR__) . '/.schema_v3_done';
if(!file_exists($_schema_v3) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    // Create table fresh if not exists (with correct schema)
    $conn->query("CREATE TABLE IF NOT EXISTS `blood_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_name` VARCHAR(100) NOT NULL,
        `blood_group` VARCHAR(5) NOT NULL,
        `hospital` VARCHAR(200) NOT NULL,
        `contact` VARCHAR(20) NOT NULL,
        `urgency` VARCHAR(10) DEFAULT 'High',
        `bags_needed` INT DEFAULT 1,
        `note` VARCHAR(500) DEFAULT '',
        `status` VARCHAR(20) DEFAULT 'Active',
        `req_ip` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Convert ENUM → VARCHAR (error suppressed if already VARCHAR)
    $conn->query("ALTER TABLE blood_requests MODIFY COLUMN urgency VARCHAR(10) DEFAULT 'High'");
    $conn->query("ALTER TABLE blood_requests MODIFY COLUMN status VARCHAR(20) DEFAULT 'Active'");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v3, date('Y-m-d H:i:s'));
}
// ── Schema v5: tie blood_requests to signed-in account ───────
// Adds auth_uid so a logged-in user can manage/delete their own
// requests directly from the Account Dashboard — no token needed.
$_schema_v5 = dirname(__DIR__) . '/.schema_v5_done';
if(!file_exists($_schema_v5) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE blood_requests ADD COLUMN auth_uid VARCHAR(128) DEFAULT NULL");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v5, date('Y-m-d H:i:s'));
}
// ── Schema v13: "required by" date/time for blood requests ───────────
// রক্ত কখন প্রয়োজন তা সংরক্ষণ করতে নতুন column। NULL allowed — পুরনো rows-এ ফাঁকা।
$_schema_v13 = dirname(__DIR__) . '/.schema_v13_done';
if(!file_exists($_schema_v13) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE blood_requests ADD COLUMN required_at DATETIME DEFAULT NULL");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v13, date('Y-m-d H:i:s'));
}
// ── Schema v14: Privacy + gender + request-notification system ───────
//  donors          : gender / hide_me / allow_call  (gender-based privacy defaults)
//  blood_requests  : hospital_lat / hospital_lng / verified_location (map + badge)
//  contact_requests: "Request" button → donor notification + accept/contact flow
//  Existing donor rows keep pre-change behaviour via column defaults
//  (hide_me=0 → visible, allow_call=1 → callable). New registrations get
//  gender-based values set explicitly in the registration handler.
$_schema_v14 = dirname(__DIR__) . '/.schema_v14_done';
if(!file_exists($_schema_v14) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS gender VARCHAR(10) DEFAULT NULL");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS hide_me TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS allow_call TINYINT(1) DEFAULT 1");
    $conn->query("ALTER TABLE blood_requests ADD COLUMN IF NOT EXISTS hospital_lat DECIMAL(10,7) DEFAULT NULL");
    $conn->query("ALTER TABLE blood_requests ADD COLUMN IF NOT EXISTS hospital_lng DECIMAL(10,7) DEFAULT NULL");
    $conn->query("ALTER TABLE blood_requests ADD COLUMN IF NOT EXISTS verified_location TINYINT(1) DEFAULT 0");
    $conn->query("CREATE TABLE IF NOT EXISTS `contact_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `donor_id` INT NOT NULL,
        `donor_auth_uid` VARCHAR(128) DEFAULT NULL,
        `requester_auth_uid` VARCHAR(128) DEFAULT NULL,
        `requester_name` VARCHAR(120) DEFAULT NULL,
        `requester_phone` VARCHAR(20) DEFAULT NULL,
        `blood_group` VARCHAR(5) DEFAULT NULL,
        `message` VARCHAR(500) DEFAULT '',
        `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_donor` (`donor_id`),
        KEY `idx_donor_uid` (`donor_auth_uid`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v14, date('Y-m-d H:i:s'));
}
// ── Schema v4: persistent analytics_counters table ───────────
// This table stores ever-increasing counters that never decrease,
// even if call_logs are cleared or donors are deleted.
$_schema_v4 = dirname(__DIR__) . '/.schema_v4_done';
if(!file_exists($_schema_v4) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `analytics_counters` (
        `counter_name` VARCHAR(50) PRIMARY KEY,
        `counter_value` BIGINT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Seed initial values from existing data so counts don't reset on first deploy
    $conn->query("INSERT INTO analytics_counters (counter_name, counter_value)
        SELECT 'total_calls_ever', COUNT(*) FROM call_logs
        ON DUPLICATE KEY UPDATE counter_value = VALUES(counter_value)");
    $conn->query("INSERT INTO analytics_counters (counter_name, counter_value)
        SELECT 'total_donations_ever', COALESCE(SUM(total_donations),0) FROM donors
        ON DUPLICATE KEY UPDATE counter_value = VALUES(counter_value)");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v4, date('Y-m-d H:i:s'));
}
// ── Schema v6: request_documents (blood request attachments) ─────────
// রক্তের অনুরোধের সাথে রোগীর ছবি/প্রেসক্রিপশন (≤২টি JPEG)। file_path হলো
// UPLOAD_DIR-এর সাপেক্ষে relative; token দিয়ে ?req_doc=token endpoint serve করে।
//
// NOTE: কোনো FOREIGN KEY নয় — DirectAdmin/shared hosting-এ FK fail করলে পুরো
// CREATE silently fail করে।
//
// IMPORTANT (stale-flag bug fix): আগে flag-file (.schema_v6_done) দিয়ে gate করা
// হতো। পুরনো FK-ভার্সন CREATE fail করেও flag লিখে ফেলত → নতুন কোড "done" ভেবে
// table কখনো বানাতো না (production-এ ছবি save হচ্ছিল না)। তাই এখন flag-file আর
// ব্যবহার করি না — blood_requests-এর মতোই প্রতি (non-serve) request-এ
// `CREATE TABLE IF NOT EXISTS` চালাই। table থাকলে এটা কার্যত no-op, আর stale
// flag-এর কোনো সুযোগই থাকে না। InnoDB fail করলে MyISAM fallback।
// (?req_doc image-serve fast-path-এ skip করি — latency বাঁচাতে।)
if(isset($conn) && !isset($_GET['req_doc'])){
    mysqli_report(MYSQLI_REPORT_OFF);
    $reqdoc_ddl = "CREATE TABLE IF NOT EXISTS `request_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `request_id` INT NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `token` VARCHAR(64) NOT NULL,
        `mime` VARCHAR(20) DEFAULT 'image/jpeg',
        `bytes` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_token` (`token`),
        KEY `idx_request` (`request_id`)
    ) ENGINE=%s DEFAULT CHARSET=utf8mb4";
    if (!$conn->query(sprintf($reqdoc_ddl, 'InnoDB'))) {
        $reqdoc_err = $conn->error; // InnoDB shared-host-এ off থাকলে MyISAM চেষ্টা
        if (!$conn->query(sprintf($reqdoc_ddl, 'MyISAM'))) {
            error_log('schema v6 request_documents create failed — InnoDB: ' . $reqdoc_err . ' | MyISAM: ' . $conn->error);
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
if(isset($conn)){
    // Set strictly to utf8mb4 to prevent multi-byte encoding SQL injection attacks
    $conn->set_charset("utf8mb4");
}

// ════════════════════════════════════════════════════════════════════
//  REQUEST DOCUMENTS — upload helpers + serve endpoint
//  রোগীর ছবি/প্রেসক্রিপশন (JPEG-এ normalize + compress, web root-এর বাইরে রাখা)।
// ════════════════════════════════════════════════════════════════════

// UPLOAD_DIR নিশ্চিত করো — না থাকলে বানাও, সাথে defense .htaccess + index.html।
// (UPLOAD_DIR web root-এর বাইরে হলে .htaccess নিষ্প্রয়োজন, কিন্তু in-root fallback-এর
//  জন্য রাখা — দুই ক্ষেত্রেই নিরাপদ।)
function reqdoc_ensure_dir() {
    $dir = UPLOAD_DIR;
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    if (is_dir($dir)) {
        $ht = $dir . '/.htaccess';
        if (!file_exists($ht)) @file_put_contents($ht, "Require all denied\nDeny from all\n");
        $idx = $dir . '/index.html';
        if (!file_exists($idx)) @file_put_contents($idx, '');
    }
    return is_dir($dir) && is_writable($dir);
}

// কোন image library আছে detect করো: 'imagick' (HEIC সহ), 'gd' (JPG/PNG/WEBP), বা ''।
function reqdoc_image_engine() {
    static $engine = null;
    if ($engine !== null) return $engine;
    if (extension_loaded('imagick') && class_exists('Imagick')) {
        $engine = 'imagick';
    } elseif (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
        $engine = 'gd';
    } else {
        $engine = '';
    }
    return $engine;
}

// একটি uploaded ফাইল process করো: validate → JPEG-এ convert → ≤REQ_DOC_TARGET_KB
// compress → UPLOAD_DIR-এ সেভ। ফেরত: ['ok'=>bool,'relpath'=>str,'bytes'=>int,'err'=>str]
function reqdoc_process_upload($tmp, $origSize) {
    if (!is_uploaded_file($tmp)) return ['ok'=>false,'err'=>'invalid upload'];
    if ($origSize <= 0 || $origSize > REQ_DOC_MAX_BYTES) {
        return ['ok'=>false,'err'=>'ফাইলটি অনেক বড় (সর্বোচ্চ ৫MB)।'];
    }
    $engine = reqdoc_image_engine();
    if ($engine === '') return ['ok'=>false,'err'=>'সার্ভারে ছবি প্রসেস করার লাইব্রেরি নেই।'];

    // type detect (path দিয়ে — extension trust করি না)
    $info = @getimagesize($tmp);
    $type = $info['mime'] ?? '';
    // getimagesize HEIC চেনে না → finfo দিয়ে আবার চেক
    if ($type === '' && function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        $type = (string)@finfo_file($f, $tmp);
        finfo_close($f);
    }
    $is_heic = stripos($type, 'heic') !== false || stripos($type, 'heif') !== false;
    $is_std  = in_array($type, ['image/jpeg','image/png','image/webp'], true);
    if (!$is_std && !$is_heic) {
        return ['ok'=>false,'err'=>'শুধু JPG/PNG/WEBP/HEIC ছবি দেওয়া যাবে।'];
    }
    if ($is_heic && $engine !== 'imagick') {
        return ['ok'=>false,'err'=>'HEIC সাপোর্ট নেই — JPG/PNG দিয়ে চেষ্টা করুন।'];
    }
    if (!reqdoc_ensure_dir()) return ['ok'=>false,'err'=>'স্টোরেজ লেখা যাচ্ছে না।'];

    // relative path: YYYYMM/<random>.jpg
    $sub = date('Ym');
    $absSub = UPLOAD_DIR . '/' . $sub;
    if (!is_dir($absSub)) @mkdir($absSub, 0775, true);
    $name = bin2hex(random_bytes(16)) . '.jpg';
    $rel  = $sub . '/' . $name;
    $dest = UPLOAD_DIR . '/' . $rel;

    try {
        if ($engine === 'imagick') {
            $img = new Imagick();
            $img->readImage($tmp);          // HEIC/WEBP/PNG/JPEG — libheif থাকলে HEIC ok
            $img->setImageFormat('jpeg');
            $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            if (method_exists($img, 'autoOrient')) @$img->autoOrient();
            $img->stripImage();
            // বড় হলে ছোট করো (long edge ≤ 1600px)
            $w = $img->getImageWidth(); $h = $img->getImageHeight();
            $maxEdge = 1600;
            if (max($w,$h) > $maxEdge) {
                if ($w >= $h) $img->resizeImage($maxEdge, 0, Imagick::FILTER_LANCZOS, 1);
                else          $img->resizeImage(0, $maxEdge, Imagick::FILTER_LANCZOS, 1);
            }
            // quality step down until ≤ target
            $q = 85; $blob = '';
            do {
                $img->setImageCompressionQuality($q);
                $blob = $img->getImagesBlob();
                if (strlen($blob) <= REQ_DOC_TARGET_KB * 1024) break;
                $q -= 10;
            } while ($q >= 35);
            $img->clear(); $img->destroy();
            if (@file_put_contents($dest, $blob) === false) {
                return ['ok'=>false,'err'=>'ফাইল সেভ করা যায়নি।'];
            }
        } else { // gd
            $data = @file_get_contents($tmp);
            $src  = @imagecreatefromstring($data);
            if (!$src) return ['ok'=>false,'err'=>'ছবিটি পড়া যায়নি।'];
            $w = imagesx($src); $h = imagesy($src);
            $maxEdge = 1600;
            if (max($w,$h) > $maxEdge) {
                $scale = $maxEdge / max($w,$h);
                $nw = (int)round($w*$scale); $nh = (int)round($h*$scale);
                $dst = imagecreatetruecolor($nw, $nh);
                // PNG/WEBP transparency → white background (JPEG-এ alpha নেই)
                $white = imagecolorallocate($dst, 255,255,255);
                imagefilledrectangle($dst, 0,0,$nw,$nh, $white);
                imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh, $w,$h);
                imagedestroy($src); $src = $dst;
            }
            $q = 85; $ok = false;
            do {
                ob_start(); imagejpeg($src, null, $q); $blob = ob_get_clean();
                if (strlen($blob) <= REQ_DOC_TARGET_KB * 1024 || $q < 35) {
                    $ok = (@file_put_contents($dest, $blob) !== false); break;
                }
                $q -= 10;
            } while (true);
            imagedestroy($src);
            if (!$ok) return ['ok'=>false,'err'=>'ফাইল সেভ করা যায়নি।'];
        }
    } catch (\Throwable $e) {
        error_log('reqdoc process: ' . $e->getMessage());
        return ['ok'=>false,'err'=>'ছবি প্রসেস করা যায়নি।'];
    }

    return ['ok'=>true, 'relpath'=>$rel, 'bytes'=>(int)@filesize($dest)];
}

// একগুচ্ছ request id-র জন্য doc URL ফেরত দেয়: [request_id => ['?req_doc=tok', ...]]।
function reqdoc_fetch_for($conn, array $ids) {
    $out = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return $out;
    $in = implode(',', $ids); // ints only — safe to inline
    mysqli_report(MYSQLI_REPORT_OFF);
    $res = $conn->query("SELECT request_id, token FROM request_documents WHERE request_id IN ($in) ORDER BY id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rid = (int)$r['request_id'];
            $out[$rid][] = '?req_doc=' . $r['token'];
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $out;
}

// ── Serve endpoint: ?req_doc=<token> — same-origin only ──────────────
// ফাইল web root-এর বাইরে; শুধু এই PHP endpoint দিয়ে, এবং শুধু নিজেদের সাইট
// থেকে (Sec-Fetch-Site / Referer) serve হয়।
if (isset($_GET['req_doc']) && isset($conn)) {
    while (ob_get_level()) ob_end_clean();
    $tok = preg_replace('/[^a-f0-9]/', '', (string)$_GET['req_doc']);
    // same-origin / same-site guard ("আমাদের সাইট থেকেই")
    $sfs = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    $ok_origin = in_array($sfs, ['same-origin','same-site','none'], true);
    if (!$ok_origin) {
        // Sec-Fetch-* অনুপস্থিত (পুরনো iOS Safari, Facebook/Messenger in-app
        // webview) — Referer host দিয়ে যাচাই। Referer host == আসল request host
        // (HTTP_HOST) হলেই same-origin; canonical SITE_URL host-ও মানি যাতে www/
        // apex বা proxy-তে 403 না হয়। SITE_URL একা hardcode করলে www. ভার্সনে
        // legacy browser-এ ছবি ভেঙে যেত।
        $ref      = $_SERVER['HTTP_REFERER'] ?? '';
        $refHost  = $ref ? parse_url($ref, PHP_URL_HOST) : '';
        $reqHost  = strtok((string)($_SERVER['HTTP_HOST'] ?? ''), ':'); // :port বাদ
        $siteHost = parse_url(SITE_URL, PHP_URL_HOST);
        $ok_origin = $refHost && (
            ($reqHost  && strcasecmp($refHost, $reqHost)  === 0) ||
            ($siteHost && strcasecmp($refHost, $siteHost) === 0)
        );
    }
    if ($tok === '' || !$ok_origin) { http_response_code(403); exit; }

    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("SELECT file_path, mime FROM request_documents WHERE token=? LIMIT 1");
    if (!$stmt) { http_response_code(404); exit; }
    $stmt->bind_param("s", $tok); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if (!$row) { http_response_code(404); exit; }

    // path traversal guard — resolved file must stay inside UPLOAD_DIR
    $full = rtrim(UPLOAD_DIR, "/\\") . '/' . $row['file_path'];
    $real = realpath($full);
    $base = realpath(UPLOAD_DIR);
    if (!$real || !$base || strncmp($real, $base, strlen($base)) !== 0 || !is_file($real)) {
        http_response_code(404); exit;
    }
    header('Content-Type: ' . ($row['mime'] ?: 'image/jpeg'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline');
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: ' . filesize($real));
    readfile($real);
    exit;
}

// ── Schema v5: service notifications ─────────────────────────
//  (পুরনো secret_code recovery — ref_code ও security_code_requests — বাদ
//   দেওয়া হয়েছে; এখন donor identity হলো Firebase account।)
$_schema_v5 = dirname(__DIR__) . '/.schema_v5_done';
if(!file_exists($_schema_v5) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `service_notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `device_id` VARCHAR(100) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v5, date('Y-m-d H:i:s'));
}
// ── Schema v6: admin_messages table ──────────────────────
$_schema_v6 = dirname(__DIR__) . '/.schema_v6_done';
if(!file_exists($_schema_v6) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sender_name` VARCHAR(100) NOT NULL,
        `sender_phone` VARCHAR(20) NOT NULL,
        `message` TEXT NOT NULL,
        `device_id` VARCHAR(100) NOT NULL,
        `is_read` TINYINT DEFAULT 0,
        `admin_reply` TEXT DEFAULT NULL,
        `replied_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v6, date('Y-m-d H:i:s'));
}
// ── Schema v7: (retired — secret-code recovery removed) ──────
// ── Schema v8: Firebase auth users (Google sign-in + phone OTP) ──
$_schema_v8 = dirname(__DIR__) . '/.schema_v8_done';
if(!file_exists($_schema_v8) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `auth_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `firebase_uid` VARCHAR(128) NOT NULL,
        `provider` VARCHAR(20) NOT NULL,
        `email` VARCHAR(190) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `name` VARCHAR(120) DEFAULT NULL,
        `device_id` VARCHAR(100) DEFAULT NULL,
        `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_uid` (`firebase_uid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v8, date('Y-m-d H:i:s'));
}
// ── Schema v9: link donor rows to a signed-in account (Google / Phone OTP) ──
//  secret_code পদ্ধতি বাদ — এখন donor identity হলো Firebase account (auth_uid)।
//  auth_uid = firebase_uid (registration-এ set হয়); legacy donor রা একই phone-এ
//  OTP sign-in করলে load_my_donor স্বয়ংক্রিয়ভাবে auth_uid বসিয়ে দেয়।
$_schema_v9 = dirname(__DIR__) . '/.schema_v9_done';
if(!file_exists($_schema_v9) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS auth_uid VARCHAR(128) DEFAULT NULL");
    $conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS auth_email VARCHAR(190) DEFAULT NULL");
    $conn->query("ALTER TABLE donors ADD INDEX idx_auth_uid (auth_uid)");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v9, date('Y-m-d H:i:s'));
}
// ── Schema v10: online visitor tracking ──────────────────────
$_schema_v10 = dirname(__DIR__) . '/.schema_v10_done';
if(!file_exists($_schema_v10) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `online_visitors` (
        `visitor_token` VARCHAR(100) PRIMARY KEY,
        `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v10, date('Y-m-d H:i:s'));
}
// ── Schema v11: account verification via Telegram / WhatsApp bot OTP ──
//  auth_users-এ verified state, আর OTP code রাখার জন্য otp_verifications টেবিল।
//  bind না করলে account unverified → call করা যাবে না (get_phone/log_call gate)।
$_schema_v11 = dirname(__DIR__) . '/.schema_v11_done';
if(!file_exists($_schema_v11) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS verified TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS verify_channel VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS verify_phone VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS telegram_chat_id VARCHAR(40) DEFAULT NULL");
    $conn->query("ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS verified_at DATETIME DEFAULT NULL");
    $conn->query("CREATE TABLE IF NOT EXISTS `otp_verifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `auth_uid` VARCHAR(128) DEFAULT NULL,
        `channel` VARCHAR(20) NOT NULL,
        `token` VARCHAR(64) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `tg_chat_id` VARCHAR(40) DEFAULT NULL,
        `code_hash` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `attempts` INT NOT NULL DEFAULT 0,
        `expires_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_token` (`token`),
        KEY `idx_auth_uid` (`auth_uid`),
        KEY `idx_phone` (`phone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v11, date('Y-m-d H:i:s'));
}
// ── Schema v12: per-donation history (My Donations → Donation History) ──
//  donors.total_donations হলো শুধু গণনা; প্রতিটি রক্তদানের তারিখ আলাদা করে
//  রাখার জন্য এই টেবিল। "আমি এইমাত্র রক্ত দিয়েছি" চাপলে একটি row যোগ হয়।
$_schema_v12 = dirname(__DIR__) . '/.schema_v12_done';
if(!file_exists($_schema_v12) && isset($conn)){
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `donation_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `auth_uid` VARCHAR(128) DEFAULT NULL,
        `donor_id` INT DEFAULT NULL,
        `donation_date` DATE NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_auth_uid` (`auth_uid`),
        KEY `idx_donor` (`donor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @file_put_contents($_schema_v12, date('Y-m-d H:i:s'));
}
// === ENHANCED SECURITY HEADERS (XSS + Clickjacking + HSTS + Permissions) ===
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.myinstants.com https://www.gstatic.com https://www.gstatic.com/firebasejs/ https://cdn.firebase.com https://apis.google.com https://www.google.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; img-src 'self' data: https: blob:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://*.basemaps.cartocdn.com https://nominatim.openstreetmap.org https://fcm.googleapis.com https://*.firebaseio.com https://*.googleapis.com https://firebaseinstallations.googleapis.com https://identitytoolkit.googleapis.com https://securetoken.googleapis.com https://www.googleapis.com; frame-src 'self' https://shsmc-blood-portal.firebaseapp.com https://*.firebaseapp.com https://apis.google.com https://www.google.com https://accounts.google.com; media-src 'self' https://www.myinstants.com; worker-src 'self' blob:;");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Permissions-Policy: geolocation=(self)");
header("Cache-Control: no-store, no-cache, must-revalidate, private");
header("X-Permitted-Cross-Domain-Policies: none");

// === XSS ESCAPE HELPER ===
function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// === CSRF & HIGH SECURITY SESSION ===
// Detect HTTPS — works on both localhost (HTTP) and production (HTTPS)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Strip port from HTTP_HOST for cookie domain (e.g. "localhost:8080" → "localhost")
$cookieDomain = strtok($_SERVER['HTTP_HOST'] ?? '', ':');

// ── Long-lived login session ──
// আগে cookie 1 দিন ও সার্ভার default gc_maxlifetime (~24 মিনিট idle) ছিল →
// একটু পরেই session মুছে যেত, ফলে ব্যবহারকারী auto "logged out" দেখত ও
// বারবার Google সাইন-ইন করতে হত। এখন 1 বছর — একবার সাইন-ইন করলে manually
// logout না করা পর্যন্ত signed-in থাকে। (client-side silent restore-ও এর
// বাইরে গেলে Firebase persisted user দিয়ে session আবার বানিয়ে নেয়।)
$SESSION_LIFETIME = 60 * 60 * 24 * 365; // 1 বছর = 31536000 সেকেন্ড
@ini_set('session.gc_maxlifetime', (string)$SESSION_LIFETIME);
@ini_set('session.cookie_lifetime', (string)$SESSION_LIFETIME);

session_set_cookie_params([
    'lifetime' => $SESSION_LIFETIME,
    'path' => '/',
    'domain' => $cookieDomain,
    'secure' => $isHttps, // true on HTTPS production, false on HTTP localhost
    'httponly' => true, // Prevents JS access to session
    'samesite' => 'Strict' // Prevents cross-site request forgery
]);
session_start();

// Prevent Session Fixation — only regenerate once per new session, not every request
if (empty($_SESSION['_initiated'])) {
    session_regenerate_id(true);
    $_SESSION['_initiated'] = true;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Online visitor ping — no CSRF needed (just a heartbeat) ──
if (!empty($_POST['ping_online'])) {
    $vt = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($_POST['visitor_token'] ?? '')), 0, 100);
    if ($vt && isset($conn)) {
        $e = mysqli_real_escape_string($conn, $vt);
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn->query("INSERT INTO online_visitors (visitor_token,last_seen) VALUES('$e',NOW()) ON DUPLICATE KEY UPDATE last_seen=NOW()");
        $conn->query("DELETE FROM online_visitors WHERE last_seen < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
    $cnt = 1;
    if (isset($conn)) {
        $r = @$conn->query("SELECT COUNT(*) c FROM online_visitors");
        if ($r) $cnt = max(1, (int)$r->fetch_assoc()['c']);
    }
    while(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['online' => $cnt]);
    exit;
}

// === RATE LIMITING (session-based) ===
function checkRateLimit($action, $maxAttempts = 10, $windowSeconds = 60) {
    $key = 'rl_' . $action;
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'window_start' => $now];
    }
    if ($now - $_SESSION[$key]['window_start'] > $windowSeconds) {
        $_SESSION[$key] = ['count' => 0, 'window_start' => $now];
    }
    $_SESSION[$key]['count']++;
    if ($_SESSION[$key]['count'] > $maxAttempts) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        die(json_encode(["status" => "error", "msg" => "অনেক বেশি চেষ্টা করা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।"]));
    }
}

// === INPUT LENGTH LIMITS ===
function validateLength($value, $max, $label) {
    if (mb_strlen($value, 'UTF-8') > $max) {
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        die(json_encode(["status" => "error", "msg" => "$label অনেক বড়। সর্বোচ্চ $max অক্ষর।"]));
    }
}

// CSRF check function — also enforces POST-only for all sensitive actions
function checkCSRF() {
    // Block GET requests from ever triggering sensitive actions
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        die(json_encode(["status" => "error", "msg" => "Method not allowed."]));
    }
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        http_response_code(403);
        die(json_encode(["status" => "error", "msg" => "Security check failed. Please refresh the page."]));
    }
}

// === AUTH HELPERS (Firebase account = donor identity) ===
// signed-in account-এর firebase_uid ফেরত দেয়, না থাকলে null
function currentAuthUid() {
    return !empty($_SESSION['auth_uid']) ? $_SESSION['auth_uid'] : null;
}
// sensitive handler-এর শুরুতে কল করো — sign-in না থাকলে JSON error দিয়ে exit করে
function requireAuth() {
    if (empty($_SESSION['auth_uid'])) {
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        die(json_encode([
            "status"   => "error",
            "code"     => "auth_required",
            "msg"      => "⚠️ আগে সাইন ইন করুন (Google অথবা ফোন নম্বর দিয়ে)।"
        ]));
    }
    return $_SESSION['auth_uid'];
}

// === ACCOUNT VERIFICATION HELPERS (Telegram / WhatsApp bot OTP) ===
//  verified = Telegram বা WhatsApp bind করা আছে। Firebase Phone-OTP (SMS) sign-in
//  নিজেই phone প্রমাণ করে — config-এ PHONE_OTP_COUNTS_VERIFIED true হলে সেটাও verified।
//  $_SESSION['auth_verified'] login/bind-এর সময় সেট হয়; এখানে সেটাই পড়া হয়।
function _auth_is_verified() {
    if (empty($_SESSION['auth_uid'])) return false;
    if (!empty($_SESSION['auth_verified'])) return true;
    if (PHONE_OTP_COUNTS_VERIFIED && ($_SESSION['auth_provider'] ?? '') === 'phone'
        && !empty($_SESSION['auth_phone'])) return true;
    return false;
}

// signed-in account verified কিনা DB থেকে নির্ণয় করে $_SESSION['auth_verified'] সেট করে
function _refresh_auth_verified($conn) {
    if (empty($_SESSION['auth_uid']) || !isset($conn)) return;
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT verified, verify_channel, verify_phone FROM auth_users WHERE firebase_uid=? LIMIT 1");
    if ($q) {
        $q->bind_param("s", $_SESSION['auth_uid']); $q->execute();
        $r = $q->get_result()->fetch_assoc(); $q->close();
        if ($r) {
            $_SESSION['auth_verified'] = !empty($r['verified']) ? true : false;
            $_SESSION['auth_verify_channel'] = $r['verify_channel'] ?? null;
            $_SESSION['auth_verify_phone'] = $r['verify_phone'] ?? null;
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// bot-এর /send endpoint-এ কোড পাঠানোর curl helper (WhatsApp ও Telegram দুটোই ব্যবহার করে)
//  $base = bot base URL, $payload = ["secret"=>..,"phone"=>..,"message"=>..], $insecure = self-signed হলে true
//  ফেরত দেয়: ["http"=>int, "body"=>string]  (http===0 মানে সংযোগই হয়নি)
function _bot_send($base, $payload, $insecure, $path = '/send') {
    $ch = curl_init(rtrim($base, '/') . $path);
    $opts = [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ];
    if ($insecure) { $opts[CURLOPT_SSL_VERIFYPEER] = false; $opts[CURLOPT_SSL_VERIFYHOST] = 0; }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["http" => $http, "body" => (string)$body];
}

// === LOCATION PRIVACY — server-side coordinate jitter ===
//  একটি donor-এর exact pinpoint কখনো client-এ পাঠানো হয় না; সবসময় এই helper
//  দিয়ে jitter করে পাঠানো হয়। $seed (যেমন donor id) + ~10-মিনিটের time-bucket
//  ব্যবহার করায় একই window-এ মান স্থির থাকে (UI flicker নেই), সময়ের সাথে সামান্য
//  বদলায় (privacy ভালো), আর কখনো exact coordinate-এ reverse করা যায় না।
//  $minMeters..$maxMeters radius-এর মধ্যে random bearing-এ shift করা হয়।
function applyLocationJitter($lat, $lng, $minMeters, $maxMeters, $seed = '') {
    $lat = (float)$lat; $lng = (float)$lng;
    if ($lat == 0.0 && $lng == 0.0) return [$lat, $lng];
    if ($maxMeters < $minMeters) { $t = $minMeters; $minMeters = $maxMeters; $maxMeters = $t; }
    $bucket = (int)floor(time() / 600);                       // ~10 min stability window
    $h1 = crc32($seed . '|dist|' . $bucket) & 0x7fffffff;     // distance entropy
    $h2 = crc32($seed . '|brng|' . $bucket) & 0x7fffffff;     // bearing entropy
    $r1 = ($h1 % 100000) / 100000.0;                          // 0..1
    $r2 = ($h2 % 100000) / 100000.0;                          // 0..1
    $dist    = $minMeters + $r1 * ($maxMeters - $minMeters);  // metres
    $bearing = $r2 * 2 * M_PI;                                // radians
    // metres → degrees (≈ 111320 m per degree of latitude)
    $dLat   = ($dist * cos($bearing)) / 111320.0;
    $cosLat = cos(deg2rad($lat));
    $dLng   = ($dist * sin($bearing)) / (111320.0 * ($cosLat != 0.0 ? $cosLat : 1e-6));
    return [$lat + $dLat, $lng + $dLng];
}

// === Telegram donor notification (best-effort) ===
//  Donor-এর নম্বর bot-এ linked থাকলে তার Telegram-এ message যায়; না থাকলে চুপচাপ skip।
//  Node bot-এর নতুন /notify endpoint-এ POST করে (secret-guarded)।
function notifyDonorTelegram($phone, $message) {
    if (TELEGRAM_BOT_URL === '' || TELEGRAM_BOT_SECRET === '') return false;
    if (!preg_match('/^\+8801\d{9}$/', (string)$phone)) return false;
    $r = _bot_send(TELEGRAM_BOT_URL,
        ["secret" => TELEGRAM_BOT_SECRET, "phone" => $phone, "message" => $message],
        defined('TELEGRAM_BOT_INSECURE_TLS') && TELEGRAM_BOT_INSECURE_TLS, '/notify');
    return ((int)($r['http'] ?? 0)) === 200;
}

// === AJAX DETECTION — skip heavy queries on every AJAX call ===
$_is_ajax = !empty($_POST['log_call']) || !empty($_POST['get_phone'])
         || !empty($_POST['ajax_filter']) || !empty($_POST['ajax_submit'])
         || !empty($_POST['get_blood_requests']) || !empty($_POST['ajax_update'])
         || !empty($_POST['load_my_donor']) || !empty($_POST['submit_report'])
         || !empty($_POST['submit_blood_request']) || !empty($_POST['save_push_sub'])
         || !empty($_POST['delete_donor'])
         || !empty($_POST['get_analytics']) || !empty($_POST['get_map_data'])
         || !empty($_POST['get_nearby_donors'])
         || !empty($_POST['get_service_notifs'])
         || !empty($_POST['mark_service_notif_read'])
         || !empty($_POST['delete_service_notif'])
         || !empty($_POST['submit_admin_message'])
         || !empty($_POST['get_admin_messages'])
         || !empty($_POST['mark_admin_msg_read'])
         || !empty($_POST['save_device_id'])
         || !empty($_POST['save_fcm_token'])
         || !empty($_POST['firebase_auth'])
         || !empty($_POST['firebase_logout'])
         || !empty($_POST['account_info'])
         || !empty($_POST['get_my_messages'])
         || !empty($_POST['get_my_requests'])
         || !empty($_POST['delete_my_request'])
         || !empty($_POST['wa_send_otp'])
         || !empty($_POST['wa_verify_otp'])
         || !empty($_POST['tg_send_otp'])
         || !empty($_POST['tg_verify_otp'])
         || !empty($_POST['cn_send_otp'])
         || !empty($_POST['cn_verify_otp'])
         || !empty($_POST['update_privacy'])
         || !empty($_POST['send_contact_request'])
         || !empty($_POST['get_my_contact_requests'])
         || !empty($_POST['act_contact_request']);

// === LIVE COUNTS — only on full page load, never on AJAX ===
$avail_counts = ["A+"=>0,"A-"=>0,"B+"=>0,"B-"=>0,"AB+"=>0,"AB-"=>0,"O+"=>0,"O-"=>0];
$total_donors_count = 0;
if (!$_is_ajax) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $avail_q = $conn->query("SELECT blood_group, COUNT(*) as cnt FROM donors 
        WHERE (willing_to_donate IS NULL OR willing_to_donate='yes' OR willing_to_donate='')
          AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00'
               OR DATEDIFF(CURDATE(), last_donation) >= 120)
        GROUP BY blood_group");
    if ($avail_q) {
        while ($rowc = $avail_q->fetch_assoc())
            if (isset($avail_counts[$rowc['blood_group']]))
                $avail_counts[$rowc['blood_group']] = (int)$rowc['cnt'];
    }
    $tc = $conn->query("SELECT COUNT(*) as c FROM donors");
    $total_donors_count = $tc ? (int)($tc->fetch_assoc()['c'] ?? 0) : 0;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// --- AJAX: Log Call Activity ---
if(isset($_POST['log_call'])){
    checkCSRF();
    header('Content-Type: text/plain; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start(); // flush any buffered warnings before output
    checkRateLimit('log_call', 20, 60);
    // ── Verification gate — unverified account call log করতে পারবে না ──
    if(!_auth_is_verified()){ ob_clean(); echo "unverified"; exit(); }
    $d_id = (int)$_POST['donor_id'];
    // ── Caller identity = signed-in verified account, never client-supplied ──
    //  verified number (Telegram/WhatsApp bind) অগ্রাধিকার; নইলে phone-OTP নম্বর।
    //  client থেকে আর number/name চাওয়া হয় না — spoof বন্ধ।
    $c_phone = trim($_SESSION['auth_verify_phone'] ?? '') ?: trim($_SESSION['auth_phone'] ?? '');
    $c_name = trim($_SESSION['auth_name'] ?? '') ?: $c_phone;
    $loc = trim($_POST['location_data'] ?? 'Not provided');
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 300, 'UTF-8');

    // Server-side validation
    if(!preg_match('/^\+8801\d{9}$/', $c_phone)) { ob_clean(); echo "invalid"; exit(); }
    validateLength($c_name, 100, 'নাম');
    validateLength($loc, 500, 'Location');
    if(empty($c_name)) { ob_clean(); echo "invalid"; exit(); }

    $stmt = $conn->prepare("INSERT INTO call_logs (donor_id, caller_name, caller_phone, caller_ip, caller_location, device_info) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $d_id, $c_name, $c_phone, $ip, $loc, $device);
    $stmt->execute();
    $stmt->close();
    // Increment persistent counter — never decreases even if call_logs is cleared
    $conn->query("INSERT INTO analytics_counters (counter_name, counter_value) VALUES ('total_calls_ever', 1)
        ON DUPLICATE KEY UPDATE counter_value = counter_value + 1");

    // ── FCM push to donor: "[number] [name] [place] blood dite iccuk" ──
    mysqli_report(MYSQLI_REPORT_OFF);
    $dinfo = $conn->prepare("SELECT name, location, device_id FROM donors WHERE id=?");
    $dinfo->bind_param("i", $d_id);
    $dinfo->execute();
    $donor_row = $dinfo->get_result()->fetch_assoc();
    $dinfo->close();
    if ($donor_row && !empty($donor_row['device_id'])) {
        $d_name = $donor_row['name'];
        $d_loc  = $donor_row['location'];
        // ── Store a service notification → দাতার Notification bell + Messages এ দেখাবে ──
        //  caller-এর verified নম্বরসহ (যে account দিয়ে call করা হয়েছে তার verify_phone)।
        $call_notif_msg = "📞 কেউ আপনাকে রক্তের জন্য কল করেছেন।\n"
                        . "👤 " . esc($c_name) . "\n"
                        . "📱 " . esc($c_phone);
        $sn_call = $conn->prepare("INSERT INTO service_notifications (device_id, type, message) VALUES (?,?,?)");
        if ($sn_call) {
            $sn_call_type = 'donor_called';
            $sn_call->bind_param("sss", $donor_row['device_id'], $sn_call_type, $call_notif_msg);
            $sn_call->execute(); $sn_call->close();
        }
        // Look up FCM token for this donor's device_id
        $ftq = $conn->prepare("SELECT fcm_token FROM fcm_tokens WHERE device_id=? LIMIT 1");
        $ftq->bind_param("s", $donor_row['device_id']);
        $ftq->execute();
        $ftrow = $ftq->get_result()->fetch_assoc();
        $ftq->close();
        if ($ftrow && !empty($ftrow['fcm_token'])) {
            $push_title = "📞 {$c_phone}";
            $push_body  = "{$c_name} ({$c_phone}) — {$d_loc} রক্ত দিতে ইচ্ছুক।";
            $oauth_token = @_fcm_get_oauth_token();
            if ($oauth_token) {
                $fcm_endpoint = "https://fcm.googleapis.com/v1/projects/shsmc-blood-portal/messages:send";
                // Data-only payload — notification field সরানো।
                // notification থাকলে FCM bypass করে, onBackgroundMessage call হয় না।
                $msg_payload = json_encode([
                    "message" => [
                        "token" => $ftrow['fcm_token'],
                        "webpush" => [
                            "fcm_options" => ["link" => SITE_URL . "/"]
                        ],
                        "data" => [
                            "type"   => "donor_called",
                            "caller" => $c_name,
                            "phone"  => $c_phone,
                            "title"  => $push_title,
                            "body"   => $push_body,
                            "tag"    => "donor-call-{$d_id}"
                        ]
                    ]
                ]);
                $ch = curl_init($fcm_endpoint);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Authorization: Bearer '.$oauth_token]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $msg_payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

    while(ob_get_level()) ob_end_clean(); ob_start();
    echo "logged";
    exit();
}

// --- AJAX: Report Harassment ---
if(isset($_POST['submit_report'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start(); // flush any buffered warnings before JSON output
    checkRateLimit('submit_report', 5, 300); // Max 5 reports per 5 min
    $d_phone = trim($_POST['donor_phone'] ?? '');
    $h_info = trim($_POST['harasser_info'] ?? '');
    $comment = trim($_POST['report_comment'] ?? '');

    // Server-side validation
    if(!preg_match('/^\+8801\d{9}$/', $d_phone)) {
        echo json_encode(["status"=>"error","msg"=>"সঠিক ফোন নম্বর দিন।"]);
        exit();
    }
    validateLength($h_info, 200, 'হয়রানিকারীর তথ্য');
    validateLength($comment, 1000, 'অভিযোগ');
    if(empty($h_info) || empty($comment)) {
        echo json_encode(["status"=>"error","msg"=>"সব তথ্য দিন।"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO reports (donor_phone, harasser_info, report_comment) VALUES (?,?,?)");
    $stmt->bind_param("sss", $d_phone, $h_info, $comment);
    if($stmt->execute()){
        $to = "2005siam1hasan@gmail.com";
        $subject = "Donor Harassment Report - Blood Arena";
        // FIX: strip newlines to prevent email header injection
        $safe_phone   = str_replace(["\r", "\n"], '', $d_phone);
        $safe_hinfo   = str_replace(["\r", "\n"], '', $h_info);
        $safe_comment = str_replace(["\r", "\n"], '', $comment);
        $message = "Donor Phone: $safe_phone\nHarasser Details: $safe_hinfo\nComment: $safe_comment";
        mail($to, $subject, $message);
        while(ob_get_level()) ob_end_clean(); ob_start();
        echo "success";
    } else {
        while(ob_get_level()) ob_end_clean(); ob_start();
        echo json_encode(["status"=>"error","msg"=>"রিপোর্ট জমা দেওয়া ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"]);
    }
    $stmt->close();
    exit();
}

// --- Secure Phone Fetch ---
if(isset($_POST['get_phone'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start(); // flush any buffered warnings before JSON output
    checkRateLimit('get_phone', 10, 60); // FIX: prevent phone number enumeration
    // ── Verification gate: unverified account দাতার নম্বর পাবে না (call করতে পারবে না) ──
    if(!_auth_is_verified()){
        while(ob_get_level()) ob_end_clean(); ob_start();
        echo "unverified"; exit();
    }
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("SELECT phone, allow_call FROM donors WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    while(ob_get_level()) ob_end_clean(); ob_start();
    if(!$res){ echo "error"; exit(); }
    // Allow Call = OFF → নম্বর কখনো expose হবে না; requester-কে "Request" পাঠাতে হবে (point #3)
    if((int)($res['allow_call'] ?? 1) === 0){ echo "request_only"; exit(); }
    echo trim($res['phone']);
    exit();
}

// === LOAD MY DONOR INFO (signed-in account → own donor record) ===
//  পুরনো verify_secret (secret code) এর বদলে — এখন session auth_uid দিয়ে
//  নিজের donor record load হয়। legacy donor (auth_uid খালি) একই phone-এ OTP
//  sign-in করলে এখানে auth_uid বসিয়ে claim করা হয়।
if(isset($_POST['load_my_donor'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('load_my_donor', 30, 60);
    $uid   = requireAuth();
    $phone = $_SESSION['auth_phone'] ?? null;

    mysqli_report(MYSQLI_REPORT_OFF);
    // 1) auth_uid দিয়ে খোঁজো
    $stmt = $conn->prepare("SELECT name, phone, location, last_donation, willing_to_donate, total_donations, reg_geo, gender, hide_me, allow_call FROM donors WHERE auth_uid=? LIMIT 1");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 2) না পেলে legacy fallback: একই phone, auth_uid খালি → claim করো
    if(!$res && $phone){
        $lc = $conn->prepare("SELECT id, name, phone, location, last_donation, willing_to_donate, total_donations, reg_geo, gender, hide_me, allow_call FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $lc->bind_param("s", $phone);
        $lc->execute();
        $res = $lc->get_result()->fetch_assoc();
        $lc->close();
        if($res){
            $email = $_SESSION['auth_email'] ?? null;
            $cl = $conn->prepare("UPDATE donors SET auth_uid=?, auth_email=? WHERE id=?");
            $cl->bind_param("ssi", $uid, $email, $res['id']);
            $cl->execute(); $cl->close();
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if(!$res){
        echo json_encode(["status"=>"error","code"=>"no_donor","msg"=>"আপনার কোনো donor profile পাওয়া যায়নি। প্রথমে রেজিস্ট্রেশন করুন।"]);
        exit();
    }
    $display_last = ($res['last_donation'] == 'no' || empty($res['last_donation']) || $res['last_donation']=='0000-00-00') ? 'no' : date('d/m/Y', strtotime($res['last_donation']));
    $badge = getBadgeInfo((int)$res['total_donations']);
    $geo_lat = ''; $geo_lng = '';
    if(!empty($res['reg_geo']) && preg_match('/Lat:\s*([\-0-9.]+),\s*Lon:\s*([\-0-9.]+)/', $res['reg_geo'], $gm)){
        $geo_lat = $gm[1]; $geo_lng = $gm[2];
    }
    echo json_encode([
        "status"=>"success",
        "name"=>$res['name'],
        "phone"=>$res['phone'] ?? '',
        "location"=>$res['location'],
        "last_donation"=>$display_last,
        "willing"=>$res['willing_to_donate'],
        "total_donations"=>(int)$res['total_donations'],
        "badge_level"=>$badge['level'],
        "badge_icon"=>$badge['icon'],
        "geo_lat"=>$geo_lat,
        "geo_lng"=>$geo_lng,
        "gender"=>$res['gender'] ?? null,
        "hide_me"=>(int)($res['hide_me'] ?? 0),
        "allow_call"=>(int)($res['allow_call'] ?? 1)
    ]);
    exit();
}

// === SET WILLINGNESS (signed-in account → instant Available/Unavailable toggle) ===
//  Account Dashboard থেকে এক ট্যাপে নিজেকে "রক্তদানে অনিচ্ছুক" (willing_to_donate='no')
//  বা আবার "ইচ্ছুক" ('yes') করা যায়। donor record account (auth_uid) দিয়ে মেলে;
//  না পেলে legacy fallback: একই phone, auth_uid খালি → claim করে আপডেট করে।
if(isset($_POST['set_willing'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    mysqli_report(MYSQLI_REPORT_OFF);
    checkRateLimit('set_willing', 20, 60);
    $uid     = requireAuth();
    $willing = trim($_POST['willing'] ?? '');
    if(!in_array($willing, ['yes','no'], true)){
        echo json_encode(["status"=>"error","msg"=>"Invalid value."]);
        exit();
    }
    $phone = $_SESSION['auth_phone'] ?? null;

    // 1) account দিয়ে নিজের donor row খোঁজো
    $stmt = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 2) না পেলে legacy fallback: একই phone, auth_uid খালি → claim করো
    if(!$row && $phone){
        $lc = $conn->prepare("SELECT id FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $lc->bind_param("s", $phone);
        $lc->execute();
        $row = $lc->get_result()->fetch_assoc();
        $lc->close();
        if($row){
            $email = $_SESSION['auth_email'] ?? null;
            $cl = $conn->prepare("UPDATE donors SET auth_uid=?, auth_email=? WHERE id=?");
            $cl->bind_param("ssi", $uid, $email, $row['id']);
            $cl->execute(); $cl->close();
        }
    }

    if(!$row){
        echo json_encode(["status"=>"error","code"=>"no_donor","msg"=>"আপনার কোনো donor profile পাওয়া যায়নি। প্রথমে রেজিস্ট্রেশন করুন।"]);
        exit();
    }

    $up = $conn->prepare("UPDATE donors SET willing_to_donate=? WHERE id=?");
    $up->bind_param("si", $willing, $row['id']);
    $ok = $up->execute();
    $up->close();
    if(!$ok){
        echo json_encode(["status"=>"error","msg"=>"পরিবর্তন সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।"]);
        exit();
    }
    echo json_encode(["status"=>"success","willing"=>$willing]);
    exit();
}

// === UPDATE DONOR INFO (signed-in account) ===
if(isset($_POST['ajax_update'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('ajax_update', 10, 60);
    $uid        = requireAuth();
    $name       = trim($_POST['name']);
    $location   = trim($_POST['location']);
    $last_input = trim($_POST['last_donation']);

    validateLength($name, 100, 'নাম');
    validateLength($location, 300, 'Location');

    if(empty($name) || !preg_match('/^[\p{Bengali}a-zA-Z\s]+$/u', $name)){
        echo json_encode(["status"=>"error", "msg"=>"❌ নামে শুধুমাত্র অক্ষর ও স্পেস থাকতে পারবে।"]);
        exit();
    }

    // Resolve which donor row this account owns (auth_uid; legacy phone fallback)
    $phone = $_SESSION['auth_phone'] ?? null;
    mysqli_report(MYSQLI_REPORT_OFF);
    $find = $conn->prepare("SELECT id, last_donation FROM donors WHERE auth_uid=? LIMIT 1");
    $find->bind_param("s", $uid); $find->execute();
    $drow = $find->get_result()->fetch_assoc(); $find->close();
    if(!$drow && $phone){
        $find2 = $conn->prepare("SELECT id, last_donation FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $find2->bind_param("s", $phone); $find2->execute();
        $drow = $find2->get_result()->fetch_assoc(); $find2->close();
        if($drow){ // claim legacy row for this account
            $email = $_SESSION['auth_email'] ?? null;
            $cl = $conn->prepare("UPDATE donors SET auth_uid=?, auth_email=? WHERE id=?");
            $cl->bind_param("ssi", $uid, $email, $drow['id']); $cl->execute(); $cl->close();
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if(!$drow){
        echo json_encode(["status"=>"error","code"=>"no_donor","msg"=>"আপনার donor profile পাওয়া যায়নি। প্রথমে রেজিস্ট্রেশন করুন।"]);
        exit();
    }
    $donor_id = (int)$drow['id'];

    // ── Privacy toggles from Update form (point #1) — শুধু পাঠানো field বদলায় ──
    if(isset($_POST['hide_me']) || isset($_POST['allow_call'])){
        mysqli_report(MYSQLI_REPORT_OFF);
        if(isset($_POST['hide_me'])){
            $hm = (trim($_POST['hide_me'])==='1') ? 1 : 0;
            if($p=$conn->prepare("UPDATE donors SET hide_me=? WHERE id=?")){ $p->bind_param("ii",$hm,$donor_id); $p->execute(); $p->close(); }
        }
        if(isset($_POST['allow_call'])){
            $ac = (trim($_POST['allow_call'])==='1') ? 1 : 0;
            if($p=$conn->prepare("UPDATE donors SET allow_call=? WHERE id=?")){ $p->bind_param("ii",$ac,$donor_id); $p->execute(); $p->close(); }
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    $today = date("Y-m-d");
    $last_to_save = "no";
    $reg_geo_update = trim($_POST['reg_geo_update'] ?? '');
    validateLength($reg_geo_update, 200, 'Geo location');

    if(strtolower($last_input) != 'no' && !empty($last_input)){
        $d = DateTime::createFromFormat('d/m/Y', $last_input);
        if(!$d || $d->format('d/m/Y') !== $last_input){
            echo json_encode(["status"=>"error", "msg"=>"Error: Last Blood Donation Date must be 'no' or in dd/mm/yyyy format."]);
            exit();
        }
        $formatted = $d->format('Y-m-d');
        if($formatted > $today || (int)$d->format('Y') < 1940){
            echo json_encode(["status"=>"error", "msg"=>"Error: Invalid date."]);
            exit();
        }
        $last_to_save = $formatted;
    }

    $willing      = trim($_POST['willing_to_donate'] ?? 'yes');
    $just_donated = (int)($_POST['just_donated'] ?? 0);
    if(!in_array($willing, ['yes','no'], true)) $willing = 'yes';
    // Save device_id to donors table so admin can send notifications
    $upd_device_id = trim($_POST['device_id'] ?? '');
    if(!empty($upd_device_id)){
        @$conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS device_id VARCHAR(100) DEFAULT NULL");
        $udq = $conn->prepare("UPDATE donors SET device_id=? WHERE id=? AND (device_id IS NULL OR device_id='')");
        $udq->bind_param("si", $upd_device_id, $donor_id);
        $udq->execute(); $udq->close();
    }

    // Server-side guard: just_donated=1 is only valid if last_donation is today
    // Prevents double-counting if user clicks "I just donated" multiple times
    if($just_donated === 1){
        if($last_to_save !== date('Y-m-d')){
            $just_donated = 0; // Silently ignore — date mismatch means it's not a fresh donation
        } elseif(($drow['last_donation'] ?? '') === date('Y-m-d')){
            $just_donated = 0; // Already counted today
        }
    }

    // Build query — optionally include reg_geo update
    $geo_set  = !empty($reg_geo_update) ? ", reg_geo=?" : "";
    $badge_expr_inc = "CASE WHEN total_donations+1>=10 THEN 'Legend' WHEN total_donations+1>=5 THEN 'Hero' WHEN total_donations+1>=2 THEN 'Active' ELSE 'New' END";
    $badge_expr_cur = "CASE WHEN total_donations>=10 THEN 'Legend' WHEN total_donations>=5 THEN 'Hero' WHEN total_donations>=2 THEN 'Active' ELSE 'New' END";

    if($just_donated === 1){
        $stmt = $conn->prepare("UPDATE donors SET name=?, location=?, last_donation=?, willing_to_donate=?, total_donations=total_donations+1, badge_level=$badge_expr_inc$geo_set WHERE id=?");
        if(!empty($reg_geo_update)) { $stmt->bind_param("sssssi", $name, $location, $last_to_save, $willing, $reg_geo_update, $donor_id); }
        else { $stmt->bind_param("ssssi", $name, $location, $last_to_save, $willing, $donor_id); }
    } else {
        $stmt = $conn->prepare("UPDATE donors SET name=?, location=?, last_donation=?, willing_to_donate=?, badge_level=$badge_expr_cur$geo_set WHERE id=?");
        if(!empty($reg_geo_update)) { $stmt->bind_param("sssssi", $name, $location, $last_to_save, $willing, $reg_geo_update, $donor_id); }
        else { $stmt->bind_param("ssssi", $name, $location, $last_to_save, $willing, $donor_id); }
    }

    if($stmt->execute()){
        $stmt->close();
        $s2 = $conn->prepare("SELECT total_donations FROM donors WHERE id=?");
        $s2->bind_param("i", $donor_id);
        $s2->execute();
        $r2 = $s2->get_result()->fetch_assoc();
        $s2->close();
        if($r2){
            $badge = getBadgeInfo((int)$r2['total_donations']);
            // Increment persistent donation counter if donor just donated
            if($just_donated === 1){
                $conn->query("INSERT INTO analytics_counters (counter_name, counter_value) VALUES ('total_donations_ever', 1)
                    ON DUPLICATE KEY UPDATE counter_value = counter_value + 1");
                // Record this donation in history (My Donations → Donation History)
                mysqli_report(MYSQLI_REPORT_OFF);
                $dh = $conn->prepare("INSERT INTO donation_history (auth_uid, donor_id, donation_date) VALUES (?,?,?)");
                if($dh){
                    $dh->bind_param("sis", $uid, $donor_id, $last_to_save);
                    $dh->execute(); $dh->close();
                }
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }
            echo json_encode([
                "status"          => "success",
                "msg"             => "✅ তথ্য সফলভাবে আপডেট হয়েছে!",
                "badge_level"     => $badge['level'],
                "badge_icon"      => $badge['icon'],
                "total_donations" => (int)$r2['total_donations']
            ]);
        } else {
            echo json_encode(["status"=>"error", "msg"=>"❌ Donor record খুঁজে পাওয়া যায়নি।"]);
        }
    } else {
        $stmt->close();
        echo json_encode(["status"=>"error", "msg"=>"❌ Update failed. Please try again."]);
    }
    exit();
}

// === UPDATE PRIVACY / GENDER (settings + profile, point #1) ===
//  Logged-in donor নিজের gender / hide_me / allow_call যেকোনো সময় বদলাতে পারে।
//  শুধু যে field পাঠানো হবে সেটিই বদলায় — gender বদলালেও hide_me/allow_call
//  auto-reset হয় না (manual override বজায় থাকে)।
if(isset($_POST['update_privacy'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('update_privacy', 20, 60);
    $uid   = requireAuth();
    $phone = $_SESSION['auth_phone'] ?? null;

    // Resolve this account's donor row (auth_uid; legacy phone fallback + claim)
    mysqli_report(MYSQLI_REPORT_OFF);
    $find = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $find->bind_param("s", $uid); $find->execute();
    $drow = $find->get_result()->fetch_assoc(); $find->close();
    if(!$drow && $phone){
        $f2 = $conn->prepare("SELECT id FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $f2->bind_param("s", $phone); $f2->execute();
        $drow = $f2->get_result()->fetch_assoc(); $f2->close();
        if($drow){
            $email = $_SESSION['auth_email'] ?? null;
            $cl = $conn->prepare("UPDATE donors SET auth_uid=?, auth_email=? WHERE id=?");
            $cl->bind_param("ssi", $uid, $email, $drow['id']); $cl->execute(); $cl->close();
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if(!$drow){
        echo json_encode(["status"=>"error","code"=>"no_donor","msg"=>"আপনার donor profile পাওয়া যায়নি। প্রথমে রেজিস্ট্রেশন করুন।"]);
        exit();
    }
    $donor_id = (int)$drow['id'];

    // Partial update — only the fields that were actually sent.
    // NOTE (point #1): gender registration-এ একবার set হয় ও locked — এই endpoint দিয়ে
    //  কখনো পরিবর্তন করা যায় না (gender field পাঠালেও উপেক্ষা করা হয়)।
    $sets = []; $vals = []; $types = "";
    if(isset($_POST['hide_me'])){
        $hm = (trim($_POST['hide_me']) === '1') ? 1 : 0;
        $sets[] = "hide_me=?"; $vals[] = $hm; $types .= "i";
    }
    if(isset($_POST['allow_call'])){
        $ac = (trim($_POST['allow_call']) === '1') ? 1 : 0;
        $sets[] = "allow_call=?"; $vals[] = $ac; $types .= "i";
    }
    if(empty($sets)){
        echo json_encode(["status"=>"error","msg"=>"কোনো পরিবর্তন পাঠানো হয়নি।"]); exit();
    }
    $vals[] = $donor_id; $types .= "i";
    $stmt = $conn->prepare("UPDATE donors SET " . implode(", ", $sets) . " WHERE id=?");
    $stmt->bind_param($types, ...$vals);
    $ok = $stmt->execute(); $stmt->close();

    if($ok){
        $g2 = $conn->prepare("SELECT gender, hide_me, allow_call FROM donors WHERE id=?");
        $g2->bind_param("i", $donor_id); $g2->execute();
        $st = $g2->get_result()->fetch_assoc(); $g2->close();
        echo json_encode([
            "status"     => "success",
            "msg"        => "✅ প্রাইভেসি সেটিংস আপডেট হয়েছে।",
            "gender"     => $st['gender'] ?? null,
            "hide_me"    => (int)($st['hide_me'] ?? 0),
            "allow_call" => (int)($st['allow_call'] ?? 1)
        ]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"❌ আপডেট ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"]);
    }
    exit();
}

// ============================================================
// FEATURE: CONTACT REQUESTS (Allow Call = OFF → "Request" flow, point #3)
//  Requester (logged-in) donor-কে contact request পাঠায় → donor Telegram +
//  in-app notification পায় → donor "Accept" করলে requester-এর নাম+phone দেখে
//  নিজে যোগাযোগ করে। Donor-এর নিজের নম্বর কখনো requester-কে দেখানো হয় না।
// ============================================================

// --- Requester → Donor: send a contact request ---
if(isset($_POST['send_contact_request'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('contact_request', 10, 300);
    $uid = requireAuth(); // শুধু logged-in user request পাঠাতে পারবে
    if(!_auth_is_verified()){
        echo json_encode(["status"=>"error","msg"=>"Request পাঠাতে হলে প্রথমে আপনার ফোন নম্বর verify করুন।"]); exit();
    }
    $donor_id = (int)($_POST['donor_id'] ?? 0);
    $message  = mb_substr(trim($_POST['message'] ?? ''), 0, 500, 'UTF-8');
    if($donor_id <= 0){ echo json_encode(["status"=>"error","msg"=>"Donor পাওয়া যায়নি।"]); exit(); }

    mysqli_report(MYSQLI_REPORT_OFF);
    // Target donor
    $dstmt = $conn->prepare("SELECT id, name, phone, auth_uid, allow_call, blood_group, device_id FROM donors WHERE id=? LIMIT 1");
    $dstmt->bind_param("i", $donor_id); $dstmt->execute();
    $donor = $dstmt->get_result()->fetch_assoc(); $dstmt->close();
    if(!$donor){ mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT); echo json_encode(["status"=>"error","msg"=>"Donor পাওয়া যায়নি।"]); exit(); }

    // Requester profile (name + phone) — own donor profile, fallback to session phone
    $rname = ''; $rphone = '';
    $rp = $conn->prepare("SELECT name, phone FROM donors WHERE auth_uid=? LIMIT 1");
    $rp->bind_param("s", $uid); $rp->execute();
    $rprof = $rp->get_result()->fetch_assoc(); $rp->close();
    if($rprof){ $rname = (string)$rprof['name']; $rphone = (string)$rprof['phone']; }
    if($rphone === ''){ $rphone = trim($_SESSION['auth_phone'] ?? ''); }
    if($rname === ''){ $rname = trim($_POST['requester_name'] ?? '') ?: 'একজন রক্তগ্রহীতা'; }
    $rname = mb_substr($rname, 0, 120, 'UTF-8');
    if(!preg_match('/^\+8801\d{9}$/', $rphone)){
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        echo json_encode(["status"=>"error","code"=>"need_profile","msg"=>"Request পাঠাতে আপনার একটি verified ফোন নম্বর দরকার।"]); exit();
    }

    // Prevent duplicate pending spam to the same donor (within 1 hour)
    $dup = $conn->prepare("SELECT id FROM contact_requests WHERE donor_id=? AND requester_auth_uid=? AND status='pending' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1");
    $dup->bind_param("is", $donor_id, $uid); $dup->execute();
    $hasDup = $dup->get_result()->fetch_assoc(); $dup->close();
    if($hasDup){
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        echo json_encode(["status"=>"success","msg"=>"⏳ আপনি ইতিমধ্যে এই দাতাকে request পাঠিয়েছেন। দাতার সাড়ার অপেক্ষা করুন।"]); exit();
    }

    $bg = (string)$donor['blood_group'];
    $donor_uid = $donor['auth_uid']; // may be NULL for legacy rows
    $ins = $conn->prepare("INSERT INTO contact_requests (donor_id, donor_auth_uid, requester_auth_uid, requester_name, requester_phone, blood_group, message, status) VALUES (?,?,?,?,?,?,?, 'pending')");
    $ins->bind_param("issssss", $donor_id, $donor_uid, $uid, $rname, $rphone, $bg, $message);
    $ok = $ins->execute(); $ins->close();
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    if(!$ok){ echo json_encode(["status"=>"error","msg"=>"Request পাঠাতে ব্যর্থ। আবার চেষ্টা করুন।"]); exit(); }

    // ── Notify donor: in-app (service_notifications) + Telegram (best-effort) ──
    $notif_text = "🩸 নতুন রক্তের অনুরোধ\n\n{$rname} আপনার সাথে যোগাযোগ করতে চান ({$bg} প্রয়োজন)।"
                . ($message !== '' ? "\n\n📝 {$message}" : "")
                . "\n\nBloodArena অ্যাপে গিয়ে Accept করলে যোগাযোগের তথ্য দেখতে পাবেন।";
    if(!empty($donor['device_id'])){
        mysqli_report(MYSQLI_REPORT_OFF);
        $sn = $conn->prepare("INSERT INTO service_notifications (device_id, type, message) VALUES (?, 'contact_request', ?)");
        if($sn){ $sn->bind_param("ss", $donor['device_id'], $notif_text); @$sn->execute(); $sn->close(); }
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    }
    @notifyDonorTelegram($donor['phone'], $notif_text); // bot-এ linked থাকলেই যাবে

    echo json_encode(["status"=>"success","msg"=>"✅ আপনার অনুরোধ দাতাকে পাঠানো হয়েছে। দাতা Accept করলে যোগাযোগ করবেন।"]);
    exit();
}

// --- Donor: list contact requests addressed to me ---
if(isset($_POST['get_my_contact_requests'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('get_contact_requests', 30, 60);
    $uid   = requireAuth();
    $phone = $_SESSION['auth_phone'] ?? null;

    mysqli_report(MYSQLI_REPORT_OFF);
    // Resolve my donor row id (legacy rows without auth_uid still match by id)
    $my_id = 0;
    $f = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $f->bind_param("s", $uid); $f->execute();
    $fr = $f->get_result()->fetch_assoc(); $f->close();
    if($fr) $my_id = (int)$fr['id'];
    elseif($phone){
        $f2 = $conn->prepare("SELECT id FROM donors WHERE phone=? LIMIT 1");
        $f2->bind_param("s", $phone); $f2->execute();
        $fr2 = $f2->get_result()->fetch_assoc(); $f2->close();
        if($fr2) $my_id = (int)$fr2['id'];
    }
    $rows = [];
    $q = $conn->prepare("SELECT id, requester_name, requester_phone, blood_group, message, status, UNIX_TIMESTAMP(created_at) as created_at FROM contact_requests WHERE (donor_auth_uid=? OR donor_id=?) AND status<>'declined' ORDER BY (status='pending') DESC, created_at DESC LIMIT 50");
    $q->bind_param("si", $uid, $my_id); $q->execute();
    $rr = $q->get_result();
    while($row = $rr->fetch_assoc()){
        // Requester phone revealed only after the donor accepts (point #3 accept flow)
        if($row['status'] !== 'accepted') $row['requester_phone'] = null;
        $rows[] = $row;
    }
    $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"success","requests"=>$rows], JSON_UNESCAPED_UNICODE);
    exit();
}

// --- Donor: accept / decline a contact request ---
if(isset($_POST['act_contact_request'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('act_contact_request', 30, 60);
    $uid    = requireAuth();
    $phone  = $_SESSION['auth_phone'] ?? null;
    $req_id = (int)($_POST['request_id'] ?? 0);
    $action = trim($_POST['act'] ?? '');
    if($req_id <= 0 || !in_array($action, ['accept','decline'], true)){
        echo json_encode(["status"=>"error","msg"=>"Invalid request."]); exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    // Resolve my donor id for ownership check
    $my_id = 0;
    $f = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $f->bind_param("s", $uid); $f->execute();
    $fr = $f->get_result()->fetch_assoc(); $f->close();
    if($fr) $my_id = (int)$fr['id'];
    elseif($phone){
        $f2 = $conn->prepare("SELECT id FROM donors WHERE phone=? LIMIT 1");
        $f2->bind_param("s", $phone); $f2->execute();
        $fr2 = $f2->get_result()->fetch_assoc(); $f2->close();
        if($fr2) $my_id = (int)$fr2['id'];
    }
    // Ownership: only the targeted donor can act
    $own = $conn->prepare("SELECT id, requester_name, requester_phone FROM contact_requests WHERE id=? AND (donor_auth_uid=? OR donor_id=?) LIMIT 1");
    $own->bind_param("isi", $req_id, $uid, $my_id); $own->execute();
    $crow = $own->get_result()->fetch_assoc(); $own->close();
    if(!$crow){
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        echo json_encode(["status"=>"error","msg"=>"এই request আপনার নয় অথবা পাওয়া যায়নি।"]); exit();
    }
    $new_status = ($action === 'accept') ? 'accepted' : 'declined';
    $up = $conn->prepare("UPDATE contact_requests SET status=? WHERE id=?");
    $up->bind_param("si", $new_status, $req_id); $up->execute(); $up->close();
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    if($action === 'accept'){
        echo json_encode([
            "status"=>"success","accepted"=>true,
            "requester_name"=>$crow['requester_name'],
            "requester_phone"=>$crow['requester_phone'],
            "msg"=>"✅ Accept করেছেন — এখন requester-এর সাথে যোগাযোগ করুন।"
        ]);
    } else {
        echo json_encode(["status"=>"success","accepted"=>false,"msg"=>"Request বাতিল করা হয়েছে।"]);
    }
    exit();
}

// === DELETE DONOR (Self-delete by signed-in account) ===
if(isset($_POST['delete_donor'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('delete_donor', 5, 300); // Max 5 attempts per 5 min

    $uid     = requireAuth();
    $phone   = $_SESSION['auth_phone'] ?? null;
    $confirm = trim($_POST['confirm'] ?? '');

    if($confirm !== 'DELETE'){
        echo json_encode(["status"=>"error","msg"=>"❌ নিশ্চিত করতে DELETE লিখুন।"]);
        exit();
    }

    // Find this account's donor row (auth_uid; legacy phone fallback)
    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $stmt->bind_param("s", $uid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if(!$row && $phone){
        $s2 = $conn->prepare("SELECT id FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $s2->bind_param("s", $phone); $s2->execute();
        $row = $s2->get_result()->fetch_assoc(); $s2->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if(!$row){
        echo json_encode(["status"=>"error","msg"=>"❌ আপনার donor profile পাওয়া যায়নি।"]);
        exit();
    }

    $del = $conn->prepare("DELETE FROM donors WHERE id=?");
    $del->bind_param("i", $row['id']);
    if($del->execute()){
        echo json_encode(["status"=>"success","msg"=>"✅ আপনার সকল তথ্য database থেকে সম্পূর্ণ মুছে ফেলা হয়েছে।"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"❌ মুছতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"]);
    }
    $del->close();
    exit();
}

// === AJAX Registration (signed-in account) ===
if(isset($_POST['ajax_submit'])){
    checkCSRF();
    while(ob_get_level()) ob_end_clean(); ob_start(); // re-buffer: stray PHP warnings/notes must not corrupt the JSON body
    header('Content-Type: application/json; charset=utf-8');
    // This handler checks query results by return value (if($stmt->execute()){...}else{...}),
    // so turn OFF mysqli exception mode (globally ERROR|STRICT at top of file). Otherwise a
    // failing query throws an uncaught mysqli_sql_exception whose "Fatal error … #0 {main}"
    // page reaches the client — and the "{main}" defeats safeJSON(), producing the
    // "Response parse করা যায়নি / Registration failed" error instead of a clean JSON message.
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
    checkRateLimit('register', 5, 300);
    $uid        = requireAuth();
    $auth_email = $_SESSION['auth_email'] ?? null;

    // ── Verification gate — ফোন verify না থাকলে register করা যাবে না ──
    //  client-side gate bypass করলেও server মানবে না। DB থেকে fresh state নাও।
    _refresh_auth_verified($conn);
    if(!_auth_is_verified()){
        echo json_encode(["status"=>"error","msg"=>"রেজিস্ট্রেশন করতে হলে প্রথমে আপনার ফোন নম্বর verify করুন।"]);
        exit();
    }

    $name       = trim($_POST['name']             ?? '');
    $phone      = trim($_POST['phone']            ?? '');
    $location   = trim($_POST['location']         ?? '');
    $group      = trim($_POST['group']            ?? '');
    $last_input = trim($_POST['last_donation']    ?? '');
    $reg_geo    = trim($_POST['reg_geo_location'] ?? 'Not captured');
    $reg_ip     = $_SERVER['REMOTE_ADDR'];
    $reg_device = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 300, 'UTF-8');

    validateLength($name,     100, 'নাম');
    validateLength($location, 300, 'Location');
    validateLength($reg_geo,  200, 'Geo location');

    if(empty($name) || !preg_match('/^[\p{Bengali}a-zA-Z\s]+$/u', $name)){
        echo json_encode(["status"=>"error","msg"=>"নামে শুধুমাত্র অক্ষর ও স্পেস থাকতে পারবে।"]);
        exit();
    }
    $valid_groups = ["A+","A-","B+","B-","AB+","AB-","O+","O-"];
    if(!in_array($group, $valid_groups, true)){
        echo json_encode(["status"=>"error","msg"=>"Invalid blood group."]);
        exit();
    }
    if(!preg_match('/^\+8801\d{9}$/', $phone)){
        echo json_encode(["status"=>"error","msg"=>"Phone must start with +8801 followed by 9 digits."]);
        exit();
    }

    // verify করা নম্বর থাকলে সেটিই বাধ্যতামূলক — client-side lock bypass করলেও server মানবে না।
    //  Telegram/WhatsApp bind নম্বর অগ্রাধিকার; নইলে phone-OTP sign-in নম্বর (BA_AUTH-এর fallback-এর মতো)।
    $verified_phone = trim($_SESSION['auth_verify_phone'] ?? '') ?: trim($_SESSION['auth_phone'] ?? '');
    if($verified_phone !== '' && $phone !== $verified_phone){
        echo json_encode(["status"=>"error","msg"=>"আপনার verify করা নম্বরটিই ব্যবহার করতে হবে।"]);
        exit();
    }

    $today        = date("Y-m-d");
    $last_to_save = "no";
    if(strtolower($last_input) !== 'no' && !empty($last_input)){
        $d = DateTime::createFromFormat('d/m/Y', $last_input);
        if(!$d || $d->format('d/m/Y') !== $last_input){
            echo json_encode(["status"=>"error","msg"=>"Last donation date must be 'no' or dd/mm/yyyy."]);
            exit();
        }
        $formatted_last = $d->format('Y-m-d');
        if($formatted_last > $today || (int)$d->format('Y') < 1940){
            echo json_encode(["status"=>"error","msg"=>"Invalid date."]);
            exit();
        }
        $last_to_save = $formatted_last;
    }

    $reg_total_donations = max(0, (int)($_POST['total_donations_reg'] ?? 0));
    if($last_to_save === 'no') $reg_total_donations = 0;
    if($reg_total_donations > 999) $reg_total_donations = 999;
    $reg_badge   = getBadgeInfo($reg_total_donations)['level'];

    // ── Gender + privacy (point #1) ──────────────────────────────
    //  gender required (Male/Female)। front-end gender অনুযায়ী hide_me/allow_call-এর
    //  default বসায় (Female → hidden + no-call, Male → visible + call) এবং user
    //  override করতে পারে। submitted মান নিই; না থাকলে gender-default fallback।
    $gender = trim($_POST['gender'] ?? '');
    if(!in_array($gender, ['Male','Female'], true)){
        echo json_encode(["status"=>"error","msg"=>"লিঙ্গ (Male / Female) নির্বাচন করুন।"]);
        exit();
    }
    $gdef_hide  = ($gender === 'Female') ? 1 : 0;
    $gdef_call  = ($gender === 'Female') ? 0 : 1;
    $hide_me    = isset($_POST['hide_me'])    ? ((trim($_POST['hide_me'])    === '1') ? 1 : 0) : $gdef_hide;
    $allow_call = isset($_POST['allow_call']) ? ((trim($_POST['allow_call']) === '1') ? 1 : 0) : $gdef_call;

    // Ensure account-link + device columns exist on donors
    @$conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS device_id VARCHAR(100) DEFAULT NULL");
    @$conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS auth_uid VARCHAR(128) DEFAULT NULL");
    @$conn->query("ALTER TABLE donors ADD COLUMN IF NOT EXISTS auth_email VARCHAR(190) DEFAULT NULL");
    $reg_device_id = trim($_POST['device_id'] ?? '');

    // Phone already registered? Decide claim vs. block.
    $chk = $conn->prepare("SELECT id, auth_uid FROM donors WHERE phone=? LIMIT 1");
    $chk->bind_param("s", $phone);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();
    if($existing){
        if(!empty($existing['auth_uid']) && $existing['auth_uid'] === $uid){
            echo json_encode(["status"=>"error","msg"=>"আপনি ইতিমধ্যে রেজিস্ট্রেশন করেছেন। তথ্য বদলাতে \"Update My Info\" ব্যবহার করুন।"]);
            exit();
        }
        if(!empty($existing['auth_uid']) && $existing['auth_uid'] !== $uid){
            echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে ইতোমধ্যে অন্য একটি অ্যাকাউন্টে রেজিস্ট্রেশন করা হয়েছে।"]);
            exit();
        }
        // Legacy row (no auth_uid) with same phone → claim & update it for this account
        // 11 params: name,location,group,last,geo,total(i),badge,device,uid,email + id(i)
        $up = $conn->prepare("UPDATE donors SET name=?, location=?, blood_group=?, last_donation=?, reg_geo=?, total_donations=?, badge_level=?, device_id=?, auth_uid=?, auth_email=?, gender=?, hide_me=?, allow_call=? WHERE id=?");
        $up->bind_param("sssssisssssiii", $name,$location,$group,$last_to_save,$reg_geo,$reg_total_donations,$reg_badge,$reg_device_id,$uid,$auth_email,$gender,$hide_me,$allow_call,$existing['id']);
        if($up->execute()){
            $up->close();
            if($reg_total_donations > 0){
                $conn->query("INSERT INTO analytics_counters (counter_name,counter_value) VALUES ('total_donations_ever',$reg_total_donations)
                    ON DUPLICATE KEY UPDATE counter_value=counter_value+$reg_total_donations");
            }
            echo json_encode(["status"=>"success","claimed"=>true,"msg"=>"✅ আপনার পুরনো প্রোফাইল এই অ্যাকাউন্টের সাথে যুক্ত ও আপডেট হয়েছে!"]);
        } else {
            $up->close();
            echo json_encode(["status"=>"error","msg"=>"Registration failed. Please try again."]);
        }
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO donors (name,phone,location,blood_group,last_donation,reg_ip,reg_device,reg_geo,total_donations,badge_level,device_id,auth_uid,auth_email,gender,hide_me,allow_call) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssisssssii", $name,$phone,$location,$group,$last_to_save,$reg_ip,$reg_device,$reg_geo,$reg_total_donations,$reg_badge,$reg_device_id,$uid,$auth_email,$gender,$hide_me,$allow_call);
    if($stmt->execute()){
        if($reg_total_donations > 0){
            $conn->query("INSERT INTO analytics_counters (counter_name,counter_value) VALUES ('total_donations_ever',$reg_total_donations)
                ON DUPLICATE KEY UPDATE counter_value=counter_value+$reg_total_donations");
        }
        // ── Welcome service notification — শুধু এই নতুন user পাবে ──
        if (!empty($reg_device_id)) {
            $welcome_msg = "আসসালামু আলাইকুম! 🩸 BloodArena-এ আপনাকে স্বাগতম, {$name}!\n\n"
                         . "✅ আপনার নিবন্ধন সফলভাবে সম্পন্ন হয়েছে।\n\n"
                         . "যেকোনো সময় Google বা ফোন নম্বর দিয়ে সাইন ইন করে \"Update My Info\" থেকে আপনার তথ্য বদলাতে পারবেন।\n"
                         . "আল্লাহ আপনার এই মহৎ সিদ্ধান্তে উত্তম প্রতিদান দিন! ❤️";
            @$conn->query("CREATE TABLE IF NOT EXISTS `service_notifications`(
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `device_id` VARCHAR(100) NOT NULL,
                `type` VARCHAR(30) NOT NULL,
                `message` TEXT NOT NULL,
                `is_read` TINYINT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $wn = $conn->prepare("INSERT INTO service_notifications (device_id, type, message) VALUES (?,?,?)");
            $wn_type = 'welcome';
            $wn->bind_param("sss", $reg_device_id, $wn_type, $welcome_msg);
            $wn->execute(); $wn->close();
        }
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"Registration failed. Please try again."]);
    }
    $stmt->close();
    } catch (Throwable $e) {
        // With mysqli exception mode OFF, a failed prepare() returns false and the next
        // ->bind_param()/->execute() call throws a fatal Error whose "#0 {main}" trace
        // defeats safeJSON() → "Response parse করা যায়নি". Catch it and emit clean JSON.
        error_log('register handler: ' . $e->getMessage());
        while(ob_get_level()) ob_end_clean(); ob_start();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status"=>"error","msg"=>"Registration failed. Please try again."]);
    }
    exit();
}

// Three live statuses (mutually exclusive):
//   "Not Available" — donated within 120 days (in cooldown). Physical fact, so it
//                     wins over willingness: a donor who can't give isn't just "Not Willing".
//   "Unavailable"   — cooldown passed but opted out (willing_to_donate='no'). Shown as "Not Willing".
//   "Available"     — cooldown passed AND willing.
function getLiveStatus($last_donation, $willing = 'yes') {
    // Cooldown check first — donated within 120 days → Not Available regardless of willingness
    if(!($last_donation == 'no' || empty($last_donation) || $last_donation == '0000-00-00')) {
        $today = new DateTime();
        $last  = new DateTime($last_donation);
        $diff  = $today->diff($last)->days;
        if($diff < 120) return "Not Available";
    }
    // Cooldown passed (or never donated) → willingness decides
    if($willing === 'no') return "Unavailable"; // displayed as "Not Willing"
    return "Available";
}

function getBadgeInfo($total) {
    if($total >= 10) return ['level'=>'Legend','icon'=>'👑','color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.15)','border'=>'rgba(245,158,11,0.4)'];
    if($total >= 5)  return ['level'=>'Hero',  'icon'=>'🦸','color'=>'#8b5cf6','bg'=>'rgba(139,92,246,0.15)','border'=>'rgba(139,92,246,0.4)'];
    if($total >= 2)  return ['level'=>'Active', 'icon'=>'⭐','color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.15)','border'=>'rgba(59,130,246,0.4)'];
    return ['level'=>'New','icon'=>'🌱','color'=>'#10b981','bg'=>'rgba(16,185,129,0.15)','border'=>'rgba(16,185,129,0.4)'];
}

// === FULL AJAX FILTER WITH PAGINATION & NEW LOCATION FILTER ===
if(isset($_POST['ajax_filter'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start(); // flush any buffered warnings before JSON output
    checkRateLimit('ajax_filter', 60, 60);
    $f_group = trim($_POST['filter_group'] ?? 'All');
    $f_search = trim($_POST['search_query'] ?? '');
    $f_status = $_POST['filter_status'] ?? 'All';
    $f_location = trim($_POST['filter_location'] ?? 'All'); 
    $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;

    // Whitelist blood group
    $valid_groups = ["A+","A-","B+","B-","AB+","AB-","O+","O-","All"];
    if(!in_array($f_group, $valid_groups, true)) $f_group = "All";
    $valid_status = ["All","Available","Unavailable","Not Available"];
    if(!in_array($f_status, $valid_status, true)) $f_status = "All";
    $f_badge = trim($_POST['filter_badge'] ?? 'All');
    $valid_badges = ["All","New","Active","Hero","Legend"];
    if(!in_array($f_badge, $valid_badges, true)) $f_badge = "All";
    // Recently-donated mode: only donors with a real last_donation date, newest first
    $f_donated = ($_POST['filter_donated'] ?? '0') === '1';

    // Length limits on filter inputs
    $f_search = mb_substr($f_search, 0, 100, 'UTF-8');
    $f_location = mb_substr($f_location, 0, 200, 'UTF-8');
    $limit = 20;
    $start = ($page - 1) * $limit;

    $query_parts = [];
    $params =[];
    $types = "";

    if($f_group != "All" && $f_group != "") { 
        $query_parts[] = "blood_group = ?";
        $params[] = $f_group;
        $types .= "s";
    }
    if($f_location != "All" && $f_location != "") { 
        $query_parts[] = "location LIKE ?";
        $params[] = $f_location . "%"; 
        $types .= "s";
    }
    if($f_search != "") { 
        $query_parts[] = "(name LIKE ? OR location LIKE ?)";
        $like = "%$f_search%";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }
    // Three live statuses match getLiveStatus(): cooldown wins, then willingness.
    // COOLDOWN_PASSED = never donated OR donated >=120 days ago.
    $cooldown_passed = "(last_donation IS NULL OR last_donation = 'no' OR last_donation = '' OR last_donation = '0000-00-00' OR DATEDIFF(CURDATE(), last_donation) >= 120)";
    $in_cooldown     = "(last_donation IS NOT NULL AND last_donation <> 'no' AND last_donation <> '' AND last_donation <> '0000-00-00' AND DATEDIFF(CURDATE(), last_donation) < 120)";
    if($f_status == "Available") {
        // Willing (not 'no') AND cooldown passed
        $query_parts[] = "((willing_to_donate IS NULL OR willing_to_donate<>'no') AND $cooldown_passed)";
    } elseif($f_status == "Unavailable") {
        // Not Willing: opted out AND cooldown passed (in-cooldown opt-outs show as Not Available)
        $query_parts[] = "(willing_to_donate='no' AND $cooldown_passed)";
    } elseif($f_status == "Not Available") {
        // In cooldown — donated within 120 days, regardless of willingness
        $query_parts[] = $in_cooldown;
    }
    if($f_badge != "All" && $f_badge != "") {
        $query_parts[] = "badge_level = ?";
        $params[] = $f_badge;
        $types .= "s";
    }
    if($f_donated) {
        $query_parts[] = "(last_donation IS NOT NULL AND last_donation != 'no' AND last_donation != '' AND last_donation != '0000-00-00')";
    }

    $where = count($query_parts) > 0 ? "WHERE " . implode(" AND ", $query_parts) : "";
    // Recently-donated view sorts by donation date (newest first); default keeps newest-registered first
    $order_by = $f_donated ? "ORDER BY last_donation DESC, id DESC" : "ORDER BY id DESC";
    
    $count_q = "SELECT COUNT(*) as total FROM donors $where";
    $stmt_count = $conn->prepare($count_q);
    if($types !== "") $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();

    $data_q = "SELECT * FROM donors $where $order_by LIMIT ?, ?";
    $stmt = $conn->prepare($data_q);
    $types_limit = $types . "ii";
    $params_limit = array_merge($params, [$start, $limit]);
    if($types !== "") {
        $stmt->bind_param($types_limit, ...$params_limit);
    } else {
        $stmt->bind_param("ii", $start, $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    
    $output = "";   // desktop table rows
    $cards  = "";   // mobile cards
    $serial = $start + 1;
    $found = false;
    $counts =["A+"=>0,"A-"=>0,"B+"=>0,"B-"=>0,"AB+"=>0,"AB-"=>0,"O+"=>0,"O-"=>0];
    
    while($row = $res->fetch_assoc()){
        $last_val = $row['last_donation'];
        $willing_val = $row['willing_to_donate'] ?? 'yes';
        $current_status = getLiveStatus($last_val, $willing_val);
        // Use DB badge_level directly — avoids mismatch with total_donations
        $db_badge_level = $row['badge_level'] ?? '';
        if($db_badge_level === 'Legend') $donor_badge = getBadgeInfo(10);
        elseif($db_badge_level === 'Hero') $donor_badge = getBadgeInfo(5);
        elseif($db_badge_level === 'Active') $donor_badge = getBadgeInfo(2);
        else $donor_badge = getBadgeInfo(0); // New
        
        if($current_status == "Available") { 
            if (isset($counts[$row['blood_group']])) $counts[$row['blood_group']]++; 
        }
        
        $found = true;
        
        if($last_val == 'no' || empty($last_val) || $last_val == '0000-00-00'){
            $display_last = 'Never donated';
        } else {
            $display_last = date("d M Y", strtotime($last_val));
            if(strpos($display_last, '1970') !== false || strpos($display_last, '-0001') !== false) { $display_last = 'Never donated'; }
        }

        // Member-since (registration date) for the detail popup
        $since_val = $row['created_at'] ?? '';
        if(empty($since_val) || $since_val == '0000-00-00 00:00:00'){
            $display_since = '—';
        } else {
            $display_since = date("d M Y", strtotime($since_val));
            if(strpos($display_since, '1970') !== false || strpos($display_since, '-0001') !== false) { $display_since = '—'; }
        }
        $total_don = (int)($row['total_donations'] ?? 0);
        
        if($current_status == 'Available')      { $st_class='available';   $st_icon='✔'; $st_text='Available'; }
        elseif($current_status == 'Unavailable') { $st_class='unavailable';  $st_icon='⛔'; $st_text='Not Willing'; }
        else                                      { $st_class='notavailable'; $st_icon='✖'; $st_text='Not Available'; }
        $bg_class   = 'bg' . preg_replace('/[^a-zA-Z]/', '', $row['blood_group']) . (strpos($row['blood_group'],'+') !== false ? 'pos' : 'neg');
        $sn         = $serial++;

        // ── Call / Request button (availability + Allow Call, point #3) ──
        //  Available + allow_call ON  → 📞 Call (নম্বর reveal)
        //  Available + allow_call OFF → ✉️ Request (notification flow; নম্বর গোপন)
        //  Not available              → 🚫 disabled
        $is_available   = ($current_status == 'Available');
        $allow_call_row = (int)($row['allow_call'] ?? 1);
        $hide_me_row    = (int)($row['hide_me'] ?? 0);
        if(!$is_available){
            $call_btn_desktop = "<button class='phone-link-disabled' disabled title='দাতা এখন Available নেই'>🚫 Unavailable</button>";
            $call_btn_mobile  = "<button class='dc-call-btn-disabled' disabled title='দাতা এখন Available নেই' aria-label='Not available'>🚫</button>";
        } elseif($allow_call_row === 0){
            $call_btn_desktop = "<button class='phone-link request-link' onclick=\"prepRequest('".$row['id']."')\">✉️ Request</button>";
            $call_btn_mobile  = "<button class='dc-call-btn dc-req-btn unselectable' onclick=\"prepRequest('".$row['id']."')\" oncontextmenu='return false;' aria-label='Request donor'>✉️</button>";
        } else {
            $call_btn_desktop = "<button class='phone-link' onclick=\"prepCall('".$row['id']."')\">📞 Call</button>";
            $call_btn_mobile  = "<button class='dc-call-btn unselectable' onclick=\"prepCall('".$row['id']."')\" oncontextmenu='return false;' aria-label='Call donor'>📞</button>";
        }

        // ── Desktop table row ──────────────────────────────────────────
        $output .= "<tr>
            <td><span class='serial-num'>$sn</span></td>
            <td style='text-align:left; font-weight:600;'>".esc($row['name'])." <span style='font-size:0.85em;opacity:0.85;' title='".$donor_badge['level']." Donor'>".$donor_badge['icon']."</span></td>
            <td><span class='blood-badge $bg_class'>".esc($row['blood_group'])."</span></td>
            <td><span class='$st_class'>$st_icon $st_text</span></td>
            <td style='text-align:left; color:var(--text-muted); font-size:0.88em;'>📍 ".esc($row['location'])."</td>
            <td style='color:var(--text-muted); font-size:0.88em;'>🗓 ".esc($display_last)."</td>
            <td class='unselectable' oncontextmenu='return false;' oncopy='return false;'>
                $call_btn_desktop
            </td>
        </tr>";

        // ── Mobile card ────────────────────────────────────────────────
        //  Whole info area opens a read-only detail popup (no phone/email shown).
        $dc_data = "data-id='".esc($row['id'])."'"
            ." data-name='".esc($row['name'])."'"
            ." data-group='".esc($row['blood_group'])."'"
            ." data-bgclass='".esc($bg_class)."'"
            ." data-status='".esc($st_text)."'"
            ." data-stclass='".esc($st_class)."'"
            ." data-sticon='".esc($st_icon)."'"
            ." data-loc='".esc($row['location'])."'"
            ." data-last='".esc($display_last)."'"
            ." data-since='".esc($display_since)."'"
            ." data-total='".$total_don."'"
            ." data-badge='".esc($donor_badge['level'])."'"
            ." data-badgeicon='".esc($donor_badge['icon'])."'"
            ." data-available='".($is_available ? '1' : '0')."'"
            ." data-hide='".$hide_me_row."'"
            ." data-allowcall='".$allow_call_row."'";
        $cards .= "
        <div class='dc' $dc_data>
            <div class='dc-badge-wrap' onclick='openDonorDetail(this.parentNode)'>
                <span class='dc-sn'>$sn</span>
                <span class='dc-badge $bg_class'>".esc($row['blood_group'])."</span>
            </div>
            <div class='dc-info' onclick='openDonorDetail(this.parentNode)'>
                <div class='dc-name'>".esc($row['name'])." <span style='font-size:0.85em;opacity:0.85;' title='".$donor_badge['level']." Donor'>".$donor_badge['icon']."</span></div>
                <span class='$st_class dc-status-badge'>$st_icon $st_text</span>
                <div class='dc-loc'>📍 ".esc($row['location'])."</div>
                <div class='dc-last'>🗓 $display_last</div>
            </div>
            $call_btn_mobile
        </div>";
    }
    
    if(!$found) { 
        $output = "<tr><td colspan='7' class='no-data'>✖ কোনো রক্তদাতা পাওয়া যায়নি।</td></tr>";
        $cards  = "<div class='no-data' style='text-align:center;padding:30px;'>✖ কোনো রক্তদাতা পাওয়া যায়নি।</div>";
    }
    
    $total_pages = ceil($total_records / $limit);
    $pag_html = '<div class="pagination">';
    if($page > 1) $pag_html .= '<a href="#" onclick="fetchFilteredData('.($page-1).',true); return false;">Previous</a>';
    for($i = 1; $i <= $total_pages; $i++){
        $active = ($i == $page) ? ' class="active-page"' : '';
        $pag_html .= '<a href="#" onclick="fetchFilteredData('.$i.',true); return false;"'.$active.'>'.$i.'</a>';
    }
    if($page < $total_pages) $pag_html .= '<a href="#" onclick="fetchFilteredData('.($page+1).',true); return false;">Next</a>';
    $pag_html .= '</div>';
    
    // Fresh available counts — always global (not filtered) for stat cards
    // FIX: $avail_counts stays 0 on AJAX calls. Run fresh query here instead.
    $fresh_counts = ["A+"=>0,"A-"=>0,"B+"=>0,"B-"=>0,"AB+"=>0,"AB-"=>0,"O+"=>0,"O-"=>0];
    mysqli_report(MYSQLI_REPORT_OFF);
    $fc_q = $conn->query("SELECT blood_group, COUNT(*) as cnt FROM donors
        WHERE (willing_to_donate IS NULL OR willing_to_donate='yes' OR willing_to_donate='')
          AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00'
               OR DATEDIFF(CURDATE(), last_donation) >= 120)
        GROUP BY blood_group");
    if ($fc_q) while ($fcr = $fc_q->fetch_assoc())
        if (isset($fresh_counts[$fcr['blood_group']]))
            $fresh_counts[$fcr['blood_group']] = (int)$fcr['cnt'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $fresh_total_avail = array_sum($fresh_counts);
    echo json_encode(["table" => $output, "cards" => $cards, "counts" => $fresh_counts, "total_available" => $fresh_total_avail, "pagination" => $pag_html, "total" => $total_records]);
    $stmt->close();
    exit();
}

// === AJAX: Analytics Data ===
if(isset($_POST['get_analytics'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start(); // flush any buffered warnings before JSON output
    checkRateLimit('analytics', 10, 60);

    // Temporarily disable strict error reporting so missing columns don't crash analytics
    mysqli_report(MYSQLI_REPORT_OFF);

    $total       = (int)($conn->query("SELECT COUNT(*) as c FROM donors")->fetch_assoc()['c'] ?? 0);

    // Availability matches getLiveStatus(): willing (not 'no', NULL/empty = willing) AND cooldown passed
    $r_avail = $conn->query("SELECT COUNT(*) as c FROM donors WHERE (willing_to_donate IS NULL OR willing_to_donate<>'no') AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120)");
    $available   = $r_avail ? (int)$r_avail->fetch_assoc()['c'] : 0;

    // "Not Willing" — opted out AND cooldown passed (matches displayed status; in-cooldown
    // opt-outs count as Not Available, since cooldown wins in getLiveStatus()).
    $r_unav = $conn->query("SELECT COUNT(*) as c FROM donors WHERE willing_to_donate='no' AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120)");
    $unavailable = $r_unav ? (int)$r_unav->fetch_assoc()['c'] : 0;

    $r_calls = $conn->query("SELECT counter_value as c FROM analytics_counters WHERE counter_name='total_calls_ever'");
    $r_calls_row = $r_calls ? $r_calls->fetch_assoc() : null;
    if($r_calls_row !== null){
        $total_calls = (int)$r_calls_row['c'];
    } else {
        // Fallback: counter table missing/not seeded yet
        $r_calls_fb = $conn->query("SELECT COUNT(*) as c FROM call_logs");
        $total_calls = $r_calls_fb ? (int)($r_calls_fb->fetch_assoc()['c'] ?? 0) : 0;
    }

    // Blood requests stats
    $r_active_req = $conn->query("SELECT COUNT(*) as c FROM blood_requests WHERE status='Active'");
    $active_requests = $r_active_req ? (int)$r_active_req->fetch_assoc()['c'] : 0;

    // "Successfully Donated" — persistent counter, never decreases even if donor deletes account
    $r_donated = $conn->query("SELECT counter_value as c FROM analytics_counters WHERE counter_name='total_donations_ever'");
    $r_donated_row = $r_donated ? $r_donated->fetch_assoc() : null;
    if($r_donated_row !== null){
        $fulfilled_requests = (int)$r_donated_row['c'];
    } else {
        // Fallback: counter table missing/not seeded yet
        $r_donated_fb = $conn->query("SELECT COALESCE(SUM(total_donations),0) as c FROM donors");
        $fulfilled_requests = $r_donated_fb ? (int)($r_donated_fb->fetch_assoc()['c'] ?? 0) : 0;
    }

    // Blood group breakdown
    $by_group = [];
    $bg_res = $conn->query("SELECT blood_group, COUNT(*) as cnt FROM donors GROUP BY blood_group ORDER BY cnt DESC");
    if($bg_res) while($r = $bg_res->fetch_assoc()) $by_group[$r['blood_group']] = (int)$r['cnt'];

    // Badge breakdown
    $by_badge = ['New'=>0,'Active'=>0,'Hero'=>0,'Legend'=>0];
    $badge_res = $conn->query("SELECT badge_level, COUNT(*) as cnt FROM donors GROUP BY badge_level");
    if($badge_res) {
        while($r = $badge_res->fetch_assoc()) {
            if(isset($by_badge[$r['badge_level']])) $by_badge[$r['badge_level']] = (int)$r['cnt'];
        }
    }
    // Fallback: if badge_level column missing, bucket by total_donations
    if(array_sum($by_badge) === 0) {
        $td_res = $conn->query("SELECT total_donations FROM donors");
        if($td_res) {
            while($r = $td_res->fetch_assoc()){
                $t = (int)($r['total_donations'] ?? 0);
                if($t >= 10)    $by_badge['Legend']++;
                elseif($t >= 5) $by_badge['Hero']++;
                elseif($t >= 2) $by_badge['Active']++;
                else            $by_badge['New']++;
            }
        } else {
            $by_badge['New'] = $total;
        }
    }

    // Monthly registrations — created_at may not exist on some installs
    $monthly = [];
    $monthly_res = $conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as cnt FROM donors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY DATE_FORMAT(created_at,'%Y-%m') ASC");
    if($monthly_res) while($r = $monthly_res->fetch_assoc()) $monthly[] = $r;

    // Top locations
    $by_loc = [];
    $loc_res = $conn->query("SELECT SUBSTRING_INDEX(location,' - ',1) as area, COUNT(*) as cnt FROM donors GROUP BY area ORDER BY cnt DESC LIMIT 6");
    if($loc_res) while($r = $loc_res->fetch_assoc()) $by_loc[] = $r;

    // Available count by blood group (for stat cards live update)
    $by_group_avail = ["A+"=>0,"A-"=>0,"B+"=>0,"B-"=>0,"AB+"=>0,"AB-"=>0,"O+"=>0,"O-"=>0];
    $bga_res = $conn->query("SELECT blood_group, COUNT(*) as cnt FROM donors
        WHERE (willing_to_donate IS NULL OR willing_to_donate<>'no')
          AND (last_donation='no' OR last_donation='' OR last_donation='0000-00-00' OR DATEDIFF(CURDATE(),last_donation)>=120)
        GROUP BY blood_group");
    if($bga_res) while($r=$bga_res->fetch_assoc()) { if(isset($by_group_avail[$r['blood_group']])) $by_group_avail[$r['blood_group']]=(int)$r['cnt']; }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    echo json_encode(compact('total','available','unavailable','total_calls','active_requests','fulfilled_requests','by_group','by_group_avail','by_badge','monthly','by_loc'));
    exit();
}

// === AJAX: Map Data (donor locations with geo coords from reg_geo) ===
if(isset($_POST['get_map_data'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('map_data', 10, 60);
    // Point #4: map এখন Active Emergency Requests দেখায় (patient/hospital location, EXACT)।
    //  Donor-এর personal location আর কখনো map-এ plot হয় না (privacy)। শুধু geo-tagged
    //  (hospital_lat/lng সহ) active request-গুলো pin হয়।
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("UPDATE blood_requests SET status='Expired' WHERE status='Active' AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)");
    $res = $conn->query("SELECT id, patient_name, blood_group, hospital, contact, urgency, bags_needed, note, verified_location, hospital_lat, hospital_lng, UNIX_TIMESTAMP(required_at) as required_at, UNIX_TIMESTAMP(created_at) as created_at FROM blood_requests WHERE status='Active' AND hospital_lat IS NOT NULL AND hospital_lng IS NOT NULL ORDER BY FIELD(urgency,'Critical','High','Medium'), created_at DESC LIMIT 200");
    $markers = [];
    if($res){
        while($row = $res->fetch_assoc()){
            $markers[] = [
                'id'         => (int)$row['id'],
                'lat'        => (float)$row['hospital_lat'],
                'lng'        => (float)$row['hospital_lng'],
                'patient'    => esc($row['patient_name']),
                'group'      => esc($row['blood_group']),
                'hospital'   => esc($row['hospital']),
                'contact'    => esc($row['contact']),
                'urgency'    => esc($row['urgency']),
                'bags'       => (int)$row['bags_needed'],
                'note'       => esc($row['note']),
                'verified'   => (int)$row['verified_location'],
                'required_at'=> $row['required_at'] ? (int)$row['required_at'] : null,
                'created_at' => $row['created_at']  ? (int)$row['created_at']  : null
            ];
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    echo json_encode($markers, JSON_UNESCAPED_UNICODE);
    exit();
}

// ============================================================
// FEATURE: EMERGENCY BLOOD REQUESTS
// ============================================================

// Submit blood request
if(isset($_POST['submit_blood_request'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    requireAuth(); // blood request পাঠাতে সাইন ইন আবশ্যক (Google / ফোন)
    checkRateLimit('blood_request', 3, 300);

    $patient   = trim($_POST['patient_name']   ?? '');
    $blood_grp = trim($_POST['req_blood_group'] ?? '');
    $hospital  = trim($_POST['hospital']        ?? '');
    $contact   = trim($_POST['req_contact']     ?? '');
    $urgency   = trim($_POST['urgency']         ?? 'High');
    $bags      = max(1, min(10, (int)($_POST['bags_needed'] ?? 1)));
    $note      = trim($_POST['req_note']        ?? '');

    // ── "কখন রক্ত প্রয়োজন" (required by) — datetime-local পাঠায় "Y-m-dTH:i" ──
    // MySQL DATETIME-এ normalize: 'T' → space, seconds যোগ। invalid হলে ফাঁকা।
    $required_raw = trim($_POST['required_at'] ?? '');
    $required_sql = '';
    if($required_raw !== ''){
        $rr = str_replace('T', ' ', $required_raw);
        $rd = DateTime::createFromFormat('Y-m-d H:i:s', $rr)
            ?: DateTime::createFromFormat('Y-m-d H:i', $rr);
        if($rd instanceof DateTime) $required_sql = $rd->format('Y-m-d H:i:s');
    }

    // ── ছবি/প্রেসক্রিপশন এখন আবশ্যক — কমপক্ষে ১টি validly-uploaded ফাইল লাগবে ──
    // JS bypass করলেও server এখানে আটকাবে। DB insert-এর আগেই যাচাই করা হয়,
    // তাই ছবি ছাড়া কোনো request কখনো তৈরি হবে না।
    $has_doc = false;
    if (!empty($_FILES['req_docs']) && is_array($_FILES['req_docs']['error'] ?? null)) {
        foreach ($_FILES['req_docs']['error'] as $de) {
            if ($de === UPLOAD_ERR_OK) { $has_doc = true; break; }
        }
    }

    // Build response array — output NOTHING until the very end
    $resp = [];

    $valid_groups = ["A+","A-","B+","B-","AB+","AB-","O+","O-"];
    if(!in_array($blood_grp, $valid_groups, true)){
        $resp = ["status"=>"error","msg"=>"Invalid blood group."];
    } elseif(!preg_match('/^\+8801\d{9}$/', $contact)){
        $resp = ["status"=>"error","msg"=>"সঠিক যোগাযোগ নম্বর দিন।"];
    } elseif(empty($patient)||empty($hospital)){
        $resp = ["status"=>"error","msg"=>"রোগীর নাম ও হাসপাতাল দিন।"];
    } elseif($required_sql===''){
        $resp = ["status"=>"error","msg"=>"কখন রক্ত প্রয়োজন তা দিন।"];
    } elseif(!$has_doc){
        $resp = ["status"=>"error","msg"=>"ছবি / প্রেসক্রিপশন দিন (কমপক্ষে ১টি আবশ্যক)।"];
    } else {
        $valid_urgency=['Critical','High','Medium'];
        if(!in_array($urgency,$valid_urgency,true)) $urgency='High';

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn->query("CREATE TABLE IF NOT EXISTS `blood_requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `patient_name` VARCHAR(100) NOT NULL,
            `blood_group` VARCHAR(5) NOT NULL,
            `hospital` VARCHAR(200) NOT NULL,
            `contact` VARCHAR(20) NOT NULL,
            `urgency` VARCHAR(10) DEFAULT 'High',
            `bags_needed` INT DEFAULT 1,
            `note` VARCHAR(500) DEFAULT '',
            `status` VARCHAR(20) DEFAULT 'Active',
            `req_ip` VARCHAR(50) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $conn->query("UPDATE blood_requests SET status='Expired' WHERE status='Active' AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)");
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

        $ip = $_SERVER['REMOTE_ADDR'];
        $req_device_id = trim($_POST['device_id'] ?? '');
        $req_auth_uid  = currentAuthUid(); // signed-in account → tie request for account-owned management

        // ── Hospital location (point #5) — autocomplete থেকে select করলে lat/lng +
        //  verified_location=TRUE আসে; manually লিখলে coords নেই → verified=FALSE।
        $h_lat_raw = trim($_POST['hospital_lat'] ?? '');
        $h_lng_raw = trim($_POST['hospital_lng'] ?? '');
        $h_lat = ($h_lat_raw !== '' && is_numeric($h_lat_raw)) ? (float)$h_lat_raw : null;
        $h_lng = ($h_lng_raw !== '' && is_numeric($h_lng_raw)) ? (float)$h_lng_raw : null;
        $verified_loc = (trim($_POST['verified_location'] ?? '0') === '1') ? 1 : 0;
        if($h_lat === null || $h_lng === null) $verified_loc = 0; // coords ছাড়া verified হতে পারে না

        try {
            $stmt = $conn->prepare("INSERT INTO blood_requests (patient_name,blood_group,hospital,contact,urgency,bags_needed,note,required_at,req_ip,auth_uid,hospital_lat,hospital_lng,verified_location) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssissssddi",$patient,$blood_grp,$hospital,$contact,$urgency,$bags,$note,$required_sql,$ip,$req_auth_uid,$h_lat,$h_lng,$verified_loc);
            if($stmt->execute()){
                $new_id = $conn->insert_id;
                // ── Optional document uploads (≤ REQ_DOC_MAX_FILES images) ──
                // Request ইতিমধ্যে সেভ হয়ে গেছে — কোনো ফাইল fail করলেও request fail করে না।
                $docs_saved = 0; $doc_warn = '';
                if (!empty($_FILES['req_docs']) && is_array($_FILES['req_docs']['tmp_name'])) {
                    $names = $_FILES['req_docs']['tmp_name'];
                    $sizes = $_FILES['req_docs']['size'];
                    $errs  = $_FILES['req_docs']['error'];
                    $count = min(count($names), REQ_DOC_MAX_FILES);
                    for ($i = 0; $i < $count; $i++) {
                        if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                        if (($errs[$i] ?? 1) !== UPLOAD_ERR_OK) { $doc_warn = 'কিছু ছবি আপলোড হয়নি।'; continue; }
                        try {
                            $pr = reqdoc_process_upload($names[$i], (int)($sizes[$i] ?? 0));
                            if ($pr['ok']) {
                                $tok = bin2hex(random_bytes(16));
                                mysqli_report(MYSQLI_REPORT_OFF);
                                $ds = $conn->prepare("INSERT INTO request_documents (request_id,file_path,token,mime,bytes) VALUES (?,?,?,?,?)");
                                $mime = 'image/jpeg';
                                $ds->bind_param("isssi", $new_id, $pr['relpath'], $tok, $mime, $pr['bytes']);
                                $ds->execute(); $ds->close();
                                mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
                                $docs_saved++;
                            } else {
                                $doc_warn = $pr['err'] ?: 'কিছু ছবি সংরক্ষণ হয়নি।';
                            }
                        } catch (\Throwable $de) {
                            error_log('reqdoc save: ' . $de->getMessage());
                            $doc_warn = 'কিছু ছবি সংরক্ষণ হয়নি।';
                        }
                    }
                }
                $resp = [
                    "status"       => "success",
                    "msg"          => "✅ রক্তের অনুরোধ পাঠানো হয়েছে!" . ($doc_warn ? (" (" . $doc_warn . ")") : ""),
                    "request_id"   => (int)$new_id,
                    "docs_saved"   => $docs_saved
                ];
                // ── Service notification → requester device কে confirmation পাঠাও ──
                if (!empty($req_device_id)) {
                    $sn_msg = "🆘 আপনার Emergency Blood Request সফলভাবে পাঠানো হয়েছে!\n\n"
                            . "🩸 Blood Group: {$blood_grp}\n"
                            . "🏥 Hospital: {$hospital}\n"
                            . "🆔 Request ID: #{$new_id}\n\n"
                            . "🗑️ Account Dashboard → \"আমার Requests\" থেকে যেকোনো সময় Request মুছতে পারবেন।\n"
                            . "⏳ ৩ দিন পর Request স্বয়ংক্রিয়ভাবে Expire হয়ে যাবে।";
                    $sn_type = 'blood_request';
                    mysqli_report(MYSQLI_REPORT_OFF);
                    $sn = $conn->prepare("INSERT INTO service_notifications (device_id, type, message) VALUES (?,?,?)");
                    $sn->bind_param("sss", $req_device_id, $sn_type, $sn_msg);
                    $sn->execute(); $sn->close();
                    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
                }
                // ── Save vars for background FCM push ──
                $_fcm_new_id    = $new_id;
                $_fcm_blood_grp = $blood_grp;
                $_fcm_patient   = $patient;
                $_fcm_hospital  = $hospital;
                $_fcm_contact   = $contact;
                $_fcm_urgency   = $urgency;
            } else {
                $resp = ["status"=>"error","msg"=>"ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"];
            }
            $stmt->close();
        } catch(Exception $ex) {
            $resp = ["status"=>"error","msg"=>"DB error। আবার চেষ্টা করুন।"];
        }
    }

    // ── Send JSON response to browser IMMEDIATELY ──────────────
    // FCM curl calls happen AFTER this — browser never waits for them
    while(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    ignore_user_abort(true);
    $resp_json = json_encode($resp);
    header('Content-Length: ' . strlen($resp_json));
    echo $resp_json;
    @ob_flush(); @flush();
    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();

    // ── FCM Push AFTER response sent (background) ──────────────
    if (!empty($_fcm_new_id)) {
        $urgency_bn = ['Critical'=>'🔴 অতিজরুরি','High'=>'🟠 জরুরি','Medium'=>'🔵 প্রয়োজন'];
        $urg_label  = $urgency_bn[$_fcm_urgency] ?? '🟠 জরুরি';
        $push_title = '🩸 ' . $_fcm_blood_grp . ' রক্ত দরকার! — ' . $urg_label;
        $push_body  = "👤 " . $_fcm_patient . "\n🏥 " . $_fcm_hospital . "\n📞 " . $_fcm_contact . "\n🤲 আল্লাহর ওয়াস্তে এগিয়ে আসুন!";
        $push_url   = SITE_URL . "/?tab=emergency";

        function _fcm_base64url($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }
        function _fcm_get_oauth_token() {
            $cache_file = dirname(__DIR__) . '/.fcm_token_cache';
            if (file_exists($cache_file)) {
                $cached = @json_decode(@file_get_contents($cache_file), true);
                if ($cached && isset($cached['token'], $cached['exp']) && time() < $cached['exp']) {
                    return $cached['token'];
                }
            }
            $client_email = "firebase-adminsdk-fbsvc@shsmc-blood-portal.iam.gserviceaccount.com";
            $private_key  = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCaYTTALQ5tu9/j\n2IviUJI5F6nMwLYIGdAJKIMVdml4gxrgptWEJYXTb7e5p5yFlu9sGpDdcRD+BUlB\nXy8TnRtieQ1B1Kjqko+EyXVsIC+Kf+CN/yq/mVCySFEBgzOe+2efxWSvZiLQdv/6\nV7PfVJS9Mv0/hFtnUC+6EKKRBPDsLo8d4qD8hftdNBL5lWS4XtBP7MjEKLm6S3QO\nMlbgxTeLFspcb7eNZK755c4C3AHyhdnrBNNmHZTlVuVGVtfK5UXq3MVKyW10/Ek2\naurh+kfzow3OgEk6SW46dL31KkGYa2GFxOS/6rlaGMiFK0QkJL1GNa2BCFAQYT7J\nAPAzvJPjAgMBAAECggEAC2xTQZUT6D1qnQASTwtfMSegbNd69gZ9mkU2eIlr1yWn\nANCCJBESt0kg8x/ajm1TXKW/6mLKEGxxCzab09EK4bJ2BKpTsBJq2Yx+n82R4acC\nBVUdjf0uN22acN41x6HFUnvXWL3Z/aA7OK7x+aiB3li+McuoEnD11x1mqgxk4f+X\n2/Iie+fYBnL/OQoHMi7w/XHnHqoqGiWQLP/mTfzX43albR2b/JR0cVHii//hqeMz\nmlF3rv6fTIfh+mBxBH2GtjN93LaNsBWpitMER2hpX7gG/INEy7sUXI6jz2Rh7/Lf\nNWWzKU//xu37j7GtsV+LLak04TZ4ByfaFA4r7VdIYQKBgQDMCbhenujXz6JM0AHR\nde4wAp4xJrxB/wa4EXUJXzEFfPe5rlWjii7dXUWo2oYp6j1sHcBH3GbfhGlrp9oB\nvi+Kb3DwWBhGB6MCE4YJdaIEpIdWEoxEXirzQSf0yE1OKYjEkavENEuoOQssL2lA\nqv0fLsIWWKL6ouQXh1ozuZDBDQKBgQDBsgKAsRyLUvz05d4se3teZhJPgguk52tT\nL3wpFiRSIsB8zuIP0IH+ovp1puerdDZCtvf/lTS431EU/Hfe3orEgZHEd2vWD3xi\nxeHmtw9e0t7UkIu0q7LsUTJM+XhL9p7NydFNXTW1nH2bVNkHCu7JGvNZyvST2KAS\nJSGQTKwMrwKBgDJIhvZSpUFiOzZA4OHU9WFBk+i7ChQdnHNKYhRwMC2REZ/h9dr6\n1/fX363wRLYZsw9s+ZD8ISIeiLhuQkzBqQet1SB2JW1EvohpdVPpeIc6YNv2cDj9\nGAqg2Q77Ogn0NG91EuakmKyZekZmXMMCIKVJqa1GJMwtzpZ51eH/bkwVAoGBAIow\nGaD+usKbfmSp6owJvMZoQ//9Y5lOkT9TzVzysw72RCXG43ks5NFqLQ3q+bVUv7Fx\nIBVzuZ17lTlHta2HT7FKT1i/amvZuIAvdS9Iwup/vwIf7cwEAy6d7ykDglOPq1Rd\n+7kaGstqziIXso5Xumw3kg4pwbwI/Ip1ezCbwtN5AoGALxmWPYZ4bLwd5CkUop+S\nAjk1S4U2XNxVO+WXeEGc1ZyqyV6sjzf/cU0FkXZ8F1UA2WcEj54/9O0bp7d1Asss\nZN4YF8seZuzPSce2KMXdCJK8U6B7yg60CINZ2YxyCbxAJxbyPOt03+/WxTtuNoaR\n7WPRHDoF1VMk/DzBD4d6yP0=\n-----END PRIVATE KEY-----\n";
            $now = time();
            $header  = _fcm_base64url(json_encode(["alg"=>"RS256","typ"=>"JWT"]));
            $payload = _fcm_base64url(json_encode(["iss"=>$client_email,"scope"=>"https://www.googleapis.com/auth/firebase.messaging","aud"=>"https://oauth2.googleapis.com/token","iat"=>$now,"exp"=>$now+3600]));
            $signing_input = $header . '.' . $payload;
            $signature = '';
            $pkey = openssl_pkey_get_private($private_key);
            if (!$pkey) return null;
            openssl_sign($signing_input, $signature, $pkey, 'SHA256');
            $jwt = $signing_input . '.' . _fcm_base64url($signature);
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>8,
                CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt])]);
            $res = curl_exec($ch); curl_close($ch);
            if (!$res) return null;
            $data = json_decode($res, true);
            if (empty($data['access_token'])) return null;
            @file_put_contents($cache_file, json_encode(['token'=>$data['access_token'],'exp'=>$now+3300]));
            return $data['access_token'];
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $tok_q = $conn->query("SELECT fcm_token FROM fcm_tokens");
        $fcm_tokens_arr = [];
        if ($tok_q) { while ($tr = $tok_q->fetch_assoc()) $fcm_tokens_arr[] = $tr['fcm_token']; }
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

        if (!empty($fcm_tokens_arr)) {
            $oauth_token = @_fcm_get_oauth_token();
            if ($oauth_token) {
                $fcm_endpoint = "https://fcm.googleapis.com/v1/projects/shsmc-blood-portal/messages:send";
                $stale_tokens = [];
                // ── Use curl_multi to send ALL pushes in parallel ──
                $mh = curl_multi_init();
                $handles = [];
                foreach ($fcm_tokens_arr as $fcm_tok) {
                    // Data-only — notification field সরানো হয়েছে।
                    // FCM notification field থাকলে onBackgroundMessage bypass হয়।
                    $msg_payload = json_encode(["message"=>["token"=>$fcm_tok,
                        "webpush"=>["fcm_options"=>["link"=>$push_url]],
                        "data"=>[
                            "type"         => "blood_request",
                            "request_id"   => (string)$_fcm_new_id,
                            "blood_group"  => $_fcm_blood_grp,
                            "hospital"     => $_fcm_hospital,
                            "contact"      => $_fcm_contact,
                            "patient_name" => $_fcm_patient,
                            "urgency"      => $_fcm_urgency,
                            "url"          => $push_url,
                            "title"        => $push_title,
                            "body"         => $push_body
                        ]]]);
                    $ch = curl_init($fcm_endpoint);
                    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>6,
                        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$oauth_token],
                        CURLOPT_POSTFIELDS=>$msg_payload]);
                    curl_multi_add_handle($mh, $ch);
                    $handles[$fcm_tok] = $ch;
                }
                // Execute all in parallel
                $running = null;
                do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
                // Check results, remove stale tokens
                foreach ($handles as $tok => $ch) {
                    $res = curl_multi_getcontent($ch);
                    if ($res) {
                        $r = json_decode($res, true);
                        if (!empty($r['error']['status']) && in_array($r['error']['status'],['UNREGISTERED','INVALID_ARGUMENT']))
                            $stale_tokens[] = $tok;
                    }
                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);
                }
                curl_multi_close($mh);
                if (!empty($stale_tokens)) {
                    mysqli_report(MYSQLI_REPORT_OFF);
                    foreach ($stale_tokens as $st) {
                        $del = $conn->prepare("DELETE FROM fcm_tokens WHERE fcm_token=?");
                        $del->bind_param("s", $st); $del->execute(); $del->close();
                    }
                    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
                }
            }
        }
    }
    exit();
}

// === GET MY BLOOD REQUESTS (signed-in account) ===
if(isset($_POST['get_my_requests'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('get_my_requests', 30, 60);
    if(empty($_SESSION['auth_uid'])){
        echo json_encode(["status"=>"error","msg"=>"logged out"]); exit();
    }
    $uid = $_SESSION['auth_uid'];
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("UPDATE blood_requests SET status='Expired' WHERE status='Active' AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)");
    $rows = [];
    $stmt = $conn->prepare("SELECT id,patient_name,blood_group,hospital,contact,urgency,bags_needed,note,verified_location,hospital_lat,hospital_lng,UNIX_TIMESTAMP(required_at) as required_at,UNIX_TIMESTAMP(created_at) as created_at FROM blood_requests WHERE auth_uid=? AND status='Active' ORDER BY created_at DESC LIMIT 20");
    if($stmt){
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $docmap = reqdoc_fetch_for($conn, array_column($rows, 'id'));
    foreach ($rows as &$rw) { $rw['docs'] = $docmap[(int)$rw['id']] ?? []; }
    unset($rw);
    echo json_encode(["status"=>"success","requests"=>$rows], JSON_UNESCAPED_UNICODE);
    exit();
}

// === DELETE MY BLOOD REQUEST (account-owned) ===
if(isset($_POST['delete_my_request'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('delete_my_request', 15, 60);
    $uid = requireAuth();
    $req_id = (int)($_POST['request_id'] ?? 0);
    if($req_id <= 0){
        echo json_encode(["status"=>"error","msg"=>"❌ Request পাওয়া যায়নি।"]); exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    // Ownership যাচাই — শুধু নিজের request মুছতে পারবে
    $stmt = $conn->prepare("SELECT id FROM blood_requests WHERE id=? AND auth_uid=? LIMIT 1");
    $stmt->bind_param("is", $req_id, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if(!$row){
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        echo json_encode(["status"=>"error","msg"=>"❌ এই Request আপনার নয় অথবা পাওয়া যায়নি।"]); exit();
    }
    $upd = $conn->prepare("UPDATE blood_requests SET status='Deleted' WHERE id=? AND auth_uid=?");
    $upd->bind_param("is", $req_id, $uid);
    $ok = $upd->execute();
    $upd->close();
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    if($ok){
        echo json_encode(["status"=>"success","msg"=>"✅ আপনার Request মুছে ফেলা হয়েছে।"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"❌ মুছতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"]);
    }
    exit();
}

// Get active blood requests
if(isset($_POST['get_blood_requests'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('get_requests',30,60);
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `blood_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_name` VARCHAR(100) NOT NULL,
        `blood_group` VARCHAR(5) NOT NULL,
        `hospital` VARCHAR(200) NOT NULL,
        `contact` VARCHAR(20) NOT NULL,
        `urgency` VARCHAR(10) DEFAULT 'High',
        `bags_needed` INT DEFAULT 1,
        `note` VARCHAR(500) DEFAULT '',
        `status` VARCHAR(20) DEFAULT 'Active',
        `req_ip` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Auto-expire: any Active request older than 72 hours → Expired
    $conn->query("UPDATE blood_requests SET status='Expired' WHERE status='Active' AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)");
    // UNIX_TIMESTAMP = seconds since epoch, completely timezone-independent
    // DATE_FORMAT with 'Z' suffix failed on InfinityFree (MySQL timezone != UTC)
    $res = $conn->query("SELECT id,patient_name,blood_group,hospital,contact,urgency,bags_needed,note,verified_location,hospital_lat,hospital_lng,UNIX_TIMESTAMP(required_at) as required_at,UNIX_TIMESTAMP(created_at) as created_at FROM blood_requests WHERE status='Active' ORDER BY FIELD(urgency,'Critical','High','Medium'), created_at DESC LIMIT 20");
    $requests=[];
    if($res) while($r=$res->fetch_assoc()) $requests[]=$r;
    $docmap = reqdoc_fetch_for($conn, array_column($requests, 'id'));
    foreach ($requests as &$rw) { $rw['docs'] = $docmap[(int)$rw['id']] ?? []; }
    unset($rw);
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    echo json_encode($requests);
    exit();
}

// ============================================================
// FEATURE: NEARBY DONORS (Haversine distance filter)
// ============================================================
if(isset($_POST['get_nearby_donors'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('nearby',20,60);
    $user_lat = (float)($_POST['lat'] ?? 0);
    $user_lng = (float)($_POST['lng'] ?? 0);
    $radius_km = min(50, max(1, (float)($_POST['radius'] ?? 5)));
    $f_group  = trim($_POST['filter_group'] ?? 'All');
    $f_status = trim($_POST['filter_status'] ?? 'All');
    $valid_groups=["A+","A-","B+","B-","AB+","AB-","O+","O-","All"];
    $valid_statuses=["All","Available","Not Available","Unavailable"];
    if(!in_array($f_group,$valid_groups,true)) $f_group='All';
    if(!in_array($f_status,$valid_statuses,true)) $f_status='All';

    if($user_lat==0&&$user_lng==0){echo json_encode(["status"=>"error","msg"=>"Location পাওয়া যায়নি।"]);exit();}

    $stmt = $conn->prepare("SELECT id,name,blood_group,location,last_donation,willing_to_donate,total_donations,reg_geo,hide_me,allow_call FROM donors WHERE reg_geo LIKE 'Lat:%'");
    $stmt->execute();
    $res=$stmt->get_result();
    $nearby=[];
    while($row=$res->fetch_assoc()){
        preg_match('/Lat:\s*([\-0-9.]+),\s*Lon:\s*([\-0-9.]+)/',$row['reg_geo'],$m);
        if(count($m)!==3) continue;
        // ── Location privacy (point #2): exact coordinate কখনো ব্যবহার করে দূরত্ব
        //  পাঠানো হয় না। hide_me অনুযায়ী jitter করে, jittered coordinate থেকেই
        //  distance হিসাব হয় — ফলে প্রকৃত proximity leak হয় না।
        $hide_me    = (int)($row['hide_me'] ?? 0);
        $allow_call = (int)($row['allow_call'] ?? 1);
        $seed = (string)$row['id'];
        if($hide_me){ [$jlat,$jlng] = applyLocationJitter((float)$m[1],(float)$m[2],500,1000,$seed); }
        else        { [$jlat,$jlng] = applyLocationJitter((float)$m[1],(float)$m[2],100,500,$seed); }
        $dlat=deg2rad($jlat-$user_lat);
        $dlng=deg2rad($jlng-$user_lng);
        $a=sin($dlat/2)*sin($dlat/2)+cos(deg2rad($user_lat))*cos(deg2rad($jlat))*sin($dlng/2)*sin($dlng/2);
        $dist=6371*2*atan2(sqrt($a),sqrt(1-$a));
        if($dist>$radius_km) continue;
        if($f_group!=='All'&&$row['blood_group']!==$f_group) continue;
        $status=getLiveStatus($row['last_donation'],$row['willing_to_donate']??'yes');
        // Filter by live status
        if($f_status!=='All'&&$status!==$f_status) continue;
        $badge=getBadgeInfo((int)($row['total_donations']??0));
        // Address text: hidden → শুধু broad area (সবচেয়ে শেষ অংশ = জেলা/শহর), কখনো
        //  পুরো reg-location text নয়; comma না থাকলে কিছুই দেখাবে না (শুধু "Location Hidden")।
        //  visible → full text।
        $loc_full = (string)$row['location'];
        if($hide_me){
            $parts = array_values(array_filter(array_map('trim', explode(',', $loc_full)), fn($p)=>$p!==''));
            $loc_show = count($parts) >= 2 ? $parts[count($parts)-1] : '';
        } else {
            $loc_show = $loc_full;
        }
        $nearby[]=[
            'id'        =>$row['id'],
            'name'      =>esc($row['name']),
            'group'     =>esc($row['blood_group']),
            'loc'       =>esc($loc_show),
            'status'    =>$status,
            'badge'     =>$badge['icon'].' '.$badge['level'],
            'badge_icon'=>$badge['icon'],
            'dist'      =>round($dist,1),
            'allow_call'=>$allow_call,
            'hidden'    =>$hide_me ? 1 : 0,
            'loc_label' =>$hide_me ? '📍 Location Hidden · আনুমানিক' : ''
        ];
    }
    $stmt->close();
    usort($nearby,fn($a,$b)=>$a['dist']<=>$b['dist']);
    echo json_encode(["status"=>"success","donors"=>array_slice($nearby,0,30)], JSON_UNESCAPED_UNICODE);
    exit();
}

// ============================================================
// FEATURE: PUSH NOTIFICATION SUBSCRIPTION STORE
// ============================================================
if(isset($_POST['save_push_sub'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('push_sub', 5, 60);
    $endpoint  = trim($_POST['endpoint']  ?? '');
    $p256dh    = trim($_POST['p256dh']    ?? '');
    $auth      = trim($_POST['auth']      ?? '');
    $device_id = trim($_POST['device_id'] ?? '');
    if(empty($endpoint)||empty($p256dh)||empty($auth)){echo "error";exit();}
    validateLength($device_id, 100, 'Device ID');
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `push_subscriptions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `endpoint` TEXT NOT NULL,
        `p256dh` TEXT NOT NULL,
        `auth` TEXT NOT NULL,
        `device_id` VARCHAR(100) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Add device_id column if missing on older installs
    @$conn->query("ALTER TABLE push_subscriptions ADD COLUMN IF NOT EXISTS device_id VARCHAR(100) DEFAULT NULL");
    $chk=$conn->prepare("SELECT id FROM push_subscriptions WHERE endpoint=?");
    $chk->bind_param("s",$endpoint);$chk->execute();
    $existing=$chk->get_result()->fetch_assoc();$chk->close();
    if(!$existing){
        $ins=$conn->prepare("INSERT INTO push_subscriptions (endpoint,p256dh,auth,device_id) VALUES (?,?,?,?)");
        $ins->bind_param("ssss",$endpoint,$p256dh,$auth,$device_id);
        $ins->execute();$ins->close();
    } elseif(!empty($device_id)){
        // Update device_id if already exists but device_id was missing
        $upd=$conn->prepare("UPDATE push_subscriptions SET device_id=? WHERE endpoint=? AND (device_id IS NULL OR device_id='')");
        $upd->bind_param("ss",$device_id,$endpoint);$upd->execute();$upd->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    echo "ok";exit();
}

// === SAVE FIREBASE FCM TOKEN ===
if(isset($_POST['save_fcm_token'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('save_fcm', 10, 60);
    $fcm_token = trim($_POST['fcm_token'] ?? '');
    $device_id = trim($_POST['device_id'] ?? '');
    if(empty($fcm_token)){ echo json_encode(["status"=>"error","msg"=>"Token missing"]); exit(); }
    validateLength($fcm_token, 512, 'FCM Token');
    validateLength($device_id, 100, 'Device ID');
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `fcm_tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `fcm_token` VARCHAR(512) NOT NULL,
        `device_id` VARCHAR(100) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_token` (`fcm_token`(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Widen legacy VARCHAR(300) column so long FCM tokens are never rejected/truncated
    @$conn->query("ALTER TABLE fcm_tokens MODIFY COLUMN `fcm_token` VARCHAR(512) NOT NULL");
    $chk = $conn->prepare("SELECT id FROM fcm_tokens WHERE fcm_token=?");
    $chk->bind_param("s", $fcm_token); $chk->execute();
    $exists = $chk->get_result()->fetch_assoc(); $chk->close();
    if(!$exists){
        $ins = $conn->prepare("INSERT INTO fcm_tokens (fcm_token, device_id) VALUES (?,?)");
        $ins->bind_param("ss", $fcm_token, $device_id); $ins->execute(); $ins->close();
    } else {
        $upd = $conn->prepare("UPDATE fcm_tokens SET device_id=?, updated_at=NOW() WHERE fcm_token=?");
        $upd->bind_param("ss", $device_id, $fcm_token); $upd->execute(); $upd->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"ok"]); exit();
}

// === FIREBASE AUTH — verify Google / Phone-OTP ID token, create session ===
// Shared host এ Admin SDK / Composer নেই — তাই Google-এর Identity Toolkit
// REST endpoint (accounts:lookup) দিয়ে ID token verify করা হয়। এটি Google
// নিজে token-এর signature ও expiry যাচাই করে; invalid হলে error দেয়।
if(isset($_POST['firebase_auth'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('firebase_auth', 20, 300);

    $id_token  = trim($_POST['id_token'] ?? '');
    $device_id = trim($_POST['device_id'] ?? '');
    validateLength($id_token, 4096, 'ID Token');
    validateLength($device_id, 100, 'Device ID');
    if($id_token === ''){
        echo json_encode(["status"=>"error","msg"=>"Token missing."]); exit();
    }

    // Firebase Web API key (public — same as client config apiKey)
    $FB_API_KEY = FIREBASE['apiKey'];
    $lookup_url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $FB_API_KEY;

    $ch = curl_init($lookup_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(["idToken" => $id_token])
    ]);
    $resp     = curl_exec($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http !== 200 || !$resp){
        echo json_encode(["status"=>"error","msg"=>"যাচাই ব্যর্থ হয়েছে। আবার চেষ্টা করুন।"]); exit();
    }
    $data = json_decode($resp, true);
    if(empty($data['users'][0])){
        echo json_encode(["status"=>"error","msg"=>"Invalid বা মেয়াদোত্তীর্ণ token।"]); exit();
    }

    $u            = $data['users'][0];
    $firebase_uid = $u['localId'] ?? '';
    $email        = $u['email'] ?? null;
    $phone        = $u['phoneNumber'] ?? null;
    $disp_name    = $u['displayName'] ?? null;
    $photo_url    = $u['photoUrl'] ?? null;
    if($firebase_uid === ''){
        echo json_encode(["status"=>"error","msg"=>"Invalid token।"]); exit();
    }

    // Provider নির্ণয়
    $provider = $phone ? 'phone' : 'google';

    // Phone হলে অবশ্যই Bangladeshi (+880) হতে হবে
    if($phone && !preg_match('/^\+8801\d{9}$/', $phone)){
        echo json_encode(["status"=>"error","msg"=>"শুধুমাত্র বাংলাদেশি (+8801...) নম্বর সমর্থিত।"]); exit();
    }

    // Upsert auth_users
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `auth_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `firebase_uid` VARCHAR(128) NOT NULL,
        `provider` VARCHAR(20) NOT NULL,
        `email` VARCHAR(190) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `name` VARCHAR(120) DEFAULT NULL,
        `device_id` VARCHAR(100) DEFAULT NULL,
        `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_uid` (`firebase_uid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sel = $conn->prepare("SELECT id FROM auth_users WHERE firebase_uid=? LIMIT 1");
    $sel->bind_param("s", $firebase_uid); $sel->execute();
    $exists = $sel->get_result()->fetch_assoc(); $sel->close();
    if($exists){
        $up = $conn->prepare("UPDATE auth_users SET provider=?, email=?, phone=?, name=?, device_id=?, last_login=NOW() WHERE firebase_uid=?");
        $up->bind_param("ssssss", $provider, $email, $phone, $disp_name, $device_id, $firebase_uid);
        $up->execute(); $up->close();
    } else {
        $in = $conn->prepare("INSERT INTO auth_users (firebase_uid, provider, email, phone, name, device_id) VALUES (?,?,?,?,?,?)");
        $in->bind_param("ssssss", $firebase_uid, $provider, $email, $phone, $disp_name, $device_id);
        $in->execute(); $in->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

    // Login session set
    $_SESSION['auth_uid']      = $firebase_uid;
    $_SESSION['auth_provider'] = $provider;
    $_SESSION['auth_email']    = $email;
    $_SESSION['auth_phone']    = $phone;
    $_SESSION['auth_name']     = $disp_name;
    $_SESSION['auth_photo']    = $photo_url;

    // verified state DB থেকে নাও (Telegram/WhatsApp bind করা থাকলে true);
    // Phone-OTP sign-in হলে config অনুযায়ী নিজেই verified ধরা হয়।
    _refresh_auth_verified($conn);

    // যদি এই phone দিয়ে কোনো donor আগে register করা থাকে — link দেখাও
    $linked_donor = null;
    if($phone){
        mysqli_report(MYSQLI_REPORT_OFF);
        $dq = $conn->prepare("SELECT name FROM donors WHERE phone=? LIMIT 1");
        $dq->bind_param("s", $phone); $dq->execute();
        $dr = $dq->get_result()->fetch_assoc(); $dq->close();
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        if($dr) $linked_donor = $dr['name'];
    }

    echo json_encode([
        "status"         => "success",
        "provider"       => $provider,
        "email"          => $email,
        "phone"          => $phone,
        "name"           => $disp_name,
        "photo"          => $photo_url,
        "linked_donor"   => $linked_donor,
        "has_donor"      => _has_donor_for_uid($conn, $firebase_uid, $phone),
        "verified"       => _auth_is_verified(),
        "verify_channel" => $_SESSION['auth_verify_channel'] ?? null,
        "verify_phone"   => $_SESSION['auth_verify_phone'] ?? ($phone ?: null)
    ]);
    exit();
}

// === FIREBASE LOGOUT — clear auth session ===
if(isset($_POST['firebase_logout'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    unset($_SESSION['auth_uid'], $_SESSION['auth_provider'], $_SESSION['auth_email'],
          $_SESSION['auth_phone'], $_SESSION['auth_name'], $_SESSION['auth_photo'],
          $_SESSION['auth_verified'], $_SESSION['auth_verify_channel'],
          $_SESSION['auth_verify_phone']);
    echo json_encode(["status"=>"ok"]);
    exit();
}

// ════════════════════════════════════════════════════════════════════
//  ACCOUNT VERIFICATION — Telegram / WhatsApp bot OTP
//  Google/Phone দিয়ে login করার পর এই handler গুলো দিয়ে Telegram বা WhatsApp
//  bind করে account verified করা হয়। verified না হলে call করা যায় না।
// ════════════════════════════════════════════════════════════════════

// ── একটি verified bind DB-তে লিখে session আপডেট করে (WA ও TG দুটোই ব্যবহার করে) ──
function _mark_account_verified($conn, $uid, $channel, $phone, $tg_chat_id = null) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $up = $conn->prepare("UPDATE auth_users SET verified=1, verify_channel=?, verify_phone=?,
        telegram_chat_id=?, verified_at=NOW() WHERE firebase_uid=?");
    $up->bind_param("ssss", $channel, $phone, $tg_chat_id, $uid);
    $up->execute(); $up->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $_SESSION['auth_verified'] = true;
    $_SESSION['auth_verify_channel'] = $channel;
    $_SESSION['auth_verify_phone'] = $phone;
}

// ── এই নম্বরটি কি অন্য কোনো verified account-এ ইতিমধ্যে ব্যবহৃত? ──
//  একটি নম্বর দিয়ে একটাই account verify করা যাবে (duplicate বন্ধ)।
function _phone_taken_by_other($conn, $phone, $uid) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT firebase_uid FROM auth_users
        WHERE verify_phone=? AND verified=1 AND firebase_uid<>? LIMIT 1");
    $q->bind_param("ss", $phone, $uid); $q->execute();
    $taken = (bool)$q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $taken;
}

// ── এই account-এর কি ইতিমধ্যে donor profile আছে? (register tab gate-এর জন্য) ──
//  auth_uid দিয়ে মেলে; না পেলে legacy fallback: একই verify/phone নম্বর, auth_uid খালি।
function _has_donor_for_uid($conn, $uid, $phone = null) {
    if (empty($uid) || !isset($conn)) return false;
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $q->bind_param("s", $uid); $q->execute();
    $has = (bool)$q->get_result()->fetch_assoc(); $q->close();
    if (!$has && !empty($phone)) {
        $q2 = $conn->prepare("SELECT id FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid='') LIMIT 1");
        $q2->bind_param("s", $phone); $q2->execute();
        $has = (bool)$q2->get_result()->fetch_assoc(); $q2->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $has;
}

// === WHATSAPP — Step 1: কোড পাঠাও (whatsapp-web.js bot-এ) ===
if(isset($_POST['wa_send_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('wa_otp_' . ($_SESSION['auth_uid'] ?? session_id()), 5, 600);
    requireAuth();
    if(WA_BOT_URL === '' || WA_BOT_SECRET === ''){
        echo json_encode(["status"=>"error","msg"=>"WhatsApp যাচাই এখনো চালু হয়নি।"]); exit();
    }
    $phone = trim($_POST['phone'] ?? '');
    if(preg_match('/^01\d{9}$/', $phone)) $phone = '+88' . $phone;
    if(!preg_match('/^\+8801\d{9}$/', $phone)){
        echo json_encode(["status"=>"error","msg"=>"সঠিক বাংলাদেশি WhatsApp নম্বর দিন (+8801XXXXXXXXX)।"]); exit();
    }
    $uid  = $_SESSION['auth_uid'];
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    mysqli_report(MYSQLI_REPORT_OFF);
    // আগের pending whatsapp চেষ্টা মুছে ফেলো — একসাথে একটাই সক্রিয় কোড
    $del = $conn->prepare("DELETE FROM otp_verifications WHERE auth_uid=? AND channel='whatsapp' AND status!='verified'");
    $del->bind_param("s", $uid); $del->execute(); $del->close();
    $ins = $conn->prepare("INSERT INTO otp_verifications (auth_uid, channel, phone, code_hash, status, expires_at)
        VALUES (?, 'whatsapp', ?, ?, 'code_sent', DATE_ADD(NOW(), INTERVAL ? SECOND))");
    $ttl = (int)VERIFY_OTP_TTL;
    $ins->bind_param("sssi", $uid, $phone, $hash, $ttl);
    $ins->execute(); $ins->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // bot-কে message পাঠাতে বলো
    $message = "🩸 Blood Arena যাচাই কোড: {$code}\n\nএই কোডটি সাইটে বসান। ৫ মিনিটের জন্য বৈধ। কাউকে শেয়ার করবেন না।";
    $r = _bot_send(WA_BOT_URL, ["secret"=>WA_BOT_SECRET, "phone"=>$phone, "message"=>$message],
                   defined('WA_BOT_INSECURE_TLS') && WA_BOT_INSECURE_TLS);
    if($r['http'] !== 200){
        // bot-এর আসল error কোড দেখিয়ে দাও যাতে কারণ বোঝা যায় (শুধু "failed" নয়)
        $err = '';
        $j = json_decode($r['body'] ?? '', true);
        if(is_array($j) && !empty($j['error'])) $err = $j['error'];
        $map = [
            'not_ready'       => "WhatsApp bot এখনো প্রস্তুত নয় (সম্ভবত QR scan/পুনঃসংযোগ দরকার)। একটু পরে আবার চেষ্টা করুন।",
            'not_on_whatsapp' => "এই নম্বরটি WhatsApp-এ পাওয়া যায়নি। সঠিক WhatsApp নম্বর দিন।",
            'bad_phone'       => "নম্বরটি সঠিক ফরম্যাটে নেই (+8801XXXXXXXXX)।",
            'bad_message'     => "বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।",
            'forbidden'       => "যাচাই সার্ভিসে অনুমোদন ব্যর্থ (secret mismatch)। অ্যাডমিনকে জানান।",
            'send_failed'     => "কোড পাঠানো যায়নি। একটু পরে আবার চেষ্টা করুন।",
        ];
        $msg = $map[$err] ?? ($r['http'] === 0
            ? "যাচাই সার্ভারে সংযোগ করা যায়নি। একটু পরে আবার চেষ্টা করুন।"
            : "কোড পাঠানো যায়নি। নম্বরটি WhatsApp-এ আছে কিনা দেখে আবার চেষ্টা করুন।");
        echo json_encode(["status"=>"error","msg"=>$msg]); exit();
    }
    echo json_encode(["status"=>"success","msg"=>"📲 WhatsApp-এ কোড পাঠানো হয়েছে {$phone} নম্বরে।"]);
    exit();
}

// === WHATSAPP — Step 2: কোড যাচাই করে account verified করো ===
if(isset($_POST['wa_verify_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('wa_verify_otp', 10, 600);
    requireAuth();
    $code = trim($_POST['code'] ?? '');
    if(!preg_match('/^\d{6}$/', $code)){
        echo json_encode(["status"=>"error","msg"=>"৬-সংখ্যার কোড দিন।"]); exit();
    }
    $uid = $_SESSION['auth_uid'];
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id, phone, code_hash, attempts FROM otp_verifications
        WHERE auth_uid=? AND channel='whatsapp' AND status='code_sent' AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1");
    $q->bind_param("s", $uid); $q->execute();
    $row = $q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if(!$row){
        echo json_encode(["status"=>"error","msg"=>"কোডের মেয়াদ শেষ বা পাওয়া যায়নি। আবার পাঠান।"]); exit();
    }
    if((int)$row['attempts'] >= 5){
        echo json_encode(["status"=>"error","msg"=>"অনেকবার ভুল হয়েছে। নতুন কোড পাঠান।"]); exit();
    }
    if(password_verify($code, $row['code_hash'])){
        // এই নম্বর অন্য account-এ ব্যবহৃত হলে block করো
        if(_phone_taken_by_other($conn, $row['phone'], $uid)){
            echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে আগে অন্য একটি অ্যাকাউন্ট verify করা হয়েছে। একটি নম্বর দিয়ে শুধু একটি অ্যাকাউন্ট verify করা যায়।"]); exit();
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $u = $conn->prepare("UPDATE otp_verifications SET status='verified' WHERE id=?");
        $u->bind_param("i", $row['id']); $u->execute(); $u->close();
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        _mark_account_verified($conn, $uid, 'whatsapp', $row['phone']);
        echo json_encode(["status"=>"success","msg"=>"✅ WhatsApp যাচাই সম্পন্ন!","channel"=>"whatsapp","phone"=>$row['phone']]);
        exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $u = $conn->prepare("UPDATE otp_verifications SET attempts=attempts+1 WHERE id=?");
    $u->bind_param("i", $row['id']); $u->execute(); $u->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"error","msg"=>"ভুল কোড। আবার দেখুন।"]);
    exit();
}

// === TELEGRAM — Step 1: কোড পাঠাও (Node bot-এর /send-এ) ===
//  user আগে Telegram bot-এ গিয়ে নিজের নম্বর লিংক করে রাখে (bot phone→chatId map রাখে)।
//  তারপর এখানে নম্বর দিলে PHP কোড generate করে bot-কে পাঠাতে বলে।
if(isset($_POST['tg_send_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('tg_otp_' . ($_SESSION['auth_uid'] ?? session_id()), 5, 600);
    requireAuth();
    if(TELEGRAM_BOT_URL === '' || TELEGRAM_BOT_SECRET === ''){
        echo json_encode(["status"=>"error","msg"=>"Telegram যাচাই এখনো চালু হয়নি।"]); exit();
    }
    $phone = trim($_POST['phone'] ?? '');
    if(preg_match('/^01\d{9}$/', $phone)) $phone = '+88' . $phone;
    if(!preg_match('/^\+8801\d{9}$/', $phone)){
        echo json_encode(["status"=>"error","msg"=>"সঠিক বাংলাদেশি নম্বর দিন (+8801XXXXXXXXX)।"]); exit();
    }
    $uid  = $_SESSION['auth_uid'];
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    mysqli_report(MYSQLI_REPORT_OFF);
    $del = $conn->prepare("DELETE FROM otp_verifications WHERE auth_uid=? AND channel='telegram' AND status!='verified'");
    $del->bind_param("s", $uid); $del->execute(); $del->close();
    $ins = $conn->prepare("INSERT INTO otp_verifications (auth_uid, channel, phone, code_hash, status, expires_at)
        VALUES (?, 'telegram', ?, ?, 'code_sent', DATE_ADD(NOW(), INTERVAL ? SECOND))");
    $ttl = (int)VERIFY_OTP_TTL;
    $ins->bind_param("sssi", $uid, $phone, $hash, $ttl);
    $ins->execute(); $ins->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // bot-এ OTP জমা রাখো (prepare), user deep link দিয়ে bot খুললে bot পাঠাবে
    $r = _bot_send(TELEGRAM_BOT_URL, ["secret"=>TELEGRAM_BOT_SECRET, "phone"=>$phone, "otp"=>$code],
                   defined('TELEGRAM_BOT_INSECURE_TLS') && TELEGRAM_BOT_INSECURE_TLS, '/prepare');
    if($r['http'] !== 200){
        echo json_encode(["status"=>"error","msg"=>"Bot সংযোগ সমস্যা। একটু পর আবার চেষ্টা করুন।"]); exit();
    }
    // phone থেকে + বাদ দিয়ে Telegram start param বানাও (+8801... → 8801...)
    $start_param = ltrim($phone, '+');
    $link = TELEGRAM_BOT_USERNAME !== '' ? ('https://t.me/' . TELEGRAM_BOT_USERNAME . '?start=' . $start_param) : null;
    echo json_encode(["status"=>"open_bot","link"=>$link,"msg"=>"Telegram খুলুন — OTP আসবে।"]);
    exit();
}

// === TELEGRAM — Step 2: কোড যাচাই করে account verified করো ===
if(isset($_POST['tg_verify_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('tg_verify_otp', 10, 600);
    requireAuth();
    $code = trim($_POST['code'] ?? '');
    if(!preg_match('/^\d{6}$/', $code)){
        echo json_encode(["status"=>"error","msg"=>"৬-সংখ্যার কোড দিন।"]); exit();
    }
    $uid = $_SESSION['auth_uid'];
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id, phone, code_hash, attempts FROM otp_verifications
        WHERE auth_uid=? AND channel='telegram' AND status='code_sent' AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1");
    $q->bind_param("s", $uid); $q->execute();
    $row = $q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if(!$row){
        echo json_encode(["status"=>"error","msg"=>"কোডের মেয়াদ শেষ বা পাওয়া যায়নি। আবার পাঠান।"]); exit();
    }
    if((int)$row['attempts'] >= 5){
        echo json_encode(["status"=>"error","msg"=>"অনেকবার ভুল হয়েছে। নতুন কোড পাঠান।"]); exit();
    }
    if(password_verify($code, $row['code_hash'])){
        // এই নম্বর অন্য account-এ ব্যবহৃত হলে block করো
        if(_phone_taken_by_other($conn, $row['phone'], $uid)){
            echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে আগে অন্য একটি অ্যাকাউন্ট verify করা হয়েছে। একটি নম্বর দিয়ে শুধু একটি অ্যাকাউন্ট verify করা যায়।"]); exit();
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $u = $conn->prepare("UPDATE otp_verifications SET status='verified' WHERE id=?");
        $u->bind_param("i", $row['id']); $u->execute(); $u->close();
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        _mark_account_verified($conn, $uid, 'telegram', $row['phone']);
        echo json_encode(["status"=>"success","msg"=>"✅ Telegram যাচাই সম্পন্ন!","channel"=>"telegram","phone"=>$row['phone']]);
        exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $u = $conn->prepare("UPDATE otp_verifications SET attempts=attempts+1 WHERE id=?");
    $u->bind_param("i", $row['id']); $u->execute(); $u->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"error","msg"=>"ভুল কোড। আবার দেখুন।"]);
    exit();
}

// ════════════════════════════════════════════════════════════════════
//  CHANGE NUMBER — registered donor নম্বর বদলানো (Update My Info থেকে)
//  নতুন নম্বর Telegram/WhatsApp দিয়ে আবার verify করতে হয়। verify সফল হলেই
//  donors.phone + auth_users.verify_phone দুটোই আপডেট হয় (atomic)।
//  সাধারণ verify OTP-এর সাথে যাতে collision না হয় — channel marker
//  'tg_change' / 'wa_change' ব্যবহার করা হয়।
// ════════════════════════════════════════════════════════════════════

// ── এই account-এর নিজের donor row id খোঁজে (auth_uid দিয়ে) ──
function _cn_owned_donor_id($conn, $uid) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id FROM donors WHERE auth_uid=? LIMIT 1");
    $q->bind_param("s", $uid); $q->execute();
    $r = $q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $r ? (int)$r['id'] : 0;
}

// ── নতুন নম্বরটি অন্য কোনো donor-এ register করা আছে কিনা (নিজের row বাদে) ──
function _cn_phone_on_other_donor($conn, $phone, $uid) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id FROM donors WHERE phone=? AND (auth_uid IS NULL OR auth_uid<>?) LIMIT 1");
    $q->bind_param("ss", $phone, $uid); $q->execute();
    $taken = (bool)$q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $taken;
}

// === CHANGE NUMBER — Step 1: নতুন নম্বরে কোড পাঠাও (Telegram বা WhatsApp) ===
if(isset($_POST['cn_send_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('cn_otp_' . ($_SESSION['auth_uid'] ?? session_id()), 5, 600);
    requireAuth();
    $uid     = $_SESSION['auth_uid'];
    $channel = trim($_POST['channel'] ?? '');
    if(!in_array($channel, ['tg','wa'], true)){
        echo json_encode(["status"=>"error","msg"=>"চ্যানেল নির্বাচন করুন (Telegram বা WhatsApp)।"]); exit();
    }
    // এই account-এর donor profile থাকতে হবে — তবেই নম্বর বদলানো যাবে
    if(!_cn_owned_donor_id($conn, $uid)){
        echo json_encode(["status"=>"error","msg"=>"আপনার donor profile পাওয়া যায়নি। প্রথমে তথ্য লোড করুন।"]); exit();
    }
    $phone = trim($_POST['phone'] ?? '');
    if(preg_match('/^01\d{9}$/', $phone)) $phone = '+88' . $phone;
    if(!preg_match('/^\+8801\d{9}$/', $phone)){
        echo json_encode(["status"=>"error","msg"=>"সঠিক বাংলাদেশি নম্বর দিন (+8801XXXXXXXXX)।"]); exit();
    }
    // বর্তমান verify করা নম্বরের সাথে একই হলে বদলানোর দরকার নেই
    $cur = trim($_SESSION['auth_verify_phone'] ?? '');
    if($cur !== '' && $phone === $cur){
        echo json_encode(["status"=>"error","msg"=>"এটি আপনার বর্তমান নম্বরই। নতুন নম্বর দিন।"]); exit();
    }
    // নম্বরটি অন্য account/donor-এ ব্যবহৃত হলে block করো
    if(_phone_taken_by_other($conn, $phone, $uid)){
        echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে আগে অন্য একটি অ্যাকাউন্ট verify করা হয়েছে।"]); exit();
    }
    if(_cn_phone_on_other_donor($conn, $phone, $uid)){
        echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে ইতিমধ্যে একজন রক্তদাতা register করা আছে।"]); exit();
    }

    $marker = ($channel === 'tg') ? 'tg_change' : 'wa_change';
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    mysqli_report(MYSQLI_REPORT_OFF);
    $del = $conn->prepare("DELETE FROM otp_verifications WHERE auth_uid=? AND channel=? AND status!='verified'");
    $del->bind_param("ss", $uid, $marker); $del->execute(); $del->close();
    $ins = $conn->prepare("INSERT INTO otp_verifications (auth_uid, channel, phone, code_hash, status, expires_at)
        VALUES (?, ?, ?, ?, 'code_sent', DATE_ADD(NOW(), INTERVAL ? SECOND))");
    $ttl = (int)VERIFY_OTP_TTL;
    $ins->bind_param("ssssi", $uid, $marker, $phone, $hash, $ttl);
    $ins->execute(); $ins->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if($channel === 'tg'){
        if(TELEGRAM_BOT_URL === '' || TELEGRAM_BOT_SECRET === ''){
            echo json_encode(["status"=>"error","msg"=>"Telegram যাচাই এখনো চালু হয়নি।"]); exit();
        }
        $r = _bot_send(TELEGRAM_BOT_URL, ["secret"=>TELEGRAM_BOT_SECRET, "phone"=>$phone, "otp"=>$code],
                       defined('TELEGRAM_BOT_INSECURE_TLS') && TELEGRAM_BOT_INSECURE_TLS, '/prepare');
        if($r['http'] !== 200){
            echo json_encode(["status"=>"error","msg"=>"Bot সংযোগ সমস্যা। একটু পর আবার চেষ্টা করুন।"]); exit();
        }
        $start_param = ltrim($phone, '+');
        $link = TELEGRAM_BOT_USERNAME !== '' ? ('https://t.me/' . TELEGRAM_BOT_USERNAME . '?start=' . $start_param) : null;
        echo json_encode(["status"=>"open_bot","link"=>$link,"msg"=>"Telegram খুলুন — OTP আসবে।"]);
        exit();
    } else {
        if(WA_BOT_URL === '' || WA_BOT_SECRET === ''){
            echo json_encode(["status"=>"error","msg"=>"WhatsApp যাচাই এখনো চালু হয়নি।"]); exit();
        }
        $message = "🩸 Blood Arena নম্বর পরিবর্তন কোড: {$code}\n\nএই কোডটি সাইটে বসান। ৫ মিনিটের জন্য বৈধ। কাউকে শেয়ার করবেন না।";
        $r = _bot_send(WA_BOT_URL, ["secret"=>WA_BOT_SECRET, "phone"=>$phone, "message"=>$message],
                       defined('WA_BOT_INSECURE_TLS') && WA_BOT_INSECURE_TLS);
        if($r['http'] !== 200){
            $err = '';
            $j = json_decode($r['body'] ?? '', true);
            if(is_array($j) && !empty($j['error'])) $err = $j['error'];
            $map = [
                'not_ready'       => "WhatsApp bot এখনো প্রস্তুত নয়। একটু পরে আবার চেষ্টা করুন।",
                'not_on_whatsapp' => "এই নম্বরটি WhatsApp-এ পাওয়া যায়নি। সঠিক WhatsApp নম্বর দিন।",
                'bad_phone'       => "নম্বরটি সঠিক ফরম্যাটে নেই (+8801XXXXXXXXX)।",
                'bad_message'     => "বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।",
                'forbidden'       => "যাচাই সার্ভিসে অনুমোদন ব্যর্থ (secret mismatch)। অ্যাডমিনকে জানান।",
                'send_failed'     => "কোড পাঠানো যায়নি। একটু পরে আবার চেষ্টা করুন।",
            ];
            $msg = $map[$err] ?? ($r['http'] === 0
                ? "যাচাই সার্ভারে সংযোগ করা যায়নি। একটু পরে আবার চেষ্টা করুন।"
                : "কোড পাঠানো যায়নি। নম্বরটি WhatsApp-এ আছে কিনা দেখে আবার চেষ্টা করুন।");
            echo json_encode(["status"=>"error","msg"=>$msg]); exit();
        }
        echo json_encode(["status"=>"success","msg"=>"📲 WhatsApp-এ কোড পাঠানো হয়েছে {$phone} নম্বরে।"]);
        exit();
    }
}

// === CHANGE NUMBER — Step 2: কোড যাচাই করে donor নম্বর আপডেট করো ===
if(isset($_POST['cn_verify_otp'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('cn_verify_otp', 10, 600);
    requireAuth();
    $uid     = $_SESSION['auth_uid'];
    $channel = trim($_POST['channel'] ?? '');
    if(!in_array($channel, ['tg','wa'], true)){
        echo json_encode(["status"=>"error","msg"=>"চ্যানেল নির্বাচন করুন।"]); exit();
    }
    $marker = ($channel === 'tg') ? 'tg_change' : 'wa_change';
    $code = trim($_POST['code'] ?? '');
    if(!preg_match('/^\d{6}$/', $code)){
        echo json_encode(["status"=>"error","msg"=>"৬-সংখ্যার কোড দিন।"]); exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $q = $conn->prepare("SELECT id, phone, code_hash, attempts FROM otp_verifications
        WHERE auth_uid=? AND channel=? AND status='code_sent' AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1");
    $q->bind_param("ss", $uid, $marker); $q->execute();
    $row = $q->get_result()->fetch_assoc(); $q->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if(!$row){
        echo json_encode(["status"=>"error","msg"=>"কোডের মেয়াদ শেষ বা পাওয়া যায়নি। আবার পাঠান।"]); exit();
    }
    if((int)$row['attempts'] >= 5){
        echo json_encode(["status"=>"error","msg"=>"অনেকবার ভুল হয়েছে। নতুন কোড পাঠান।"]); exit();
    }
    if(password_verify($code, $row['code_hash'])){
        $newphone = $row['phone'];
        // race-condition guard — verify-এর মুহূর্তে আবার uniqueness দেখো
        if(_phone_taken_by_other($conn, $newphone, $uid)){
            echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে আগে অন্য একটি অ্যাকাউন্ট verify করা হয়েছে।"]); exit();
        }
        if(_cn_phone_on_other_donor($conn, $newphone, $uid)){
            echo json_encode(["status"=>"error","msg"=>"এই নম্বরটি দিয়ে ইতিমধ্যে একজন রক্তদাতা register করা আছে।"]); exit();
        }
        $donor_id = _cn_owned_donor_id($conn, $uid);
        if(!$donor_id){
            echo json_encode(["status"=>"error","msg"=>"আপনার donor profile পাওয়া যায়নি।"]); exit();
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $u = $conn->prepare("UPDATE otp_verifications SET status='verified' WHERE id=?");
        $u->bind_param("i", $row['id']); $u->execute(); $u->close();
        // donors.phone আপডেট করো (নিজের row)
        $dp = $conn->prepare("UPDATE donors SET phone=? WHERE id=?");
        $dp->bind_param("si", $newphone, $donor_id); $dp->execute(); $dp->close();
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // auth_users.verify_phone + verify_channel + session আপডেট করো
        $verify_channel = ($channel === 'tg') ? 'telegram' : 'whatsapp';
        _mark_account_verified($conn, $uid, $verify_channel, $newphone);
        echo json_encode(["status"=>"success","msg"=>"✅ আপনার নম্বর সফলভাবে পরিবর্তন হয়েছে!","phone"=>$newphone]);
        exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $u = $conn->prepare("UPDATE otp_verifications SET attempts=attempts+1 WHERE id=?");
    $u->bind_param("i", $row['id']); $u->execute(); $u->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"error","msg"=>"ভুল কোড। আবার দেখুন।"]);
    exit();
}

// === ACCOUNT DASHBOARD — profile + linked donor record ===
if(isset($_POST['account_info'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('account_info', 30, 60);

    if(empty($_SESSION['auth_uid'])){
        echo json_encode(["status"=>"error","msg"=>"logged out"]); exit();
    }
    $uid = $_SESSION['auth_uid'];

    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("SELECT provider, email, phone, name, created_at, last_login,
        verified, verify_channel, verify_phone FROM auth_users WHERE firebase_uid=? LIMIT 1");
    $stmt->bind_param("s", $uid); $stmt->execute();
    $au = $stmt->get_result()->fetch_assoc(); $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if(!$au){
        echo json_encode(["status"=>"error","msg"=>"Account not found।"]); exit();
    }

    // session verified flag DB-র সাথে sync রাখো
    $_SESSION['auth_verified'] = !empty($au['verified']) ? true : false;
    $_SESSION['auth_verify_channel'] = $au['verify_channel'] ?? null;

    $auth = [
        "provider"       => $au['provider'] ?? '',
        "email"          => $au['email'] ?? null,
        "phone"          => $au['phone'] ?? null,
        "name"           => $au['name'] ?? null,
        "photo"          => $_SESSION['auth_photo'] ?? null,
        "member_since"   => (!empty($au['created_at']) ? date('d M Y', strtotime($au['created_at'])) : null),
        "verified"       => _auth_is_verified(),
        "verify_channel" => $au['verify_channel'] ?? null,
        "verify_phone"   => $au['verify_phone'] ?? null,
    ];

    // Linked donor record — match by account (auth_uid), legacy fallback by phone
    $donor = null;
    mysqli_report(MYSQLI_REPORT_OFF);
    $dq = $conn->prepare("SELECT id, name, blood_group, location, total_donations,
        willing_to_donate, last_donation, created_at FROM donors WHERE auth_uid=? LIMIT 1");
    $dq->bind_param("s", $uid); $dq->execute();
    $dr = $dq->get_result()->fetch_assoc(); $dq->close();
    if(!$dr && !empty($au['phone'])){
        $dq2 = $conn->prepare("SELECT id, name, blood_group, location, total_donations,
            willing_to_donate, last_donation, created_at FROM donors WHERE phone=? LIMIT 1");
        $dq2->bind_param("s", $au['phone']); $dq2->execute();
        $dr = $dq2->get_result()->fetch_assoc(); $dq2->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    {
        if($dr){
            $last = ($dr['last_donation']=='no' || empty($dr['last_donation']) || $dr['last_donation']=='0000-00-00')
                  ? 'no' : date('d/m/Y', strtotime($dr['last_donation']));
            $badge = getBadgeInfo((int)$dr['total_donations']);
            $donor = [
                "id"              => (int)$dr['id'],
                "name"            => $dr['name'],
                "blood_group"     => $dr['blood_group'],
                "location"        => $dr['location'],
                "total_donations" => (int)$dr['total_donations'],
                "willing"         => $dr['willing_to_donate'],
                "last_donation"   => $last,
                "badge_level"     => $badge['level'],
                "badge_icon"      => $badge['icon'],
                "badge_color"     => $badge['color'],
                "badge_bg"        => $badge['bg'],
                "badge_border"    => $badge['border'],
                "member_since"    => (!empty($dr['created_at']) ? date('d M Y', strtotime($dr['created_at'])) : null),
            ];
        }
    }

    echo json_encode([
        "status" => "success",
        "auth"   => $auth,
        "donor"  => $donor
    ], JSON_UNESCAPED_UNICODE);
    exit();
}


// === GET MY DONATIONS (signed-in account → donation history with dates) ===
if(isset($_POST['get_my_donations'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('get_my_donations', 30, 60);
    if(empty($_SESSION['auth_uid'])){
        echo json_encode(["status"=>"error","msg"=>"logged out"]); exit();
    }
    $uid   = $_SESSION['auth_uid'];
    $phone = $_SESSION['auth_phone'] ?? null;

    mysqli_report(MYSQLI_REPORT_OFF);
    // Resolve this account's donor row (auth_uid; legacy phone fallback)
    $donor_id = 0; $total_donations = 0; $last_donation = 'no';
    $dq = $conn->prepare("SELECT id, total_donations, last_donation FROM donors WHERE auth_uid=? LIMIT 1");
    $dq->bind_param("s", $uid); $dq->execute();
    $dr = $dq->get_result()->fetch_assoc(); $dq->close();
    if(!$dr && $phone){
        $dq2 = $conn->prepare("SELECT id, total_donations, last_donation FROM donors WHERE phone=? LIMIT 1");
        $dq2->bind_param("s", $phone); $dq2->execute();
        $dr = $dq2->get_result()->fetch_assoc(); $dq2->close();
    }
    if($dr){
        $donor_id        = (int)$dr['id'];
        $total_donations = (int)$dr['total_donations'];
        $last_donation   = ($dr['last_donation']=='no' || empty($dr['last_donation']) || $dr['last_donation']=='0000-00-00')
                         ? 'no' : date('d/m/Y', strtotime($dr['last_donation']));
    }

    // Recorded donation history rows (by account, or by resolved donor_id for legacy)
    $history = [];
    $hq = $conn->prepare("SELECT UNIX_TIMESTAMP(donation_date) as ts FROM donation_history
        WHERE auth_uid=? OR (donor_id=? AND ?>0) ORDER BY donation_date DESC, id DESC LIMIT 50");
    if($hq){
        $hq->bind_param("sii", $uid, $donor_id, $donor_id);
        $hq->execute();
        $hres = $hq->get_result();
        while($r = $hres->fetch_assoc()) $history[] = ["ts"=>(int)$r['ts']];
        $hq->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    echo json_encode([
        "status"          => "success",
        "history"         => $history,
        "total_donations" => $total_donations,
        "last_donation"   => $last_donation,
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// === GET SERVICE NOTIFICATIONS FOR DEVICE ===
if(isset($_POST['get_service_notifs'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('svc_notifs', 30, 60);
    $device_id = trim($_POST['device_id'] ?? '');
    validateLength($device_id, 100, 'Device ID');
    if(empty($device_id)){ echo json_encode([]); exit(); }
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `service_notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `device_id` VARCHAR(100) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stmt = $conn->prepare("SELECT id, type, message, is_read, UNIX_TIMESTAMP(created_at) as ts FROM service_notifications WHERE device_id=? AND is_read=0 ORDER BY ts DESC LIMIT 30");
    $stmt->bind_param("s", $device_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $notifs = [];
    while($r = $res->fetch_assoc()) $notifs[] = $r;
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode($notifs);
    exit();
}

// === MARK SERVICE NOTIFICATION READ ===
if(isset($_POST['mark_service_notif_read'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    $notif_id  = (int)($_POST['notif_id'] ?? 0);
    $device_id = trim($_POST['device_id'] ?? '');
    if($notif_id <= 0 || empty($device_id)){
        echo json_encode(["status"=>"error"]);
        exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("UPDATE service_notifications SET is_read=1 WHERE id=? AND device_id=?");
    $stmt->bind_param("is", $notif_id, $device_id);
    $stmt->execute();
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"success"]);
    exit();
}

// === DELETE SERVICE NOTIFICATION (permanently — bell dismiss) ===
// Bell থেকে মুছলে DB থেকেও delete হবে — পরের poll এ আর আসবে না
if(isset($_POST['delete_service_notif'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('del_svc_notif', 60, 60);
    $notif_id  = (int)($_POST['notif_id'] ?? 0);
    $device_id = trim($_POST['device_id'] ?? '');
    $del_all   = !empty($_POST['del_all']); // true = delete all for this device
    if(empty($device_id)){ echo json_encode(["status"=>"error"]); exit(); }
    mysqli_report(MYSQLI_REPORT_OFF);
    if($del_all){
        $stmt = $conn->prepare("DELETE FROM service_notifications WHERE device_id=?");
        $stmt->bind_param("s", $device_id);
    } else {
        if($notif_id <= 0){ echo json_encode(["status"=>"error"]); exit(); }
        $stmt = $conn->prepare("DELETE FROM service_notifications WHERE id=? AND device_id=?");
        $stmt->bind_param("is", $notif_id, $device_id);
    }
    $stmt->execute();
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"success"]);
    exit();
}

// === ADMIN: SEND CUSTOM SERVICE NOTIFICATION ===
if(isset($_POST['admin_send_service_notif'])){
    if(empty($_SESSION['admin_logged_in'])){
        header('Content-Type: application/json; charset=utf-8');
        while(ob_get_level()) ob_end_clean(); ob_start();
        echo json_encode(["status"=>"error","msg"=>"Unauthorized"]);
        exit();
    }
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    $device_id = trim($_POST['device_id'] ?? '');
    $type      = trim($_POST['notif_type'] ?? 'info');
    $message   = trim($_POST['message'] ?? '');
    $valid_types = ['location_on','notif_on','info','warning'];
    if(!in_array($type, $valid_types, true)) $type = 'info';
    validateLength($message, 500, 'Message');
    if(empty($device_id) || empty($message)){
        echo json_encode(["status"=>"error","msg"=>"device_id ও message দিন।"]);
        exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("INSERT INTO service_notifications (device_id, type, message) VALUES (?,?,?)");
    $stmt->bind_param("sss", $device_id, $type, $message);
    if($stmt->execute()){
        echo json_encode(["status"=>"success","msg"=>"✅ Notification পাঠানো হয়েছে।"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"❌ Failed."]);
    }
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    exit();
}

// === SUBMIT MESSAGE TO ADMIN ===
if(isset($_POST['submit_admin_message'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('admin_msg', 3, 300); // max 3 per 5 min
    $sender_name  = trim($_POST['sender_name']  ?? '');
    $sender_phone = trim($_POST['sender_phone'] ?? '');
    $message      = trim($_POST['message']      ?? '');
    $device_id    = trim($_POST['device_id']    ?? '');
    if(empty($sender_name) || empty($sender_phone) || empty($message) || empty($device_id)){
        echo json_encode(["status"=>"error","msg"=>"সব তথ্য দিন।"]); exit();
    }
    if(!preg_match('/^\+8801\d{9}$/', $sender_phone)){
        echo json_encode(["status"=>"error","msg"=>"সঠিক ফোন নম্বর দিন (+8801XXXXXXXXX)।"]); exit();
    }
    validateLength($sender_name, 100, 'নাম');
    validateLength($message, 1000, 'Message');
    validateLength($device_id, 100, 'Device ID');
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sender_name` VARCHAR(100) NOT NULL,
        `sender_phone` VARCHAR(20) NOT NULL,
        `message` TEXT NOT NULL,
        `device_id` VARCHAR(100) NOT NULL,
        `is_read` TINYINT DEFAULT 0,
        `admin_reply` TEXT DEFAULT NULL,
        `replied_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stmt = $conn->prepare("INSERT INTO admin_messages (sender_name, sender_phone, message, device_id) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $sender_name, $sender_phone, $message, $device_id);
    if($stmt->execute()){
        echo json_encode(["status"=>"success","msg"=>"✅ ধন্যবাদ! আপনার বার্তা পাঠানো হয়েছে। Admin এর reply আপনার Services notification এ আসবে।"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>"❌ পাঠানো যায়নি। আবার চেষ্টা করুন।"]);
    }
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    exit();
}

// === GET ADMIN REPLIES FOR DEVICE ===
if(isset($_POST['get_admin_messages'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('get_admin_msgs', 20, 60);
    $device_id = trim($_POST['device_id'] ?? '');
    if(empty($device_id)){ echo json_encode([]); exit(); }
    validateLength($device_id, 100, 'Device ID');
    mysqli_report(MYSQLI_REPORT_OFF);
    // Only return messages FROM this device that have admin_reply
    $stmt = $conn->prepare("SELECT id, message, admin_reply, is_read, UNIX_TIMESTAMP(replied_at) as replied_ts
        FROM admin_messages WHERE device_id=? AND admin_reply IS NOT NULL ORDER BY replied_at DESC LIMIT 20");
    $stmt->bind_param("s", $device_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode($rows);
    exit();
}

// === SAVE DEVICE ID (silent — permission allow OR deny উভয়ে call হয়) ===
if(isset($_POST['save_device_id'])){
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    $device_id = trim($_POST['device_id'] ?? '');
    $context   = trim($_POST['context']   ?? 'unknown');
    $valid_ctx = ['notif_allow','notif_deny','loc_allow','loc_deny','notif_prompt','loc_prompt','first_visit'];
    if(!in_array($context, $valid_ctx, true)) $context = 'unknown';
    validateLength($device_id, 100, 'Device ID');
    if(!empty($device_id)){
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300, 'UTF-8');
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn->query("CREATE TABLE IF NOT EXISTS `device_tokens` (
            `device_id` VARCHAR(100) PRIMARY KEY,
            `context` VARCHAR(30) DEFAULT 'unknown',
            `ip` VARCHAR(50) DEFAULT NULL,
            `ua` VARCHAR(300) DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $conn->prepare("INSERT INTO device_tokens (device_id, context, ip, ua) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE context=VALUES(context), ip=VALUES(ip), updated_at=CURRENT_TIMESTAMP");
        if($stmt){
            $stmt->bind_param("ssss", $device_id, $context, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        }
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    }
    echo json_encode(["status"=>"ok"]);
    exit();
}

// === MARK ADMIN MSG REPLY AS READ ===
if(isset($_POST['mark_admin_msg_read'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    $msg_id    = (int)($_POST['msg_id']    ?? 0);
    $device_id = trim($_POST['device_id'] ?? '');
    if($msg_id <= 0 || empty($device_id)){
        echo json_encode(["status"=>"error"]); exit();
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("UPDATE admin_messages SET is_read=1 WHERE id=? AND device_id=?");
    $stmt->bind_param("is", $msg_id, $device_id);
    $stmt->execute();
    $stmt->close();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode(["status"=>"success"]);
    exit();
}

// === MY MESSAGES — full conversation thread for this device (account dashboard) ===
if(isset($_POST['get_my_messages'])){
    checkCSRF();
    header('Content-Type: application/json; charset=utf-8');
    while(ob_get_level()) ob_end_clean(); ob_start();
    checkRateLimit('my_msgs', 20, 60);
    $device_id = trim($_POST['device_id'] ?? '');
    validateLength($device_id, 100, 'Device ID');
    if(empty($device_id)){ echo json_encode([]); exit(); }
    mysqli_report(MYSQLI_REPORT_OFF);
    $stmt = $conn->prepare("SELECT id, message, admin_reply, is_read,
        UNIX_TIMESTAMP(created_at) as ts, UNIX_TIMESTAMP(replied_at) as replied_ts
        FROM admin_messages WHERE device_id=? ORDER BY created_at DESC LIMIT 30");
    $rows = [];
    if($stmt){
        $stmt->bind_param("s", $device_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit();
}
?>  
