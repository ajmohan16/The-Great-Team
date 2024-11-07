<?php
include 'database_connection.php'; // Include your MySQL DB connection

function getDiscussions($topic_type, $topic_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT d.discussion_id, d.topic_type, d.title, d.content, d.created_at, u.username
        FROM discussions d
        JOIN users u ON d.created_by = u.user_id
        WHERE d.topic_type = ? AND d.topic_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$topic_type, $topic_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Example usage: replace with actual values from a form or query string
$topic_type = 'album';  // Example values: 'album', 'artist', 'song'
$topic_id = 1;

$discussions = getDiscussions($topic_type, $topic_id);

header('Content-Type: application/json');
echo json_encode($discussions);
?>

