<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection settings
$pdo = new PDO('mysql:host=localhost;dbname=QueueExample', 'testUser', 'Test@1234');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

// Declare the queue with durability enabled
$channel->queue_declare('reviews_queue', false, true, false, false);

echo "Waiting for review messages. To exit, press CTRL+C\n";

// Callback function to process each message
$callback = function($msg) use ($pdo) {
    $data = json_decode($msg->body, true);

    // Validate and retrieve data
    $user_id = $data['user_id'] ?? null;
    $song_id = $data['song_id'] ?? null;
    $rating = $data['rating'] ?? null;
    $review_text = $data['review_text'] ?? '';

    if (is_null($user_id) || is_null($song_id) || is_null($rating)) {
        echo "Error: Missing required data (user_id, song_id, or rating). Review not added.\n";
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        return;
    }

    try {
        // Insert the review into the database
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, song_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $song_id, $rating, $review_text]);

        echo "Review for song ID $song_id added to database by user ID $user_id\n";

        // Acknowledge the message after successful insertion
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
};

// Start consuming messages with manual acknowledgment
$channel->basic_consume('reviews_queue', '', false, false, false, false, $callback);

// Keep the script running to listen for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close the channel and connection
$channel->close();
$connection->close();
?>

