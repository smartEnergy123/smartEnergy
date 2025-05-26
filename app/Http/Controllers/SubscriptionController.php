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

    // Helper function to send JSON responses (ensure this is consistent across your controller)
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
        $planType = $input['planType'] ?? null; // e.g., 'monthly_standard', 'daily_top_up'
        $amountPaid = $input['amountPaid'] ?? null;
        $quotaGrantedWh = $input['quotaGrantedWh'] ?? null;

        if (!$userId || !$planType || $amountPaid === null || $quotaGrantedWh === null) {
            $this->sendJsonResponse('error', 'Missing subscription data.', 400);
        }

        try {
            // Use DB class methods for transactions
            $this->db->beginTransaction();

            $endDate = null;
            $isActive = true;

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
                INSERT INTO user_subscriptions (user_id, plan_type, amount_paid, quota_granted_wh, start_date, end_date, is_active)
                VALUES (:userId, :planType, :amountPaid, :quotaGrantedWh, NOW(), :endDate, :isActive)
            ";
            $this->db->execute($insertSubscriptionQuery, [
                ':userId' => $userId,
                ':planType' => $planType,
                ':amountPaid' => $amountPaid,
                ':quotaGrantedWh' => $quotaGrantedWh,
                ':endDate' => $endDate,
                ':isActive' => $isActive
            ]);

            // Update client_profiles.daily_quota_wh
            $updateClientProfileQuery = "";
            if ($planType === 'monthly_standard') {
                // For a monthly subscription, set their base daily quota
                $updateClientProfileQuery = "
                    INSERT INTO client_profiles (user_id, daily_quota_wh, updated_at)
                    VALUES (:userId, :quotaGrantedWh, NOW())
                    ON DUPLICATE KEY UPDATE daily_quota_wh = :quotaGrantedWh, updated_at = NOW()
                ";
                $this->db->execute($updateClientProfileQuery, [
                    ':userId' => $userId,
                    ':quotaGrantedWh' => $quotaGrantedWh
                ]);

                // Also, reset their daily consumption for a fresh start with new subscription
                // This assumes consumption_logs stores daily_consumption_wh for the current day.
                // It's better to update the current day's log entry or ensure it's reset by a daily cron.
                // For now, we'll zero out the current day's log entry if it exists.
                $this->db->execute("UPDATE consumption_logs SET daily_consumption_wh = 0 WHERE user_id = :userId AND DATE(timestamp) = CURDATE()", [':userId' => $userId]);
            } elseif ($planType === 'daily_top_up') {
                // For a top-up, add to their current daily quota
                $updateClientProfileQuery = "
                    INSERT INTO client_profiles (user_id, daily_quota_wh, updated_at)
                    VALUES (:userId, :quotaGrantedWh, NOW())
                    ON DUPLICATE KEY UPDATE daily_quota_wh = daily_quota_wh + :quotaGrantedWh, updated_at = NOW()
                ";
                $this->db->execute($updateClientProfileQuery, [
                    ':userId' => $userId,
                    ':quotaGrantedWh' => $quotaGrantedWh
                ]);
            }

            // Use DB class method for commit
            $this->db->commit();

            $this->sendJsonResponse('success', 'Subscription processed successfully.', 200);
        } catch (PDOException $e) {
            // Use DB class method for rollback
            $this->db->rollBack();
            error_log('Subscription processing database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database operation failed: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            // Use DB class method for rollback
            $this->db->rollBack();
            error_log('Subscription processing general error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error processing subscription.', 500);
        }
    }
}
