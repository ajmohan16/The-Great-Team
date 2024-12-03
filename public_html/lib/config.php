<?php
// Load .env file 
$env = parse_ini_file(__DIR__ . '/.env');

// Define constants or set environment variables
define("CLIENT_ID", $env['SPOTIFY_CLIENT_ID']);
define("CLIENT_SECRET", $env['SPOTIFY_CLIENT_SECRET']);
define("AUTH_URL", "https://accounts.spotify.com/api/token");
define("ACCESS_TOKEN", getSpotifyAccessToken()); 
// Function to get Spotify Access Token
function getSpotifyAccessToken() {
    $auth = base64_encode(CLIENT_ID . ":" . CLIENT_SECRET);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, AUTH_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . $auth,
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}
?>

