<?php
// register.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "Username and password are required.";
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert the user into the database
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
    try {
        $stmt->execute(['username' => $username, 'password_hash' => $password_hash]);
        echo "Registration successful!";
    } catch (PDOException $e) {
        echo "Registration failed: " . $e->getMessage();
    }
}
?>
