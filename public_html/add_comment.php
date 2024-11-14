<?php
include 'database_connection.php';

header('Content-Type: application/json');

// Function to add a comment to the database
function addComment($discussion_id, $user_id, $comment_text, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (discussion_id, user_id, comment_text, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$discussion_id, $user_id, $comment_text]);

        return ["status" => "success", "message" => "Comment added successfully!"];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to add comment: " . $e->getMessage()];
    }
}

// Retrieve parameters from POST data or CLI arguments
if (php_sapi_name() === 'cli') {
    $discussion_id = $argv[1] ?? null;
    $user_id = $argv[2] ?? null;
    $comment_text = $argv[3] ?? null;
} else {
    $discussion_id = $_POST['discussion_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $comment_text = $_POST['comment_text'] ?? null;
}

// Validate input
if (!$discussion_id || !$user_id || empty($comment_text)) {
    echo json_encode(["status" => "error", "message" => "Please provide discussion_id, user_id, and comment_text."]);
    exit;
}

// Add the comment and output the response
$response = addComment($discussion_id, $user_id, $comment_text, $pdo);
echo json_encode($response);
?>

