<?php
// db_config.php — Your database connection
// Matches your XAMPP setup

$host = "Localhost";
$user = "root";        // XAMPP default
$pass = "";            // XAMPP default (no password)
$dbname = "laundry_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4"); 