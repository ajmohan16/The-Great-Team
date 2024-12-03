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

<<<<<<<< HEAD:public_html/RabbitMQloginrecommedation_songs (2).php
function sendLoginRequest($username, $password) {
========
function sendRegisterRequest($username, $hashed_password, $email) {
>>>>>>>> 94b2c36ccb9d5abe4c801d66a34316b5952bc58b:register.php
    global $rabbitmq_host, $request_queue, $response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare queues
        $channel->queue_declare($request_queue, false, false, false, false);
        $channel->queue_declare($response_queue, false, false, false, false);

<<<<<<<< HEAD:public_html/RabbitMQloginrecommedation_songs (2).php
        // Prepare login request message
        $messageBody = json_encode(['username' => $username]);
========
        // Prepare registration request message
        $messageBody = json_encode([
            'username' => $username,
            'password' => $hashed_password, // Send the hashed password
            'email' => $email
        ]);
>>>>>>>> 94b2c36ccb9d5abe4c801d66a34316b5952bc58b:register.php
        $message = new AMQPMessage($messageBody, [
            'reply_to' => $response_queue // Set reply-to header for response
        ]);

        // Send login request
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
            $channel->wait(null, false, 10); // Timeout after 10 seconds
        }

        // Process the response
        if (isset($response['login_success']) && $response['login_success']) {
            $hashed_password = $response['hashed_password'] ?? null;

            if ($hashed_password && password_verify($password, $hashed_password)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                header('Location: home.php');
                exit();
            } else {
                echo "Invalid username or password.";
            }
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

<<<<<<<< HEAD:public_html/RabbitMQloginrecommedation_songs (2).php
    // Send login request to RabbitMQ
    sendLoginRequest($username, $password);
========
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,20}$/', $password)) {
        echo "Password must be 8-20 characters long, include at least one uppercase letter, one lowercase letter, and one digit.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Send registration request to RabbitMQ
    sendRegisterRequest($username, $hashed_password, $email);
>>>>>>>> 94b2c36ccb9d5abe4c801d66a34316b5952bc58b:register.php
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
            <input type="password" id="password" name="password" 
                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,20}" 
                   title="Password must be 8-20 characters long, include at least one uppercase letter, one lowercase letter, and one digit." 
                   required>
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
