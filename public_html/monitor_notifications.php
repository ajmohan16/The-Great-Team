<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$mysqlHost = 'localhost';
$mysqlDB = 'QueueExample';
$mysqlUser = 'testUser';
$mysqlPassword = 'Test@1234';

$rabbitHost = '172.26.184.4';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$rabbitVhost = 'testHost';

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

    if ($user && password_verify($password, $user['password'])) {
        return true;
    }
    return false;
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

function consumeRequests() {
    global $rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost;
    global $login_request_queue, $login_response_queue, $register_request_queue, $register_response_queue;

    try {
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
        $channel = $connection->channel();

        $channel->queue_declare($login_request_queue, false, false, false, false);
        $channel->queue_declare($register_request_queue, false, false, false, false);

        $callback = function($msg) {
            $data = json_decode($msg->body, true);

            if (!$data) {
                echo "Invalid message received.\n";
                return;
            }

            if ($msg->delivery_info['routing_key'] === 'login_requests') {
                handleLoginRequest($data);
            } elseif ($msg->delivery_info['routing_key'] === 'register_requests') {
                handleRegisterRequest($data);
            } else {
                echo "Unknown message type.\n";
            }
        };

        $channel->basic_consume($login_request_queue, '', false, true, false, false, $callback);
        $channel->basic_consume($register_request_queue, '', false, true, false, false, $callback);

        echo "Waiting for login and registration requests...\n";

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        echo "Error consuming requests: " . $e->getMessage() . "\n";
    }
}

function handleLoginRequest($data) {
    global $login_response_queue;

    $username = $data['username'] ?? 'unknown';
    $password = $data['password'] ?? 'unknown';

    echo "Processing login request for user: $username\n";

    $login_success = verifyLoginCredentials($username, $password);

    if ($login_success) {
        echo "Login successful for user: $username\n";
    } else {
        echo "Login failed for user: $username\n";
    }

    sendResponse($login_response_queue, [
        'username' => $username,
        'login_success' => $login_success,
    ]);
}

function handleRegisterRequest($data) {
    global $register_response_queue;

    $username = $data['username'] ?? 'unknown';
    $password = $data['password'] ?? 'unknown';
    $email = $data['email'] ?? 'unknown';

    echo "Processing registration request for user: $username\n";

    $register_result = registerUser($username, $password, $email);

    if ($register_result === true) {
        echo "Registration successful for user: $username\n";
    } else {
        echo "Registration failed for user: $username - Error: $register_result\n";
    }

    sendResponse($register_response_queue, [
        'username' => $username,
        'register_success' => $register_result === true,
        'error' => is_string($register_result) ? $register_result : null,
    ]);
}

consumeRequests();

