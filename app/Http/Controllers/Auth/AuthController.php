<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\DB;
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

        return false;
    }

    // Register a new user
    public function register(string $username, string $email, string $password, string $userType)
    {
        try {

            //Determine if the user already exists
            $isUser = $this->isUser((string)$email);
            if (!empty($isUser)) {
                $_SESSION['error_message'] = MessageService::errorMessage("User Already exists...");
            } else {
                // Register a new user
                $db = new DB;
                $query = "INSERT INTO users (username, email, user_password, user_type) VALUES (:username, :email, :password, :uTyp)";
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $params = [
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':uTyp' => $userType
                ];
                $result =  $db->execute($query, $params);
                if (empty($result)) {
                    return false;
                } else {
                    return true;
                }
            }
        } catch (PDOException $error) {
            echo MessageService::errorMessage("Failed to relate to the database and register this user...") . $error->getMessage();
            return false;
        }
    }

    // Determine if a user exists
    public function isUser(string $email)
    {
        try {
            $db = new DB;
            $query = "SELECT * FROM users WHERE email=:email";
            $params = [':email' => $email];
            $result = $db->fetchSingleData($query, $params);
            return $result; //user found
        } catch (PDOException $error) {
            echo MessageService::errorMessage("Failed to relate to the database and find this user isUser??...") . $error->getMessage();
            return false;
        }
        return false; //user not found
    }
}
