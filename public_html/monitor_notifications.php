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

function getPDOConnection() {
    global $mysqlHost, $mysqlDB, $mysqlUser, $mysqlPassword;

    try {
        $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Database connection error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function verifyLoginCredentials($username, $password) {
    $pdo = getPDOConnection();

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user && password_verify($password, $user['password']);
}

function registerUser($username, $password, $email) {
    $pdo = getPDOConnection();

    try {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            return "Username or email already exists.";
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
        $stmt->execute(['username' => $username, 'password' => $hashedPassword, 'email' => $email]);
        return true;
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

function sendResponse($queue, $data) {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost;

    try {
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, false, false, false);

        $message = new AMQPMessage(json_encode($data));
        $channel->basic_publish($message, '', $queue);

        echo "Response sent to queue: $queue\n";

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        echo "Failed to send response: " . $e->getMessage() . "\n";
    }
}

function consumeLoginRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost, $login_request_queue, $login_response_queue;

    try {
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
    } catch (Exception $e) {
        echo "Error consuming login requests: " . $e->getMessage() . "\n";
    }
}

function consumeRegisterRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost, $register_request_queue, $register_response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
        $channel = $connection->channel();

        $channel->queue_declare($register_request_queue, false, false, false, false);

        $callback = function($msg) use ($register_response_queue) {
            $data = json_decode($msg->body, true);
            $username = $data['username'];
            $password = $data['password'];
            $email = $data['email'];

            $register_result = registerUser($username, $password, $email);

            sendResponse($register_response_queue, [
                'username' => $username,
                'register_success' => $register_result === true,
                'error' => is_string($register_result) ? $register_result : null,
            ]);
        };

        $channel->basic_consume($register_request_queue, '', false, true, false, false, $callback);
        echo "Waiting for registration requests...\n";

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        echo "Error consuming registration requests: " . $e->getMessage() . "\n";
    }
}

function startConsumers() {
    consumeLoginRequests();
    consumeRegisterRequests();
}

startConsumers();

