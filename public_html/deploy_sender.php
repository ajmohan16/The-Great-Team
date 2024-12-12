<?php
require __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ Configuration
$rabbitmq_host = '172.26.184.4';
$request_queue = 'zip_file_queue';

// Absolute path to the folder that will be zipped
$folder_to_zip = __DIR__ . '/files_to_zip';  // Corrected path

// Path for the ZIP file
$zip_file_path = __DIR__ . '/tmp/back_end.zip';  // Dynamic path relative to the current script

// Create the parent directory for the ZIP file if it does not exist
if (!is_dir(dirname($zip_file_path))) {
    mkdir(dirname($zip_file_path), 0775, true);
    echo "Created missing directory: " . dirname($zip_file_path) . "\n";
}

// Check if the folder to zip exists
if (!is_dir($folder_to_zip)) {
    echo "Directory does not exist: $folder_to_zip\n";
    exit;
}

function createZip($folder, $zipFile) {
    // Ensure the parent directory for the ZIP file exists
    if (!is_dir(dirname($zipFile))) {
        mkdir(dirname($zipFile), 0775, true);
        echo "Created missing directory: " . dirname($zipFile) . "\n";
    }

    $zip = new ZipArchive();
    $result = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result !== TRUE) {
        echo "Error: Failed to open ZIP file. Error code: $result\n";
        switch ($result) {
            case ZipArchive::ER_EXISTS:
                echo "Error: File already exists.\n";
                break;
            case ZipArchive::ER_NOENT:
                echo "Error: No such file or directory.\n";
                break;
            case ZipArchive::ER_OPEN:
                echo "Error: Cannot open the file.\n";
                break;
            case ZipArchive::ER_INVAL:
                echo "Error: Invalid argument.\n";
                break;
            default:
                echo "Error: Unknown error code ($result).\n";
                break;
        }
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

    if (!file_exists($zipFile)) {
        echo "Error: ZIP file was not created: $zipFile\n";
        return false;
    }

    return true;
}

if (createZip($folder_to_zip, $zip_file_path)) {
    try {
        // Establish RabbitMQ connection
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();
        $channel->queue_declare($request_queue, false, true, false, false);

        // Read the ZIP content and encode it in base64
        $zipContent = file_get_contents($zip_file_path);
        if ($zipContent === false) {
            throw new Exception('Failed to read the ZIP file.');
        }

        // Prepare the message with file content encoded in base64
        $messageBody = json_encode([
            'bundle_name' => 'back_end_bundle.zip',
            'version' => '1.0.0',
            'file_content' => base64_encode($zipContent)
        ]);

        // Send the message to RabbitMQ
        $message = new AMQPMessage($messageBody, ['delivery_mode' => 2]);
        $channel->basic_publish($message, '', $request_queue);

        echo "ZIP file sent to RabbitMQ successfully.\n";

        // Clean up by closing the channel and connection
        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . "\n";
    }
} else {
    echo "Failed to create ZIP file.\n";
}

