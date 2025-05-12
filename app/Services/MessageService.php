<?php

namespace App\Services;

class MessageService
{

    public static function errorMessage(string $msg)
    {

        return '<div class="error">' . $msg . '</div>';
    }

    public static function successMessage(string $msg)
    {

        return '<div class="success">' . $msg . '</div>';
    }
}
