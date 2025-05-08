<?php

declare(strict_types=1);

namespace app\Http\Controllers\Auth;

use APP\Models\DB;
use App\Services\MessageService;
use PDOException;

require_once __DIR__ . "/../../../../vendor/autoload.php";

class AuthController
{
    public function login(string $email, string $password)
    {
        try {
            $db = new DB;
            $query = "SELECT * FROM users WHERE email = :email";
            $params = [
                ":email" => $email
            ];
            $result = $db->fetchSingleData($query, $params);
            if (empty($result)) {
                return false; //user deos not exists...
            } else {
                $userID = $result['id'];
                $dbpassword = $result['user_password'];
                $dbEmail = $result['email'];
                $userType = $result['user_type'];
                $username = $result['username'];

                if (password_verify($password, $dbpassword)) {
                    //$userdata will store all user relevant data
                    $userData = [
                        'id' => $userID,
                        'username' => $username,
                        'email' => $dbEmail,
                        'user_type' => $userType
                    ];

                    return $userData;
                }
            }
        } catch (PDOException $error) {
            echo MessageService::errorMessage("Failed to login this user...") . $error->getMessage();
            return false;
        };
    }
}
