<?php
// mailer/dev_email_view.php
// Serves a saved email preview file by ID

$id   = preg_replace('/[^a-z0-9_]/', '', $_GET['id'] ?? '');
$file = __DIR__ . '/dev_inbox/' . $id . '.html';

if (!$id || !file_exists($file)) {
    http_response_code(404);
    die('<div style="font-family:system-ui;padding:40px;text-align:center;color:#f87171;background:#030b18;min-height:100vh"><h2>Email not found or expired.</h2></div>');
}

// Serve the file directly
readfile($file);