<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\DB; // Assuming your DB class is in App\Models
use PDOException;
use Exception;

class UserController
{
    private $db;

    public function __construct()
    {
        // Set the JSON header first for API responses
        header("Content-Type: application/json; charset=UTF-8");

        $this->db = new DB();

        if (!$this->db->connection()) {
            error_log("FATAL: Database connection failed in UserController constructor.");
            $this->sendJsonResponse('error', 'Database connection error.', 500);
        }
    }

    /**
     * Helper method to send a consistent JSON response and terminate script execution.
     *
     * @param string $status The status of the response.
     * @param string $message A human-readable message.
     * @param int $statusCode The HTTP status code.
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
     * Lists all users with user_type = 'client'.
     * Supports filtering by username or user ID via 'search' query parameter.
     * Requires admin authentication.
     */
    public function listClientUsers()
    {
        // Basic admin authentication check
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            $this->sendJsonResponse('error', 'Unauthorized access. Admin privileges required.', 403);
        }

        $searchTerm = $_GET['search'] ?? '';
        $params = [];
        $whereClause = "WHERE u.user_type = 'client'";

        if (!empty($searchTerm)) {
            $whereClause .= " AND (u.username LIKE :searchTerm OR u.id = :searchId)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
            $params[':searchId'] = (int)$searchTerm; // Try to cast to int for ID search
        }

