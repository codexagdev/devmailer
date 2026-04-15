<?php
/**
 * Basic DevMailer Usage Example
 */

require_once '../src/DevMailer.php';

// Optional: Set your site URL
define('SITE_URL', 'http://localhost:8000');

// Send a welcome email
sendEmail(
    'dev@example.com',
    'Codex AG',
    'Welcome to Our App!',
    '<h1>Welcome Codex!</h1><p>Thanks for joining us. We\'re excited to have you on board.</p>'
);

// The email will open automatically when you load this page
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevMailer Example</title>
</head>
<body>
    <h1>Email Preview Should Open in a New Tab</h1>
    <p>If not, check your popup blocker.</p>
    <?php renderDevMailJS(); ?>
</body>
</html>