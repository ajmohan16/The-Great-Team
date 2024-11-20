<?php
// MySQL Database connection settings
$mysqlHost = 'localhost';
$mysqlDB = 'QueueExample';
$mysqlUser = 'testUser';
$mysqlPassword = 'Test@1234';

try {
    // Establish a PDO connection to the MySQL database
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


