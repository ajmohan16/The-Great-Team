<?php 

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$track_id = isset($_POST['track_id']) ? (int)$_POST['track_id'] : null;
	$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
	$description = isset($_POST['description']) ? trim($_POST['description']) : '';
}

	if ($track_id === null || $rating === null || $description === '') {
		echo "Invalid input";
		exit;
	}

	if ($rating < 1 || $rating > 5) {
		echo "Rating must be between 1 and 5";
		exit;
	}
$connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
$channel = $connection->channel();

$channel->queue_declare('reviews_queue', false, true, false, false);

$reviewData = json_encode([
	'track_id' => $track_id,
	'rating' => $rating,
	'description' => $description,
]);

$msg = new AMQPMessage($reviewData, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
$channel->basic_publish($msg, '', 'reviews_queue');

echo "Review submitted";

$channel->close();
$connection->close();
?>

<html>
<head>
<title>Review</title>
</head>
<h2>Leave a Review</h2>
<form action="send_review.php" method="post">
<label for="track_id">Track ID:</label>
<input type="number" name="track_id" required><br><br>

<label for="rating">Rating (1-5):</label>
<input type="number" name="rating" min="1" max="5" required><br><br>

<label for="description">Review:</label>
<textarea name="description" required></textarea><br><br>

<button type="submit">Submit Review</button>
</form>
</body>
</html>