<?php
// app/Http/Controllers/ApplianceController.php

namespace App\Http\Controllers;

use App\Models\DB; // Import your custom DB class
use PDO;            // Import the global PDO class for constants like PDO::PARAM_STR
use PDOException;   // Import PDOException for explicit error handling
use Exception;      // Import generic Exception for other potential errors

class ApplianceController
{
    private $db;

    public function __construct()
    {
        // Always set the JSON header first for API responses
        // This is crucial to ensure consistent output, even if an error occurs early.
        header("Content-Type: application/json; charset=UTF-8");

        // Instantiate your DB class. Autoloader will find App\Models\DB.
        $this->db = new DB();

        // It's good practice to ensure the database connection is available
        // If your DB class's constructor handles connection errors by throwing,
        // this check might be slightly redundant but harmless.
        // Assuming DB->connection() establishes and returns a PDO object, or null on failure.
        if (!$this->db->connection()) {
            error_log("FATAL: Database connection failed in ApplianceController constructor.");
            $this->sendJsonResponse('error', 'Database connection error.', 500);
            // The exit is crucial here to prevent further execution if DB connection fails
        }
    }

    /**
     * Helper method to send a consistent JSON response and terminate script execution.
     *
     * @param string $status The status of the response (e.g., 'success', 'error').
     * @param string $message A human-readable message.
     * @param int $statusCode The HTTP status code (e.g., 200, 400, 500).
     * @param array $data Optional additional data to include in the response.
     */
    private function sendJsonResponse($status, $message, $statusCode = 200, $data = [])
    {
        // header("Content-Type: application/json; charset=UTF-8"); // Already set in constructor
        http_response_code($statusCode);
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
        exit; // Terminate script execution after sending JSON
    }

