
# Examples & Use Cases

Real-world examples of DevMailer in action.

## Example Projects

DevMailer comes with several complete example projects:

### 1. Basic Usage

Simple demonstration of sending a welcome email.

```php
<?php
require_once '../../src/DevMailer.php';
define('SITE_URL', 'http://localhost:8000');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sendEmail(
        $_POST['email'],
        $_POST['name'],
        'Welcome!',
        '<h1>Welcome ' . htmlspecialchars($_POST['name']) . '!</h1>'
    );
}
?>
```

### Common Use Cases
***User Registration
```php

<?php
function registerUser($email, $name, $password) {
    // Save user to database
    $userId = saveUser($email, $name, $password);
    
    // Generate verification token
    $token = bin2hex(random_bytes(32));
    saveToken($userId, $token);
    
    // Send verification email
    sendVerificationEmail($email, $name, $token);
    
    return $userId;
}

```

### Order Confirmation
```php
<?php
function sendOrderConfirmation($order) {
    $items = '';
    foreach ($order['items'] as $item) {
        $items .= "<li>{$item['name']} - \${$item['price']}</li>";
    }
    
    $body = "
        <h2>Order Confirmation #{$order['id']}</h2>
        <p>Thank you for your order!</p>
        <ul>{$items}</ul>
        <p><strong>Total: \${$order['total']}</strong></p>
    ";
    
    sendEmail(
        $order['email'],
        $order['name'],
        "Order Confirmation #{$order['id']}",
        emailTemplate('Order Confirmation', $body)
    );
}
```

### Newsletter Preview
```php
<?php
function previewNewsletter($subject, $content) {
    $previewEmails = ['test1@example.com', 'test2@example.com'];
    
    foreach ($previewEmails as $email) {
        sendEmail(
            $email,
            'Preview User',
            "[PREVIEW] {$subject}",
            emailTemplate($subject, $content)
        );
    }
}
```

### Batch Testing
```php
<?php
function testEmailTemplates() {
    $testCases = [
        ['Welcome Email', 'welcome_template.html'],
        ['Password Reset', 'reset_template.html'],
        ['Invoice', 'invoice_template.html'],
    ];
    
    foreach ($testCases as $case) {
        $html = file_get_contents($case[1]);
        sendEmail(
            'test@example.com',
            'Test User',
            $case[0],
            $html
        );
        sleep(1); // Prevent tab spam
    }
}
```
### A/B Testing Emails
```php
<?php
function abTestEmail($email, $name) {
    $variants = [
        'A' => ['subject' => 'Limited Time Offer!', 'template' => 'variant_a.html'],
        'B' => ['subject' => 'Special Deal Inside', 'template' => 'variant_b.html'],
    ];
    
    $variant = array_rand($variants);
    $html = file_get_contents($variants[$variant]['template']);
    
    sendEmail(
        $email,
        $name,
        $variants[$variant]['subject'],
        $html
    );
    
    // Log which variant was sent
    error_log("Sent variant {$variant} to {$email}");
}
```
### Integration Examples
***With PHPMailer
```php
<?php
// Hybrid approach: Use PHPMailer for real emails, DevMailer for preview
function smartSend($to, $subject, $body) {
    if (defined('DEV_MODE') && DEV_MODE) {
        sendEmail($to, '', $subject, $body);
    } else {
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->setFrom('noreply@example.com');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    }
}
```
### With Symfony Mailer
```php
<?php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DevMailerWrapper
{
    private $mailer;
    private $isDev;
    
    public function __construct(MailerInterface $mailer, bool $isDev = false)
    {
        $this->mailer = $mailer;
        $this->isDev = $isDev;
    }
    
    public function send(Email $email)
    {
        if ($this->isDev) {
            $to = $email->getTo()[0]->getAddress();
            $subject = $email->getSubject();
            $html = $email->getHtmlBody();
            
            return sendEmail($to, '', $subject, $html);
        }
        
        return $this->mailer->send($email);
    }
}
```

### With Twig Templates
```php
<?php
require_once 'vendor/autoload.php';
require_once 'src/DevMailer.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

function sendTwigEmail($to, $subject, $template, $data) {
    global $twig;
    
    $html = $twig->render($template, $data);
    
    sendEmail(
        $to,
        $data['name'] ?? '',
        $subject,
        emailTemplate($subject, $html)
    );
}

// Usage
sendTwigEmail(
    'user@example.com',
    'Welcome!',
    'emails/welcome.twig',
    ['name' => 'John', 'link' => 'https://example.com']
);
```

### Advanced Scenarios
***Multi-language Emails
```php
<?php
function sendLocalizedEmail($user, $templateKey) {
    $lang = $user['language'] ?? 'en';
    
    $templates = [
        'welcome' => [
            'en' => ['Welcome!', 'welcome_en.html'],
            'es' => ['¡Bienvenido!', 'welcome_es.html'],
            'fr' => ['Bienvenue!', 'welcome_fr.html'],
        ],
    ];
    
    $template = $templates[$templateKey][$lang];
    $html = file_get_contents("templates/{$lang}/{$template[1]}");
    
    sendEmail(
        $user['email'],
        $user['name'],
        $template[0],
        $html
    );
}
```

### Scheduled Emails
```php
<?php
function scheduleEmail($to, $subject, $body, $sendAt) {
    // Store in database
    $id = saveScheduledEmail($to, $subject, $body, $sendAt);
    
    // For immediate preview in dev
    if (defined('DEV_MODE')) {
        sendEmail($to, '', "[SCHEDULED] {$subject}", $body);
    }
    
    return $id;
}
```

### Email Analytics Preview
```php
<?php
function trackEmailOpens($emailId, $to, $subject, $body) {
    // Add tracking pixel
    $trackingUrl = SITE_URL . "/track.php?id={$emailId}";
    $pixel = "<img src='{$trackingUrl}' width='1' height='1' />";
    $body .= $pixel;
    
    // Convert links for click tracking
    $body = preg_replace_callback('/<a href="([^"]+)"/', function($matches) use ($emailId) {
        $trackedUrl = SITE_URL . "/click.php?id={$emailId}&url=" . urlencode($matches[1]);
        return '<a href="' . $trackedUrl . '"';
    }, $body);
    
    sendEmail($to, '', $subject, $body);
    
    // DevMailer's link panel will show tracked URLs
}
```

### Performance Testing
```php
<?php
function benchmarkEmailSending($count = 100) {
    $start = microtime(true);
    
    for ($i = 0; $i < $count; $i++) {
        sendEmail(
            "test{$i}@example.com",
            "User {$i}",
            "Test Email {$i}",
            "<p>This is test email number {$i}</p>"
        );
    }
    
    $time = microtime(true) - $start;
    echo "Sent {$count} emails in " . round($time, 2) . " seconds\n";
    echo "Average: " . round($time / $count * 1000, 2) . "ms per email\n";
}
```
