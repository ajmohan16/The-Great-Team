<?php
include 'database_connection.php'; // Include your MySQL DB connection

function getComments($discussion_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.created_at, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.discussion_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$discussion_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Retrieve discussion_id from URL parameters or command line
if (php_sapi_name() === 'cli') {
    $discussion_id = $argv[1] ?? null;
} else {
    $discussion_id = isset($_GET['discussion_id']) ? (int)$_GET['discussion_id'] : null;
}

if (!$discussion_id) {
    echo json_encode(["status" => "error", "message" => "Please provide a valid discussion_id."]);
    exit;
}

$comments = getComments($discussion_id);

header('Content-Type: application/json');
echo json_encode($comments);
?>

