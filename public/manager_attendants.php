<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('manager');

$activeNav = 'attendants';
require __DIR__ . '/../resources/views/manager_attendants.blade.php';
