<?php
require_once __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';

require_once __DIR__ . '/../app/routes/web.php';
