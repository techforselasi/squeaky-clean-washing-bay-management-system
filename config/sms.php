<?php
/**
 * SMS delivery for digital receipts (customer Messages app — not WhatsApp).
 *
 * Needs internet + an SMS provider account (e.g. Arkesel in Ghana).
 * Paste your API key below. Leave DEMO mode on to test the Send button
 * without a live provider (message is logged locally, not sent to a phone).
 *
 * Arkesel: https://sms.arkesel.com → get API key + approved sender ID
 */
const SMS_PROVIDER = 'arkesel'; // arkesel | demo
const SMS_API_KEY = 'dmZTcUFlYlRWanRTSFR5WVdjd0s';
const SMS_SENDER_ID = 'SqueakyCln'; // max ~11 chars; must be approved by provider
const SMS_API_URL = 'https://sms.arkesel.com/api/v2/sms/send';

/**
 * When true (or provider is demo / key still PASTE_...), send is simulated
 * so you can test the UI offline. Set false + real key for live SMS.
 */
const SMS_DEMO_MODE = true;
