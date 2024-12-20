<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting script...\n";

// RabbitMQ connection settings
$rabbitHost = '172.26.184.4';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$rabbitVhost = 'testHost_QA';

try {
    echo "Connecting to RabbitMQ...\n";
    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();
    echo "Connection to RabbitMQ successful.\n";

    // Declare the queue (change "notifications" to the correct queue name)
    $channel->queue_declare('notifications', false, true, false, false);
    echo "Queue 'notifications' declared successfully.\n";

    // Callback function that will be called each time a message is received
    $callback = function ($msg) {
        echo ' [x] Received message: ', $msg->body, "\n";
    };

    // Consume messages from the queue
    $channel->basic_consume('notifications', '', false, true, false, false, $callback);

    echo "Waiting for messages...\n";

    // Wait for messages indefinitely
    while ($channel->is_consuming()) {
        $channel->wait();
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

