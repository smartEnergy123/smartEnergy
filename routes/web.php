<?php

$request = $_SERVER['REQUEST_URI'];

$request = str_replace('/public/index.php', '/smartEnergy', $request);

$viewDir = '/resources/Views/';


switch ($request) {
    case '/smartEnergy':
    case '/landing':
        require __DIR__ . $viewDir . 'landing.php';
        break;

    default:
        require __DIR__ . $viewDir . '404.php';
        break;
}
