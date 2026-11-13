<?php
/**
 * Outgoing mail for digital receipts.
 *
 * For local Herd/XAMPP, PHP mail() may not deliver unless a mail server is set up.
 * Paste a real From address you control when you go live.
 */
const MAIL_FROM_ADDRESS = 'receipts@squeakyclean.com';
const MAIL_FROM_NAME = 'Squeaky Clean Washing Bay';
const MAIL_SUBJECT_PREFIX = 'Your Squeaky Clean receipt';
