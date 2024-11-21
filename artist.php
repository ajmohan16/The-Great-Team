<?php
require_once 'config.php';
// Spotify API endpoints
define("ARTIST_URL", "https://api.spotify.com/v1/artists");
define("ARTIST_ALBUMS_URL", "https://api.spotify.com/v1/artists/{id}/albums");

// Function to get artist details
function getArtist($id) {
    $accessToken = ACCESS_TOKEN;
    if (!$accessToken) {
        return null;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, ARTIST_URL . "/" . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Function to get albums of the artist
function getArtistAlbums($id) {
    $accessToken = ACCESS_TOKEN;
    if (!$accessToken) {
        return [];
    }

    $url = str_replace("{id}", $id, ARTIST_ALBUMS_URL) . "?include_groups=album&limit=10";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true)['items'];
}

// Check if an artist ID was provided
if (isset($_GET['id'])) {
    $artistId = $_GET['id'];

    // Get artist details and albums
    $artist = getArtist($artistId);
    $albums = getArtistAlbums($artistId);
} else {
    echo "No artist ID provided.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artist['name']); ?> - Spotify</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($artist['name']); ?></h1>
    <p>Genre: <?php echo implode(', ', $artist['genres']); ?></p>
    <p>Followers: <?php echo number_format($artist['followers']['total']); ?></p>
    <img src="<?php echo $artist['images'][0]['url']; ?>" alt="<?php echo htmlspecialchars($artist['name']); ?>" width="200">

    <h2>Albums</h2>
    <div>
        <?php foreach ($albums as $album): ?>
            <div>
                <img src="<?php echo $album['images'][0]['url']; ?>" alt="<?php echo htmlspecialchars($album['name']); ?>" width="150">
                <p><?php echo htmlspecialchars($album['name']); ?></p>
                <p>Release Date: <?php echo htmlspecialchars($album['release_date']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
