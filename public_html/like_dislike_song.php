<?php
include 'public_html/database_connection.php';

header('Content-Type: application/json');

// Function to like or dislike a song
function likeOrDislikeSong($user_id, $song_id, $liked, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_likes_dislikes (user_id, song_id, liked)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE liked = VALUES(liked)
        ");
        $stmt->execute([$user_id, $song_id, $liked]);

        $message = $liked ? "Song liked!" : "Song disliked!";
        return ["status" => "success", "message" => $message];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to update like/dislike status: " . $e->getMessage()];
    }
}

// Retrieve parameters from POST or command-line arguments
if (php_sapi_name() === 'cli') {
    $user_id = $argv[1] ?? null;
    $song_id = $argv[2] ?? null;
    $liked = isset($argv[3]) ? (bool)$argv[3] : null;
} else {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : null;
    $liked = isset($_POST['liked']) ? (bool)$_POST['liked'] : null;
}

// Validate input
if (!$user_id || !$song_id || is_null($liked)) {
    echo json_encode(["status" => "error", "message" => "Please provide user_id, song_id, and liked (1 or 0)."]);
    exit;
}

// Like or dislike the song and output the response
$response = likeOrDislikeSong($user_id, $song_id, $liked, $pdo);
echo json_encode($response);
?>

