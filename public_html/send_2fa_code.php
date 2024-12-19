<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$phone_number = $_POST['phone_number'];

$code = mt_rand(100000, 999999);

file_put_contents("2fa_codes.txt", "$phone_number:$code\n", FILE_APPEND);

$connection = new AMQPStreamConnection('172.26.184.4', 5672, 'test', 'test', 'testHost');
$channel->queue_declare('sms_request_queue', false, true, false, false);

$msg_data = json_encode([
	'phone_number' => $phone_number,
	'2fa_code' => $code
]);

$msg = new AMQPMessage($msg_data);

$channel->basic_publish($msg, '', 'sms_request_queue');

echo "2FA code sent to $phone_number. Please check your SMS.";

$channel->close();
$connection-close();
?>
