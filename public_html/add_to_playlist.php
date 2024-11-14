<?php
include 'public_html/database_connection.php';

header('Content-Type: application/json');

// Function to add a song to a playlist
function addToPlaylist($playlist_id, $song_id, $pdo) {
    try {
        // Insert song into playlist
        $stmt = $pdo->prepare("
            INSERT INTO playlist_songs (playlist_id, song_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE song_id = song_id
        ");
        $stmt->execute([$playlist_id, $song_id]);

        return ["status" => "success", "message" => "Song added to playlist successfully!"];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to add song to playlist: " . $e->getMessage()];
    }
}

// Retrieve parameters from POST data or CLI arguments
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : $argv[1] ?? null;
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : $argv[2] ?? null;

// Validate input
if (!$playlist_id || !$song_id) {
    echo json_encode(["status" => "error", "message" => "Please provide both playlist_id and song_id."]);
    exit;
}

// Add the song to the playlist and output the response
$response = addToPlaylist($playlist_id, $song_id, $pdo);
echo json_encode($response);
?>

