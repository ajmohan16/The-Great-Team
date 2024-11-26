<?php 
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id = isset($_POST['track_id']) ? (int)$_POST['track_id'] : null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate and Review Songs</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Rate and Review Songs</h1>

    <h2>Submit a Review</h2>
    <form id="submitReviewForm" action="reviews.php" method="POST">
        <input type="text" name="track_id" id="reviewSongId" placeholder="Enter song ID" required>
        <input type="number" name="rating" id="rating" placeholder="Enter rating (1-5)" min="1" max="5" required>
        <textarea name="description" id="reviewText" placeholder="Enter your review" required></textarea>
        <button type="submit">Submit Review</button>
    </form>

    <h2>Existing Reviews</h2>
    <div id="reviewsContainer" class="results"></div>

    <script>
        document.getElementById('submitReviewForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const songId = document.getElementById('reviewSongId').value;
            const rating = document.getElementById('rating').value;
            const reviewText = document.getElementById('reviewText').value;

            fetch(`scripts/send_review.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ song_id: songId, rating: rating, review: reviewText })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Review submitted successfully!');
                loadReviews();
            })
            .catch(error => console.error('Error:', error));
        });

        function loadReviews() {
            fetch('scripts/review_consumer.php')
                .then(response => response.json())
                .then(data => {
                    const reviewsContainer = document.getElementById('reviewsContainer');
                    reviewsContainer.innerHTML = '';
                    if (data.error) {
                        reviewsContainer.innerHTML = `<p>${data.error}</p>`;
                    } else {
                        data.forEach(review => {
                            reviewsContainer.innerHTML += `
                                <div class="review">
                                    <p><strong>Song ID:</strong> ${review.song_id}</p>
                                    <p><strong>Rating:</strong> ${review.rating}</p>
                                    <p><strong>Review:</strong> ${review.review}</p>
                                    <p><strong>Reviewed At:</strong> ${review.review_date}</p>
                                </div>
                                <hr>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        document.addEventListener('DOMContentLoaded', loadReviews);
    </script>
</body>
</html>
