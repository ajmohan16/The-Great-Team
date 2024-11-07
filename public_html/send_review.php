<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ connection settings
$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

// Declare the queue with the same settings as the consumer
$channel->queue_declare('reviews_queue', false, true, false, false);

// Sample data with all required fields
$data = [
    'user_id' => 1,                // Replace with actual user ID
    'song_id' => 3,                // Replace with actual song ID
    'rating' => 4,                 // Replace with actual rating (e.g., 1-5)
    'review_text' => "Great track!" // Optional review text
];

// Encode the data to JSON and prepare the message
$messageBody = json_encode($data);
$message = new AMQPMessage($messageBody);

// Publish the message to the 'reviews_queue'
$channel->basic_publish($message, '', 'reviews_queue');
echo "Review message sent to queue.\n";

// Close the channel and connection
$channel->close();
$connection->close();
