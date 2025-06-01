<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use PDO;
use DateTime;
use Exception;
use PDOException;
use App\Models\DB;

class ReportController
{
    private $db;

    public function __construct()
    {
        // Set the JSON header first for API responses
        header("Content-Type: application/json; charset=UTF-8");

        $this->db = new DB();

        if (!$this->db->connection()) {
            error_log("FATAL: Database connection failed in ReportController constructor.");
            $this->sendJsonResponse('error', 'Database connection error.', 500);
        }
    }

    /**
     * Helper method to send a consistent JSON response and terminate script execution.
     * Copied from ApplianceController for consistency.
     *
     * @param string $status The status of the raesponse (e.g., 'success', 'error').
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
     * Fetches daily simulation summaries for a given date range.
     * Expects GET parameters: startDate (YYYY-MM-DD), endDate (YYYY-MM-DD).
     */
    public function getDailySummaries()
    {
        try {
            $startDate = $_GET['startDate'] ?? null;
            $endDate = $_GET['endDate'] ?? null;

            if (!$startDate || !$endDate) {
                $this->sendJsonResponse('error', 'Start date and end date are required.', 400);
            }

            // Basic date validation
            if (!\DateTime::createFromFormat('Y-m-d', $startDate) || !\DateTime::createFromFormat('Y-m-d', $endDate)) {
                $this->sendJsonResponse('error', 'Invalid date format. Use YYYY-MM-DD.', 400);
            }

            $conn = $this->db->connection();
            $stmt = $conn->prepare("SELECT * FROM daily_simulation_summaries WHERE report_date BETWEEN :start_date AND :end_date ORDER BY report_date ASC");
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert numeric strings to actual numbers for JavaScript consumption
            foreach ($summaries as &$summary) {
                $summary['total_solar_generated_wh'] = (float)$summary['total_solar_generated_wh'];
                $summary['total_wind_generated_wh'] = (float)$summary['total_wind_generated_wh'];
                $summary['total_consumption_wh'] = (float)$summary['total_consumption_wh'];
                $summary['net_energy_balance_wh'] = (float)$summary['net_energy_balance_wh'];
                $summary['battery_level_start_wh'] = (float)$summary['battery_level_start_wh'];
                $summary['battery_level_end_wh'] = (float)$summary['battery_level_end_wh'];
                $summary['peak_consumption_w'] = (float)$summary['peak_consumption_w'];
                $summary['peak_production_w'] = (float)$summary['peak_production_w'];
                $summary['num_active_houses'] = (int)$summary['num_active_houses'];
            }

            $this->sendJsonResponse('success', 'Daily summaries fetched successfully.', 200, ['summaries' => $summaries]);
        } catch (PDOException $e) {
            error_log('Database error in getDailySummaries: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching daily summaries.', 500);
        } catch (Exception $e) {
            error_log('General error in getDailySummaries: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'An unexpected error occurred.', 500);
        }
    }


    /**
     * Fetches detailed simulation state history for a given date range.
     * This might return a large dataset, so consider pagination or stricter date limits for production.
     * Expects GET parameters: startDate (YYYY-MM-DD), endDate (YYYY-MM-DD).
     */
    public function getSimulationStateHistory()
    {
        try {
            $startDate = $_GET['startDate'] ?? null;
            $endDate = $_GET['endDate'] ?? null;

            if (!$startDate || !$endDate) {
                $this->sendJsonResponse('error', 'Start date and end date are required.', 400);
            }

            // Basic date validation
            if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
                $this->sendJsonResponse('error', 'Invalid date format. Use YYYY-MM-DD.', 400);
            }

            $conn = $this->db->connection();
            // Assuming 'current_time' in simulation_state is DATETIME and can be filtered by date part
            $stmt = $conn->prepare("SELECT * FROM simulation_state WHERE DATE(current_time) BETWEEN :start_date AND :end_date ORDER BY current_time ASC");
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert numeric strings to actual numbers
            foreach ($history as &$record) {
                $record['temperature'] = (float)$record['temperature'];
                $record['wind_speed'] = (float)$record['wind_speed'];
                $record['solar_irradiance'] = (float)$record['solar_irradiance'];
                $record['total_co2_emissions'] = (float)$record['total_co2_emissions'];
                $record['current_battery_level_wh'] = (float)$record['current_battery_level_wh'];
                $record['simulated_minutes'] = (int)$record['simulated_minutes'];
                $record['num_houses'] = (int)$record['num_houses'];
                $record['daily_quota_per_house_wh'] = (float)$record['daily_quota_per_house_wh'];
                $record['total_solar_output_wh'] = (float)$record['total_solar_output_wh'];
                $record['total_wind_output_wh'] = (float)$record['total_wind_output_wh'];
                $record['total_consumption_wh'] = (float)$record['total_consumption_wh'];
                $record['total_renewable_available_wh'] = (float)$record['total_renewable_available_wh'];
            }

            $this->sendJsonResponse('success', 'Simulation state history fetched successfully.', 200, ['history' => $history]);
        } catch (PDOException $e) {
            error_log('Database error in getSimulationStateHistory: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching simulation state history.', 500);
        } catch (Exception $e) {
            error_log('General error in getSimulationStateHistory: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'An unexpected error occurred.', 500);
        }
    }

    /**
     * Counts the number of users currently marked as online in the user_state table.
     * An "online" user is defined as is_online = 1 and last_active_at within a recent threshold.
     */
    public function getOnlineUserCount()
    {
        try {
            // Define what "recent activity" means (e.g., last 5 minutes)
            $onlineThresholdMinutes = 5;
            $query = "SELECT COUNT(user_id) as online_count FROM user_state WHERE is_online = 1 AND last_active_at >= (NOW() - INTERVAL :minutes MINUTE)";
            $params = [':minutes' => $onlineThresholdMinutes];

            $result = $this->db->fetchSingleData($query, $params);

            if ($result && isset($result['online_count'])) {
                $this->sendJsonResponse('success', 'Online user count fetched.', 200, ['onlineCount' => (int)$result['online_count']]);
            } else {
                $this->sendJsonResponse('success', 'No online users found.', 200, ['onlineCount' => 0]);
            }
        } catch (PDOException $e) {
            error_log('Database error fetching online user count: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching online user count.', 500);
        } catch (Exception $e) {
            error_log('General error fetching online user count: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching online user count.', 500);
        }
    }
}
