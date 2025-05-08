<?php

namespace App\Services;

class MessageService
{

    public static function errorMessage(string $msg)
    {

        return '<div class="error">' . $msg . '</div>';

        echo '
        <script>
            const error = document.getElementsByClassName[".error"][0];
            if(error){
                setTimeout(()=>{
                error.style.color = "red";
                }, 9000);
            }
        </script>
        ';
    }

    public static function successMessage(string $msg)
    {

        return '<div class="success">' . $msg . '</div>';

        echo '
        <script>
            const success = document.getElementsByClassName[".success"][0];
            if(success){
                setTimeout(()=>{
                success.style.color = "green";
                }, 9000);
            }
        </script>
        ';
    }
}
