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
}
