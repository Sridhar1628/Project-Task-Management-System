<?php
// email_config.example.php
// Copy to email_config.php and fill with your SMTP credentials.
// Keep email_config.php OUT of Git by using .gitignore.

$mail_host     = getenv('MAIL_HOST') ?: 'smtp.example.com';
$mail_username = getenv('MAIL_USER') ?: 'your_email@example.com';
$mail_password = getenv('MAIL_PASS') ?: 'your_app_password';
$mail_port     = getenv('MAIL_PORT') ?: 587;

// Optional: secure settings
$mail_secure   = getenv('MAIL_SECURE') ?: 'tls'; // 'ssl' or 'tls'
?>
