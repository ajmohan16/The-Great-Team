<?php
include 'database_connection.php';

// Check if running from the command line or a web request
if (php_sapi_name() === 'cli') {
    // Running from the command line
    $discussion_id = $argv[1] ?? null;
    $user_id = $argv[2] ?? null;
    $comment_text = $argv[3] ?? null;
} else {
    // Running from a web request
    $discussion_id = $_POST['discussion_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $comment_text = $_POST['comment_text'] ?? null;
}

// Ensure all required data is provided
if (!$discussion_id || !$user_id || !$comment_text) {
    echo json_encode(["status" => "error", "message" => "Please provide discussion_id, user_id, and comment_text."]);
    exit;
}

// Function to add a comment
function addComment($discussion_id, $user_id, $comment_text) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO comments (discussion_id, user_id, comment_text)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$discussion_id, $user_id, $comment_text]);
    echo json_encode(["status" => "success", "message" => "Comment added successfully!"]);
}

// Add the comment to the database
addComment($discussion_id, $user_id, $comment_text);

