<?php

// MySQL Database connection settings
$mysqlHost = 'localhost';
$mysqlDB = 'QueueExample';
$mysqlUser = 'testUser';
$mysqlPassword = 'Test@1234';

try {
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all users
    $stmt = $pdo->query("SELECT user_id, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);

        // Update the user's password with the hashed version
        $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
        $updateStmt->execute(['password' => $hashedPassword, 'user_id' => $user['user_id']]);

        echo "Password for user ID {$user['user_id']} has been updated.\n";
    }

    echo "All passwords have been hashed successfully.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

