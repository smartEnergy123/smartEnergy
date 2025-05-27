<?php
// routes/web.php

// The autoloader (usually set up in index.php) will handle loading classes
// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use Dotenv\Dotenv;
use App\Http\Controllers\ApplianceController;
use App\Http\Controllers\SubscriptionController; // NEW: Import the new SubscriptionController

require_once __DIR__ . '/../vendor/autoload.php';

if (!getenv('DB_NAME')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (isset($data['username']) && isset($data['password'])) {
                if ($data['username'] === 'admin' && $data['password'] === 'admin') {
                    $_SESSION['user_state'] = 'authenticated';
                    $_SESSION['user_data'] = ['username' => 'admin', 'user_type' => 'admin', 'id' => 'admin_1']; // Consistent ID
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/admin/dashboard']);
                    exit;
                } elseif ($data['username'] === 'user' && $data['password'] === 'user') {
                    $_SESSION['user_state'] = 'authenticated';
                    $_SESSION['user_data'] = ['username' => 'user', 'user_type' => 'client', 'id' => 'client_1']; // Consistent ID
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/client/dashboard']);
                    exit;
                } else {
                    http_response_code(401); // Unauthorized
                    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
                    exit;
                }
            } else {
                http_response_code(400); // Bad Request
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

    // CLIENT
    case '/smartEnergy/client/dashboard/': // Removed trailing slash for consistency
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/dashboard.php';
        break;
    case '/smartEnergy/client/make-subscription': // Route for the subscription page
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/makeSubscription.php';
        break;

    // ADMIN
    case '/smartEnergy/admin/dashboard/': // Removed trailing slash for consistency
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/dashboard.php';
        break;
    case '/smartEnergy/admin/viewPowerStats':
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/viewPowerStats.php'; // Assuming this is the correct path
        break;
    case '/smartEnergy/admin/simulateWeather':
        require dirname(__DIR__) . '/resources/Views/admin/simulateWeather.php';
        break;
    case '/smartEnergy/logout':
        session_unset();
        session_destroy();
        header('Location: /smartEnergy/login');
        exit;

        // --- API Routes ---
    case '/smartEnergy/api/appliance/toggle':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            $controller->toggle();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

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

        // --- NEW ROUTE TO FETCH LAST SIMULATION STATE ---
    case '/smartEnergy/api/simulation/get-state':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
            // This method needs to be implemented in ApplianceController
            // For now, it will return a 501 Not Implemented
            http_response_code(501);
            echo json_encode(['status' => 'error', 'message' => 'API route not implemented: /smartEnergy/api/simulation/get-state']);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Important: stop execution after API response


        // --- NEW ROUTE TO START A NEW SIMULATION RUN ---
    case '/smartEnergy/api/simulation/start-new-run':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController();
            // This method needs to be implemented in ApplianceController
            // For now, it will return a 501 Not Implemented
            http_response_code(501);
            echo json_encode(['status' => 'error', 'message' => 'API route not implemented: /smartEnergy/api/simulation/start-new-run']);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

        // --- NEW ROUTE FOR AUTOMATIC SIMULATION DATA UPDATES ---
    case '/smartEnergy/api/simulation/update-data':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ApplianceController(); // Instantiate controller inside the block
            // This method needs to be implemented in ApplianceController
            // For now, it will return a 501 Not Implemented
            http_response_code(501);
            echo json_encode(['status' => 'error', 'message' => 'API route not implemented: /smartEnergy/api/simulation/update-data']);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

        // --- NEW ROUTE TO GET DAILY SIMULATION SUMMARY ---
    case '/smartEnergy/api/simulation/get-daily-summary':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new ApplianceController();
            // This method needs to be implemented in ApplianceController
            // For now, it will return a 501 Not Implemented
            http_response_code(501);
            echo json_encode(['status' => 'error', 'message' => 'API route not implemented: /smartEnergy/api/simulation/get-daily-summary']);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit; // Stop execution after API response

        // NEW API ROUTE: For processing subscriptions, now handled by SubscriptionController
    case '/smartEnergy/api/process-subscription':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once dirname(__DIR__) . '/app/Http/Controllers/SubscriptionController.php'; // Ensure the file is included
            $controller = new SubscriptionController(); // Use the new controller
            $controller->processSubscription();
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
