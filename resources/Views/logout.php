<?php

use App\Http\Controllers\Auth\AuthController;

if (!$_SESSION['user_data']) {
    header('Location: /smartEnergy/login');
    exit;
}

$user = new AuthController;

$user->logout();
