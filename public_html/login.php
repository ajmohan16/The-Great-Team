<?php
// Include necessary dependencies
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Twilio\Rest\Client;

// Include navigation bar
include 'nav.php';
session_start();

// RabbitMQ Configuration
$rabbitmq_host = '172.26.184.4';
$request_queue = 'login_requests';
$response_queue = 'login_responses';

// Twilio Configuration
$twilioConfig = include 'twilio_config.php';
$twilio_sid = $twilioConfig['sid'];
$twilio_token = $twilioConfig['auth_token'];
$twilio_from = $twilioConfig['from'];

function sendLoginRequest($username, $password) {
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        // Establish connection to RabbitMQ
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare the request and response queues
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

        // Send login request to RabbitMQ
        $channel->basic_publish($message, '', $request_queue);
        echo "Login request sent for user: $username<br>";

        // Listen for a response
        $response = null;
        $callback = function ($msg) use (&$response) {
            $response = json_decode($msg->body, true);
        };

        // Set up the consumer to listen for the response
        $channel->basic_consume($response_queue, '', false, true, false, false, $callback);

        // Wait for the response message (timeout after 10 seconds)
        $start = time();
        while (!$response && (time() - $start) < 15) {  // Increase timeout to 15 seconds
            $channel->wait(null, false, 5);  // Wait for 5 seconds each loop
        }

        if ($response) {
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
                echo "Invalid username or password.<br>";
            }
        } else {
            echo "No response received from the backend. Check RabbitMQ consumer.<br>";
        }

        // Close the connection
        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        echo "An error occurred while sending the login request: " . $e->getMessage() . "<br>";
    }
}

// Function to send OTP via Twilio
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

