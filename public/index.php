<?php

if (!session_start()) {
    session_start();
}


require_once __DIR__ . '/../routes/web.php';
