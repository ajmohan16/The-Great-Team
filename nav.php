<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <style>
        /* Basic styling for the navigation bar */
        .navbar {
            display: flex;
            justify-content: space-around;
            align-items: center;
            background-color: #1DB954;
            padding: 15px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            padding: 10px;
        }
        .navbar a:hover {
            background-color: #14833b;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="landing_page.php">Home</a>
        <a href="login.php">Login</a>
        <a href="my_library.php">My Library</a>
        <a href="recommended.php">Recommended</a>
        <a href="news.php">News</a>
    </nav>
</body>
</html>

