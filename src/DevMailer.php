<?php
// ============================================================
// src/DevMailer.php — Browser-Tab Email Previewer
// Codex AG — Auth System
// ============================================================
//
// WHAT THIS DOES:
//   Instead of sending a real email, this saves the email
//   content to a session and injects a tiny JS snippet that
//   opens a preview page in a new browser tab — showing the
//   exact email the user would receive, with fully clickable
//   links.
//
// WHY THIS IS USEFUL:
//   ✅ No SMTP server needed
//   ✅ No PHPMailer dependency
//   ✅ Works 100% offline / on localhost
//   ✅ Links are real and actually work (they hit your PHP files)
//   ✅ Shows exactly what the email looks like
//   ✅ Perfect for recording YouTube tutorials (very visual!)
//   ✅ Switch to real email later by just swapping the function calls
//
// HOW IT WORKS:
//   1. sendEmail() saves the email into $_SESSION['dev_emails']
//   2. It also writes the email to /mailer/dev_inbox/ as an HTML file
//   3. It returns a JS snippet that auto-opens the preview tab
//   4. The calling script stores the snippet in session
//   5. The next page renders the JS in a <script> tag → new tab opens
//   6. The dev_email_view.php page renders the beautiful email preview
//
// USAGE:
//   Just require this file instead of Mailer.php.
//   All function signatures are identical — drop-in replacement.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Directory where email HTML files are saved (for the inbox view)
define('DEV_MAIL_DIR', __DIR__ . '/../mailer/dev_inbox/');

// Create inbox dir if it doesn't exist
if (!is_dir(DEV_MAIL_DIR)) {
    mkdir(DEV_MAIL_DIR, 0755, true);
}

// ============================================================
// SVG ICON LIBRARY
// ============================================================

function svgIcon(string $name, int $size = 20): string {
    $icons = [
        'email' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>',
        'verify' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'reset' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>',
        'link' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        'delete' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
        'inbox' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'close' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'resend' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'welcome' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'chevron' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
        'calendar' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'user' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    ];
    
    return $icons[$name] ?? $icons['email'];
}

// ============================================================
// CORE: sendEmail() — save to session + queue tab-open JS
// ============================================================

/**
 * Dev version of sendEmail().
 * Saves the email to session and queues a JS snippet to
 * open it in a new browser tab on the next page load.
 *
 * Returns true always (simulates success).
 */
function sendEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody = ''
): bool {

    // ── 1. Build a unique ID for this email ──────────────────
    $emailId  = 'email_' . time() . '_' . bin2hex(random_bytes(4));
    $filename = DEV_MAIL_DIR . $emailId . '.html';

    // ── 2. Wrap in the preview shell ─────────────────────────
    $preview  = buildPreviewShell($toEmail, $toName, $subject, $htmlBody, $emailId);

    // ── 3. Save to disk (inbox) ──────────────────────────────
    file_put_contents($filename, $preview);

    // ── 4. Store minimal metadata in session inbox list ──────
    if (!isset($_SESSION['dev_inbox'])) {
        $_SESSION['dev_inbox'] = [];
    }
    array_unshift($_SESSION['dev_inbox'], [
        'id'       => $emailId,
        'to'       => $toEmail,
        'name'     => $toName,
        'subject'  => $subject,
        'sent_at'  => date('Y-m-d H:i:s'),
    ]);

    // ── 5. Queue the "open new tab" JS for next page render ──
    $viewUrl = (defined('SITE_URL') ? SITE_URL : '') . '/mailer/dev_email_view.php?id=' . urlencode($emailId);

    if (!isset($_SESSION['dev_mail_open'])) {
        $_SESSION['dev_mail_open'] = [];
    }
    $_SESSION['dev_mail_open'][] = $viewUrl;

    return true;
}

// ============================================================
// renderDevMailJS() — call this once per page, inside <body>
// ============================================================

