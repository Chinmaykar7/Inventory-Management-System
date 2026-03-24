<?php
// db_connect.php

$host = "localhost";
$username = "root"; // Default XAMPP MySQL username
$password = "";     // Default XAMPP MySQL password (blank)
$database = "inventory_db";

// Create the connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Optional: Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");
?>