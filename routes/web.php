<?php

$request = $_SERVER['REQUEST_URI'];

if ($request === '/smartEnergy/') {
    $request = str_replace('/smartEnergy/', '/', $request);
} else {
    $request = str_replace('/index.php', '/', $request);
}
// echo $request;
$viewDir = dirname(__DIR__) . '/resources/Views/';

switch ($request) {
    case '':
    case '/':
        require $viewDir . 'landing.php';
        break;
    case '/smartEnergy/login':
        require $viewDir . 'login.php';
        break;
    case '/smartEnergy/register':
        require $viewDir . 'register.php';
        break;
    case '/smartEnergy/processAuth':
        require $viewDir . 'processAuth.php';
        break;
    case '/smartEnergy/client/dashboard/':
        require $viewDir . 'client/' . 'dashboard.php';
        break;
    case '/smartEnergy/admin/dashboard/':
        require $viewDir . 'admin/' . 'dashboard.php';
        break;
    case '/logout':
        require $viewDir . 'logout.php';
        break;

    default:
        require $viewDir . '404.php';
        break;
}
