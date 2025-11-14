<?php
// Brevo Email Service Configuration
// IMPORTANT: Never commit your actual API key to version control!

// Get API key from environment variable (recommended for production)
$brevo_api_key = getenv('BREVO_API_KEY');

define('BREVO_API_KEY', $brevo_api_key);
define('FROM_EMAIL', 'mcismartspace@gmail.com');
define('FROM_NAME', 'MCiSmartSpace');
define('ENABLE_EMAIL_NOTIFICATIONS', true);