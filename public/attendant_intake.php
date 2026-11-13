<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('attendant');

$activeNav = 'intake';
require __DIR__ . '/../resources/views/attendant_intake.blade.php';
