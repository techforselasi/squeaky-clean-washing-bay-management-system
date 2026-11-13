<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('attendant');

$activeNav = 'bays';
require __DIR__ . '/../resources/views/attendant_bays.blade.php';
