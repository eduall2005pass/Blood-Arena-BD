<?php
/**
 * cleanup_requests.php — config-driven auto-delete cron
 * ════════════════════════════════════════════════════════════════════
 *  AUTO_DELETE_DAYS (config.php থেকে) দিনের বেশি পুরোনো blood_requests এবং
 *  তাদের সংযুক্ত ছবি/ডকুমেন্ট স্বয়ংক্রিয়ভাবে মুছে ফেলে।
 *
 *  প্রতিটি request-এর জন্য:
 *    1) request_documents থেকে file_path (relative) নিয়ে UPLOAD_DIR-এর সাথে
 *       combine করে আসল server path বানিয়ে unlink() দিয়ে ফাইল মুছি।
 *    2) blood_requests row delete করি — FK ON DELETE CASCADE থাকায়
 *       request_documents row-গুলো এমনিতেই মুছে যায়।
 *
 *  নিরাপত্তা/robustness:
 *    - প্রতিটি operation try-catch এ মোড়া; একটি request fail করলে বাকিগুলো
 *      skip হয় না — শুধু সেটি log করে পরেরটায় যায়।
 *    - সব ঘটনা storage/cleanup.log এ লেখা হয়।
 *    - শুধু CLI (cron) থেকে চালানো যায় — HTTP দিয়ে চালানো ব্লক করা।
 *
 * ──────────────────────────────────────────────────────────────────
 *  DirectAdmin Cron Job (প্রতিদিন রাত ৩টায়):
 *
 *    DirectAdmin → Advanced Features → Cron Jobs এ গিয়ে:
 *      Minute: 0   Hour: 3   Day: *   Month: *   Day-of-Week: *
 *    Command (আপনার আসল path বসান):
 *      php -q /home/bloodare/domains/bloodarenabd.tech/public_html/cleanup_requests.php
 *
 *    (path বের করতে: DirectAdmin File Manager এ cleanup_requests.php এর full
 *     path দেখুন, অথবা SSH এ `pwd` চালান।)
 * ════════════════════════════════════════════════════════════════════
 */

// ── CLI-only guard: HTTP দিয়ে চালানো বন্ধ ─────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line (cron).\n");
}

require __DIR__ . '/config.php';   // AUTO_DELETE_DAYS, UPLOAD_DIR
require __DIR__ . '/db.php';       // $conn (mysqli)

// ── Logging helper ───────────────────────────────────────────────────
$LOG_FILE = __DIR__ . '/../storage/cleanup.log';
function cleanup_log($msg) {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    echo $line; // cron output / SSH এ-ও দেখা যায়
}

// ── Pre-flight ───────────────────────────────────────────────────────
$days = defined('AUTO_DELETE_DAYS') ? (int) AUTO_DELETE_DAYS : 3;
if ($days < 1) $days = 3; // sanity floor — কখনো 0/negative দিয়ে সব মুছে না ফেলি
$base = defined('UPLOAD_DIR') ? rtrim(UPLOAD_DIR, "/\\") : __DIR__ . '/../storage/req_docs';

if (!isset($conn) || !$conn) {
    cleanup_log('FATAL: DB connection unavailable — aborting.');
    exit(1);
}
mysqli_report(MYSQLI_REPORT_OFF); // manual error handling — একটিতে throw হলে যেন সব না থামে

cleanup_log("START cleanup — deleting blood_requests older than {$days} day(s).");

// ── 1. পুরোনো request id-গুলো নাও ────────────────────────────────────
$ids = [];
try {
    $stmt = $conn->prepare(
        "SELECT id FROM blood_requests WHERE created_at < (NOW() - INTERVAL ? DAY)"
    );
    if (!$stmt) throw new Exception('prepare failed: ' . $conn->error);
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $ids[] = (int) $row['id'];
    $stmt->close();
} catch (\Throwable $e) {
    cleanup_log('FATAL: could not select old requests — ' . $e->getMessage());
    exit(1);
}

if (!$ids) {
    cleanup_log('Nothing to delete. DONE.');
    exit(0);
}
cleanup_log(count($ids) . ' request(s) eligible for deletion: ' . implode(',', $ids));

// ── 2. প্রতিটি request আলাদা করে process করি (একটির fail বাকিদের থামায় না) ──
$ok_requests = 0; $failed_requests = 0; $files_deleted = 0; $files_failed = 0;

foreach ($ids as $rid) {
    try {
        // 2a. এই request-এর সব document file unlink করি
        try {
            $ds = $conn->prepare("SELECT file_path FROM request_documents WHERE request_id = ?");
            if ($ds) {
                $ds->bind_param('i', $rid);
                $ds->execute();
                $dres = $ds->get_result();
                while ($d = $dres->fetch_assoc()) {
                    $rel  = (string) $d['file_path'];
                    $full = $base . '/' . $rel;
                    $real = realpath($full);
                    $broot = realpath($base);
                    // path traversal guard — ফাইল অবশ্যই UPLOAD_DIR-এর ভিতরে হতে হবে
                    if ($real && $broot && strncmp($real, $broot, strlen($broot)) === 0 && is_file($real)) {
                        if (@unlink($real)) {
                            $files_deleted++;
                        } else {
                            $files_failed++;
                            cleanup_log("WARN: could not unlink file for request #{$rid}: {$rel}");
                        }
                    } else {
                        // ফাইল আগেই নেই বা path মেলেনি — skip (DB row CASCADE-এ মুছবে)
                        cleanup_log("INFO: file missing/skip for request #{$rid}: {$rel}");
                    }
                }
                $ds->close();
            }
        } catch (\Throwable $fe) {
            // ফাইল মুছতে সমস্যা হলেও row delete চালিয়ে যাই (orphan row রাখার চেয়ে ভালো)
            $files_failed++;
            cleanup_log("WARN: file-cleanup error for request #{$rid}: " . $fe->getMessage());
        }

        // 2b. request_documents row delete (FK/CASCADE নেই — তাই explicit)
        try {
            $dd = $conn->prepare("DELETE FROM request_documents WHERE request_id = ?");
            if ($dd) { $dd->bind_param('i', $rid); $dd->execute(); $dd->close(); }
        } catch (\Throwable $re) {
            cleanup_log("WARN: could not delete document rows for request #{$rid}: " . $re->getMessage());
        }

        // 2c. request row delete
        $del = $conn->prepare("DELETE FROM blood_requests WHERE id = ?");
        if (!$del) throw new Exception('delete prepare failed: ' . $conn->error);
        $del->bind_param('i', $rid);
        if (!$del->execute()) throw new Exception('delete execute failed: ' . $del->error);
        $del->close();
        $ok_requests++;
    } catch (\Throwable $e) {
        // এই request fail — log করে পরেরটায় যাই
        $failed_requests++;
        cleanup_log("ERROR: failed to delete request #{$rid} — " . $e->getMessage());
        continue;
    }
}

cleanup_log(
    "DONE. requests deleted: {$ok_requests}, failed: {$failed_requests}; " .
    "files deleted: {$files_deleted}, file-failures: {$files_failed}."
);

@$conn->close();
exit($failed_requests > 0 ? 2 : 0);
