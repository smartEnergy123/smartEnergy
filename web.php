<?php
// routes/web.php

// The autoloader (usually set up in index.php) will handle loading classes
// like App\Http\Controllers\ApplianceController and App\Models\DB based on PSR-4.

use Dotenv\Dotenv;
use App\Http\Controllers\ApplianceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController; // NEW: Import the ReportController

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
    $controller = new UserController();
    if ($requestMethod === 'GET') {
        $controller->getUserDetails($userId);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
    }
    exit;
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
                    $_SESSION['user_data'] = ['username' => 'admin', 'user_type' => 'admin', 'id' => 'admin_1'];
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/admin/dashboard']);
                    exit;
                } elseif ($data['username'] === 'user' && $data['password'] === 'user') {
                    $_SESSION['user_state'] = 'authenticated';
                    $_SESSION['user_data'] = ['username' => 'user', 'user_type' => 'client', 'id' => 'client_1'];
                    echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/smartEnergy/client/dashboard']);
                    exit;
                } else {
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
                    exit;
                }
            } else {
                http_response_code(400);
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
    case '/smartEnergy/client/dashboard/':
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/dashboard.php';
        break;
    case '/smartEnergy/client/make-subscription':
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/makeSubscription.php';
        break;
    case '/smartEnergy/client/view-subcription-history':
        if (!isset($_SESSION['user_state'])) {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/client/viewSubcriptionHistory.php';
        break;
    case '/smartEnergy/client/view-consumption-data':
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
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/simulateWeather.php';
        break;

    case '/smartEnergy/admin/simulation':
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/simulate.php');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/simulate.php';
        break;

    // NEW ADMIN REPORT ROUTE
    case '/smartEnergy/admin/reports':
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            header('Location: /smartEnergy/login');
            exit;
        }
        require dirname(__DIR__) . '/resources/Views/admin/report.php'; // Points to the new report file
        break;

    case '/smartEnergy/logout':
        require dirname(__DIR__) . '/resources/Views/logout.php'; // logout the user
        break;

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

    case '/smartEnergy/appliance-controller/get-simulation-config':
        if ($requestMethod === 'GET') {
            $controller = new ApplianceController();
            $controller->getNewSimulationConfig();
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


        // Route for fetching daily simulation summary
    case '/smartEnergy/appliance-controller/get-daily-simulation-summary':
        $controller = new ApplianceController();
        if ($requestMethod === 'GET') {
            $controller->getNewDailySimulationSummary();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
        }
        break;

    // Route for updating simulation state
    case '/smartEnergy/appliance-controller/update-simulation-state':
        $controller = new ApplianceController();
        if ($requestMethod === 'POST') {
            // Read raw POST data (JSON body)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true); // Decode JSON into associative array

            // Pass data to controller method
            $controller->updateSimulationState($data);
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
        }
        break;

    // Route for fetching simulation configuration
    case '/smartEnergy/appliance-controller/get-simulation-config':
        $controller = new ApplianceController();
        if ($requestMethod === 'GET') {
            $controller->getNewSimulationConfig();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
        }
        break;

    // Route for updating the user daily quota
    case '/smartEnergy/api/updateUserDailyQuota':
        $controller = new ApplianceController();
        if ($requestMethod === 'POST') {
            // Get the raw POST data (JSON)
            $rawData = file_get_contents('php://input');
            // Decode the JSON data into a PHP associative array
            $data = json_decode($rawData, true);

            // Check if JSON decoding was successful and data is not null
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => 'Invalid JSON data provided.']);
                break; // Exit the case
            }

            $controller->updateUserDailyQuota($data); // Pass the decoded data to the controller method
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
        }
        break;

    // Route for setting/updating simulation configuration
    case '/smartEnergy/appliance-controller/set-simulation-config':
        $controller = new ApplianceController();
        if ($requestMethod === 'POST') {
            // Read raw POST data (JSON body)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true); // Decode JSON into associative array

            // Pass data to controller method
            $controller->setSimulationConfig($data);
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
        }
        break;

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

        // NEW API ROUTES for ReportController
    case '/smartEnergy/api/admin/reports/daily-summary': // GET: Daily summaries
        $controller = new ReportController();
        if ($requestMethod === 'GET') {
            $controller->getDailySummaries();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

    case '/smartEnergy/api/admin/reports/simulation-state-history': // GET: Detailed simulation state history
        $controller = new ReportController();
        if ($requestMethod === 'GET') {
            $controller->getSimulationStateHistory();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // NEW API ROUTES for ReportController
    case '/smartEnergy/api/admin/reports/online-users': // GET: Count of online users
        $controller = new \App\Http\Controllers\ReportController(); // Ensure correct namespace
        if ($requestMethod === 'GET') {
            $controller->getOnlineUserCount();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
        }
        exit;

        // JS CODE FOR ALL
    case '/smartEnergy/js/dashboard.js': // admin dashboard.js
        require dirname(__DIR__) . '/public/assets/js/dashboard.js';
        exit;


    default:
        http_response_code(404); // Not Found
        require dirname(__DIR__) . '/resources/Views/404.php';
        exit; // Stop execution
}
