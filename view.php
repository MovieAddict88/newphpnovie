<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze</title>
    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet" />
    <link rel="stylesheet" href="static/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body id="viewer-page">

    <main class="container viewer-container" id="viewer-container">
        <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Browse</a>

        <div id="player-area" class="player-area">
            <video id="main-player" class="video-js vjs-big-play-centered" controls preload="auto" width="100%" height="100%"></video>
            <div id="iframe-container" style="width:100%; height:100%; display:none;"></div>
        </div>

        <div class="details-header">
            <h1 id="details-title">Loading...</h1>
            <div class="actions">
                <!-- Add actions like share, add to watchlist etc. here -->
            </div>
        </div>

        <p id="details-meta" class="details-meta">Loading meta data...</p>
        <p id="details-description" class="details-description">Loading description...</p>

        <div class="selectors">
            <select id="server-select">
                <option>Loading servers...</option>
            </select>
            <div id="episode-select-container" style="flex-grow: 1;">
                 <select id="episode-select">
                    <option>Loading episodes...</option>
                </select>
            </div>
        </div>

        <div id="seasons-list" class="seasons-list">
            <h3>Seasons</h3>
            <div id="seasons-list-container">
                <!-- Seasons will be loaded here by app.js -->
            </div>
        </div>

        <!-- Related Content can be added here -->

    </main>

    <!-- Shaka Player -->
    <script src="https://ajax.googleapis.com/ajax/libs/shaka-player/4.3.4/shaka-player.compiled.js"></script>
    <!-- Video.js -->
    <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
    <script src="static/app.js"></script>
</body>
</html>