/**
 * Outputs a <script> block that opens any queued emails in new tabs.
 *
 * Place <?php renderDevMailJS(); ?> just before </body> on every page.
 *
 * IMPORTANT: Browsers block window.open() unless it's triggered by
 * a user gesture. We use a brief setTimeout so it fires after load
 * while still being treated as page-load-triggered.
 */
function renderDevMailJS(): void
{
    if (empty($_SESSION['dev_mail_open'])) {
        return;
    }

    $urls = $_SESSION['dev_mail_open'];
    unset($_SESSION['dev_mail_open']);   // consume queue

    $jsUrls = json_encode($urls);

    echo <<<HTML
<!-- Codex AG DevMailer: auto-open email preview tabs -->
<script>
(function() {
  var urls = {$jsUrls};
  urls.forEach(function(url, i) {
    setTimeout(function() {
      var win = window.open(url, '_blank');
      if (!win) {
        // Popup was blocked — show a clickable notice instead
        var bar = document.createElement('div');
        bar.style.cssText = 'position:fixed;bottom:24px;right:24px;' +
          'background:linear-gradient(135deg,#0891b2,#22d3ee);color:#030b18;padding:12px 20px;border-radius:8px;' +
          'font:600 13px system-ui,sans-serif;z-index:99999;cursor:pointer;box-shadow:0 4px 20px rgba(8,145,178,.3);' +
          'display:flex;align-items:center;gap:8px;transition:all .2s;';
        bar.innerHTML = '<span style="display:flex;align-items:center;">' + 
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px">' +
          '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>' +
          'Dev Email Ready — Click to Open</span>';
        bar.onmouseover = function() { this.style.transform = 'translateY(-2px)'; };
        bar.onmouseout = function() { this.style.transform = 'translateY(0)'; };
        bar.onclick = function() { window.open(url, '_blank'); bar.remove(); };
        document.body.appendChild(bar);
      }
    }, i * 300); // stagger multiple tabs by 300ms each
  });
})();
</script>
HTML;
}

// ============================================================
// EMAIL TEMPLATES (identical signatures to real Mailer.php)
// ============================================================

function emailTemplate(string $title, string $bodyHtml): string
{
    // Same branded template as production — looks real
    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1)">
        <tr>
          <td style="background:linear-gradient(135deg,#0891b2,#22d3ee);padding:28px 36px;text-align:center">
            <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:3px">CODEX AG</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:13px">Build Real Projects</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px;color:#333;font-size:15px;line-height:1.7">
            ' . $bodyHtml . '
          </td>
        </tr>
        <tr>
          <td style="background:#f9f9f9;padding:20px 36px;text-align:center;color:#999;font-size:12px;border-top:1px solid #eee">
            <p style="margin:0">You received this because you signed up at Codex AG.</p>
            <p style="margin:4px 0 0">If you did not sign up, you can safely ignore this email.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}

/**
 * Project 3 — Email Verification
 */
function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    $link = (defined('SITE_URL') ? SITE_URL : '') . '/verify_email.php?token=' . urlencode($token);

    $body = '
    <p>Hi <strong>' . htmlspecialchars($toName) . '</strong>,</p>
    <p>Thanks for signing up at <strong>Codex AG</strong>! Click below to verify your email and activate your account.</p>
    <p style="text-align:center;margin:30px 0">
      <a href="' . $link . '"
         style="background:#22d3ee;color:#fff;text-decoration:none;padding:14px 32px;
                border-radius:6px;font-weight:700;font-size:15px;display:inline-block">
        ✅ Verify My Email
      </a>
    </p>
    <p style="color:#777;font-size:13px">Or copy this link:<br>
      <a href="' . $link . '" style="color:#0891b2;word-break:break-all">' . $link . '</a>
    </p>
    <p style="color:#999;font-size:12px">This link expires in <strong>' . (defined('VERIFY_TOKEN_HOURS') ? VERIFY_TOKEN_HOURS : 24) . ' hours</strong>.</p>';

    return sendEmail(
        $toEmail, $toName,
        'Verify Your Email — Codex AG',
        emailTemplate('Verify Your Email', $body)
    );
}

