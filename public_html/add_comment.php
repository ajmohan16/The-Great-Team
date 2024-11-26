<?php
include 'database_connection.php';
require_once __DIR__ . '/vendor/autoload.php'; // Ensure RabbitMQ library is loaded

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header('Content-Type: application/json');

// RabbitMQ configuration
$rabbitmq_host = '172.26.233.84';
$rabbitmq_port = 5672;
$rabbitmq_user = 'test';
$rabbitmq_password = 'test';
$rabbitmq_virtual_host = 'testHost';
$rabbitmq_queue = 'add_comment';

// Function to send a message to RabbitMQ
function sendToRabbitMQ($data, $queue) {
    global $rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_password, $rabbitmq_virtual_host;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_password, $rabbitmq_virtual_host);
        $channel = $connection->channel();

        // Declare the queue
        $channel->queue_declare($queue, false, true, false, false);

        // Prepare the message
        $messageBody = json_encode($data);
        $message = new AMQPMessage($messageBody, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        // Publish the message to the queue
        $channel->basic_publish($message, '', $queue);

        // Close the channel and connection
        $channel->close();
        $connection->close();

        return ["status" => "success", "message" => "Message sent to RabbitMQ."];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to send message to RabbitMQ: " . $e->getMessage()];
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

// Prepare data for RabbitMQ
$data = [
    "discussion_id" => $discussion_id,
    "user_id" => $user_id,
    "comment_text" => $comment_text,
    "created_at" => date('Y-m-d H:i:s')
];

// Send data to RabbitMQ
$response = sendToRabbitMQ($data, $rabbitmq_queue);
echo json_encode($response);
?>
