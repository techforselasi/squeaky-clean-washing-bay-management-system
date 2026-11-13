<?php
/**
 * Paystack (TEST mode) — paste your keys from:
 * Paystack Dashboard → Settings → API Keys & Webhooks
 *
 * - Public key is safe to send to the browser for the payment popup.
 * - Secret key must stay on the server only (never put it in JS).
 *
 * Leave the PASTE_HERE placeholders until you copy your real test keys in.
 */
const PAYSTACK_PUBLIC_KEY = 'pk_test_18444446851a07610500d73960ada1b7ecc3c322';
const PAYSTACK_SECRET_KEY = 'sk_test_8630be8732e412c9cae0ed03f6a17a8729bd3836';

/**
 * Paystack requires a valid-looking email on every charge.
 * This is a bay placeholder (not a real customer inbox).
 * Avoid .test / fake TLDs — Paystack rejects those.
 */
const PAYSTACK_CHARGE_EMAIL = 'techforselasi1@gmail.com';

const PAYSTACK_CURRENCY = 'GHS';
