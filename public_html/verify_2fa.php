<?php
require_once  'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$phone_number = $_POST['phone_number'];
$user_code = $_POST['2fa_code'];

$codes = file('2fa_codes.txt', FILE_IGNORE_NEW_LINES);
$valid_code = false;

foreach ($codes as $line) {
        list($stored_phone, $stored_code) = explode(':', $line);
        if ($stored_phone == $phone_number && $stored_code == $user_code) {
                $valid_code = true;
                break;
        }
}

if ($valid_code) {
        echo "2FA verification successful. You are logged in!";
} else {
        echo "Invalid 2FA code. Please try again.";
}
?>
               
