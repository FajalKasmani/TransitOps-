<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/classes/Database.php';
require_once __DIR__ . '/../api/classes/Auth.php';

use Api\Classes\Auth;

Auth::logout();
header("Location: login.php");
exit;
