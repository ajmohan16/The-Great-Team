<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Twilio\Rest\Client;

$twilio_sid = '...';
$twilio_auth_token = '...';
$twilio_from_number = "12293543786";

$client = new Client($twilio_sid, $twilio_auth_token);

$connection = new AMQPStreamConnection('172.26.184.4', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

$channel->queue_declare('sms_request_queue', false, true, false, false);

echo "Waiting for messages... To exit press CTRL+C\n";

$callback = function($msg) use ($client, $twilio_from_number) {
	$data = json_decode($msg->body, true);
	$phone_number = $data['phone_number'];
	$code = $data['2fa_code'];

	try {
		$message = $client->messages->create(
			$phone_number,
			[
				'from' => $twilio_from_number,
				'body' => "Your 2FA code is: $code"
			]
		);

		echo "SMS sent to $phone_number\n";
	} catch (Exception $e) {
		echo "Failed to send SMS: " . $e->getMessage() . "\n";
	}

	$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
	};

$channel->basic_consume('sms_request_queue', '', false, true, false, false, $callback);

while($channel->is_consuming()) {
	$channel->wait();
}

$channel->close();
$connection->close();
?>
