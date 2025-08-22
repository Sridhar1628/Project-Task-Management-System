<?php
// config.example.php
// Copy this to config.php and fill with your local credentials.
// Keep config.php OUT of Git by using .gitignore.

$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'your_db_username';
$password   = getenv('DB_PASS') ?: 'your_db_password';
$database   = getenv('DB_NAME') ?: 'your_db_name';

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>
