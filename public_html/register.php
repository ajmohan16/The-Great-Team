<?php
// Include necessary dependencies
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Start session for managing user states
session_start();

// RabbitMQ Configuration
$rabbitmq_host = 'localhost';
$request_queue = 'register_requests';
$response_queue = 'register_responses';

// Log errors to RabbitMQ
function logErrorToRabbitMQ($exception) {
    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare a fanout exchange
        $channel->exchange_declare('logs_exchange', 'fanout', false, false, false);

        // Prepare error message
        $errorMessage = [
            "type" => get_class($exception),
            "message" => $exception->getMessage(),
            "trace" => $exception->getTraceAsString(),
            "timestamp" => date("Y-m-d H:i:s")
        ];
        $message = new AMQPMessage(json_encode($errorMessage));

        // Publish the message to the exchange
        $channel->basic_publish($message, 'logs_exchange');

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        // Fallback to PHP's error log if RabbitMQ fails
        error_log("Failed to log error to RabbitMQ: " . $e->getMessage());
    }
}

function sendRegisterRequest($username, $password, $email, $phone) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        // Establish connection to RabbitMQ server
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare necessary queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

        // Prepare registration request message
        $messageBody = json_encode([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'phone' => $phone
        ]);
        $message = new AMQPMessage($messageBody, ['reply_to' => $response_queue]);

        // Send registration request
        $channel->basic_publish($message, '', $request_queue);

        // Listen for response
        $response = null;
        $callback = function ($msg) use (&$response) {
            $response = json_decode($msg->body, true);
        };

        $channel->basic_consume($response_queue, '', false, true, false, false, $callback);

        // Wait for a response (10-second timeout)
        while (!$response) {
            $channel->wait(null, false, 10);
        }

        // Process the response
        if (isset($response['register_success']) && $response['register_success']) {
            echo "Registration successful. Please <a href='login.php'>log in</a>.";
        } else {
            echo "Registration failed: " . ($response['error'] ?? 'Unknown error') . "<br>";
        }

        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        logErrorToRabbitMQ($e);
        echo "An error occurred during registration. Please try again later.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Validate form inputs
    if (empty($username) || empty($password) || empty($email) || empty($phone)) {
        echo "All fields are required.";
        exit();
    }

    // Send registration request
    sendRegisterRequest($username, $password, $email, $phone);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <form action="register.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number:</label>
                <input type="text" id="phone" name="phone" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <a href="login.php" class="btn btn-secondary mt-3">Back to Login</a>
    </div>
</body>
</html>
