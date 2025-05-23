<?php
// routes/web.php

// The autoloader (usually set up in index.php) will handle loading classes
// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use App\Http\Controllers\ApplianceController; // Assuming ApplianceController handles all these API methods

// --- CRITICAL CHANGE HERE: Parse the URL to get only the path part ---
// This ensures that query strings (like ?userId=...) do not prevent route matching.
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


switch ($request_path) {
    // --- Existing Web Routes ---
    case '/smartEnergy/': // Matches /smartEnergy/
    case '/': // Matches root if accessed directly (e.g., http://localhost/)
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
    // These cases will now correctly match the path part of the URL, ignoring query strings.
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

    case '/smartEnergy/api/user/dashboard-data':
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
