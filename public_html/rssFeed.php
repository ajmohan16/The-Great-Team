<?php
function getUserRSSFeed($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT name FROM artists 
                            JOIN user_likes_dislikes ON artists.artist_id = user_likes_dislikes.song_id
                            WHERE user_likes_dislikes.user_id = ? AND user_likes_dislikes.liked = TRUE");
    $stmt->execute([$user_id]);
    $liked_artists = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $url = "https://musicnewsrss.com/artist/";
    $rss_feed = fetchRSS($url);

    $filtered_feed = array_filter($rss_feed, function($item) use ($liked_artists) {
        foreach ($liked_artists as $artist) {
            if (stripos($item['title'], $artist) !== false) {
                return true;
            }
        }
        return false;
    });

    return $filtered_feed;
}

function fetchRSS($url) {
    $rss = simplexml_load_file($url);
    $items = [];
    foreach ($rss->channel->item as $item) {
        $items[] = [
            'title' => (string) $item->title,
            'link' => (string) $item->link,
            'description' => (string) $item->description
        ];
    }
    return $items;
}
?>
