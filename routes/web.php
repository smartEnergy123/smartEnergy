<?php
// routes/web.php

// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use App\Http\Controllers\ApplianceController;

$request = $_SERVER['REQUEST_URI'];

// --- CRITICAL CHANGE HERE: Parse the URL to get only the path part ---
$request_path = parse_url($request, PHP_URL_PATH);

if ($request_path === '/smartEnergy/') {
    $cleaned_request_path = str_replace('/smartEnergy/', '/', $request_path);
} else {
    $cleaned_request_path = str_replace('/index.php', '/', $request_path);
}

// Now use $cleaned_request_path in your switch statement
switch ($cleaned_request_path) {
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

    // --- API Routes (these cases are now correctly matched against the path without query string) ---
    case '/smartEnergy/api/appliance/toggle':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->toggle();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

    case '/smartEnergy/api/consumption/current':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->currentConsumption();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/costRate':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
            $controller->getCostRate();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/user/dashboard-data': // THIS WILL NOW MATCH CORRECTLY!
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
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