/**
 * Project 4 — Password Reset
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $token): bool
{
    $link = (defined('SITE_URL') ? SITE_URL : '') . '/reset_password.php?token=' . urlencode($token);

    $body = '
    <p>Hi <strong>' . htmlspecialchars($toName) . '</strong>,</p>
    <p>We received a request to reset your <strong>Codex AG</strong> password.</p>
    <p style="text-align:center;margin:30px 0">
      <a href="' . $link . '"
         style="background:#f59e0b;color:#fff;text-decoration:none;padding:14px 32px;
                border-radius:6px;font-weight:700;font-size:15px;display:inline-block">
        🔑 Reset My Password
      </a>
    </p>
    <p style="color:#777;font-size:13px">Or copy this link:<br>
      <a href="' . $link . '" style="color:#0891b2;word-break:break-all">' . $link . '</a>
    </p>
    <p style="color:#e53e3e;font-size:13px">⚠️ This link expires in <strong>' . (defined('RESET_TOKEN_HOURS') ? RESET_TOKEN_HOURS : 1) . ' hour(s)</strong>.</p>
    <p style="color:#999;font-size:12px">If you did not request a password reset, please ignore this email.</p>';

    return sendEmail(
        $toEmail, $toName,
        'Reset Your Password — Codex AG',
        emailTemplate('Password Reset', $body)
    );
}

/**
 * Resend Verification Email — same as sendVerificationEmail()
 * Exposed as a separate named function for clarity in tutorials.
 */
function resendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    return sendVerificationEmail($toEmail, $toName, $token);
}

// ============================================================
// PREVIEW SHELL — wraps the email in a dev chrome UI
// ============================================================

