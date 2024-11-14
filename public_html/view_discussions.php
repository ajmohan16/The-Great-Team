<?php
include 'database_connection.php'; // Include your MySQL DB connection

header('Content-Type: application/json');

// Function to retrieve discussions based on topic type and topic ID
function getDiscussions($topic_type, $topic_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.discussion_id, d.topic_type, d.title, d.content, d.created_at, u.username
            FROM discussions d
            JOIN users u ON d.created_by = u.user_id
            WHERE d.topic_type = ? AND d.topic_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$topic_type, $topic_id]);
        $discussions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ["status" => "success", "data" => $discussions];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to retrieve discussions: " . $e->getMessage()];
    }
}

// Retrieve GET parameters for dynamic input
$topic_type = $_GET['topic_type'] ?? null;
$topic_id = $_GET['topic_id'] ?? null;

// Validate input
if (!$topic_type || !$topic_id) {
    echo json_encode(["status" => "error", "message" => "Please provide topic_type and topic_id."]);
    exit;
}

// Fetch discussions and output the response
$response = getDiscussions($topic_type, $topic_id, $pdo);
echo json_encode($response);
?>

