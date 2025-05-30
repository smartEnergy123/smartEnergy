<?php
// routes/web.php

// The autoloader (usually set up in index.php) will handle loading classes
// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use Dotenv\Dotenv;
use App\Http\Controllers\ApplianceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController; // NEW: Import the UserController

require_once __DIR__ . '/../vendor/autoload.php';

if (!getenv('DB_NAME')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// --- Initialize request variables ---
// This ensures that query strings (like ?userId=...) do not prevent route matching.
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Handle dynamic routes first to avoid conflicts with static routes ---
// This must come BEFORE the switch statement.
// Handle dynamic routes for user details (e.g., /smartEnergy/api/admin/users/123)
// It's a simple regex match for a numeric ID, including the /smartEnergy/ base path.
if (preg_match('/^\/smartEnergy\/api\/admin\/users\/(\d+)$/', $request_path, $matches)) {
    $userId = $matches[1];
    $controller = new UserController(); // Use the imported class
    if ($requestMethod === 'GET') {
        $controller->getUserDetails($userId); // Pass the extracted user ID
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
    }
    exit; // Stop execution after handling dynamic route
}

// --- Main Switch for Static Routes and other API Endpoints ---
switch ($request_path) {
    // --- Existing Web Routes ---
    case '/smartEnergy/': // Matches /smartEnergy/
    case '/': // Matches root if accessed directly (e.g., http://localhost/)
        require dirname(__DIR__) . '/resources/Views/landing.php';
        break;
    case '/smartEnergy/login':
        if ($requestMethod === 'POST') {
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
    case '/smartEnergy/contact':
        require dirname(__DIR__) . '/resources/Views/contact.php';
        break;

    // CLIENT
    case '/smartEnergy/client/dashboard':
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
    case '/smartEnergy/client/view-subcription-history': // Route for the user subscription History
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/viewSubcriptionHistory.php';
        break;
    case '/smartEnergy/client/view-consumption-data': // Route for the user consumption log
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/viewConsumptionData.php';
        break;

    // ADMIN
    case '/smartEnergy/admin/dashboard/':
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/dashboard.php';
        break;

    case '/smartEnergy/admin/view-power-stats':
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/PowerStats.php';
        break;

    case '/smartEnergy/admin/simulateWeather':
        require dirname(__DIR__) . '/resources/Views/admin/simulateWeather.php';
        break;

    case '/smartEnergy/logout':
        session_unset();
        session_destroy();
        header('Location: /smartEnergy/login');
        exit;

        // --- NEW: Admin User Management API Routes (Static) ---
    case '/smartEnergy/admin/manage-users':
        require dirname(__DIR__) . '/resources/Views/admin/manageUsers.php';
        break;
    case '/smartEnergy/api/admin/users': // GET: List all client users (with optional search)
        $controller = new UserController();
        if ($requestMethod === 'GET') {
            $controller->listClientUsers();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        break;

    case '/smartEnergy/api/admin/users/update': // POST: Update a user
        $controller = new UserController();
        if ($requestMethod === 'POST') {
            $controller->updateUser();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        break;

    case '/smartEnergy/api/admin/users/delete': // POST: Delete a user
        $controller = new UserController();
        if ($requestMethod === 'POST') {
            $controller->deleteUser();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        break;

    // --- Other API Routes (ApplianceController related) ---
    case '/smartEnergy/api/appliance/toggle':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->toggle();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/consumption/current':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->currentConsumption();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/costRate':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->getCostRate();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/user/dashboard-data':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->dashboardData();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // --- Admin API Routes (ApplianceController related) ---
    case '/smartEnergy/api/admin/set-simulation-config':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->setSimulationConfig();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/admin/set-cost-rate':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->setCostRate();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/admin/get-simulation-config':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->getSimulationConfig();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // --- Simulation API Routes (ApplianceController related) ---
    case '/smartEnergy/api/simulation/get-state':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->getSimulationState();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/start-new-run':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->startNewSimulationRun();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/update-data':
        if ($requestMethod === 'POST') {
            $controller = new ApplianceController();
            $controller->updateSimulationData();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/simulation/get-daily-summary':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->getDailySimulationSummary();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // NEW API ROUTE: For processing subscriptions, now handled by SubscriptionController
    case '/smartEnergy/api/process-subscription':
        if ($requestMethod === 'POST') {
            $controller = new SubscriptionController();
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
