<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
redirect(current_admin() ? 'dashboard.php' : 'login.php');
