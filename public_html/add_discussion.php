<?php
include 'database_connection.php'; // Include your MySQL DB connection
require '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ connection settings
$rabbitHost = '172.26.184.4';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$rabbitVhost = 'testHost';
$discussion_queue = 'discussion_requests';
$notification_queue = 'discussion_notifications';

// Function to add a discussion and send notification
function addDiscussion($topic_type, $topic_id, $created_by, $title, $content, $pdo, $channel) {
    try {
        // Insert the discussion into the database
        $stmt = $pdo->prepare("
            INSERT INTO discussions (topic_type, topic_id, created_by, title, content, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$topic_type, $topic_id, $created_by, $title, $content]);
        
        // Send message to RabbitMQ notification queue
        $messageData = json_encode([
            'title' => $title, 
            'content' => $content,
            'topic_type' => $topic_type,
            'topic_id' => $topic_id,
            'created_by' => $created_by
        ]);
        $msg = new AMQPMessage($messageData, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, '', $notification_queue);

        echo json_encode(["status" => "success", "message" => "Discussion added and notification sent"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to add discussion: " . $e->getMessage()]);
    }
}

// Initialize RabbitMQ connection
$connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
$channel = $connection->channel();
$channel->queue_declare($discussion_queue, false, true, false, false);
$channel->queue_declare($notification_queue, false, true, false, false);

// Retrieve POST parameters for dynamic input
$topic_type = $_POST['topic_type'] ?? 'album';  // Default to 'album' for testing
$topic_id = $_POST['topic_id'] ?? 1;
$created_by = $_POST['created_by'] ?? 1;
$title = $_POST['title'] ?? 'Default Title';
$content = $_POST['content'] ?? 'This is the default content of the discussion.';

// Validate input
if (empty($topic_type) || empty($topic_id) || empty($created_by) || empty($title) || empty($content)) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

// Add the discussion and send notification
addDiscussion($topic_type, $topic_id, $created_by, $title, $content, $pdo, $channel);

// Close RabbitMQ connection
$channel->close();
$connection->close();
?>

