<?php
include 'public_html/database_connection.php';

function likeOrDislikeSong($user_id, $song_id, $liked) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO user_likes_dislikes (user_id, song_id, liked)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE liked = VALUES(liked)
    ");
    $stmt->execute([$user_id, $song_id, $liked]);
    echo json_encode(["status" => "success", "message" => $liked ? "Song liked!" : "Song disliked!"]);
}

// Retrieve POST parameters or CLI arguments
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $argv[1] ?? null;
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : $argv[2] ?? null;
$liked = isset($_POST['liked']) ? (bool)$_POST['liked'] : ($argv[3] ?? null);

if (!$user_id || !$song_id || is_null($liked)) {
    echo json_encode(["status" => "error", "message" => "Please provide user_id, song_id, and liked (1 or 0)."]);
    exit;
}

likeOrDislikeSong($user_id, $song_id, $liked);
?>

