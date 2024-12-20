<?php
// Include necessary dependencies
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Twilio\Rest\Client;

// Include the navigation bar
include 'nav.php';
session_start();

// RabbitMQ Configuration
$rabbitmq_host = '172.26.184.4';
$request_queue = 'login_requests';
$response_queue = 'login_responses';

// Twilio Configuration
$twilio_sid = 'YOUR_TWILIO_SID';
$twilio_token = 'YOUR_TWILIO_AUTH_TOKEN';
$twilio_from = 'YOUR_TWILIO_PHONE_NUMBER';

function sendLoginRequest($username, $password) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

        // Prepare login request message
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
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['username'] = $username;
            $_SESSION['phone'] = $response['phone']; // Assuming phone number is returned

            // Send OTP via SMS
            sendOtp($_SESSION['phone'], $otp);

            // Redirect to OTP verification page
            header('Location: verify_otp.php');
            exit();
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

function sendOtp($phone, $otp) {
    global $twilio_sid, $twilio_token, $twilio_from;

    try {
        $client = new Client($twilio_sid, $twilio_token);
        $client->messages->create(
            $phone,
            [
                'from' => $twilio_from,
                'body' => "Your OTP is: $otp"
            ]
        );
        echo "OTP sent to $phone<br>";
    } catch (Exception $e) {
        echo "Failed to send OTP: " . $e->getMessage() . "<br>";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Login Page</h2>
        <form action="login.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <a href="register.php" class="btn btn-link mt-3">Register</a>
    </div>
</body>
</html>
