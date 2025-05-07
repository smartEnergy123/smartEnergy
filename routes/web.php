<?php

$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/public/index.php', '/smartEnergy', $request);

$viewDir = dirname(__DIR__) . '/resources/Views/';

echo $request;

switch ($request) {
    case '/smartEnergy':
    case '/':
        require $viewDir . 'landing.php';
        break;
    case '/login':
        require $viewDir . 'login.php';
        break;
    case '/dashboard':
        require $viewDir . 'dashblard.php';
        break;
    case '/logout':
        require $viewDir . 'logout.php';
        break;

    default:
        require $viewDir . '404.php';
        break;
}
