<?php
include 'public_html/database_connection.php'; // Adjust the path if necessary

header('Content-Type: application/json');

// Function to get playlists with songs for a specific user
function getUserPlaylists($user_id, $pdo) {
    try {
        // Fetch playlists and their songs
        $stmt = $pdo->prepare("
            SELECT p.playlist_id, p.name AS playlist_name, s.song_id, s.title AS song_title, s.artist, s.album
            FROM playlists p
            LEFT JOIN playlist_songs ps ON p.playlist_id = ps.playlist_id
            LEFT JOIN songs s ON ps.song_id = s.song_id
            WHERE p.user_id = ?
            ORDER BY p.name, s.title
        ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize songs under each playlist
        $playlists = [];
        foreach ($results as $row) {
            $playlist_id = $row['playlist_id'];
            if (!isset($playlists[$playlist_id])) {
                $playlists[$playlist_id] = [
                    'playlist_id' => $playlist_id,
                    'playlist_name' => $row['playlist_name'],
                    'songs' => []
                ];
            }
            if ($row['song_id']) { // Only add if there's an associated song
                $playlists[$playlist_id]['songs'][] = [
                    'song_id' => $row['song_id'],
                    'title' => $row['song_title'],
                    'artist' => $row['artist'],
                    'album' => $row['album']
                ];
            }
        }

        return ["status" => "success", "data" => array_values($playlists)];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to retrieve playlists: " . $e->getMessage()];
    }
}

// Retrieve user_id from command-line or POST data
$user_id = php_sapi_name() === 'cli' ? $argv[1] ?? null : $_POST['user_id'] ?? null;

// Validate input
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Please provide user_id."]);
    exit;
}

// Fetch playlists and output the response
$response = getUserPlaylists($user_id, $pdo);
echo json_encode($response);
?>

