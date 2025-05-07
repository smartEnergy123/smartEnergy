<?php

$request = $_SERVER['REQUEST_URI'];

$request = str_replace($request, '', '/smartEnergy');

echo $request;
