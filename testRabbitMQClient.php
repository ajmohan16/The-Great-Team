#!/usr/bin/php
<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

try {
    // Create a new RabbitMQ client using the configuration in the .ini file
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    // Check if a message is provided as an argument, otherwise use "test message"
    $msg = isset($argv[1]) ? $argv[1] : "test message";

    // Prepare the request array
    $request = [
        'type' => "Login",
        'username' => "steve",
        'password' => "password",
        'message' => $msg,
    ];

    // Send the request and capture the response
    $response = $client->send_request($request);

    // Print the response from the RabbitMQ server
    echo "Client received response:\n";
    print_r($response);

} catch (Exception $e) {
    // Handle any exceptions that occur during execution
    echo "Failed to send message: " . $e->getMessage() . "\n";
}

echo "\n" . $argv[0] . " END\n";

