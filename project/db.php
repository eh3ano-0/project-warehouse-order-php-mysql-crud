<?php
$servername = "localhost";
$username = "";
$password = "";
$dbname = "database";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>