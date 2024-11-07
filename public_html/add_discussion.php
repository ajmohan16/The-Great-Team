<?php
include 'database_connection.php'; // Include your MySQL DB connection
require '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ connection settings
$rabbitHost = '172.26.233.84';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$rabbitVhost = 'testHost';
$discussion_queue = 'discussion_requests';

// Function to add a discussion to the database
function addDiscussion($topic_type, $topic_id, $created_by, $title, $content, $pdo, $channel) {
    $stmt = $pdo->prepare("
        INSERT INTO discussions (topic_type, topic_id, created_by, title, content)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$topic_type, $topic_id, $created_by, $title, $content]);
    
    // Send message to RabbitMQ
    $messageData = json_encode(['title' => $title, 'content' => $content]);
    $msg = new AMQPMessage($messageData, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, '', 'discussion_notifications');
    echo " [x] Discussion added and notification sent\n";
}

// Initialize RabbitMQ connection
$connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
$channel = $connection->channel();
$channel->queue_declare($discussion_queue, false, true, false, false);

// Example usage: replace these variables with actual input from a form
$topic_type = 'album';
$topic_id = 1;
$created_by = 1; 
$title = 'Discussion Title';
$content = 'This is the content of the discussion.';

// Add discussion
addDiscussion($topic_type, $topic_id, $created_by, $title, $content, $pdo, $channel);

// Close the RabbitMQ connection
$channel->close();
$connection->close();
?>