    /**
     * Handles toggling the ON/OFF state of an appliance for a user.
     * Expects POST request with userId, applianceId, and state (boolean).
     */
    public function toggle()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate input data
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['applianceId'], $data['state'])) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $applianceId = $data['applianceId'];
        $isOn = (bool)$data['state']; // 'state' from JS corresponds to 'is_on' in DB

        try {
            // Insert or update the `user_appliances` table
            // This query handles both new entries and updates for existing user-appliance pairs.
            $query = "
                INSERT INTO user_appliances (user_id, appliance_id, is_on, last_updated_at)
                VALUES (:userId, :applianceId, :isOn, NOW())
                ON DUPLICATE KEY UPDATE
                    is_on = VALUES(is_on),
                    last_updated_at = NOW()
            ";
            $params = [
                ':userId' => $userId,
                ':applianceId' => $applianceId,
                ':isOn' => $isOn
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Appliance state updated.');
        } catch (PDOException $e) {
            // Log the detailed database error for debugging purposes (e.g., to PHP's error log)
            error_log('Appliance toggle database error: ' . $e->getMessage());
            // Send a generic error message to the client to avoid exposing sensitive details
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    /**
     * Handles logging the current consumption data for a user.
     * Expects POST request with userId, currentConsumptionW, dailyConsumptionWh, and timestamp.
     */
    public function currentConsumption()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate input data
        // Ensure all required fields are present
        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !isset($data['userId'], $data['currentConsumptionW'], $data['dailyConsumptionWh'], $data['timestamp'])
        ) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $currentConsumptionW = $data['currentConsumptionW'];
        $dailyConsumptionWh = $data['dailyConsumptionWh'];
        $timestamp = $data['timestamp']; // Use the timestamp provided by the client (ISO format)

        try {
            // Insert a new log entry into the `consumption_logs` table.
            // This table stores a historical record of consumption at specific points in time.
            $query = "
                INSERT INTO consumption_logs (user_id, timestamp, current_consumption_w, daily_consumption_wh)
                VALUES (:userId, :timestamp, :currentConsumptionW, :dailyConsumptionWh)
            ";
            $params = [
                ':userId' => $userId,
                ':timestamp' => $timestamp,
                ':currentConsumptionW' => $currentConsumptionW,
                ':dailyConsumptionWh' => $dailyConsumptionWh
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Consumption logged successfully.');
        } catch (PDOException $e) {
            error_log('Current consumption logging database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    /**
     * Fetches the current energy cost rate from the simulation state.
     * Expects GET request.
     */
    public function getCostRate()
    {
        try {
            // Query the `simulation_state` table to get the current cost rate.
            // We assume there's always a single row with id = 1 for the simulation state.
            $query = "SELECT current_cost_rate FROM simulation_state WHERE id = 1";
            $result = $this->db->fetchSingleData($query);

            if ($result && isset($result['current_cost_rate'])) {
                // If data is found, send it back as JSON
                $this->sendJsonResponse('success', 'Cost rate fetched.', 200, ['costRate' => $result['current_cost_rate']]);
            } else {
                // If the row with id=1 is not found or the column is missing,
                // log a warning and send a default/placeholder value.
                error_log('Simulation state (id=1) not found or current_cost_rate missing. Returning default.');
                $this->sendJsonResponse('success', 'Cost rate not available.', 200, ['costRate' => 'Standard']); // Default rate
            }
        } catch (PDOException $e) {
            error_log('Get cost rate database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching cost rate.', 500);
        }
    }

    /**
     * Fetches initial dashboard data for a specific user, including daily quota,
     * current daily consumption, and appliance states.
     * Expects GET request with userId in query parameters.
     */
    public function dashboardData()
    {
        $userId = $_GET['userId'] ?? null; // Get userId from URL query parameter

        // Validate userId
        if (!$userId) {
            $this->sendJsonResponse('error', 'User ID is required.', 400);
        }

        try {
            // 1. Fetch daily quota from `client_profiles` table
            $quotaQuery = "SELECT daily_quota_wh FROM client_profiles WHERE user_id = :userId";
            $clientProfile = $this->db->fetchSingleData($quotaQuery, [':userId' => $userId]);
            // Provide a default if the user's profile is not found or quota is not set
            $dailyQuotaWh = $clientProfile['daily_quota_wh'] ?? 7000;

            // 2. Fetch current daily consumption from `consumption_logs` for today
            // This sums up the `daily_consumption_wh` column for the current day for the specific user.
            // Ensure your `consumption_logs` table's `daily_consumption_wh` column is correctly populated
            // by the `currentConsumption` (logConsumption) endpoint.
            $today = date('Y-m-d');
            $consumptionQuery = "
                SELECT SUM(daily_consumption_wh) as total_wh_today
                FROM consumption_logs
                WHERE user_id = :userId AND DATE(timestamp) = :today
            ";
            $dailyConsumptionResult = $this->db->fetchSingleData($consumptionQuery, [
                ':userId' => $userId,
                ':today' => $today
            ]);
            // If SUM returns NULL (e.g., no entries for today), default to 0
            $currentDailyConsumptionWh = $dailyConsumptionResult['total_wh_today'] ?? 0;

            // 3. Fetch appliance states for the user from `user_appliances` table
            $applianceStatesQuery = "SELECT appliance_id as id, is_on as state FROM user_appliances WHERE user_id = :userId";
            $applianceStates = $this->db->fetchAllData($applianceStatesQuery, [':userId' => $userId]);

            // Ensure $applianceStates is always an array, even if no records are found.
            // Your DB->fetchAllData should ideally return an empty array if no rows are found.
            if (!is_array($applianceStates)) {
                $applianceStates = [];
            }

            // Send all fetched data back to the client
            $this->sendJsonResponse('success', 'Dashboard data fetched.', 200, [
                'dailyQuotaWh' => (float)$dailyQuotaWh, // Cast to float for consistency with JS numbers
                'currentDailyConsumptionWh' => (float)$currentDailyConsumptionWh,
                'applianceStates' => $applianceStates
            ]);
        } catch (PDOException $e) {
            error_log('Dashboard data fetch error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Failed to fetch dashboard data.', 500);
        }
    }

    /**
     * Handles setting simulation configuration parameters from the admin dashboard.
     * Expects POST request with numHouses and dailyQuota.
     */
    public function setSimulationConfig()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['numHouses'], $data['dailyQuota'])) {
            $this->sendJsonResponse('error', 'Invalid or missing simulation configuration data.', 400);
        }

        $numHouses = (int)$data['numHouses'];
        $dailyQuotaPerHouse = (int)$data['dailyQuota'];

        try {
            $query = "
                INSERT INTO simulation_config (id, num_houses, daily_quota_per_house_wh, last_updated_at)
                VALUES (1, :numHouses, :dailyQuota, NOW())
                ON DUPLICATE KEY UPDATE
                    num_houses = VALUES(num_houses),
                    daily_quota_per_house_wh = VALUES(daily_quota_per_house_wh),
                    last_updated_at = NOW()
            ";
            $params = [
                ':numHouses' => $numHouses,
                ':dailyQuota' => $dailyQuotaPerHouse
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Simulation configuration updated.');
        } catch (PDOException $e) {
            error_log('Set simulation config database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    /**
     * Handles setting the global energy cost rate from the admin dashboard.
     * Expects POST request with costRate.
     */
    public function setCostRate()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['costRate'])) {
            $this->sendJsonResponse('error', 'Invalid or missing cost rate data.', 400);
        }

        $newCostRate = $data['costRate'];

        try {
            // Update the `simulation_state` table's `current_cost_rate`
            // We assume id = 1 is the primary row for global simulation state.
            $query = "
                INSERT INTO simulation_state (id, current_cost_rate, last_updated_at)
                VALUES (1, :costRate, NOW())
                ON DUPLICATE KEY UPDATE
                    current_cost_rate = VALUES(current_cost_rate),
                    last_updated_at = NOW()
            ";
            $params = [':costRate' => $newCostRate];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Cost rate updated successfully.');
        } catch (PDOException $e) {
            error_log('Set cost rate database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    /**
     * Fetches simulation configuration parameters for the admin dashboard.
     * Expects GET request.
     */
    public function getSimulationConfig()
    {
        try {
            // Fetch num_houses and daily_quota_per_house_wh from 'simulation_config' table
            $configQuery = "SELECT num_houses, daily_quota_per_house_wh FROM simulation_config WHERE id = 1";
            $simulationConfig = $this->db->fetchSingleData($configQuery);

            // Fetch current_cost_rate from 'simulation_state' table
            $costRateQuery = "SELECT current_cost_rate FROM simulation_state WHERE id = 1";
            $costRateResult = $this->db->fetchSingleData($costRateQuery);

            // Prepare data for response, providing defaults if no data is found
            $numHouses = $simulationConfig['num_houses'] ?? 20; // Default to 20
            $dailyQuotaPerHouse = $simulationConfig['daily_quota_per_house_wh'] ?? 7000; // Default to 7000 Wh
            $costRate = $costRateResult['current_cost_rate'] ?? 'Standard'; // Default to 'Standard'

            $this->sendJsonResponse('success', 'Admin configuration fetched.', 200, [
                'numHouses' => (int)$numHouses,
                'dailyQuotaPerHouse' => (int)$dailyQuotaPerHouse,
                'costRate' => $costRate
            ]);
        } catch (PDOException $e) {
            error_log('Get admin simulation config database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Failed to fetch admin configuration.', 500);
        }
    }

    /**
     * NEW METHOD: Updates global simulation data (grid import, CO2, battery, simulated time).
     * This method is intended to be called periodically (e.g., every 5 seconds) by the frontend.
     * It will simulate minute-by-minute changes based on current configuration.
     * Expects POST request (or GET if only fetching, but POST is better for state changes).
     */
    public function updateSimulationData()
    {
        try {
            // 1. Fetch current simulation configuration (num_houses, daily_quota_per_house_wh)
            $stmtConfig = $this->db->connection()->prepare("SELECT num_houses, daily_quota_per_house_wh FROM simulation_config WHERE id = 1");
            $stmtConfig->execute();
            $simConfig = $stmtConfig->fetch(PDO::FETCH_ASSOC); // Corrected: use PDO::FETCH_ASSOC

            // Defaults if config isn't found (though it should be initialized by setSimulationConfig)
            $numHouses = $simConfig['num_houses'] ?? 20;
            $dailyQuotaPerHouse = $simConfig['daily_quota_per_house_wh'] ?? 7000;

            // 2. Fetch current simulation state (to get previous values)
            $stmtState = $this->db->connection()->prepare("SELECT current_cost_rate, total_grid_import_wh, total_co2_emissions, current_battery_level_wh, simulated_minutes FROM simulation_state WHERE id = 1");
            $stmtState->execute();
            $simState = $stmtState->fetch(PDO::FETCH_ASSOC); // Corrected: use PDO::FETCH_ASSOC

            // Initialize current values, or use defaults if no state exists yet (first run)
            $currentCostRate = $simState['current_cost_rate'] ?? 'Standard';
            $currentGridImport = $simState['total_grid_import_wh'] ?? 0.00;
            $currentCO2Emissions = $simState['total_co2_emissions'] ?? 0.00;
            $currentBatteryLevel = $simState['current_battery_level_wh'] ?? 5000; // Default initial battery charge
            $simulatedMinutes = $simState['simulated_minutes'] ?? 0;

            // --- 3. Simulation Logic ---
            // These are simplified calculations for a 1-minute simulation step.
            // You can make these more sophisticated (e.g., based on time of day, weather, appliance types).

            // Simulate base consumption for each house (e.g., always-on appliances like fridges)
            $baseConsumptionPerMinute = $numHouses * (mt_rand(10, 30) / 60); // 10-30W per house for 1 minute

            // Simulate random peak usage (e.g., AC, washing machine)
            $peakConsumptionPerMinute = 0;
            if (mt_rand(1, 10) > 7) { // 30% chance of higher usage
                $peakConsumptionPerMinute = $numHouses * (mt_rand(100, 500) / 60); // 100-500W per house for 1 minute
            }

            $simulatedConsumptionThisMinute = $baseConsumptionPerMinute + $peakConsumptionPerMinute;

            // Simulate solar generation (basic daytime simulation)
            $solarGenerationThisMinute = 0;
            $hour = (int)date('H'); // Get current hour of the server
            if ($hour >= 7 && $hour <= 18) { // Simulate generation during typical daylight hours
                $solarGenerationThisMinute = $numHouses * (mt_rand(20, 150) / 60); // 20-150W per house for 1 minute
            }

            $netConsumptionThisMinute = $simulatedConsumptionThisMinute - $solarGenerationThisMinute;

            // Battery Management
            $maxBatteryCapacity = 10000; // Define your battery's max capacity in Wh
            $minBatteryLevel = 0;

            $gridImportThisMinute = 0;
            $gridExportThisMinute = 0;

            // Try to use battery if consuming, or charge battery if generating
            if ($netConsumptionThisMinute > 0) { // Net consumption (drawing power)
                if ($currentBatteryLevel > $netConsumptionThisMinute) {
                    $currentBatteryLevel -= $netConsumptionThisMinute; // Use battery
                } else {
                    // Battery doesn't cover all consumption, drain battery and import remaining
                    $gridImportThisMinute = $netConsumptionThisMinute - $currentBatteryLevel;
                    $currentBatteryLevel = $minBatteryLevel;
                }
            } elseif ($netConsumptionThisMinute < 0) { // Net generation (producing excess power)
                $excessGeneration = abs($netConsumptionThisMinute);
                if ($currentBatteryLevel + $excessGeneration < $maxBatteryCapacity) {
                    $currentBatteryLevel += $excessGeneration; // Charge battery
                } else {
                    // Battery is full, export remaining excess to grid
                    $gridExportThisMinute = ($currentBatteryLevel + $excessGeneration) - $maxBatteryCapacity;
                    $currentBatteryLevel = $maxBatteryCapacity;
                }
            }

            // Update total grid import
            $newGridImport = $currentGridImport + $gridImportThisMinute;

            // Calculate CO2 emissions (example: 0.5 kg CO2 per kWh imported)
            // Assuming CO2 only for grid import, not for power from battery or solar.
            $co2EmissionsThisMinute = ($gridImportThisMinute / 1000) * 0.5; // Convert Wh to kWh
            $newCO2Emissions = $currentCO2Emissions + $co2EmissionsThisMinute;

            // Update simulated minutes (increment by 1 minute for each update call)
            $newSimulatedMinutes = $simulatedMinutes + 1;

            // --- 4. Update the simulation_state table ---
            $stmtUpdate = $this->db->connection()->prepare("
                INSERT INTO simulation_state (id, current_cost_rate, total_grid_import_wh, total_co2_emissions, current_battery_level_wh, simulated_minutes, last_updated_at)
                VALUES (1, :current_cost_rate, :total_grid_import_wh, :total_co2_emissions, :current_battery_level_wh, :simulated_minutes, NOW())
                ON DUPLICATE KEY UPDATE
                    current_cost_rate = VALUES(current_cost_rate),
                    total_grid_import_wh = VALUES(total_grid_import_wh),
                    total_co2_emissions = VALUES(total_co2_emissions),
                    current_battery_level_wh = VALUES(current_battery_level_wh),
                    simulated_minutes = VALUES(simulated_minutes),
                    last_updated_at = NOW()
            ");

            // Corrected: Use PDO::PARAM_STR for decimals/strings and PDO::PARAM_INT for integers
            $stmtUpdate->bindParam(':current_cost_rate', $currentCostRate, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_grid_import_wh', $newGridImport, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_co2_emissions', $newCO2Emissions, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':current_battery_level_wh', $currentBatteryLevel, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':simulated_minutes', $newSimulatedMinutes, PDO::PARAM_INT);
            $stmtUpdate->execute();

            // --- 5. Send Response ---
            $this->sendJsonResponse('success', 'Simulation data updated.', 200, [
                'current_cost_rate' => $currentCostRate,
                'total_grid_import_wh' => round($newGridImport, 2),
                'total_co2_emissions' => round($newCO2Emissions, 2),
                'current_battery_level_wh' => round($currentBatteryLevel, 0),
                'simulated_minutes' => $newSimulatedMinutes,
                'num_houses' => $numHouses,
                'daily_quota_per_house_wh' => $dailyQuotaPerHouse
            ]);
        } catch (PDOException $e) {
            error_log('Simulation data update database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error during simulation update: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('General simulation data update error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error during simulation update: ' . $e->getMessage(), 500);
        }
    }
}
