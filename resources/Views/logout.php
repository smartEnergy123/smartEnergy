<?php

use App\Http\Controllers\Auth\AuthController;

require __DIR__ . '/../../vendor/autoload.php';

if (!$_SESSION['user_data']) {
    header('Location: /smartEnergy/login');
    exit;
}

$user = new AuthController;

$user->logout();
