# 📧 DevMailer — Local Email Previewer for PHP

> Stop setting up SMTP servers for local development. Preview emails instantly in your browser.

## 🎯 What is DevMailer?

DevMailer is a **drop-in replacement** for your email sending functions that opens email previews in a new browser tab instead of actually sending them. Perfect for:

- ✅ **Local Development** — No SMTP server needed
- ✅ **YouTube Tutorials** — Visually show emails being "sent"
- ✅ **Team Demos** — Let stakeholders see emails without spamming
- ✅ **Testing** — Verify email templates instantly
- ✅ **Offline Work** — Works 100% without internet

## ✨ Features

- 🚀 **Zero Dependencies** — Pure PHP, no PHPMailer or SwiftMailer required
- 📬 **Beautiful Inbox** — Browse all sent emails in a gorgeous dark-mode UI
- 🔗 **Clickable Links** — All links work and point to your local PHP files
- 📱 **Responsive Preview** — See exactly what users will see
- 🎨 **SVG Icons** — Modern, crisp icons that scale perfectly
- 🔒 **Session-Based** — No database required
- 📂 **File Storage** — Emails saved as HTML files for persistence
- 🎯 **Drop-in Ready** — Same function signatures as your real mailer

## 📦 Installation

### Option 1: Manual Download

```bash
# Clone the repository
git clone https://github.com/codexagdev/devmailer.git

# Copy the files to your project
cp -r devmailer/src/DevMailer.php
cp -r devmailer/mailer /dev_inbox.php
cp -r devmailer/mailer /dev_email_view.php