function buildPreviewShell(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $emailId
): string {

    $sentAt = date('D, d M Y H:i:s');

    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>📧 ' . htmlspecialchars($subject) . '</title>
  <style>
    :root {
      --bg: #030b18; --bg2: #071528; --bg3: #0a1e34;
      --c1: #22d3ee; --c2: #818cf8; --c3: #f59e0b; --c4: #4ade80;
      --txt: #e2e8f0; --dim: #94a3b8; --muted: #475569;
    }
    * { margin:0; padding:0; box-sizing:border-box }
    body { background:var(--bg); font-family: system-ui, -apple-system, sans-serif; min-height:100vh }
    .shell { display:flex; flex-direction:column; min-height:100vh }

    /* Top bar */
    .topbar {
      background: var(--bg2);
      border-bottom: 1px solid rgba(34,211,238,.15);
      padding: 14px 28px;
      display: flex; align-items: center; gap: 14px;
      flex-shrink: 0;
      backdrop-filter: blur(10px);
    }
    .tb-brand {
      font-family: system-ui; font-weight: 800; font-size: 14px;
      letter-spacing: 3px; color: var(--c1);
    }
    .tb-sep { width:1px; height:22px; background: rgba(255,255,255,.1) }
    .tb-badge {
      font-size: 10px; font-weight: 700; letter-spacing: 1px;
      text-transform: uppercase; padding: 4px 12px;
      background: rgba(34,211,238,.1); color: var(--c1);
      border: 1px solid rgba(34,211,238,.3); border-radius: 20px;
      display: flex; align-items: center; gap: 6px;
    }
    .tb-right { margin-left: auto; display:flex; gap:8px }
    .tb-btn {
      padding: 8px 16px; border-radius: 8px; font-size: 12px;
      font-weight: 600; cursor: pointer; border: none; font-family: system-ui;
      transition: all .18s; display: flex; align-items: center; gap: 6px;
    }
    .tb-inbox {
      background: rgba(129,140,248,.1); color: var(--c2);
      border: 1px solid rgba(129,140,248,.2);
    }
    .tb-inbox:hover { background: rgba(129,140,248,.2); transform: translateY(-1px); }
    .tb-close {
      background: rgba(248,113,113,.1); color: #f87171;
      border: 1px solid rgba(248,113,113,.2);
    }
    .tb-close:hover { background: rgba(248,113,113,.2); transform: translateY(-1px); }

    /* Envelope meta */
    .envelope {
      background: var(--bg2);
      border: 1px solid rgba(255,255,255,.06);
      border-radius: 12px;
      margin: 20px 28px 0;
      padding: 20px 24px;
      flex-shrink: 0;
    }
    .env-row {
      display: flex; gap: 12px; align-items: center;
      padding: 6px 0;
      border-bottom: 1px solid rgba(255,255,255,.04);
      font-size: 13px;
    }
    .env-row:last-child { border-bottom: none }
    .env-label { 
      color: var(--muted); font-size: 11px; font-weight: 700;
      letter-spacing: .5px; text-transform: uppercase; min-width: 60px;
      display: flex; align-items: center; gap: 6px;
    }
    .env-value { color: var(--txt); display: flex; align-items: center; gap: 6px; }
    .env-subject { font-weight: 700; font-size: 15px; color: var(--c1) }

    /* Link extractor */
    .links-panel {
      background: var(--bg2);
      border: 1px solid rgba(34,211,238,.15);
      border-radius: 12px;
      margin: 12px 28px 0;
      padding: 16px 24px;
      flex-shrink: 0;
    }
    .lp-head {
      font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
      text-transform: uppercase; color: var(--c1); margin-bottom: 12px;
      display: flex; align-items: center; gap: 6px;
    }
    .link-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 14px; border-radius: 8px; margin-bottom: 8px;
      border: 1px solid rgba(255,255,255,.07);
      background: rgba(0,0,0,.25); transition: all .18s;
    }
    .link-item:last-child { margin-bottom: 0 }
    .link-item:hover { border-color: rgba(34,211,238,.3); background: rgba(34,211,238,.04); transform: translateX(2px); }
    .li-icon { flex-shrink: 0; color: var(--c1); display: flex; align-items: center; }
    .li-info { flex:1; min-width:0 }
    .li-label { font-size: 12px; font-weight: 600; color: var(--txt); margin-bottom: 4px; }
    .li-url {
      font-family: ui-monospace, monospace; font-size: 11px; color: var(--c1);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 500px; display: block;
    }
    .li-btn {
      padding: 8px 16px; border-radius: 8px; font-size: 12px;
      font-weight: 600; cursor: pointer; border: none;
      background: linear-gradient(135deg,#22d3ee,#0891b2);
      color: #030b18; font-family: system-ui; white-space: nowrap;
      transition: all .18s; text-decoration: none; flex-shrink: 0;
      display: flex; align-items: center; gap: 6px;
    }
    .li-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(34,211,238,.3); }

    /* Email iframe */
    .email-frame-wrap {
      margin: 12px 28px 28px;
      border: 1px solid rgba(255,255,255,.07);
      border-radius: 12px;
      overflow: hidden;
      flex: 1;
      min-height: 400px;
      background: #fff;
    }
    .email-frame-label {
      background: var(--bg3);
      padding: 8px 20px;
      font-size: 11px; font-weight: 700; letter-spacing: 1px;
      text-transform: uppercase; color: var(--muted);
      border-bottom: 1px solid rgba(255,255,255,.05);
      display: flex; align-items: center; gap: 6px;
    }
    iframe {
      width: 100%; height: 500px; border: none; display: block;
    }

    /* SVG icon utility */
    .icon { display: inline-flex; align-items: center; }
  </style>
