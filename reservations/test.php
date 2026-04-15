<?php
// We are trying to connect to MySQL on localhost
// using username "root", no password, and a database named "cinema"
$conn = new mysqli("localhost", "root", "", "cinema");// Check if the connection failed
if ($conn->connect_error) {
    die("DB failed: " . $conn->connect_error);
}

// If it works, print this message
echo "Database connected successfully!";
?>