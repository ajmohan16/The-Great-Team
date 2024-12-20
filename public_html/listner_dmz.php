<?php
require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($argc !== 2) {
    echo "Usage: php listener_apache.php <destination>\n";
    exit(1);
}

$destination = $argv[1];
$rabbitmq_host = '172.26.233.84';
$queue_name = 'zip_file_queue';
$unzipFolder = '/home/vimerlis/git/rabbitmqphp_example/The-Great-Team/public_html';  // Folder to unzip the files

if (!file_exists($unzipFolder)) {
    mkdir($unzipFolder, 0777, true);
}
try {
    $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
    $channel = $connection->channel();

    // Declare the queue
    $channel->queue_declare($queue_name, false, true, false, false);

    // Callback function to handle received messages
    $callback = function ($msg) use ($destination, $unzipFolder) {
        $messageData = json_decode($msg->body, true);

        if ($messageData['destination'] === $destination) {
            echo "Received message for $destination: Processing zip file '{$messageData['zip_file_name']}'\n";

            // Decode the base64 zip file content
            $zipFileContent = base64_decode($messageData['zip_file_content']);
            $zipFilePath = $unzipFolder . '/' . $messageData['zip_file_name'];

            // Save the zip file to the server
            file_put_contents($zipFilePath, $zipFileContent);

            // Unzip the file into the specified folder
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath) === true) {
                $zip->extractTo($unzipFolder);  // Extract to the folder
                $zip->close();
                echo "Zip file '{$messageData['zip_file_name']}' unzipped to $unzipFolder.\n";
            } else {
                echo "Failed to unzip '{$messageData['zip_file_name']}'.\n";
            }
        }
    };

    // Set up the consumer to listen for messages
    $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

    echo "Listener on DMZ server is waiting for messages...\n";
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>


