<?php
//include 'public_html/database_connection.php'; // Adjust the path if necessary
// Database connection settings
//$pdo = new PDO('mysql:host=localhost;dbname=QueueExample', 'testUser', 'Test@1234');
//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

header('Content-Type: application/json');

// Function to generate song recommendations based on user likes and playlists
function getRecommendations($user_id, $pdo) {
    try {
        // Fetch liked songs for the user
        $likedStmt = $pdo->prepare("
            SELECT s.song_id, s.title
            FROM user_likes_dislikes uld
            JOIN songs s ON uld.song_id = s.song_id
            WHERE uld.user_id = ? AND uld.liked = 1
        ");
        $likedStmt->execute([$user_id]);
        $likedSongs = $likedStmt->fetchAll(PDO::FETCH_ASSOC);

        // If no liked songs are found, exit early
        if (empty($likedSongs)) {
            return ["status" => "error", "message" => "No liked songs found for recommendations."];
        }

        $connection = new AMQPStreamConnection('172.26.233.84', 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();
        $channel->queue_declare('reviews_queue', false, true, false, false);
    

        // Fetch recommended songs based on albums of liked songs and playlists
        $recommendStmt = $pdo->prepare("
            SELECT DISTINCT s.song_id, s.title, s.album_id
            FROM songs s
            WHERE (s.album_id IN (
                SELECT s.album_id FROM user_likes_dislikes uld
                JOIN songs s ON uld.song_id = s.song_id
                WHERE uld.user_id = ? AND uld.liked = 1
            ) OR s.album_id IN (
                SELECT s.album_id FROM playlist_songs ps
                JOIN songs s ON ps.song_id = s.song_id
                JOIN playlists p ON ps.playlist_id = p.playlist_id
                WHERE p.user_id = ?
            )) AND s.song_id NOT IN (
                SELECT song_id FROM user_likes_dislikes WHERE user_id = ?
            )
            LIMIT 10
        ");
        $recommendStmt->execute([$user_id, $user_id, $user_id]);
        $recommendedSongs = $recommendStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($recommendedSongs)) {
            return ["status" => "error", "message" => "No recommendations found based on user preferences."];
        }

        return ["status" => "success", "data" => $recommendedSongs];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to retrieve recommendations: " . $e->getMessage()];
    }
}

// Retrieve user ID from command line or POST data
if (php_sapi_name() === 'cli') {
    $user_id = $argv[1] ?? null;
} else {
    $user_id = $_POST['user_id'] ?? null;
}

// Validate the user ID
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Please provide user_id."]);
    exit;
}

// Fetch recommendations and output the response
$response = getRecommendations($user_id, $pdo);
echo json_encode($response);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Recommendations</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Recommended Songs</h1>

    <!-- Recommendations Display -->
    <div id="recommendationsContainer" class="results"></div>

    <script>
        // Fetch and Display Recommendations
        function loadRecommendations() {
            fetch('scripts/recommend_songs.php')
                .then(response => response.json())
                .then(data => {
                    const recommendationsContainer = document.getElementById('recommendationsContainer');
                    recommendationsContainer.innerHTML = '';
                    if (data.error) {
                        recommendationsContainer.innerHTML = `<p>${data.error}</p>`;
                    } else {
                        data.forEach(song => {
                            recommendationsContainer.innerHTML += `
                                <div class="song">
                                    <p><strong>Title:</strong> ${song.name}</p>
                                    <p><strong>Artist:</strong> ${song.artist}</p>
                                    <p><strong>Album:</strong> ${song.album}</p>
                                    <p><a href="${song.link}" target="_blank">Listen on Spotify</a></p>
                                </div>
                                <hr>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Load recommendations on page load
        document.addEventListener('DOMContentLoaded', loadRecommendations);
    </script>
</body>
</html>