<?php
require 'get_spotify_token.php';
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendAlbumSearchRequest($artist_id, $access_token) {
    $connection = new AMQPStreamConnection('172.26.184.4', 5672, 'test', 'test', 'testHost');
    $channel = $connection->channel();
    $channel->queue_declare('album_search_requests', false, false, false, false);
    $channel->queue_declare('album_search_responses', false, false, false, false);

    // Prepare the Spotify API request
    $url = "https://api.spotify.com/v1/artists/$artist_id/albums";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    // Send the albums response through RabbitMQ
    $messageBody = json_encode(['artist_id' => $artist_id, 'albums' => json_decode($response, true)]);
    $message = new AMQPMessage($messageBody, ['reply_to' => 'album_search_responses']);
    $channel->basic_publish($message, '', 'album_search_requests');

    // Listen for the response
    $response = null;
    $callback = function ($msg) use (&$response) {
        $response = json_decode($msg->body, true);
    };
    $channel->basic_consume('album_search_responses', '', false, true, false, false, $callback);

    // Wait for the response message
    while (!$response) {
        $channel->wait(null, false, 10);  // Timeout after 10 seconds
    }

    $channel->close();
    $connection->close();
    return $response;
}

// Main execution
$artist_id = $_GET['artist_id'] ?? null;
if (!$artist_id) {
    die(json_encode(['error' => 'Artist ID is required.']));
}

$access_token = getSpotifyToken();
if (!$access_token) {
    die(json_encode(['error' => 'Failed to retrieve access token.']));
}

$response = sendAlbumSearchRequest($artist_id, $access_token);
header('Content-Type: application/json');
echo json_encode($response);
?>

