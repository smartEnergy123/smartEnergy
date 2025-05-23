<?php
// routes/web.php

// The autoloader (usually set up in index.php) will handle loading classes
// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use App\Http\Controllers\ApplianceController; // Assuming ApplianceController handles all these API methods

require_once __DIR__ . '/../vendor/autoload.php';

$request = $_SERVER['REQUEST_URI'];

// Clean up the request URI as per your existing logic
if ($request === '/smartEnergy/') {
    $request = str_replace('/smartEnergy/', '/', $request);
} else {
    $request = str_replace('/index.php', '/', $request);
}

switch ($request) {
    // --- Existing Web Routes ---
    case '':
    case '/':
        require dirname(__DIR__) . '/resources/Views/landing.php';
        break;
    case '/smartEnergy/login':
        require dirname(__DIR__) . '/resources/Views/login.php';
        break;
    case '/smartEnergy/register':
        require dirname(__DIR__) . '/resources/Views/register.php';
        break;
    case '/smartEnergy/processAuth':
        require dirname(__DIR__) . '/resources/Views/processAuth.php';
        break;
    case '/smartEnergy/client/dashboard/':
        require dirname(__DIR__) . '/resources/Views/client/dashboard.php';
        break;
    case '/smartEnergy/admin/dashboard/':
        require dirname(__DIR__) . '/resources/Views/admin/dashboard.php';
        break;
    case '/smartEnergy/admin/viewPowerStats':
        require dirname(__DIR__) . '/resources/Views/admin/powerStats.php';
        break;
    case '/smartEnergy/admin/simulateWeather':
        require dirname(__DIR__) . '/resources/Views/admin/simulateWeather.php';
        break;
    case '/smartEnergy/logout':
        require dirname(__DIR__) . '/resources/Views/logout.php';
        break;

    // --- API Routes ---
    case '/smartEnergy/api/appliance/toggle':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->toggle();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

    case '/smartEnergy/api/consumption/current': // Corrected endpoint name
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->currentConsumption(); // Renamed method for clarity
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/costRate': // Corrected endpoint name
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
            $controller->getCostRate();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/user/dashboard-data': // New route for initial dashboard data
        if ($_SERVER['REQUEST_METHOD'] === 'GET') { // This should be a GET request
            $controller = new ApplianceController(); // Or a dedicated UserController
            $controller->dashboardData();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    default:
        http_response_code(404); // Not Found
        require dirname(__DIR__) . '/resources/Views/404.php';
        exit; // Stop execution
}
