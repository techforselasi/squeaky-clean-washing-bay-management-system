<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('manager');

$activeNav = 'reports';
require __DIR__ . '/../resources/views/manager_reports.blade.php';
