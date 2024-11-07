<?php
require 'get_spotify_token.php';

$access_token = getSpotifyToken();
if (!$access_token) {
    die('Failed to retrieve access token');
}

$artist_id = '4dpARuHxo51G3z768sgnrY';  // Replace with the artist ID obtained from `search_artist.php`
$url = "https://api.spotify.com/v1/artists/$artist_id/albums";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
]);

$response = curl_exec($ch);
curl_close($ch);

$albums = json_decode($response, true);

// Display album information
foreach ($albums['items'] as $album) {
    echo "Album: " . $album['name'] . "\n";
    echo "Release Date: " . $album['release_date'] . "\n";
    echo "Spotify Link: " . $album['external_urls']['spotify'] . "\n";
    echo "-------------------\n";
}
?>

