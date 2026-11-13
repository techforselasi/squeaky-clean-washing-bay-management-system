<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('manager');

$activeNav = 'performance';
require __DIR__ . '/../resources/views/manager_performance.blade.php';
