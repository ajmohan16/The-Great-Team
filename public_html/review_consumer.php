<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection settings
$pdo = new PDO('mysql:host=localhost;dbname=QueueExample', 'testUser', 'Test@1234');

// RabbitMQ Connection
$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

// Declare the queue
$channel->queue_declare('reviews_queue', false, true, false, false);

echo 'Waiting for messages. To exit press CTRL+C', "\n";

// Callback to process each message
$callback = function($msg) use ($pdo) {
    $data = json_decode($msg->body, true);

    // Check and assign values with defaults
    $user_id = isset($data['user_id']) ? $data['user_id'] : null;
    $song_id = isset($data['song_id']) ? $data['song_id'] : null;
    $rating = isset($data['rating']) ? $data['rating'] : null;
    $review_text = isset($data['review_text']) ? $data['review_text'] : '';

    // Validate required data
    if (is_null($user_id) || is_null($song_id) || is_null($rating)) {
        echo "Error: Missing required data (user_id, song_id, or rating). Review not added.\n";
        return;
    }

    // Insert the review into the database
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, song_id, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $song_id, $rating, $review_text]);

    echo "Review for song ID $song_id added to database by user ID $user_id\n";
};

// Start consuming messages
$channel->basic_consume('reviews_queue', '', false, true, false, false, $callback);

while($channel->is_consuming()) {
    $channel->wait();
}

// Close the connection
$channel->close();
$connection->close();
?>

