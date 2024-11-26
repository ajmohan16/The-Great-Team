<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=QueueExample', 'testUser', 'Test@1234');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// RabbitMQ connection
$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

// Declare the queues
$channel->queue_declare('recommendation_requests', false, true, false, false);
$channel->queue_declare('recommendation_responses', false, true, false, false);

// Function to generate song recommendations
function getRecommendations($user_id, $pdo) {
    try {
        $likedStmt = $pdo->prepare("
            SELECT s.song_id, s.title
            FROM user_likes_dislikes uld
            JOIN songs s ON uld.song_id = s.song_id
            WHERE uld.user_id = ? AND uld.liked = 1
        ");
        $likedStmt->execute([$user_id]);
        $likedSongs = $likedStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($likedSongs)) {
            return ["status" => "error", "message" => "No liked songs found for recommendations."];
        }

        $recommendStmt = $pdo->prepare("
            SELECT DISTINCT s.song_id, s.title, s.album_id
            FROM songs s
            WHERE (s.album_id IN (
                SELECT s.album_id FROM user_likes_dislikes uld
                JOIN songs s ON uld.song_id = s.song_id
                WHERE uld.user_id = ? AND uld.liked = 1
            ) OR s.album_id IN (
                SELECT s.album_id FROM playlist_songs ps
                JOIN songs s ON ps.song_id = s.song_id
                JOIN playlists p ON ps.playlist_id = p.playlist_id
                WHERE p.user_id = ?
            )) AND s.song_id NOT IN (
                SELECT song_id FROM user_likes_dislikes WHERE user_id = ?
            )
            LIMIT 10
        ");
        $recommendStmt->execute([$user_id, $user_id, $user_id]);
        $recommendedSongs = $recommendStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($recommendedSongs)) {
            return ["status" => "error", "message" => "No recommendations found based on user preferences."];
        }

        return ["status" => "success", "data" => $recommendedSongs];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to retrieve recommendations: " . $e->getMessage()];
    }
}

// Callback to process messages
$callback = function ($msg) use ($pdo, $channel) {
    $request = json_decode($msg->body, true);
    $user_id = $request['user_id'] ?? null;

    if (!$user_id) {
        $response = ["status" => "error", "message" => "Please provide user_id."];
    } else {
        $response = getRecommendations($user_id, $pdo);
    }

    $responseMessage = new AMQPMessage(
        json_encode($response),
        ['correlation_id' => $msg->get('correlation_id'), 'delivery_mode' => 2] // Persistent message
    );

    // Publish the response to the `recommendation_responses` queue
    $channel->basic_publish($responseMessage, '', 'recommendation_responses');

    // Acknowledge the message
    $msg->ack();
};

// Consume messages from the `recommendation_requests` queue
$channel->basic_consume('recommendation_requests', '', false, false, false, false, $callback);

echo "Waiting for recommendation requests. To exit, press CTRL+C\n";

// Keep the script running to listen for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close connections when done
$channel->close();
$connection->close();

