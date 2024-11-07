<?php
include 'public_html/database_connection.php';

function addToPlaylist($playlist_id, $song_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO playlist_songs (playlist_id, song_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$playlist_id, $song_id]);
    echo json_encode(["status" => "success", "message" => "Song added to playlist!"]);
}

// Retrieve POST parameters or CLI arguments
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : $argv[1] ?? null;
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : $argv[2] ?? null;

if (!$playlist_id || !$song_id) {
    echo json_encode(["status" => "error", "message" => "Please provide both playlist_id and song_id."]);
    exit;
}

addToPlaylist($playlist_id, $song_id);
?>