        try {
            $query = "
                SELECT
                    u.id,
                    u.username,
                    u.email,
                    u.user_type,
                    u.created_at
                FROM
                    users u
                {$whereClause}
                ORDER BY u.username ASC
            ";

            // Using fetchAllData from the updated DB class
            $users = $this->db->fetchAllData($query, $params);

            if ($users === false) {
                throw new Exception("Failed to fetch users from database.");
            }

            $this->sendJsonResponse('success', 'Client users fetched successfully.', 200, $users);
        } catch (PDOException $e) {
            error_log('List client users database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching users.', 500);
        } catch (Exception $e) {
            error_log('General error listing client users: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching users.', 500);
        }
    }

    /**
     * Fetches comprehensive details for a specific user.
     * Includes data from users, client_profiles, user_appliances, and latest consumption_logs.
     * Requires admin authentication.
     *
     * @param int $userId The ID of the user to fetch details for.
     */
    public function getUserDetails($userId)
    {
        // Basic admin authentication check
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            $this->sendJsonResponse('error', 'Unauthorized access. Admin privileges required.', 403);
        }

        if (!$userId) {
            $this->sendJsonResponse('error', 'User ID is required.', 400);
        }

        try {
            // Fetch user basic info
            $userQuery = "SELECT id, username, email, user_type, created_at FROM users WHERE id = :userId";
            // Using fetchSingleData from the updated DB class
            $user = $this->db->fetchSingleData($userQuery, [':userId' => $userId]);

            if (!$user) {
                $this->sendJsonResponse('error', 'User not found.', 404);
            }

            $userDetails = $user;

            // Fetch client profile (if exists)
            $profileQuery = "SELECT email, display_username, daily_quota_wh FROM client_profiles WHERE user_id = :userId";
            // Using fetchSingleData from the updated DB class
            $profile = $this->db->fetchSingleData($profileQuery, [':userId' => $userId]);
            $userDetails['profile'] = $profile ?: null;

            // Fetch user appliances
            $appliancesQuery = "
                SELECT id, appliance_id, is_on, last_updated_at
                FROM user_appliances
                WHERE user_id = :userId
            ";
            // Using fetchAllData from the updated DB class
            $appliances = $this->db->fetchAllData($appliancesQuery, [':userId' => $userId]);
            $userDetails['appliances'] = $appliances ?: [];

            // Fetch latest consumption log
            $consumptionQuery = "
                SELECT current_consumption_w, daily_consumption_wh, timestamp
                FROM consumption_logs
                WHERE user_id = :userId
                ORDER BY timestamp DESC
                LIMIT 1
            ";
            // Using fetchSingleData from the updated DB class
            $latestConsumption = $this->db->fetchSingleData($consumptionQuery, [':userId' => $userId]);
            $userDetails['latest_consumption'] = $latestConsumption ?: null;

            $this->sendJsonResponse('success', 'User details fetched successfully.', 200, $userDetails);
        } catch (PDOException $e) {
            error_log('Get user details database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error fetching user details.', 500);
        } catch (Exception $e) {
            error_log('General error fetching user details: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error fetching user details.', 500);
        }
    }

    /**
     * Updates a user's information.
     * Can update fields in 'users' and 'client_profiles' tables.
     * Requires admin authentication.
     */
    public function updateUser()
    {
        // Basic admin authentication check
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            $this->sendJsonResponse('error', 'Unauthorized access. Admin privileges required.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $userId = $input['userId'] ?? null;
        $username = $input['username'] ?? null;
        $email = $input['email'] ?? null;
        $firstName = $input['first_name'] ?? null;
        $lastName = $input['last_name'] ?? null;
        $address = $input['address'] ?? null;
        $dailyQuotaWh = $input['daily_quota_wh'] ?? null;
        $currentBalance = $input['current_balance'] ?? null;

        if (!$userId) {
            $this->sendJsonResponse('error', 'User ID is required for update.', 400);
        }

        try {
            $this->db->beginTransaction(); // Using beginTransaction from DB class

            // Update 'users' table
            $updateUserQuery = "UPDATE users SET username = :username, email = :email WHERE id = :userId";
            $this->db->execute($updateUserQuery, [ // Using execute from DB class
                ':username' => $username,
                ':email' => $email,
                ':userId' => $userId
            ]);

            // Update or Insert into 'client_profiles' table
            $updateProfileQuery = "
                INSERT INTO client_profiles (user_id, first_name, last_name, address, daily_quota_wh, current_balance, updated_at)
                VALUES (:userId, :firstName, :lastName, :address, :dailyQuotaWh, :currentBalance, NOW())
                ON DUPLICATE KEY UPDATE
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    address = VALUES(address),
                    daily_quota_wh = VALUES(daily_quota_wh),
                    current_balance = VALUES(current_balance),
                    updated_at = NOW()
            ";
            $this->db->execute($updateProfileQuery, [ // Using execute from DB class
                ':userId' => $userId,
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':address' => $address,
                ':dailyQuotaWh' => $dailyQuotaWh,
                ':currentBalance' => $currentBalance
            ]);

            $this->db->commit(); // Using commit from DB class
            $this->sendJsonResponse('success', 'User updated successfully.', 200);
        } catch (PDOException $e) {
            $this->db->rollBack(); // Using rollBack from DB class
            error_log('Update user database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error updating user: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            $this->db->rollBack(); // Using rollBack from DB class
            error_log('General error updating user: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error updating user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Deletes a user and all their associated data (profile, appliances, consumption logs).
     * Requires admin authentication.
     */
    public function deleteUser()
    {
        // Basic admin authentication check
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_state']) || $_SESSION['user_data']['user_type'] !== 'admin') {
            $this->sendJsonResponse('error', 'Unauthorized access. Admin privileges required.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['userId'] ?? null;

        if (!$userId) {
            $this->sendJsonResponse('error', 'User ID is required for deletion.', 400);
        }

        try {
            $this->db->beginTransaction(); // Using beginTransaction from DB class

            // Delete from dependent tables first to avoid foreign key constraints
            $this->db->execute("DELETE FROM user_appliances WHERE user_id = :userId", [':userId' => $userId]);
            $this->db->execute("DELETE FROM consumption_logs WHERE user_id = :userId", [':userId' => $userId]);
            $this->db->execute("DELETE FROM client_profiles WHERE user_id = :userId", [':userId' => $userId]);
            $this->db->execute("DELETE FROM user_subscriptions WHERE user_id = :userId", [':userId' => $userId]);

            // Finally, delete from the main users table
            $this->db->execute("DELETE FROM users WHERE id = :userId", [':userId' => $userId]);

            $this->db->commit(); // Using commit from DB class
            $this->sendJsonResponse('success', 'User and all associated data deleted successfully.', 200);
        } catch (PDOException $e) {
            $this->db->rollBack(); // Using rollBack from DB class
            error_log('Delete user database error: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Database error deleting user: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            $this->db->rollBack(); // Using rollBack from DB class
            error_log('General error deleting user: ' . $e->getMessage());
            $this->sendJsonResponse('error', 'Server error deleting user: ' . $e->getMessage(), 500);
        }
    }
}