</head>
<body>
<div class="shell">

  <!-- Top bar -->
  <div class="topbar">
    <span class="tb-brand">CODEX AG</span>
    <div class="tb-sep"></div>
    <span class="tb-badge">
      ' . svgIcon('email', 14) . '
      Dev Email Preview
    </span>
    <div class="tb-right">
      <button class="tb-btn tb-inbox" onclick="window.open(\'' . (defined('SITE_URL') ? SITE_URL : '') . '/mailer/dev_inbox.php\',\'_blank\')">
        ' . svgIcon('inbox', 14) . '
        View Inbox
      </button>
      <button class="tb-btn tb-close" onclick="window.close()">
        ' . svgIcon('close', 14) . '
        Close
      </button>
    </div>
  </div>

  <!-- Envelope meta -->
  <div class="envelope">
    <div class="env-row">
      <span class="env-label">' . svgIcon('user', 14) . ' To</span>
      <span class="env-value">' . htmlspecialchars($toName) . ' &lt;' . htmlspecialchars($toEmail) . '&gt;</span>
    </div>
    <div class="env-row">
      <span class="env-label">' . svgIcon('email', 14) . ' Subject</span>
      <span class="env-value env-subject">' . htmlspecialchars($subject) . '</span>
    </div>
    <div class="env-row">
      <span class="env-label">' . svgIcon('calendar', 14) . ' Sent</span>
      <span class="env-value">' . $sentAt . '</span>
    </div>
    <div class="env-row">
      <span class="env-label">🆔 ID</span>
      <span class="env-value" style="font-family:ui-monospace;font-size:11px;color:var(--muted)">' . $emailId . '</span>
    </div>
  </div>

  <!-- Link extractor panel -->
  <div class="links-panel" id="linksPanel">
    <div class="lp-head">
      ' . svgIcon('link', 14) . '
      Clickable Links in This Email
    </div>
    <div id="linksList">Extracting links...</div>
  </div>

  <!-- Email render -->
  <div class="email-frame-wrap">
    <div class="email-frame-label">
      ' . svgIcon('email', 14) . '
      Email Render — exactly what the user would see
    </div>
    <iframe id="emailFrame" title="Email Preview"></iframe>
  </div>

</div>

<script>
// ── Write email HTML into iframe ────────────────────────────
var emailHtml = ' . json_encode($htmlBody) . ';
var frame = document.getElementById("emailFrame");
frame.onload = function() {};
var doc = frame.contentDocument || frame.contentWindow.document;
doc.open(); doc.write(emailHtml); doc.close();

// Auto-resize iframe to content height
frame.onload = function() {
  try {
    var h = frame.contentDocument.body.scrollHeight;
    frame.style.height = Math.max(h + 40, 400) + "px";
  } catch(e) {}
};

// ── Extract links from email HTML ───────────────────────────
(function extractLinks() {
  var tmp = document.createElement("div");
  tmp.innerHTML = emailHtml;
  var anchors = tmp.querySelectorAll("a[href]");
  var panel = document.getElementById("linksList");
  
  if (!anchors.length) {
    panel.innerHTML = "<span style=\'color:#475569;font-size:13px\'>No links found in this email.</span>";
    return;
  }
  
  var iconSvgs = {
    verify: \'' . addslashes(svgIcon('verify', 16)) . '\',
    reset: \'' . addslashes(svgIcon('reset', 16)) . '\',
    resend: \'' . addslashes(svgIcon('resend', 16)) . '\',
    default: \'' . addslashes(svgIcon('link', 16)) . '\'
  };
  
  panel.innerHTML = "";
  anchors.forEach(function(a) {
    var href = a.href || a.getAttribute("href");
    var text = a.textContent.trim() || href;
    if (!href || href === "#") return;
    
    // Determine icon
    var iconSvg = iconSvgs.default;
    if (href.indexOf("verify") > -1)  iconSvg = iconSvgs.verify;
    if (href.indexOf("reset")  > -1)  iconSvg = iconSvgs.reset;
    if (href.indexOf("resend") > -1)  iconSvg = iconSvgs.resend;
    
    var item = document.createElement("div");
    item.className = "link-item";
    item.innerHTML =
      "<span class=\'li-icon\'>" + iconSvg + "</span>" +
      "<div class=\'li-info\'>" +
        "<div class=\'li-label\'>" + text.substring(0, 60) + "</div>" +
        "<span class=\'li-url\' title=\'" + href + "\'>" + href + "</span>" +
      "</div>" +
      "<a href=\'" + href + "\' target=\'_self\' class=\'li-btn\'>" +
        \'' . addslashes(svgIcon('chevron', 14)) . '\' +
        "Open Link</a>";
    panel.appendChild(item);
  });
})();
</script>
</body>
</html>';
}

