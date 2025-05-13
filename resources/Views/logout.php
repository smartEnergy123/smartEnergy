<?php

use App\Http\Controllers\Auth\AuthController;

require __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}


$user = new AuthController;

$user->logout();
