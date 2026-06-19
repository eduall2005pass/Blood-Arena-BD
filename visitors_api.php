<?php
/* ============================================================================
 *  visitors_api.php — Real-time visitor counter for the Register page card.
 *
 *  Reuses the existing DB connection (db.php → $conn). DOES NOT open a new one.
 *
 *  Behaviour
 *  ─────────
 *   • POST (with session_id) → logs/refreshes this visitor, then returns counts.
 *   • GET / POST (no session_id) → read-only, just returns counts.
 *   • "live"  = distinct sessions seen in the last 5 minutes.
 *   • "total" = all-time visit total (persisted in analytics_counters so it
 *               survives the 30-minute row cleanup).
 *   • Sessions idle > 30 minutes are auto-deleted on every write.
 *
 *  Response:  { "live": <int>, "total": <int> }
 * ========================================================================== */

// Never let PHP notices/warnings corrupt the JSON body (InfinityFree injects HTML).
while (ob_get_level()) { ob_end_clean(); }
ob_start();

require __DIR__ . '/db.php';   // provides $conn (mysqli) — shared connection

// Throwing on SQL errors would break the JSON contract; handle gracefully instead.
mysqli_report(MYSQLI_REPORT_OFF);

/* ── helper: emit JSON and stop ─────────────────────────────────────────── */
function vis_respond($live, $total) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode(['live' => (int)$live, 'total' => (int)$total]);
    exit;
}

if (!isset($conn) || !$conn) { vis_respond(1, 0); }

/* ── one-time schema (guarded by a flag file, like the rest of the app) ──── */
$vis_flag = __DIR__ . '/.visitors_schema_done';
if (!file_exists($vis_flag)) {
    $conn->query("CREATE TABLE IF NOT EXISTS `visitors` (
        `session_id` VARCHAR(100) PRIMARY KEY,
        `last_seen`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `page`       VARCHAR(255) DEFAULT '',
        KEY `idx_last_seen` (`last_seen`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // analytics_counters already exists (schema v4); make sure the row is present.
    $conn->query("CREATE TABLE IF NOT EXISTS `analytics_counters` (
        `counter_name` VARCHAR(50) PRIMARY KEY,
        `counter_value` BIGINT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("INSERT IGNORE INTO analytics_counters (counter_name, counter_value) VALUES ('total_visitors_ever', 0)");
    @file_put_contents($vis_flag, date('Y-m-d H:i:s'));
}

/* ── log the current visitor (only when a session id is supplied) ───────── */
$session_id = $_POST['session_id'] ?? $_GET['session_id'] ?? '';
$session_id = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($session_id)), 0, 100);

if ($session_id !== '') {
    $sid  = mysqli_real_escape_string($conn, $session_id);
    $page = $_POST['page'] ?? $_GET['page'] ?? '';
    $page = substr(preg_replace('/[^a-zA-Z0-9_\-\/.?=&]/', '', trim($page)), 0, 255);
    $pg   = mysqli_real_escape_string($conn, $page);

    // New session? (also true for a returning visitor whose row was cleaned up)
    $isNew = false;
    $chk = @$conn->query("SELECT 1 FROM visitors WHERE session_id='$sid' LIMIT 1");
    if ($chk && $chk->num_rows === 0) { $isNew = true; }

    @$conn->query("INSERT INTO visitors (session_id, last_seen, page)
                   VALUES ('$sid', NOW(), '$pg')
                   ON DUPLICATE KEY UPDATE last_seen = NOW(), page = '$pg'");

    if ($isNew) {
        @$conn->query("INSERT INTO analytics_counters (counter_name, counter_value)
                       VALUES ('total_visitors_ever', 1)
                       ON DUPLICATE KEY UPDATE counter_value = counter_value + 1");
    }

    // Auto-cleanup: drop sessions idle for more than 30 minutes.
    @$conn->query("DELETE FROM visitors WHERE last_seen < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
}

/* ── live = active in the last 5 minutes ────────────────────────────────── */
$live = 0;
$r = @$conn->query("SELECT COUNT(*) c FROM visitors WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
if ($r && ($row = $r->fetch_assoc())) { $live = (int)$row['c']; }
if ($session_id !== '') { $live = max(1, $live); }   // we know at least *this* visitor is here

/* ── total = persisted all-time count ───────────────────────────────────── */
$total = 0;
$r = @$conn->query("SELECT counter_value v FROM analytics_counters WHERE counter_name='total_visitors_ever'");
if ($r && ($row = $r->fetch_assoc())) { $total = (int)$row['v']; }

vis_respond($live, $total);
