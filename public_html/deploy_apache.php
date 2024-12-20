<?php
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$rabbitmq_host = '172.26.233.84';
$request_queue = 'zip_file_queue';


$folder_to_zip = '/var/www/music/The-Great-Team/public_html';


$zip_file_path = '/tmp/front_end.zip';

if (!is_dir($folder_to_zip)) {
    echo "Directory does not exist: $folder_to_zip\n";
    exit;
}

function createZip($folder, $zipFile) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return true;
    }
    return false;
}

if (createZip($folder_to_zip, $zip_file_path)) {
    try {
        
        $connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();
        $channel->queue_declare($request_queue, false, true, false, false);

       
        $zipContent = file_get_contents($zip_file_path);
        if ($zipContent === false) {
            throw new Exception('Failed to read the ZIP file.');
        }

        
        $messageBody = json_encode([
            'bundle_name' => 'front_end_bundle.zip',
            'version' => '1.0.0',
            'file_content' => base64_encode($zipContent)
        ]);

        $message = new AMQPMessage($messageBody, ['delivery_mode' => 2]);
        $channel->basic_publish($message, '', $request_queue);

        echo "ZIP file sent to RabbitMQ successfully.\n";

        $channel->close();
        $connection->close();

    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . "\n";
    }
} else {
    echo "Failed to create ZIP file.\n";
}
?>