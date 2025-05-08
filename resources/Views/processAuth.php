<?php

use App\Http\Controllers\Auth\AuthController;
use App\Services\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';


if (isset($_POST['loginBtn'])) {
    processLogin();
} else {
    processRegistration();
}

// Process user login
function processLogin()
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo MessageService::errorMessage("Ensure all fields are filled!!!");
    } else {
        $authUser = new AuthController;
        $authUserResult = $authUser->login((string)$email, (string)$password);

        if ($authUserResult === false) {
            echo MessageService::errorMessage("User Does not exists...");
        } else {
            $_SESSION['user_data'] = $authUserResult;
            if ($_SESSION['user_data']['user_type'] === 'admin') {  //redirect the users to their respective dashboards
                header("Location: /admin/dashboard"); //admin
                exit;
            } else {
                header("Location: /client/dashboard"); //client
                exit;
            }
        }
    }
}

// process user registration
function processRegistration()
{
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        echo MessageService::errorMessage("Ensure all fields are filled!!!");
    } else {

        $registerUser = new AuthController;

        if ($registerUser->register((string) $username, (string) $email, (string)$password) != false) {
            echo MessageService::successMessage("User Does not exists...");
        } else {
            echo MessageService::errorMessage("Failed to register this user... processRegistration function processAuth");
        }
    }
}
