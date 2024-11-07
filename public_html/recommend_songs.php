<?php
include 'public_html/database_connection.php'; // Adjust the path if necessary

function getRecommendations($user_id) {
    global $pdo;

    // Fetch liked songs
    $likedStmt = $pdo->prepare("
        SELECT s.song_id, s.title
        FROM user_likes_dislikes uld
        JOIN songs s ON uld.song_id = s.song_id
        WHERE uld.user_id = ? AND uld.liked = 1
    ");
    $likedStmt->execute([$user_id]);
    $likedSongs = $likedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recommended songs based on albums from liked songs and playlists
    $recommendStmt = $pdo->prepare("
        SELECT DISTINCT s.song_id, s.title, s.album_id
        FROM songs s
        WHERE s.album_id IN (
            SELECT s.album_id FROM user_likes_dislikes uld
            JOIN songs s ON uld.song_id = s.song_id
            WHERE uld.user_id = ? AND uld.liked = 1
        )
        OR s.album_id IN (
            SELECT s.album_id FROM playlist_songs ps
            JOIN songs s ON ps.song_id = s.song_id
            JOIN playlists p ON ps.playlist_id = p.playlist_id
            WHERE p.user_id = ?
        )
        AND s.song_id NOT IN (
            SELECT song_id FROM user_likes_dislikes WHERE user_id = ?
        )
        LIMIT 10
    ");
    $recommendStmt->execute([$user_id, $user_id, $user_id]);
    return $recommendStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Retrieve user_id from command line or POST data
if (php_sapi_name() === 'cli') {
    $user_id = $argv[1] ?? null;
} else {
    $user_id = $_POST['user_id'] ?? null;
}

// Check if user_id is provided
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Please provide user_id."]);
    exit;
}

// Fetch recommendations
$recommendations = getRecommendations($user_id);

header('Content-Type: application/json');
echo json_encode($recommendations);
?>

