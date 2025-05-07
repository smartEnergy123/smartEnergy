<?php

if (!session_start()) {
    session_start();
}


require __DIR__ . '/../routes/web.php';
