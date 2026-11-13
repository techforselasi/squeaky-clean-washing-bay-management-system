<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth('manager');

$activeNav = 'machinery';
require __DIR__ . '/../resources/views/manager_machinery.blade.php';
