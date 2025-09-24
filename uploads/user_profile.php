<?php
session_start();
require_once 'db_connect.php';


if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in (optional, currently commented out)
    // header('Location: login.php');
    // exit;
}

echo 'Hello';


