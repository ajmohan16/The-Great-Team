<?php
include 'database_connection.php'; // Include your MySQL DB connection

header('Content-Type: application/json');

// Function to retrieve comments for a specific discussion
function getComments($discussion_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.comment_id, c.comment_text AS content, c.created_at, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.discussion_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$discussion_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ["status" => "success", "data" => $comments];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to retrieve comments: " . $e->getMessage()];
    }
}

// Retrieve discussion_id from GET parameter or command line
if (php_sapi_name() === 'cli') {
    $discussion_id = $argv[1] ?? null;
} else {
    $discussion_id = isset($_GET['discussion_id']) ? (int)$_GET['discussion_id'] : null;
}

// Validate input
if (!$discussion_id) {
    echo json_encode(["status" => "error", "message" => "Please provide a valid discussion_id."]);
    exit;
}

// Fetch comments and output the response
$response = getComments($discussion_id, $pdo);
echo json_encode($response);
?>

