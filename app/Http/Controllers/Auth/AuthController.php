<?php

namespace app\Http\Controllers\Auth;

use APP\Models\DB;
use PDOException;

require_once __DIR__ . "/../../../../vendor/autoload.php";

class AuthController
{
    public function login(string $email, string $password)
    {
        try {
            $db = new DB;
        } catch (PDOException $error) {
        };
    }
}