// ============================================================
// DEV INBOX VIEW
// ============================================================

function renderDevInbox(): void {
    // Load emails from disk (most recent first)
    $files = glob(__DIR__ . '/dev_inbox/*.html') ?: [];
    usort($files, fn($a,$b) => filemtime($b) - filemtime($a));

    // Parse metadata from filenames / file contents
    $emails = [];
    foreach ($files as $f) {
        $id      = basename($f, '.html');
        $content = file_get_contents($f);
        // Extract subject from <title>
        preg_match('/<title>📧 (.*?)<\/title>/', $content, $subjectM);
        // Extract "To:" from envelope
        preg_match('/env-subject">(.*?)<\/span>/', $content, $subM);
        $emails[] = [
            'id'      => $id,
            'file'    => $f,
            'subject' => html_entity_decode($subjectM[1] ?? 'No subject'),
            'time'    => date('d M Y H:i:s', filemtime($f)),
            'ts'      => filemtime($f),
        ];
    }

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $did  = preg_replace('/[^a-z0-9_]/', '', $_POST['id'] ?? '');
        $df   = __DIR__ . '/dev_inbox/' . $did . '.html';
        if (file_exists($df)) unlink($df);
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/mailer/dev_inbox.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
        foreach ($files as $f) unlink($f);
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/mailer/dev_inbox.php');
        exit;
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Codex AG — Dev Email Inbox</title>
    <style>
    :root{--bg:#030b18;--bg2:#071528;--bg3:#0a1e34;--c1:#22d3ee;--c2:#818cf8;--c3:#f59e0b;--c4:#4ade80;--c5:#f87171;--txt:#e2e8f0;--dim:#94a3b8;--muted:#475569}
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:var(--bg);color:var(--txt);font-family:system-ui,-apple-system,sans-serif;min-height:100vh}
    ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--bg3);border-radius:3px}

    .topbar{background:var(--bg2);border-bottom:1px solid rgba(34,211,238,.12);padding:14px 32px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 20px rgba(0,0,0,.4);backdrop-filter:blur(10px)}
    .tb-brand{font-weight:800;font-size:15px;letter-spacing:3px;color:var(--c1)}
    .tb-badge{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:4px 12px;background:rgba(34,211,238,.08);color:var(--c1);border:1px solid rgba(34,211,238,.25);border-radius:20px;display:flex;align-items:center;gap:6px}
    .tb-count{font-size:12px;color:var(--muted)}
    .tb-right{margin-left:auto;display:flex;gap:8px}
    .btn-sm{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:system-ui;transition:all .18s;display:flex;align-items:center;gap:6px}
    .btn-danger{background:rgba(248,113,113,.1);color:var(--c5);border:1px solid rgba(248,113,113,.2)}
    .btn-danger:hover{background:rgba(248,113,113,.2);transform:translateY(-1px)}
    .btn-back{background:rgba(255,255,255,.06);color:var(--dim);border:1px solid rgba(255,255,255,.09)}
    .btn-back:hover{background:rgba(255,255,255,.1);color:var(--txt);transform:translateY(-1px)}

    .main{max-width:900px;margin:0 auto;padding:40px 24px}
    .sec-head{margin-bottom:32px}
    .sec-title{font-size:28px;font-weight:800;color:var(--txt);margin-bottom:8px;display:flex;align-items:center;gap:10px}
    .sec-title span{color:var(--c1)}
    .sec-sub{font-size:14px;color:var(--dim);line-height:1.6}

    .empty{text-align:center;padding:80px 20px;color:var(--muted)}
    .empty-icon{font-size:48px;margin-bottom:16px;opacity:.4}
    .empty-txt{font-size:14px}

    .email-list{display:flex;flex-direction:column;gap:12px}
    .email-card{
      background:var(--bg2);border:1px solid rgba(255,255,255,.06);border-radius:12px;
      display:flex;align-items:center;gap:16px;padding:16px 20px;
      transition:all .2s;cursor:pointer;text-decoration:none;
    }
    .email-card:hover{border-color:rgba(34,211,238,.3);background:rgba(34,211,238,.03);transform:translateX(4px)}
    .ec-icon{flex-shrink:0;color:var(--c1);display:flex;align-items:center}
    .ec-info{flex:1;min-width:0}
    .ec-subject{font-size:15px;font-weight:700;color:var(--txt);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ec-meta{display:flex;align-items:center;gap:12px;font-size:11px;color:var(--muted)}
    .ec-time{font-family:ui-monospace,monospace}
    .ec-actions{display:flex;gap:8px;flex-shrink:0}
    .btn-open{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:system-ui;background:linear-gradient(135deg,#22d3ee,#0891b2);color:#030b18;text-decoration:none;display:flex;align-items:center;gap:6px;transition:all .18s}
    .btn-open:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(34,211,238,.3)}
    .btn-del{padding:8px 12px;border-radius:8px;font-size:12px;cursor:pointer;border:1px solid rgba(248,113,113,.2);background:rgba(248,113,113,.07);color:var(--c5);font-family:system-ui;transition:all .18s}
    .btn-del:hover{background:rgba(248,113,113,.15);transform:translateY(-1px)}
    
    .icon{display:inline-flex;align-items:center}
    </style>
    </head>
    <body>

    <div class="topbar">
      <span class="tb-brand">CODEX AG</span>
      <span class="tb-badge">
        <?= svgIcon('inbox', 14) ?>
        Dev Inbox
      </span>
      <span class="tb-count"><?= count($emails) ?> email<?= count($emails) !== 1 ? 's' : '' ?></span>
      <div class="tb-right">
        <button class="btn-sm btn-back" onclick="window.close()">
          <?= svgIcon('close', 14) ?>
          Close
        </button>
        <?php if ($emails): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm(\'Delete all emails?\')">
          <input type="hidden" name="action" value="clear">
          <button class="btn-sm btn-danger" type="submit">
            <?= svgIcon('delete', 14) ?>
            Clear All
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="main">
      <div class="sec-head">
        <div class="sec-title">
          <?= svgIcon('inbox', 28) ?>
          Dev Email <span>Inbox</span>
        </div>
        <div class="sec-sub">All emails sent during development. Click to preview. Links inside are fully clickable and point to your local PHP files.</div>
      </div>

      <?php if (!$emails): ?>
        <div class="empty">
          <div class="empty-icon"><?= svgIcon('email', 48) ?></div>
          <div class="empty-txt">No emails yet. Register an account or trigger a password reset to see emails here.</div>
        </div>
      <?php else: ?>
        <div class="email-list">
          <?php foreach ($emails as $e): ?>
            <?php
              $icon = 'email';
              if (stripos($e['subject'], 'verify')  !== false) $icon = 'verify';
              if (stripos($e['subject'], 'reset')   !== false) $icon = 'reset';
              if (stripos($e['subject'], 'welcome') !== false) $icon = 'welcome';
              $viewUrl = (defined('SITE_URL') ? SITE_URL : '') . '/mailer/dev_email_view.php?id=' . urlencode($e['id']);
            ?>
            <div class="email-card">
              <div class="ec-icon"><?= svgIcon($icon, 24) ?></div>
              <div class="ec-info">
                <div class="ec-subject"><?= htmlspecialchars($e['subject']) ?></div>
                <div class="ec-meta">
                  <span class="ec-time"><?= htmlspecialchars($e['time']) ?></span>
                  <span>•</span>
                  <span>ID: <?= htmlspecialchars($e['id']) ?></span>
                </div>
              </div>
              <div class="ec-actions">
                <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" class="btn-open">
                  <?= svgIcon('chevron', 14) ?>
                  Open
                </a>
                <form method="POST" onsubmit="return confirm(\'Delete this email?\')" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>">
                  <button type="submit" class="btn-del" title="Delete">
                    <?= svgIcon('delete', 14) ?>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    </body>
    </html>
    <?php
}