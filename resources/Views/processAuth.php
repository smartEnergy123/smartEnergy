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
            header("Location: /dashboard");
            exit;
        }
    }
}

// process user registration
function processRegistration() {}
