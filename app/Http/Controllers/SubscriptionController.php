<?php

namespace App\Http\Controllers;

use App\Models\DB; // Assuming your DB class is in App\Models
use DateTime;
use PDOException; // Import PDOException

class SubscriptionController
{
    private $db;

    public function __construct()
    {
        $this->db = new DB();
        if (!$this->db->connection()) {
            error_log("FATAL: Database connection failed in SubscriptionController constructor.");
            $this->sendJsonResponse('error', 'Database connection error.', 500);
        }
    }

    // Helper function to send JSON responses
    private function sendJsonResponse($status, $message, $statusCode = 200, $data = [])
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Processes a user subscription or top-up.
     * Inserts into user_subscriptions and updates client_profiles.
     */
    public function processSubscription()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $userId = $input['userId'] ?? null;
        $username = $input['userName'] ?? null;
        $userEmail = $input['userEmail'] ?? null;
        $planType = $input['planType'] ?? null; // e.g., 'monthly_standard', 'daily_top_up'
        $amountPaid = $input['amountPaid'] ?? null;
        $quotaGrantedWh = $input['quotaGrantedWh'] ?? null;

        if (!$userId || !$planType || $amountPaid === null || $quotaGrantedWh === null) {
            $this->sendJsonResponse('error', 'Missing subscription data.', 400);
        }

        try {
            $this->db->beginTransaction();

            $endDate = null;
            $isActive = true;
            $transactionId = uniqid('sub_', true);

            if ($planType === 'monthly_standard') {
                $endDate = (new DateTime())->modify('+1 month')->format('Y-m-d H:i:s');
            } elseif ($planType === 'daily_top_up') {
                $endDate = null; // Top-ups don't have a specific end date, they add to current quota
            } else {
                $this->db->rollBack();
                $this->sendJsonResponse('error', 'Invalid plan type.', 400);
            }

            // Insert into user_subscriptions table
            $insertSubscriptionQuery = "
                INSERT INTO user_subscriptions (user_id, plan_type, amount_paid, quota_granted_wh, start_date, end_date, is_active, transaction_id)
                VALUES (:userId, :planType, :amountPaid, :quotaGrantedWh, NOW(), :endDate, :isActive, :transactionId)
            ";
            $insertSubscriptionParams = [
                ':userId' => $userId,
                ':planType' => $planType,
                ':amountPaid' => $amountPaid,
                ':quotaGrantedWh' => $quotaGrantedWh,
                ':endDate' => $endDate,
                ':isActive' => $isActive,
                ':transactionId' => $transactionId
            ];
            // error_log("DEBUG: user_subscriptions INSERT Query: " . $insertSubscriptionQuery); // Keep for further debugging if needed
            // error_log("DEBUG: user_subscriptions INSERT Params: " . json_encode($insertSubscriptionParams)); // Keep for further debugging if needed
            $this->db->execute($insertSubscriptionQuery, $insertSubscriptionParams);


            // Update client_profiles.daily_quota_wh
            $updateClientProfileQuery = "";
            if ($planType === 'monthly_standard') {
                // For a monthly subscription, set their base daily quota
                $updateClientProfileQuery = "
                    INSERT INTO client_profiles (user_id,email, display_username, daily_quota_wh, updated_at)
                    VALUES (:userId, :userEmail, :username, :quotaGrantedWh, NOW())
                    ON DUPLICATE KEY UPDATE daily_quota_wh = :updateQuotaGrantedWh, updated_at = NOW()
                ";
                $updateClientProfileParams = [
                    ':userId' => $userId,
                    ':userEmail' => $userEmail,
                    ':username' => $username,
                    ':quotaGrantedWh' => $quotaGrantedWh,
                    ':updateQuotaGrantedWh' => $quotaGrantedWh // IMPORTANT: New parameter for the UPDATE clause
                ];
                // error_log("DEBUG: client_profiles INSERT/UPDATE (monthly) Query: " . $updateClientProfileQuery); // Keep for further debugging if needed
                // error_log("DEBUG: client_profiles INSERT/UPDATE (monthly) Params: " . json_encode($updateClientProfileParams)); // Keep for further debugging if needed

                $this->db->execute($updateClientProfileQuery, $updateClientProfileParams);

                // Reset their daily consumption for a fresh start with new subscription
                $resetConsumptionQuery = "UPDATE consumption_logs SET daily_consumption_wh = 0 WHERE user_id = :userId AND DATE(timestamp) = CURDATE()";
                $resetConsumptionParams = [':userId' => $userId];
                // error_log("DEBUG: consumption_logs UPDATE (monthly) Query: " . $resetConsumptionQuery); // Keep for further debugging if needed
                // error_log("DEBUG: consumption_logs UPDATE (monthly) Params: " . json_encode($resetConsumptionParams)); // Keep for further debugging if needed
                $this->db->execute($resetConsumptionQuery, $resetConsumptionParams);
            } elseif ($planType === 'daily_top_up') {
                // For a top-up, add to their current daily quota
                $updateClientProfileQuery = "
                    INSERT INTO client_profiles (user_id, daily_quota_wh, updated_at)
                    VALUES (:userId, :quotaGrantedWh, NOW())
                    ON DUPLICATE KEY UPDATE daily_quota_wh = daily_quota_wh + :updateQuotaGrantedWh, updated_at = NOW()
                ";
                $updateClientProfileParams = [
                    ':userId' => $userId,
                    ':quotaGrantedWh' => $quotaGrantedWh,
                    ':updateQuotaGrantedWh' => $quotaGrantedWh // IMPORTANT: New parameter for the UPDATE clause
                ];
                // error_log("DEBUG: client_profiles INSERT/UPDATE (top-up) Query: " . $updateClientProfileQuery); // Keep for further debugging if needed
                // error_log("DEBUG: client_profiles INSERT/UPDATE (top-up) Params: " . json_encode($updateClientProfileParams)); // Keep for further debugging if needed
                $this->db->execute($updateClientProfileQuery, $updateClientProfileParams);
            }

            $this->db->commit();

            $this->sendJsonResponse('success', 'Subscription processed successfully.', 200);
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Subscription processing database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Subscription processing general error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error processing subscription.', 500);
        }
    }

    // GET USER SUBSCRIPTION DATA
    public function getUserSubscriptionData($userId)
    {
        try {
            $query = "SELECT * FROM user_subscriptions WHERE user_id = :userId ORDER BY start_date DESC";
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
