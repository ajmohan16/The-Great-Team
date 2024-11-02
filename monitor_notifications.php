<?php

require 'vendor/autoload.php';

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

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDB", $mysqlUser, $mysqlPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL\n";

    // Connect to RabbitMQ with the specified virtual host
    $rabbitConnection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $rabbitVhost);
    $channel = $rabbitConnection->channel();
    
    // Declare the queue in RabbitMQ
    $channel->queue_declare('NewPersonQueue', false, true, false, false);
    echo "Connected to RabbitMQ\n";

    while (true) {
        // Check for new notifications in MySQL
        $stmt = $pdo->query("SELECT NotificationID, PersonID FROM Notifications WHERE NotificationType = 'NewPerson'");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notifications as $notification) {
            $personId = $notification['PersonID'];
            $notificationId = $notification['NotificationID'];

            // Fetch person details from the People table
            $personStmt = $pdo->prepare("SELECT FirstName, LastName, EmailAddress, DOB FROM People WHERE ID = ?");
            $personStmt->execute([$personId]);
            $person = $personStmt->fetch(PDO::FETCH_ASSOC);

            if ($person) {
                // Prepare message data
                $messageData = json_encode([
                    'ID' => $personId,
                    'FirstName' => $person['FirstName'],
                    'LastName' => $person['LastName'],
                    'EmailAddress' => $person['EmailAddress'],
                    'DOB' => $person['DOB']
                ]);

                // Send message to RabbitMQ
                $msg = new AMQPMessage($messageData, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
                $channel->basic_publish($msg, '', 'NewPersonQueue');
                echo "Sent to RabbitMQ: $messageData\n";

                // Delete processed notification to avoid re-processing
                $deleteStmt = $pdo->prepare("DELETE FROM Notifications WHERE NotificationID = ?");
                $deleteStmt->execute([$notificationId]);
            }
        }

        // Sleep to prevent continuous querying and reduce server load
        sleep(5);
    }

    // Close connections (though this code will not be reached due to the infinite loop)
    $channel->close();
    $rabbitConnection->close();
    $pdo = null;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
