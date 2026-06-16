<?php
// ════════════════════════════════════════════════════════════════════
//  index.php — orchestrator
//  Loads config + backend logic, then renders the page from partials.
//  Real code lives in: config.php · includes/ · partials/ · assets/
// ════════════════════════════════════════════════════════════════════
require __DIR__ . '/config.php';
require __DIR__ . '/includes/backend.php';   // DB + all AJAX/manifest handlers (may exit() early)
?>
<!DOCTYPE html>
<html lang="en">
<?php require __DIR__ . '/partials/head.php'; ?>
<body>
<?php require __DIR__ . '/partials/body.php'; ?>
</body></html>
