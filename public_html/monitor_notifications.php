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
$rabbitVhost = 'testHost';

// Queue names
$login_request_queue = 'login_requests';
$login_response_queue = 'login_responses';
$register_request_queue = 'register_requests';
$register_response_queue = 'register_responses';

// Verify login credentials
function verifyLoginCredentials($username, $password) {
    global $mysqlHost, $mysqlDB, $mysqlUser, $mysqlPassword;

    try {
        $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username AND password = :password");
        $stmt->execute(['username' => $username, 'password' => $password]);
        $user = $stmt->fetch();

        return $user ? true : false;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
        return false;
    }
}

// Register new user
function registerUser($username, $password, $email) {
    global $mysqlHost, $mysqlDB, $mysqlUser, $mysqlPassword;

    try {
        $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
        $stmt->execute(['username' => $username, 'password' => $password, 'email' => $email]);

        return true;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
        return false;
    }
}

// Send response back to RabbitMQ
function sendResponse($queue, $data) {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost;

    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();

    $channel->queue_declare($queue, false, false, false, false);

    $message = new AMQPMessage(json_encode($data));
    $channel->basic_publish($message, '', $queue);

    echo "Response sent to queue: $queue\n";

    $channel->close();
    $connection->close();
}

// Consume login requests
function consumeLoginRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost, $login_request_queue, $login_response_queue;

    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();

    $channel->queue_declare($login_request_queue, false, false, false, false);

    $callback = function($msg) use ($login_response_queue) {
        $data = json_decode($msg->body, true);
        $username = $data['username'];
        $password = $data['password'];

        $login_success = verifyLoginCredentials($username, $password);

        sendResponse($login_response_queue, [
            'username' => $username,
            'login_success' => $login_success,
        ]);
    };

    $channel->basic_consume($login_request_queue, '', false, true, false, false, $callback);
    echo "Waiting for login requests...\n";

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
}

// Consume registration requests
function consumeRegisterRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost, $register_request_queue, $register_response_queue;

    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $connection->channel();

    $channel->queue_declare($register_request_queue, false, false, false, false);

    $callback = function($msg) use ($register_response_queue) {
        $data = json_decode($msg->body, true);
        $username = $data['username'];
        $password = $data['password'];
        $email = $data['email'];

        $register_success = registerUser($username, $password, $email);

        sendResponse($register_response_queue, [
            'username' => $username,
            'register_success' => $register_success,
        ]);
    };

    $channel->basic_consume($register_request_queue, '', false, true, false, false, $callback);
    echo "Waiting for registration requests...\n";

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
}

// Start both consumers
function startConsumers() {
    echo "Starting consumers...\n";

    // Run both consumers in separate threads
    $pid = pcntl_fork();

    if ($pid == -1) {
        die("Could not fork");
    } elseif ($pid) {
        // Parent process
        consumeLoginRequests();
    } else {
        // Child process
        consumeRegisterRequests();
    }
}

startConsumers();

