<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// MySQL Database connection settings
$mysqlHost = 'localhost';
$mysqlDB = 'QueueExample';
$mysqlUser = 'testUser';
$mysqlPassword = 'Test@1234';

// RabbitMQ connection settings
$rabbitHost = '172.26.233.84';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$rabbitVhost = 'testHost'; // Set the virtual host here
$request_queue = 'login_requests';

function verifyLoginCredentials($username, $password) {
    global $mysqlHost, $mysqlDB, $mysqlUser, $mysqlPassword;

    try {
        // Connect to MySQL
        $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to check username and password_hash
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND password = :password");
        $stmt->execute(['username' => $username, 'password' => $password]);

        return $stmt->fetch() ? true : false;

    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
        return false;
    }
}

// Send response back to the reply queue
function sendLoginResponse($reply_to, $correlation_id, $username, $success) {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost;

    // Connect to RabbitMQ
    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();

    // Declare the reply queue
    $channel->queue_declare($reply_to, false, false, false, false);

    // Create response message with correlation_id
    $responseBody = json_encode([
        'username' => $username,
        'login_success' => $success,
    ]);
    $message = new AMQPMessage(
        $responseBody,
        ['correlation_id' => $correlation_id]
    );

    // Publish response message to the reply queue
    $channel->basic_publish($message, '', $reply_to);
    echo "Login response sent for user: $username, success: $success\n";

    // Close the connection
    $channel->close();
    $connection->close();
}

// Consume login requests and verify credentials
function consumeLoginRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost, $request_queue;

    // Connect to RabbitMQ
    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();

    // Declare the request queue
    $channel->queue_declare($request_queue, false, false, false, false);

    // Callback function to handle login requests
    $callback = function($msg) {
        $data = json_decode($msg->body, true);
        $username = $data['username'];
        $password = $data['password'];
        $reply_to = $msg->get('reply_to');
        $correlation_id = $msg->get('correlation_id');

        // Verify credentials in MySQL
        $login_success = verifyLoginCredentials($username, $password);

        // Send the response back to the specified reply queue with correlation ID
        sendLoginResponse($reply_to, $correlation_id, $username, $login_success);
    };

    // Start consuming messages
    $channel->basic_consume($request_queue, '', false, true, false, false, $callback);
    echo "Waiting for login requests...\n";

    // Keep the consumer running
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    // Close the connection
    $channel->close();
    $connection->close();
}

// Start the consumer
consumeLoginRequests();

