
# Troubleshooting Guide

Common issues and their solutions when using DevMailer.

## Quick Diagnostic

Run this diagnostic script to check your setup:

```php
<?php
// diagnostic.php
echo "<h2>DevMailer Diagnostic</h2>";

// Check PHP version
echo "PHP Version: " . phpversion();
echo phpversion() >= '7.4' ? " ✅" : " ❌ (Need 7.4+)";
echo "<br>";

// Check sessions
echo "Sessions: ";
echo session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "⚠️ Not started";
echo "<br>";

// Check DevMailer
if (file_exists('src/DevMailer.php')) {
    require_once 'src/DevMailer.php';
    echo "DevMailer: ✅ Found<br>";
} else {
    echo "DevMailer: ❌ Not found<br>";
}

// Check permissions
$inboxDir = 'mailer/dev_inbox';
if (is_writable($inboxDir)) {
    echo "Inbox directory: ✅ Writable<br>";
} else {
    echo "Inbox directory: ❌ Not writable<br>";
}

// Check SITE_URL
if (defined('SITE_URL')) {
    echo "SITE_URL: " . SITE_URL . " ✅<br>";
} else {
    echo "SITE_URL: ❌ Not defined<br>";
}
?>