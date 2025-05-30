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
}
