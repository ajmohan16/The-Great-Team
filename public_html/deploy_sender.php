<?php
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// RabbitMQ Configuration
$rabbitmq_host = '172.26.184.4';
$rabbitmq_port = 5672;
$rabbitmq_user = 'test';
$rabbitmq_password = 'test';
$rabbitmq_virtual_host = 'testHost';
$rabbitmq_queue = 'zip_file_queue'; 

// Directory and ZIP File Configuration
$folder_to_zip = '/home/nic/midterm/rabbitmqphp_example/public_html';
$zip_file_path = realpath(__DIR__) . '/folder_bundle.zip';

if (!file_exists($folder_to_zip)) {
    mkdir($folder_to_zip, 0775, true);
    echo "Created missing directory: $folder_to_zip\n";
}

function createZip($folder, $zipFile) {
    if (!file_exists($folder)) {
        echo "Error: Directory $folder does not exist.\n";
        return false;
    }

    if (!is_writable(dirname($zipFile))) {
        echo "Error: Directory is not writable: " . dirname($zipFile) . "\n";
        return false;
    }

    $zip = new ZipArchive();
    $result = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result !== TRUE) {
        echo "Error: Failed to open ZIP file. Error code: $result\n";
        return false;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($folder) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    sleep(1);
    return true;
}

try {
    $connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_password, $rabbitmq_virtual_host);
    $channel = $connection->channel();

    $channel->queue_declare($rabbitmq_queue, false, true, false, false);

    if (createZip($folder_to_zip, $zip_file_path)) {
        if (file_exists($zip_file_path)) {
            $data = file_get_contents($zip_file_path);
            $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', $rabbitmq_queue);
            echo "Success: ZIP file created and sent.\n";
        } else {
            echo "Error: ZIP file not found at $zip_file_path.\n";
        }
    }

    $channel->close();
    $connection->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

