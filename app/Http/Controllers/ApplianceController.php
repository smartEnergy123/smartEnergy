<?php
// app/Http/Controllers/ApplianceController.php

namespace App\Http\Controllers;


use APP\Models\DB;
use PDOException;

class ApplianceController
{
    private $db;

    public function __construct()
    {
        // Instantiate your DB class. Autoloader will find App\Models\DB.
        $this->db = new DB();
        // Ensure the connection is established or handle connection errors early
        if (!$this->db->connection()) {
            // Handle connection failure, e.g., log error and return a 500 response
            error_log("Database connection failed in ApplianceController constructor.");
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database connection error.']);
            exit;
        }
    }

    private function sendJsonResponse($status, $message, $statusCode = 200, $data = [])
    {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($statusCode);
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
        exit;
    }

    public function toggle()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['applianceId'], $data['state'])) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $applianceId = $data['applianceId'];
        $isOn = (bool)$data['state']; // 'state' from JS corresponds to 'is_on' in DB

        try {
            // Update the `user_appliances` table
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

    // Renamed from logConsumption to currentConsumption to match client-side API call
    public function currentConsumption()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Ensure client sends currentConsumptionW. dailyConsumptionWh is optional for this endpoint.
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['currentConsumptionW'])) {
            $this->sendJsonResponse('error', 'Invalid or missing data.', 400);
        }

        $userId = $data['userId'];
        $currentConsumptionW = $data['currentConsumptionW'];
        $dailyConsumptionWh = $data['dailyConsumptionWh'] ?? 0; // Client sends this if it's available

        // Use server's timestamp for logging to ensure consistency
        $timestamp = date('Y-m-d H:i:s');

        try {
            // This endpoint logs instantaneous current consumption and daily aggregate.
            // You might want to refine this:
            // 1. Store only current_consumption_w in a high-frequency log table.
            // 2. Have a separate mechanism (e.g., a daily cron job or a different API call)
            //    to update `daily_consumption_wh` for a user's profile.
            // For now, we'll log both to `consumption_logs`.

            $query = "
                INSERT INTO consumption_logs (user_id, timestamp, current_consumption_w, daily_consumption_wh)
                VALUES (:userId, :timestamp, :currentConsumptionW, :dailyConsumptionWh)
            ";
            $params = [
                ':userId' => $userId,
                ':timestamp' => $timestamp,
                ':currentConsumptionW' => $currentConsumptionW,
                ':dailyConsumptionWh' => $dailyConsumptionWh // This assumes client sends it or it's 0
            ];
            $this->db->execute($query, $params);

            $this->sendJsonResponse('success', 'Current consumption logged.');
        } catch (PDOException $e) {
            error_log('Current consumption logging database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed.', 500);
        }
    }

    public function getCostRate()
    {
        try {
            // Check if the `simulation_state` table exists and has data.
            // You might need a more robust way to get the cost rate,
            // perhaps from a configuration or a more complex simulation state.
            $query = "SELECT current_cost_rate FROM simulation_state WHERE id = 1";
            $result = $this->db->fetchSingleData($query);

            if ($result) {
                $this->sendJsonResponse('success', 'Cost rate fetched.', 200, ['costRate' => $result['current_cost_rate']]);
            } else {
                // If the row doesn't exist, return a default or 'N/A'
                error_log('Simulation state (id=1) not found for cost rate. Returning default.');
                $this->sendJsonResponse('success', 'Cost rate not available.', 200, ['costRate' => 'N/A']);
            }
        } catch (PDOException $e) {
            error_log('Get cost rate database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching cost rate.', 500);
        }
    }

    public function dashboardData()
    {
        $userId = $_GET['userId'] ?? null;

        if (!$userId) {
            $this->sendJsonResponse('error', 'User ID is required.', 400);
        }

        try {
            // Fetch daily quota from client_profiles
            $quotaQuery = "SELECT daily_quota_wh FROM client_profiles WHERE user_id = :userId";
            $clientProfile = $this->db->fetchSingleData($quotaQuery, [':userId' => $userId]);
            $dailyQuotaWh = $clientProfile['daily_quota_wh'] ?? 7000; // Default if not found

            // Fetch current daily consumption from consumption_logs for today
            // This sums up `current_consumption_w` over the day and converts to Wh.
            // Ensure your `consumption_logs` table stores `current_consumption_w` as actual Watts.
            $today = date('Y-m-d');
            $consumptionQuery = "
                SELECT SUM(current_consumption_w * (TIME_TO_SEC(TIMEDIFF(NOW(), timestamp)) / 3600)) as total_wh_today
                FROM consumption_logs
                WHERE user_id = :userId AND DATE(timestamp) = :today
            ";
            // NOTE: The above query for `total_wh_today` is a *very rough* approximation
            // if `current_consumption_w` is instantaneous.
            // A more accurate daily consumption would sum `daily_consumption_wh` if that's
            // what's being logged, or sum `current_consumption_w` over fixed intervals.
            // For simplicity, let's just sum `daily_consumption_wh` if it's logged,
            // or return 0 if it's not reliably accumulated.
            // If `daily_consumption_wh` is always sent from client, sum that.
            // If `current_consumption_w` is the only thing logged, you need a more robust
            // server-side aggregation for `total_wh_today`.

            // Let's simplify: if `daily_consumption_wh` is logged, sum that.
            // Otherwise, we'll need to rethink how `currentDailyConsumptionWh` is calculated.
            $consumptionQuery = "
                SELECT SUM(daily_consumption_wh) as total_wh_today
                FROM consumption_logs
                WHERE user_id = :userId AND DATE(timestamp) = :today
            ";
            $dailyConsumptionResult = $this->db->fetchSingleData($consumptionQuery, [
                ':userId' => $userId,
                ':today' => $today
            ]);
            $currentDailyConsumptionWh = $dailyConsumptionResult['total_wh_today'] ?? 0;


            // Fetch appliance states for the user
            // Ensure your DB->fetchAllData returns an array of associative arrays
            $applianceStatesQuery = "SELECT appliance_id as id, is_on as state FROM user_appliances WHERE user_id = :userId";
            $applianceStates = $this->db->fetchAllData($applianceStatesQuery, [':userId' => $userId]);

            // If fetchAllData returns a single row or false when no data, ensure it returns an empty array for iteration
            if (!is_array($applianceStates)) {
                $applianceStates = [];
            }


            $this->sendJsonResponse('success', 'Dashboard data fetched.', 200, [
                'dailyQuotaWh' => (float)$dailyQuotaWh,
                'currentDailyConsumptionWh' => (float)$currentDailyConsumptionWh,
                'applianceStates' => $applianceStates
            ]);
        } catch (PDOException $e) {
            error_log('Dashboard data fetch error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Failed to fetch dashboard data.', 500);
        }
    }
}
