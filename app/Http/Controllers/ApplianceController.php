<?php
// app/Http/Controllers/ApplianceController.php

namespace App\Http\Controllers;

use App\Models\DB; // Import your custom DB class
use PDOException;   // Import PDOException for explicit error handling

class ApplianceController
{
    private $db;

    public function __construct()
    {
        $this->db = new DB();
    }

    public function toggle()
    {
        header("Content-Type: application/json; charset=UTF-8");

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['applianceId'], $data['isOn'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
            exit;
        }

        $userId = $data['userId'];
        $applianceId = $data['applianceId'];
        $isOn = (bool)$data['isOn'];

        try {
            // Use your DB class's execute method for INSERT/UPDATE
            $query = "
                INSERT INTO user_appliances (user_id, appliance_id, is_on)
                VALUES (:userId, :applianceId, :isOn)
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

            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Appliance state updated.']);
        } catch (PDOException $e) {
            // Log database errors (e.g., to a file, not directly to user in production)
            error_log('Appliance toggle database error: ' . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'Database operation failed.']);
        }
        exit;
    }

    public function logConsumption()
    {
        header("Content-Type: application/json; charset=UTF-8");

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['userId'], $data['currentConsumptionW'], $data['dailyConsumptionWh'], $data['timestamp'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
            exit;
        }

        $userId = $data['userId'];
        $currentConsumptionW = $data['currentConsumptionW'];
        $dailyConsumptionWh = $data['dailyConsumptionWh'];
        $timestamp = $data['timestamp'];

        try {
            // Use your DB class's execute method for INSERT
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

            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Consumption logged successfully.']);
        } catch (PDOException $e) {
            error_log('Consumption log database error: ' . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'Database operation failed.']);
        }
        exit;
    }

    public function getCostRate()
    {
        header("Content-Type: application/json; charset=UTF-8");
        try {
            // Use your DB class's fetchSingleData method for SELECT
            $query = "SELECT current_cost_rate FROM simulation_state WHERE id = 1";
            $result = $this->db->fetchSingleData($query);

            if ($result) {
                http_response_code(200); // OK
                echo json_encode(['status' => 'success', 'costRate' => $result['current_cost_rate']]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Simulation state not found.']);
            }
        } catch (PDOException $e) {
            error_log('Get cost rate database error: ' . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        exit;
    }
}
