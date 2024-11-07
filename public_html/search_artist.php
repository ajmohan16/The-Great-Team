<?php
require 'get_spotify_token.php';

$access_token = getSpotifyToken();
if (!$access_token) {
    die('Failed to retrieve access token');
}

// Get artist name from the URL parameter
$artist_name = isset($_GET['artist']) ? $_GET['artist'] : null;
if (!$artist_name) {
    die('Artist name is required.');
}

$search_query = urlencode($artist_name);
$url = "https://api.spotify.com/v1/search?q=$search_query&type=artist";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Display artist information
header('Content-Type: application/json');
if (isset($data['artists']['items']) && count($data['artists']['items']) > 0) {
    $artist = $data['artists']['items'][0];
    echo json_encode([
        'Artist Name' => $artist['name'],
        'Genres' => $artist['genres'],
        'Followers' => $artist['followers']['total'],
        'Spotify Link' => $artist['external_urls']['spotify'],
    ]);
} else {
    echo json_encode(['error' => 'Artist not found.']);
}
?>
