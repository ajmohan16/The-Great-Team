<?php
require 'get_spotify_token.php';
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ Configuration
$rabbitmq_host = '172.26.184.4';
$request_queue = 'artist_search_requests';
$response_queue = 'artist_search_responses';

function sendArtistSearchRequest($artist_name) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

        // Prepare search request message
        $messageBody = json_encode(['artist_name' => $artist_name]);
        $message = new AMQPMessage($messageBody, [
            'reply_to' => $response_queue
        ]);

        // Send search request
        $channel->basic_publish($message, '', $request_queue);

        // Listen for a response
        $response = null;
        $callback = function ($msg) use (&$response) {
            $response = json_decode($msg->body, true);
        };

        // Set up the consumer
        $channel->basic_consume($response_queue, '', false, true, false, false, $callback);

        // Wait for the response message
        while (!$response) {
            $channel->wait(null, false, 10);  // Timeout after 10 seconds
        }

        // Close the connection
        $channel->close();
        $connection->close();

        return $response;
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
        return null;
    }
}

// Main execution
$artist_name = isset($_GET['artist']) ? $_GET['artist'] : null;
if (!$artist_name) {
    die(json_encode(['error' => 'Artist name is required.']));
}

$response = sendArtistSearchRequest($artist_name);
header('Content-Type: application/json');
echo json_encode($response);
?>

