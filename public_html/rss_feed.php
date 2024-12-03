<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalized RSS Feed</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'nav.php'; ?> 
    <h1>Personalized RSS Feed</h1>

    <!-- RSS Feed Display -->
    <div id="rssFeedContainer" class="results"></div>

    <script>
        // Fetch and Display RSS Feed
        function loadRSSFeed() {
            fetch('scripts/rssFeed.php')
                .then(response => response.json())
                .then(data => {
                    const rssFeedContainer = document.getElementById('rssFeedContainer');
                    rssFeedContainer.innerHTML = '';
                    if (data.error) {
                        rssFeedContainer.innerHTML = `<p>${data.error}</p>`;
                    } else {
                        data.forEach(feedItem => {
                            rssFeedContainer.innerHTML += `
                                <div class="rss-item">
                                    <h3>${feedItem.title}</h3>
                                    <p>${feedItem.description}</p>
                                    <p><a href="${feedItem.link}" target="_blank">Read more</a></p>
                                    <p><em>Published on:</em> ${feedItem.pubDate}</p>
                                </div>
                                <hr>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Load RSS feed on page load
        document.addEventListener('DOMContentLoaded', loadRSSFeed);
    </script>
</body>
</html>

