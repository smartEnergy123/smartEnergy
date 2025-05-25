<?php
// routes/web.php

// Ensure necessary classes are imported if they are not already
// This assumes your DB class is in App\Models and ApplianceController in App\Http\Controllers
use App\Models\DB;
use App\Http\Controllers\ApplianceController;
use Dotenv\Dotenv; // Add Dotenv import

// Load environment variables if not already loaded (e.g., for direct script access)
// This should ideally be handled by your front controller (index.php)
if (!getenv('DB_NAME')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Get the request path from the URL
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route handling using a switch statement
switch ($request_path) {
    // --- Existing Web Routes ---
    case '/smartEnergy/': // Matches /smartEnergy/
    case '/': // Matches root if accessed directly (e.g., http://localhost/)
        require dirname(__DIR__) . '/resources/Views/landing.php';
        break;
    case '/smartEnergy/login':
        // The previous version had a direct require, but the updated one handles POST for login.
        // I will keep the POST handling as it was in the "web-php-updated" Canvas document.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (isset($data['username']) && isset($data['password'])) {
                if ($data['username'] === 'admin' && $data['password'] === 'admin') {
                    $_SESSION['user_state'] = 'authenticated';
                    $_SESSION['user_data'] = ['username' => 'admin', 'user_type' => 'admin'];
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/admin/dashboard']);
                    exit;
                } elseif ($data['username'] === 'user' && $data['password'] === 'user') {
                    $_SESSION['user_state'] = 'authenticated';
                    $_SESSION['user_data'] = ['username' => 'user', 'user_type' => 'client', 'user_id' => 'user-id-1']; // Example user ID
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/client/dashboard']);
                    exit;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
                exit;
            }
        } else {
            require dirname(__DIR__) . '/resources/Views/login.php';
        }
        break;
    case '/smartEnergy/register':
        require dirname(__DIR__) . '/resources/Views/register.php';
        break;
    case '/smartEnergy/processAuth':
        require dirname(__DIR__) . '/resources/Views/processAuth.php';
        break;
    case '/smartEnergy/client/dashboard/':
        // The dashboard.php in the previous Canvas document already handles session checks.

        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/dashboard.php';
        break;
    case '/smartEnergy/admin/dashboard/':
        // The dashboard.php in the previous Canvas document already handles session checks.

        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/dashboard.php';
        break;
    case '/smartEnergy/admin/viewPowerStats':
        // This route was not in the previous web.php you provided but was in the Canvas document.
        // I'll keep it as it was in the Canvas document.

        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/viewPowerStats.php'; // Assuming this is the correct path
        break;
    case '/smartEnergy/admin/simulateWeather':
        // This route was in the previous web.php you provided.
        require dirname(__DIR__) . '/resources/Views/admin/simulateWeather.php';
        break;
    case '/smartEnergy/logout':
        // This route was in the previous web.php you provided.
        header('Location: /smartEnergy/login');
        exit;

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

        // --- NEW ADMIN API ROUTES ---
        // These routes will allow the admin dashboard to send configuration data to the backend.
    case '/smartEnergy/api/admin/set-simulation-config':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController(); // Or a dedicated AdminController
            $controller->setSimulationConfig();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/admin/set-cost-rate': // Explicit route for setting cost rate
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->setCostRate();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/admin/get-simulation-config': // NEW: Route to fetch admin config
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
            $controller->getSimulationConfig(); // New method to implement
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // --- NEW ROUTE FOR AUTOMATIC SIMULATION DATA UPDATES ---
    case '/smartEnergy/api/simulation/update-data':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController(); // Instantiate controller inside the block
            $controller->updateSimulationData();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

    default:
        http_response_code(404); // Not Found
        require dirname(__DIR__) . '/resources/Views/404.php';
        exit; // Stop execution
}
