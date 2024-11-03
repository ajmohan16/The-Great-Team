<?php
$connection = new AMQPConnection([
    'host' => 'localhost',
    'port' => 5672,
    'vhost' => '/',
    'login' => 'guest',
    'password' => 'guest'
]);

if ($connection->connect()) {
    echo "Connection successful!\n";
} else {
    echo "Connection failed!\n";
}
?>
