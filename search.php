<?php
require_once 'lib/config.php';
// Spotify API endpoint for search
define("SEARCH_URL", "https://api.spotify.com/v1/search");

// Function to search Spotify for an artist, album, or song
function searchSpotify($query, $type = 'artist') {
    $accessToken = ACCESS_TOKEN;
    if (!$accessToken) {
        return "Unable to retrieve access token.";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SEARCH_URL . "?q=" . urlencode($query) . "&type=" . $type . "&limit=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Check if a search query was submitted
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = $_GET['query'];
    $result = searchSpotify($query, 'artist');

    // If an artist is found, redirect to the artist page
    if (!empty($result['artists']['items'][0])) {
        $artistId = $result['artists']['items'][0]['id'];
        header("Location: artist.php?id=" . $artistId);
        exit();
    } else {
        echo "No artist found for query: " . htmlspecialchars($query);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Search</title>
</head>
<body>
    <h1>Spotify Search</h1>
    <form action="search.php" method="get">
        <input type="text" name="query" placeholder="Search for artists, songs, or albums..." required>
        <button type="submit">Search</button>
    </form>
</body>
</html>
