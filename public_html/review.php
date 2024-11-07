<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection settings
$pdo = new PDO('mysql:host=localhost;dbname=QueueExample', 'testUser', 'Test@1234');

$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

$channel->queue_declare('reviews_queue', false, true, false, false);

echo 'Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) use ($pdo) {
    $data = json_decode($msg->body, true);
    $track_id = $data['track_id'];
    $rating = $data['rating'];
    $description = $data['description'];

    // Insert the review into the database
    $stmt = $pdo->prepare("INSERT INTO reviews (track_id, rating, description) VALUES (?, ?, ?)");
    $stmt->execute([$track_id, $rating, $description]);

    echo "Review for track ID $track_id added to database\n";
};

$channel->basic_consume('reviews_queue', '', false, true, false, false, $callback);

while($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
