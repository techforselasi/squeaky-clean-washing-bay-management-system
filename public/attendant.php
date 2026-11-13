<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('attendant');

$activeNav = 'dashboard';
require __DIR__ . '/../resources/views/attendant.blade.php';
