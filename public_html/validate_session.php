<?php
// validate_session.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_token = $_POST['session_token'];

    // Check if the session token exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE session_token = :session_token");
    $stmt->execute(['session_token' => $session_token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['message' => 'Session is valid.', 'username' => $user['username']]);
    } else {
        echo json_encode(['message' => 'Invalid session token.']);
    }
}
?>
