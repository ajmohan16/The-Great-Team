<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Recommendations</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'nav.php'; ?> 
    <h1>Recommended Songs</h1>

    <!-- Recommendations Display -->
    <div id="recommendationsContainer" class="results"></div>

    <script>
        // Fetch and Display Recommendations
        function loadRecommendations() {
            fetch('scripts/recommend_songs.php')
                .then(response => response.json())
                .then(data => {
                    const recommendationsContainer = document.getElementById('recommendationsContainer');
                    recommendationsContainer.innerHTML = '';
                    if (data.error) {
                        recommendationsContainer.innerHTML = `<p>${data.error}</p>`;
                    } else {
                        data.forEach(song => {
                            recommendationsContainer.innerHTML += `
                                <div class="song">
                                    <p><strong>Title:</strong> ${song.name}</p>
                                    <p><strong>Artist:</strong> ${song.artist}</p>
                                    <p><strong>Album:</strong> ${song.album}</p>
                                    <p><a href="${song.link}" target="_blank">Listen on Spotify</a></p>
                                </div>
                                <hr>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Load recommendations on page load
        document.addEventListener('DOMContentLoaded', loadRecommendations);
    </script>
</body>
</html>

