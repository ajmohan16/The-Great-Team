<?php
// Include the config file
require_once 'lib/config.php';
//include navigation bar
include 'nav.php';
//inclue search bar
include 'search.php';


// Spotify API endpoints
define("NEW_RELEASES_URL", "https://api.spotify.com/v1/browse/new-releases");
define("TRENDING_ALBUMS_URL", "https://api.spotify.com/v1/browse/featured-playlists");



// Fetch trending albums
function getTrendingPlaylists($limit = 10) {
    $accessToken = getSpotifyAccessToken();
    if (!$accessToken) {
        return "Unable to retrieve access token.";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TRENDING_ALBUMS_URL . "?limit=" . $limit);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['playlists']['items'] ?? [];
}

// Fetch new releases
function getNewReleases($limit = 10) {
    $accessToken = getSpotifyAccessToken();
    if (!$accessToken) {
        return "Unable to retrieve access token.";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, NEW_RELEASES_URL . "?limit=" . $limit);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['albums']['items'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Music Landing Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #1DB954;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .album {
            text-align: center;
        }
        .album img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        button {
            display: block;
            margin: 10px auto;
            padding: 10px 20px;
            background-color: #1DB954;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Trending Albums Section -->
    <section id="trending-albums">
        <h2>Trending Playlists</h2>
        <div class="grid" id="trendingGrid">
            <?php
            $trendingAlbums = getTrendingPlaylists();
            foreach ($trendingAlbums as $album) {
                echo "<div class='album'>";
                echo "<img src='" . $album['images'][0]['url'] . "' alt='" . $album['name'] . "' />";
                echo "<p>" . htmlspecialchars($album['name']) . "</p>";
                echo "<p>by " . htmlspecialchars($album['owner']['display_name']) . "</p>";
                echo "</div>";
            }
            ?>
        </div>
        <button id="seeMoreTrending" onclick="loadMore('trending')">See More</button>
    </section>

    <!-- New Releases Section -->
    <section id="new-releases">
        <h2>New Releases</h2>
        <div class="grid" id="newReleasesGrid">
            <?php
            $newReleases = getNewReleases();
            foreach ($newReleases as $album) {
                echo "<div class='album'>";
                echo "<img src='" . $album['images'][0]['url'] . "' alt='" . $album['name'] . "' />";
                echo "<p>" . htmlspecialchars($album['name']) . "</p>";
                echo "<p>by " . htmlspecialchars($album['artists'][0]['name']) . "</p>";
                echo "</div>";
            }
            ?>
        </div>
        <button id="seeMoreNewReleases" onclick="loadMore('newReleases')">See More</button>
    </section>
</div>

<script>
let trendingLimit = 10;
let newReleasesLimit = 10;

function loadMore(type) {
    let limit = type === 'trending' ? (trendingLimit += 10) : (newReleasesLimit += 10);
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `?type=${type}&limit=${limit}`, true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById(`${type}Grid`).innerHTML = this.responseText;
        }
    };
    xhr.send();
}

<?php
// Server-side handler for AJAX requests
if (isset($_GET['type']) && isset($_GET['limit'])) {
    $type = $_GET['type'];
    $limit = $_GET['limit'];
    $albums = ($type === 'trending') ? getTrendingPlaylists($limit) : getNewReleases($limit);

    foreach ($albums as $album) {
        echo "<div class='album'>";
        echo "<img src='" . $album['images'][0]['url'] . "' alt='" . $album['name'] . "' />";
        echo "<p>" . htmlspecialchars($album['name']) . "</p>";
        echo "<p>by " . htmlspecialchars($album['artists'][0]['name'] ?? $album['owner']['display_name']) . "</p>";
        echo "</div>";
    }
    exit;
}
?>
</script>

</body>
</html>

