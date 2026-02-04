<?php
/**
 * Stripe test-mode configuration.
 * Replace the placeholder keys with your own test keys from https://dashboard.stripe.com/test/apikeys
 * or set environment variables STRIPE_SECRET_KEY / STRIPE_PUBLISHABLE_KEY.
 */

define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_replace_me');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_replace_me');

// Base URL for redirects back from Stripe (no trailing slash).
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost/ISDN');

if (STRIPE_SECRET_KEY === 'sk_test_replace_me' || STRIPE_PUBLISHABLE_KEY === 'pk_test_replace_me') {
    error_log('[WARN] Stripe test keys are not configured. Update payment-config.php.');
}
?>
