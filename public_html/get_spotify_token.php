<?php
function getSpotifyToken() {
    $client_id = '5c2c4333fe354aaabdad0d4cd19e11f7';
    $client_secret = '663fe28f0be14bb599694dfbe0a451a2';

    // Prepare the authorization header
    $auth = base64_encode("$client_id:$client_secret");

    // Request a token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}
?>

