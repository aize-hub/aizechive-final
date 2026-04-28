<?php
// ============================================================
//  AizeChive — Xendit Configuration (NOT YET ACTIVE)
//  config/xendit.php
//
//  This file is a placeholder for future Xendit integration.
//  Do NOT include this file in billing.php yet.
//
//  When you are ready to go live:
//  1. Replace the key below with your real Xendit secret key
//  2. Set your actual domain for success/failure URLs
//  3. Register the webhook URL in Xendit Dashboard
//  4. Follow the Xendit integration guide (see README)
// ============================================================

define('XENDIT_SECRET_KEY',   'xnd_development_jbEiAxqfr26peczsG7uMnWmeU6tQv9OCwkDYpOCJ4c5zmUmZbrlwE6rBY4CC');
define('XENDIT_WEBHOOK_TOKEN','izee_hook_2026');
define('XENDIT_SUCCESS_URL',  'https://yourdomain.com/payment-success.html');
define('XENDIT_FAILURE_URL',  'https://yourdomain.com/payment-failed.html');
