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
        //set the JSON header first for API responses
        header("Content-Type: application/json; charset=UTF-8");

        $this->db = new DB();

        if (!$this->db->connection()) {
            error_log("FATAL: Database connection failed in ApplianceController constructor.");
            $this->sendJsonResponse('error', 'Database connection error.', 500);
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
        http_response_code($statusCode);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Handles toggling the ON/OFF state of an appliance for a user.
     * Expects POST request with userId, applianceId, and state (boolean).
     */
    public function toggle()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['applianceId'], $data['state'])) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $applianceId = $data['applianceId'];
        $isOn = (bool)$data['state'];

        try {
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
            error_log('Appliance toggle database error: ' . $e->getMessage());
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

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !isset($data['userId'], $data['currentConsumptionW'], $data['dailyConsumptionWh'], $data['timestamp'])
        ) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $currentConsumptionW = $data['currentConsumptionW'];
        $dailyConsumptionWh = $data['dailyConsumptionWh'];
        $timestamp = $data['timestamp'];

        try {
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
     * Fetches the current energy cost rate from the simulation config.
     * This now fetches from `simulation_config` table.
     * Expects GET request.
     */
    public function getCostRate()
    {
        try {
            // Assuming current_cost_rate is in simulation_config with id=1
            $query = "SELECT current_cost_rate FROM simulation_config WHERE id = :id";
            $params = [':id' => 1];
            $result = $this->db->fetchSingleData($query, $params);

            if ($result && isset($result['current_cost_rate'])) {
                $this->sendJsonResponse('success', 'Cost rate fetched.', 200, ['costRate' => $result['current_cost_rate']]);
            } else {
                error_log('Simulation config (id=1) not found or current_cost_rate missing. Returning default.');
                $this->sendJsonResponse('success', 'Cost rate not available.', 200, ['costRate' => 'Standard']);
            }
        } catch (PDOException $e) {
            error_log('Get cost rate database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching cost rate.', 500);
        }
    }

    /**
     * NEW METHOD: Sets the energy cost rate in the simulation config.
     * Expects POST request with 'costRate'.
     */
    public function setCostRate()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['costRate'])) {
            $this->sendJsonResponse('error', 'Invalid or missing costRate data.', 400);
        }

        $costRate = $data['costRate'];

        try {
            // Update the current_cost_rate in simulation_config table for id = 1
            $query = "
                INSERT INTO simulation_config (id, current_cost_rate, last_updated_at)
                VALUES (1, :costRate, NOW())
                ON DUPLICATE KEY UPDATE
                    current_cost_rate = VALUES(current_cost_rate),
                    last_updated_at = NOW()
            ";
            $params = [
                ':costRate' => $costRate
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Cost rate updated successfully.', 200);
        } catch (PDOException $e) {
            error_log('Set cost rate database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        } catch (Exception $e) {
            error_log('General error setting cost rate: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error setting cost rate.', 500);
        }
    }


    /**
     * Fetches initial dashboard data for a specific user, including daily quota,
     * current daily consumption, current total consumption (wattage),
     * current cost rate, and appliance states.
     * Expects an authenticated user (session-based).
     * This method replaces the previous dashboardData logic to match expected client-side data.
     */
    public function dashboardData()
    {
        // Ensure session is started to access $_SESSION for authenticated user
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_data']['id'] ?? null; // get user ID from session

        if (!$userId) {
            // If user is not authenticated, redirect to login or send an error
            // For API calls, sending a 401 error is appropriate.
            $this->sendJsonResponse('error', 'User not authenticated.', 401);
        }

        try {

            // Update user_state to mark user as online and record activity
            $updateOnlineStatusQuery = "
            INSERT INTO user_state (user_id, is_online, last_active_at, login_time)
            VALUES (:userId, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            is_online = 1,
            last_active_at = NOW(),
            login_time = IF(login_time IS NULL OR DATEDIFF(NOW(), login_time) > 0, NOW(), login_time)
            ";
            // The DATEDIFF check above for login_time is a simple way to update login_time only on a new day or if it's null,
            // assuming a login_time persists until explicit logout or prolonged inactivity.
            // Adjust logic if "login_time" should reset more frequently or if a dedicated login method updates it.

            $this->db->execute($updateOnlineStatusQuery, [':userId' => $userId]);


            // 1. Fetch daily_quota_wh from client_profiles
            $quotaQuery = "SELECT daily_quota_wh FROM client_profiles WHERE user_id = :userId";
            $clientProfile = $this->db->fetchSingleData($quotaQuery, [':userId' => $userId]);
            // IMPORTANT: Default dailyQuotaWh to 0 if no subscription/profile, not 7000.
            // This allows the front-end to correctly identify users without a plan.
            $dailyQuotaWh = (int)($clientProfile['daily_quota_wh'] ?? 0);

            // 2. Fetch current and daily consumption from consumption_logs
            $today = date('Y-m-d');
            $consumptionQuery = "
                SELECT
                    daily_consumption_wh,
                    current_consumption_w
                FROM consumption_logs
                WHERE user_id = :userId AND DATE(timestamp) = :today
                ORDER BY timestamp DESC LIMIT 1
            ";
            $dailyConsumptionResult = $this->db->fetchSingleData($consumptionQuery, [
                ':userId' => $userId,
                ':today' => $today
            ]);
            $currentDailyConsumptionWh = (int)($dailyConsumptionResult['daily_consumption_wh'] ?? 0);
            $currentTotalConsumptionW = (int)($dailyConsumptionResult['current_consumption_w'] ?? 0);


            // 3. Fetch current cost rate from simulation_config
            // This assumes simulation_config holds the global current_cost_rate.
            $costRateQuery = "SELECT current_cost_rate FROM simulation_config WHERE id = 1";
            $costRateResult = $this->db->fetchSingleData($costRateQuery);
            $currentCostRate = $costRateResult['current_cost_rate'] ?? 'Standard'; // Default rate if not found


            // 4. Fetch appliance states for the user
            $applianceStatesQuery = "SELECT appliance_id as id, is_on as state FROM user_appliances WHERE user_id = :userId";
            $applianceStates = $this->db->fetchAllData($applianceStatesQuery, [':userId' => $userId]);
            if (!is_array($applianceStates)) { // Ensure it's an array even if no appliances
                $applianceStates = [];
            }

            // Send all required dashboard data in one response
            $this->sendJsonResponse('success', 'Dashboard data fetched.', 200, [
                'dailyQuotaWh' => $dailyQuotaWh,
                'currentDailyConsumptionWh' => $currentDailyConsumptionWh,
                'currentTotalConsumptionW' => $currentTotalConsumptionW,
                'currentCostRate' => $currentCostRate,
                'applianceStates' => $applianceStates
            ]);
        } catch (PDOException $e) {
            error_log('Dashboard data fetch database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Failed to fetch dashboard data: ' . $e->getMessage(), 500);
        } catch (Exception $e) { // Catch general exceptions as well for robustness
            error_log('Dashboard data fetch general error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching dashboard data.', 500);
        }
    }

    /**
     * Handles setting simulation configuration parameters from the admin dashboard.
     * Expects POST request with numHouses and dailyQuota.
     * Also sets the global cost rate here, assuming it's part of the config now.
     */
    public function setSimulationConfig()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['numHouses'], $data['dailyQuota'], $data['costRate'])) {
            $this->sendJsonResponse('error', 'Invalid or missing simulation configuration data.', 400);
        }

        $numHouses = (int)$data['numHouses'];
        $dailyQuotaPerHouse = (int)$data['dailyQuota'];
        $costRate = $data['costRate']; // Fetch costRate from input

        try {
            $query = "
                INSERT INTO simulation_config (id, num_houses, daily_quota_per_house_wh, current_cost_rate, last_updated_at)
                VALUES (1, :numHouses, :dailyQuota, :costRate, NOW())
                ON DUPLICATE KEY UPDATE
                    num_houses = VALUES(num_houses),
                    daily_quota_per_house_wh = VALUES(daily_quota_per_house_wh),
                    current_cost_rate = VALUES(current_cost_rate),
                    last_updated_at = NOW()
            ";
            $params = [
                ':numHouses' => $numHouses,
                ':dailyQuota' => $dailyQuotaPerHouse,
                ':costRate' => $costRate // Add costRate to params
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Simulation configuration updated.');
        } catch (PDOException $e) {
            error_log('Set simulation config database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    /**
     * Fetches simulation configuration parameters for the admin dashboard.
     * Now includes `current_cost_rate` from `simulation_config`.
     * Expects GET request.
     */
    public function getSimulationConfig()
    {
        try {
            // Assuming all config is in simulation_config with id=1
            $configQuery = "SELECT num_houses, daily_quota_per_house_wh, current_cost_rate FROM simulation_config WHERE id = 1";
            $simulationConfig = $this->db->fetchSingleData($configQuery);

            $numHouses = $simulationConfig['num_houses'] ?? 20;
            $dailyQuotaPerHouse = $simulationConfig['daily_quota_per_house_wh'] ?? 7000;
            $costRate = $simulationConfig['current_cost_rate'] ?? 'Standard';

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
     * NEW METHOD: Starts a new simulation record for the current day.
     * This will insert a new row into `simulation_state` table.
     * Returns the ID of the new simulation record.
     */
    public function startNewSimulationRun()
    {
        try {
            $today = date('Y-m-d');
            $currentTime = date('Y-m-d H:i:s');

            // Fetch initial config for the new simulation from simulation_config
            $configQuery = "SELECT num_houses, daily_quota_per_house_wh, current_cost_rate FROM simulation_config WHERE id = 1";
            $simConfig = $this->db->fetchSingleData($configQuery);
            $numHouses = $simConfig['num_houses'] ?? 20;
            $dailyQuotaPerHouse = $simConfig['daily_quota_per_house_wh'] ?? 7000;
            $currentCostRate = $simConfig['current_cost_rate'] ?? 'Standard';


            // Insert a new simulation record with initial values
            $query = "
                INSERT INTO simulation_state (
                    simulation_date, start_time, current_cost_rate, total_grid_import_wh,
                    total_co2_emissions, current_battery_level_wh, simulated_minutes,
                    num_houses, daily_quota_per_house_wh, weather_condition,
                    total_solar_output_wh, total_wind_output_wh, total_consumption_wh,
                    battery_status, total_renewable_available_wh, last_updated_at
                )
                VALUES (
                    :simulation_date, :start_time, :current_cost_rate, 0.00,
                    0.00, 5000.00, 0,
                    :num_houses, :daily_quota_per_house_wh, 'N/A',
                    0.00, 0.00, 0.00,
                    'Idle', 0.00, NOW()
                )
            ";
            $params = [
                ':simulation_date' => $today,
                ':start_time' => $currentTime,
                ':current_cost_rate' => $currentCostRate,
                ':num_houses' => $numHouses,
                ':daily_quota_per_house_wh' => $dailyQuotaPerHouse
            ];
            $newSimId = $this->db->insertAndGetId($query, $params); // Assuming DB class has this method

            if ($newSimId) {
                $this->sendJsonResponse('success', 'New simulation started.', 201, ['simulationId' => $newSimId]);
            } else {
                $this->sendJsonResponse('error', 'Failed to start new simulation.', 500);
            }
        } catch (PDOException $e) {
            error_log('Start new simulation database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error starting simulation.', 500);
        } catch (Exception $e) {
            error_log('General error starting new simulation: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error starting simulation.', 500);
        }
    }


    /**
     * UPDATED METHOD: Updates a specific simulation run's cumulative data.
     * Expects POST request with simulationId, and current (non-cumulative) values
     * for grid import, CO2, battery, simulated time, etc.
     */
    public function updateSimulationData()
    {
        error_log("updateSimulationData: Method called."); // Log entry

        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("updateSimulationData: JSON decode error: " . json_last_error_msg());
                $this->sendJsonResponse('error', 'Invalid JSON input.', 400);
            }

            // error_log("updateSimulationData: Received data: " . print_r($data, true)); // Verbose logging

            // Validate required fields
            if (!isset(
                $data['simulationId'],
                $data['simulated_minutes'],
                $data['current_battery_level_wh'],
                $data['grid_import_wh_interval'],
                $data['co2_emissions_kg_interval'],
                $data['battery_status'],
                $data['weather_condition'],
                $data['current_cost_rate'],
                $data['solar_output_wh_interval'],
                $data['wind_output_wh_interval'],
                $data['consumption_wh_interval'],
                $data['renewable_available_wh_interval']
            )) {
                error_log("updateSimulationData: Missing required fields in received data. Data: " . json_encode($data));
                $this->sendJsonResponse('error', 'Missing required simulation data fields.', 400);
            }

            $simulationId = (int)$data['simulationId'];
            $simulatedMinutes = (int)$data['simulated_minutes'];
            $currentBatteryLevel = (float)$data['current_battery_level_wh'];
            $gridImportInterval = (float)$data['grid_import_wh_interval']; // Incremental for this interval
            $co2EmissionsInterval = (float)$data['co2_emissions_kg_interval']; // Incremental for this interval
            $batteryStatus = $data['battery_status'];
            $weatherCondition = $data['weather_condition'];
            $currentCostRate = $data['current_cost_rate'];
            $solarOutputInterval = (float)$data['solar_output_wh_interval']; // Incremental for this interval
            $windOutputInterval = (float)$data['wind_output_wh_interval'];   // Incremental for this interval
            $consumptionInterval = (float)$data['consumption_wh_interval'];  // Incremental for this interval
            $renewableAvailableInterval = (float)$data['renewable_available_wh_interval']; // Incremental for this interval

            // 1. Fetch current accumulated values from the database for this specific simulationId
            $stmtFetch = $this->db->connection()->prepare("
                SELECT total_grid_import_wh, total_co2_emissions, total_solar_output_wh,
                        total_wind_output_wh, total_consumption_wh, total_renewable_available_wh
                FROM simulation_state
                WHERE id = :simulationId
            ");
            $stmtFetch->bindParam(':simulationId', $simulationId, PDO::PARAM_INT);
            $stmtFetch->execute();
            $currentDbState = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if (!$currentDbState) {
                error_log("updateSimulationData: Simulation ID {$simulationId} not found in DB for update.");
                $this->sendJsonResponse('error', 'Simulation record not found for update.', 404);
            }

            // Calculate new accumulated totals by adding current interval values to existing DB values
            $newTotalGridImport = (float)$currentDbState['total_grid_import_wh'] + $gridImportInterval;
            $newTotalCO2Emissions = (float)$currentDbState['total_co2_emissions'] + $co2EmissionsInterval;
            $newTotalSolarOutput = (float)$currentDbState['total_solar_output_wh'] + $solarOutputInterval;
            $newTotalWindOutput = (float)$currentDbState['total_wind_output_wh'] + $windOutputInterval;
            $newTotalConsumption = (float)$currentDbState['total_consumption_wh'] + $consumptionInterval;
            $newTotalRenewableAvailable = (float)$currentDbState['total_renewable_available_wh'] + $renewableAvailableInterval;


            // 2. Update the simulation_state table for the specific simulationId with new cumulative totals
            $stmtUpdate = $this->db->connection()->prepare("
                UPDATE simulation_state
                SET
                    total_grid_import_wh = :total_grid_import_wh,
                    total_co2_emissions = :total_co2_emissions,
                    current_battery_level_wh = :current_battery_level_wh,
                    simulated_minutes = :simulated_minutes,
                    battery_status = :battery_status,
                    weather_condition = :weather_condition,
                    current_cost_rate = :current_cost_rate,
                    total_solar_output_wh = :total_solar_output_wh,
                    total_wind_output_wh = :total_wind_output_wh,
                    total_consumption_wh = :total_consumption_wh,
                    total_renewable_available_wh = :total_renewable_available_wh,
                    last_updated_at = NOW()
                WHERE id = :simulationId
            ");

            $stmtUpdate->bindParam(':total_grid_import_wh', $newTotalGridImport, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_co2_emissions', $newTotalCO2Emissions, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':current_battery_level_wh', $currentBatteryLevel, PDO::PARAM_STR); // Use STR for DECIMAL
            $stmtUpdate->bindParam(':simulated_minutes', $simulatedMinutes, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':battery_status', $batteryStatus, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':weather_condition', $weatherCondition, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':current_cost_rate', $currentCostRate, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_solar_output_wh', $newTotalSolarOutput, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_wind_output_wh', $newTotalWindOutput, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_consumption_wh', $newTotalConsumption, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':total_renewable_available_wh', $newTotalRenewableAvailable, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':simulationId', $simulationId, PDO::PARAM_INT);

            $stmtUpdate->execute();

            error_log("updateSimulationData: Database update successful for simId: " . $simulationId);

            $this->sendJsonResponse('success', 'Simulation data updated.', 200, [
                'simulationId' => $simulationId,
                'total_grid_import_wh_cumulative' => round($newTotalGridImport, 2),
                'total_co2_emissions_cumulative' => round($newTotalCO2Emissions, 2),
                'current_battery_level_wh' => round($currentBatteryLevel, 2),
                'simulated_minutes' => $simulatedMinutes
            ]);
        } catch (PDOException $e) {
            error_log('Simulation data update database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error during simulation update: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('General simulation data update error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error during simulation update: ' . $e->getMessage(), 500);
        }
    }

    /**
     * NEW METHOD: Updates the `end_time` of a specific simulation record.
     */
    public function endSimulationRun()
    {
        error_log("endSimulationRun: Method called.");
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['simulationId'])) {
                $this->sendJsonResponse('error', 'Invalid or missing simulationId.', 400);
            }

            $simulationId = (int)$data['simulationId'];
            $endTime = date('Y-m-d H:i:s');

            $query = "UPDATE simulation_state SET end_time = :end_time WHERE id = :simulationId";
            $params = [
                ':end_time' => $endTime,
                ':simulationId' => $simulationId
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Simulation run ended successfully.', 200);
        } catch (PDOException $e) {
            error_log('End simulation run database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error ending simulation.', 500);
        } catch (Exception $e) {
            error_log('General error ending simulation run: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error ending simulation.', 500);
        }
    }

    /**
     * NEW METHOD: Fetches the daily simulation summary for the current date.
     * If no simulation exists for today, returns default zero values.
     * This will be used to populate the main dashboard stats area.
     */
    public function getDailySimulationSummary()
    {
        try {
            $today = date('Y-m-d');
            // Fetch the latest updated record for today (in case multiple runs in a day, though logic implies one)
            $query = "SELECT * FROM simulation_state WHERE simulation_date = :today ORDER BY last_updated_at DESC LIMIT 1";
            $result = $this->db->fetchSingleData($query, [':today' => $today]);

            if ($result) {
                // Ensure numeric values are cast to float for consistent JSON output
                $result['total_grid_import_wh'] = (float)$result['total_grid_import_wh'];
                $result['total_co2_emissions'] = (float)$result['total_co2_emissions'];
                $result['current_battery_level_wh'] = (float)$result['current_battery_level_wh'];
                $result['simulated_minutes'] = (int)$result['simulated_minutes'];
                $result['num_houses'] = (int)$result['num_houses'];
                $result['daily_quota_per_house_wh'] = (int)$result['daily_quota_per_house_wh'];
                $result['total_solar_output_wh'] = (float)$result['total_solar_output_wh'];
                $result['total_wind_output_wh'] = (float)$result['total_wind_output_wh'];
                $result['total_consumption_wh'] = (float)$result['total_consumption_wh'];
                $result['total_renewable_available_wh'] = (float)$result['total_renewable_available_wh'];

                $this->sendJsonResponse('success', 'Daily simulation summary fetched.', 200, ['data' => $result]);
            } else {
                // Return default zero values if no simulation for today
                $this->sendJsonResponse('success', 'No simulation data for today.', 200, ['data' => [
                    'id' => null,
                    'simulation_date' => $today,
                    'start_time' => null,
                    'end_time' => null,
                    'current_cost_rate' => 'Standard',
                    'total_grid_import_wh' => 0.00,
                    'total_co2_emissions' => 0.00,
                    'current_battery_level_wh' => 5000.00, // Default for display
                    'simulated_minutes' => 0,
                    'num_houses' => 20,
                    'daily_quota_per_house_wh' => 7000,
                    'weather_condition' => 'N/A',
                    'total_solar_output_wh' => 0.00,
                    'total_wind_output_wh' => 0.00,
                    'total_consumption_wh' => 0.00,
                    'battery_status' => 'Idle',
                    'total_renewable_available_wh' => 0.00,
                    'last_updated_at' => null
                ]]);
            }
        } catch (PDOException $e) {
            error_log('Get daily simulation summary database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching daily summary.', 500);
        } catch (Exception $e) {
            error_log('General error fetching daily simulation summary: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching daily summary.', 500);
        }
    }


    /**
     * NEW METHOD: Fetches the current state of a specific simulation run.
     * Expects GET request with 'simulationId' in query parameters.
     * If 'simulationId' is not provided, it attempts to fetch the latest for today.
     * This will be used by the simulation dashboard to display live data.
     */
    public function getSimulationState()
    {
        $simulationId = $_GET['simulationId'] ?? null;

        try {
            $result = null;
            if ($simulationId) {
                $query = "SELECT * FROM simulation_state WHERE id = :simulationId";
                $result = $this->db->fetchSingleData($query, [':simulationId' => $simulationId]);
            } else {
                // If no specific ID, try to get the latest for today
                $today = date('Y-m-d');
                $query = "SELECT * FROM simulation_state WHERE simulation_date = :today ORDER BY last_updated_at DESC LIMIT 1";
                $result = $this->db->fetchSingleData($query, [':today' => $today]);
            }

            if ($result) {
                // Ensure numeric values are cast for consistent JSON output
                $result['total_grid_import_wh'] = (float)$result['total_grid_import_wh'];
                $result['total_co2_emissions'] = (float)$result['total_co2_emissions'];
                $result['current_battery_level_wh'] = (float)$result['current_battery_level_wh'];
                $result['simulated_minutes'] = (int)$result['simulated_minutes'];
                $result['num_houses'] = (int)$result['num_houses'];
                $result['daily_quota_per_house_wh'] = (int)$result['daily_quota_per_house_wh'];
                $result['total_solar_output_wh'] = (float)$result['total_solar_output_wh'];
                $result['total_wind_output_wh'] = (float)$result['total_wind_output_wh'];
                $result['total_consumption_wh'] = (float)$result['total_consumption_wh'];
                $result['total_renewable_available_wh'] = (float)$result['total_renewable_available_wh'];

                $this->sendJsonResponse('success', 'Simulation state fetched.', 200, ['simulationState' => $result]);
            } else {
                $this->sendJsonResponse('error', 'Simulation state not found.', 404);
            }
        } catch (PDOException $e) {
            error_log('Get simulation state database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching simulation state.', 500);
        } catch (Exception $e) {
            error_log('General error fetching simulation state: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching simulation state.', 500);
        }
    }


    // GET USER CONSUMPTION DATA
    public function getUserConsumptionData($userId)
    {
        try {
            $query = "SELECT * FROM consumption_logs WHERE user_id = :userId ORDER BY timestamp DESC";
            $params = [
                ':userId' => $userId
            ];

            $userSubData = $this->db->fetchAllData($query, $params);
            if (!empty($userSubData)) {
                return $userSubData;
            }
        } catch (PDOException $error) {
            echo "Failed to fetch the user subscription data!" . $error->getMessage();
        }

        return "No data found!";
    }


    // GET ONLINE USERS TO GIVE POWER
    public function getOnlineUserCount()
    {
        try {
            $onlineThresholdMinutes = 5; // Define what "recent activity" means
            $query = "SELECT COUNT(user_id) as online_count FROM user_state WHERE is_online = 1 AND last_active_at >= (NOW() - INTERVAL :minutes MINUTE)";
            $params = [':minutes' => $onlineThresholdMinutes];

            $result = $this->db->fetchSingleData($query, $params);

            if ($result && isset($result['online_count'])) {
                // This function would typically be called by your simulation backend
                // You might return just the number or use sendJsonResponse if it's an API endpoint itself.
                return (int)$result['online_count'];
            }
            return 0; // No online users
        } catch (PDOException $e) {
            error_log('Database error fetching online user count: ' . $e->getMessage());
            return 0; // Handle error gracefully
        } catch (Exception $e) {
            error_log('General error fetching online user count: ' . $e->getMessage());
            return 0; // Handle error gracefully
        }
    }

    // Existing/New method: Get daily simulation summary
    public function getNewDailySimulationSummary()
    {
        header('Content-Type: application/json');
        try {
            // Fetch the most recent daily summary
            $query = "SELECT * FROM daily_simulation_summaries ORDER BY report_date DESC LIMIT 1";
            // Corrected: Pass an empty array for $params
            $summary = $this->db->fetchSingleData($query, []);

            if ($summary) {
                echo json_encode(['success' => true, 'data' => [
                    'total_solar_generated' => (float)$summary['total_solar_generated_wh'],
                    'total_wind_generated' => (float)$summary['total_wind_generated_wh'],
                    'total_consumption' => (float)$summary['total_consumption_wh'],
                    'total_grid_import' => (float)($summary['total_grid_import_wh'] ?? 0),
                    'total_grid_export' => (float)($summary['total_grid_export_wh'] ?? 0),
                    'total_co2_emissions' => (float)($summary['total_co2_emissions_kg'] ?? 0)
                ]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No daily summary found.']);
            }
        } catch (PDOException $e) {
            error_log("DB Error in getDailySimulationSummary: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            error_log("Error in getDailySimulationSummary: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }

    // Existing/New method: Update simulation state
    public function updateSimulationState($data)
    {
        header('Content-Type: application/json');
        try {
            // Validate and sanitize input data
            $solar = $data['total_solar_generated'] ?? 0;
            $wind = $data['total_wind_generated'] ?? 0;
            $consumption = $data['total_consumption'] ?? 0;
            $gridImport = $data['total_grid_import'] ?? 0;
            $gridExport = $data['total_grid_export'] ?? 0;
            $co2 = $data['co2_emissions'] ?? 0;
            $cost = $data['current_cost'] ?? 0;
            $batteryLevel = $data['battery_level'] ?? 0;
            $simulatedTime = $data['simulated_time_minutes'] ?? 0;
            $liveConsumption = $data['live_consumption'] ?? 0;
            $currentCostRate = $data['current_cost_rate'] ?? 0;
            $dailyQuotaRemaining = $data['daily_quota_remaining'] ?? 0;
            $currentDailyConsumption = $data['current_daily_consumption'] ?? 0;
            $numHouses = $data['num_houses'] ?? 0; // Assuming num_houses is sent from frontend for now

            // Get current date for simulation_date and current time for last_updated_time
            $simulationDate = date('Y-m-d');
            $lastUpdatedTime = date('Y-m-d H:i:s'); // Use full datetime for consistency

            // IMPORTANT: Using INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior
            $query = "INSERT INTO simulation_state_new (
                        simulation_date,
                        last_updated_time,
                        total_solar_output_wh,
                        total_wind_output_wh,
                        total_consumption_wh,
                        total_grid_import_wh,
                        total_grid_export_wh,
                        co2_emissions_kg,
                        current_cost_usd,
                        current_battery_level_wh,
                        simulated_time_minutes,
                        live_consumption_w,
                        current_cost_rate_usd_wh,
                        daily_quota_remaining_wh,
                        current_daily_consumption_wh,
                        num_houses
                    ) VALUES (
                        :simulation_date,
                        :last_updated_time,
                        :total_solar,
                        :total_wind,
                        :total_consumption,
                        :total_grid_import,
                        :total_grid_export,
                        :co2_emissions,
                        :current_cost,
                        :battery_level,
                        :simulated_time_minutes,
                        :live_consumption,
                        :current_cost_rate,
                        :daily_quota_remaining,
                        :current_daily_consumption,
                        :num_houses
                    )
                    ON DUPLICATE KEY UPDATE
                        last_updated_time = VALUES(last_updated_time),
                        total_solar_output_wh = VALUES(total_solar_output_wh),
                        total_wind_output_wh = VALUES(total_wind_output_wh),
                        total_consumption_wh = VALUES(total_consumption_wh),
                        total_grid_import_wh = VALUES(total_grid_import_wh),
                        total_grid_export_wh = VALUES(total_grid_export_wh),
                        co2_emissions_kg = VALUES(co2_emissions_kg),
                        current_cost_usd = VALUES(current_cost_usd),
                        current_battery_level_wh = VALUES(current_battery_level_wh),
                        simulated_time_minutes = VALUES(simulated_time_minutes),
                        live_consumption_w = VALUES(live_consumption_w),
                        current_cost_rate_usd_wh = VALUES(current_cost_rate_usd_wh),
                        daily_quota_remaining_wh = VALUES(daily_quota_remaining_wh),
                        current_daily_consumption_wh = VALUES(current_daily_consumption_wh),
                        num_houses = VALUES(num_houses);";

            $params = [
                ':simulation_date' => $simulationDate,
                ':last_updated_time' => $lastUpdatedTime,
                ':total_solar' => $solar,
                ':total_wind' => $wind,
                ':total_consumption' => $consumption,
                ':total_grid_import' => $gridImport,
                ':total_grid_export' => $gridExport,
                ':co2_emissions' => $co2,
                ':current_cost' => $cost,
                ':battery_level' => $batteryLevel,
                ':simulated_time_minutes' => $simulatedTime,
                ':live_consumption' => $liveConsumption,
                ':current_cost_rate' => $currentCostRate,
                ':daily_quota_remaining' => $dailyQuotaRemaining,
                ':current_daily_consumption' => $currentDailyConsumption,
                ':num_houses' => $numHouses
            ];

            $this->db->execute($query, $params);
            echo json_encode(['success' => true, 'message' => 'Simulation state updated successfully.']);
        } catch (PDOException $e) {
            error_log("DB Error in updateSimulationState: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            error_log("Error in updateSimulationState: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }


    // Existing/New method: Get simulation configuration
    public function getNewSimulationConfig()
    {
        header('Content-Type: application/json');
        try {
            $query = "SELECT num_houses, daily_quota_per_house_wh, current_cost_rate FROM simulation_config LIMIT 1";
            // Corrected: Pass an empty array for $params
            $config = $this->db->fetchSingleData($query, []);

            if ($config) {
                echo json_encode(['success' => true, 'data' => [
                    'num_houses' => (int)$config['num_houses'],
                    'daily_quota' => (int)$config['daily_quota_per_house_wh'], // Corrected column name based on query
                    'cost_rate' => (float)$config['current_cost_rate'] // Corrected column name based on query
                ]]);
            } else {
                // If no config found, return defaults and optionally insert them
                $defaultConfig = [
                    'num_houses' => 5,
                    'daily_quota' => 10000,
                    'cost_rate' => 0.00015
                ];
                // Optionally, insert these defaults into the DB here if they don't exist
                // $this->db->execute("INSERT INTO simulation_config (num_houses, daily_quota_per_house_wh, current_cost_rate) VALUES (?, ?, ?)", array_values($defaultConfig));
                echo json_encode(['success' => true, 'data' => $defaultConfig, 'message' => 'Using default config.']);
            }
        } catch (PDOException $e) {
            error_log("DB Error in getSimulationConfig: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            error_log("Error in getSimulationConfig: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }



    // Existing/New method: Set simulation configuration
    public function setNewSimulationConfig($data)
    {
        header('Content-Type: application/json');
        try {
            // Validate and sanitize input
            $numHouses = $data['num_houses'] ?? 0;
            $dailyQuota = $data['daily_quota'] ?? 0;
            $costRate = $data['cost_rate'] ?? 0.0;

            // Check if a config already exists to decide between INSERT or UPDATE
            $existingConfig = $this->db->fetchSingleData("SELECT COUNT(*) FROM simulation_config", []); // Corrected
            if ($existingConfig && $existingConfig['COUNT(*)'] > 0) {
                // Update existing config (assuming only one row for config)
                $query = "UPDATE simulation_config SET num_houses = :num_houses, daily_quota_per_house_wh = :daily_quota, current_cost_rate = :cost_rate WHERE id = 1"; // Assuming ID 1 for single config row
            } else {
                // Insert new config
                $query = "INSERT INTO simulation_config (num_houses, daily_quota_per_house_wh, current_cost_rate) VALUES (:num_houses, :daily_quota, :cost_rate)";
            }

            $params = [
                ':num_houses' => $numHouses,
                ':daily_quota' => $dailyQuota,
                ':cost_rate' => $costRate
            ];

            $this->db->execute($query, $params);
            echo json_encode(['success' => true, 'message' => 'Simulation configuration updated successfully.']);
        } catch (PDOException $e) {
            error_log("DB Error in setSimulationConfig: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            error_log("Error in setSimulationConfig: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }
}
