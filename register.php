<?php
// register.php
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
//include navigation bar
include 'nav.php';

// RabbitMQ Configuration
$rabbitmq_host = '172.26.233.84';
$request_queue = 'register_requests';
$response_queue = 'register_responses';

function sendRegisterRequest($username, $password, $email) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

        // Prepare registration request message
        $messageBody = json_encode([
            'username' => $username,
            'password' => $password,
            'email' => $email
        ]);
        $message = new AMQPMessage($messageBody, [
            'reply_to' => $response_queue // Set reply-to header for response
        ]);

        // Send registration request
        $channel->basic_publish($message, '', $request_queue);
        echo "Registration request sent for user: $username<br>";

        // Listen for a response
        $response = null;
        $callback = function ($msg) use (&$response) {
            $response = json_decode($msg->body, true);
        };

        // Set up the consumer
        $channel->basic_consume($response_queue, '', false, true, false, false, $callback);

        // Wait for the response message
        while (!$response) {
            $channel->wait(null, false, 10); // Timeout after 10 seconds
        }

        // Process the response
        if (isset($response['register_success']) && $response['register_success']) {
            echo "Registration successful. Please <a href='login.php'>log in</a>.";
        } else {
            echo "Registration failed: " . ($response['error'] ?? 'Unknown error') . "<br>";
        }

        // Close the connection
        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        echo "An error occurred while sending the registration request: " . $e->getMessage() . "<br>";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    if (empty($username) || empty($password) || empty($email)) {
        echo "Username, password, and email are required.";
        exit();
    }

    // Send registration request to RabbitMQ
    sendRegisterRequest($username, $password, $email);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h2>Register Page</h2>
    <form action="register.php" method="post">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <button type="submit">Register</button>
        </div>
    </form>
    <form action="login.php" method="get">
        <div>
            <button type="submit">Back to Login</button>
        </div>
    </form>
</body>
</html>
