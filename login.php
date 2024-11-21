<?php
// login.php
require 'vendor/autoload.php';
//include navigation bar
include 'nav.php';
session_start();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ Configuration
$rabbitmq_host = '172.26.233.84';
$request_queue = 'login_requests';
$response_queue = 'login_responses';

function sendLoginRequest($username, $password) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

        // Prepare login request message without correlation ID
        $messageBody = json_encode([
            'username' => $username,
            'password' => $password
        ]);
        $message = new AMQPMessage($messageBody, [
            'reply_to' => $response_queue  // Set reply-to header for response
        ]);

        // Send login request
        $channel->basic_publish($message, '', $request_queue);
        echo "Login request sent for user: $username<br>";

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

        // Process the response
        if (isset($response['login_success']) && $response['login_success']) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            header('Location: home.php');
        } else {
            echo "Invalid username or password.";
        }

        // Close the connection
        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        echo "An error occurred while sending the login request: " . $e->getMessage() . "<br>";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "Username and password are required.";
        exit();
    }

    // Send login request to RabbitMQ
    sendLoginRequest($username, $password);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login Page</h2>
    <form action="login.php" method="post">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
    <form action="register.php" method="get">
        <div>
            <button type="submit">Register</button>
        </div>
    </form>
</body>
</html>
