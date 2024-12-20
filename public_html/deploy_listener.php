<?php
require 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$rabbitmq_host = '172.26.184.4';
$request_queue = 'zip_file_queue';
$destination_folder = '/var/www/The-Great-Team/deployFiles';
$db_dsn = 'mysql:host=localhost;dbname=deploy;charset=utf8';
$db_user = 'root';
$db_password = '';

try {
	$pdo = new PDO($db_dsn, $db_user, $db_password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);

	$connection = new AMQPStreamConnection($rabbitmq_host, 5672, 'test', 'test', 'testHost');
	$channel = $connection->channel();
	$channel->queue_declare($request_queue, false, true, false, false);

	echo "Waiting for ZIP files...\n";

	$callback = function ($msg) use ($pdo, $destination_folder) {
		$data = json_decode($msg->body, true);
		if (!$data || !isset($data['bundle_name'], $data['version'], $data['file_content'])) {
			echo "Invalid message received.\n";
			return;
		}

		$bundleName = $data['bundle_name'];
		$version = $data['version'];
		$zipContent = base64_decode($data['file_content']);

		$zipFilePath = $destination_folder . $bundleName;
		file_put_contents($zipFilePath, $zipContent);

		$zip = new ZipArchive();
		if ($zip->open($zipFilePath) == TRUE) {
			$zip->extractTo($destination_folder);
			$zip->close();

			$stmt = $pdo->prepare("INSERT INTO bundles (version, bundle_name, status) VALUES (?, ?, ?)");
			$stmt->execute([$version, $bundleName, 'received']);

			echo "Bundle '$bundleName' (Version: $version) processed successfully.\n"; } else {
			echo "Failed to extract ZIP file.\n";
			}
	};

	$channel->basic_consume($request_queue, '', false, true, false, false, $callback);

	while ($channel->is_consuming()) {
		$channel->wait();
	}

	$channel->close();
	$connection->close();
} catch (Exception $e) {
	echo "Error: " . $e->getMessage() . "\n";
}
?>
