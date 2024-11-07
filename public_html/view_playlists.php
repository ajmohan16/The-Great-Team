<?php
include 'public_html/database_connection.php'; // Adjust the path if necessary

function getUserPlaylists($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.playlist_id, p.name AS playlist_name, s.song_id, s.title AS song_title
        FROM playlists p
        LEFT JOIN playlist_songs ps ON p.playlist_id = ps.playlist_id
        LEFT JOIN songs s ON ps.song_id = s.song_id
        WHERE p.user_id = ?
        ORDER BY p.name, s.title
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

// Fetch playlists and songs
$playlists = getUserPlaylists($user_id);

header('Content-Type: application/json');
echo json_encode($playlists);
?>

