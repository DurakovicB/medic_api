<?php

// Allow from any origin
header("Access-Control-Allow-Origin: *");

// Allow specific HTTP methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // If you are using cookies, you should also add 'Access-Control-Allow-Credentials' header
    header("Access-Control-Allow-Credentials: true");
    // Terminate the script if it's a preflight request
    exit(0);
}


require 'vendor/autoload.php';
include_once('routes.php');