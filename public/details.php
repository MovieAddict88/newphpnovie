<?php
require_once '../src/database.php';
require_once '../src/db_queries.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(404);
    die("Error: Content ID is missing or invalid.");
}

$content_id = (int)$_GET['id'];
$pdo = get_pdo_connection();
$item = getContentById($pdo, $content_id);

if (!$item) {
    http_response_code(404);
    die("Error: Content not found.");
}

$servers = [];
$seasons = [];
$episodes = [];
$initial_episodes_data = '[]';

if ($item['content_type'] === 'tv_series') {
    $seasons = getSeasonsBySeriesId($pdo, $content_id);
    if (!empty($seasons)) {
        // Load episodes for the first season by default
        $episodes = getEpisodesBySeasonId($pdo, $seasons[0]['id']);
        if (!empty($episodes)) {
            // Load servers for the first episode of the first season
            $servers = getServersByEpisodeId($pdo, $episodes[0]['id']);
        }

        // Prepare episodes data for JavaScript
        $all_episodes_by_season = [];
        foreach($seasons as $season) {
            $all_episodes_by_season[$season['id']] = getEpisodesBySeasonId($pdo, $season['id']);
        }
        $initial_episodes_data = json_encode($all_episodes_by_season);
    }
} else { // Movie or Live TV
    $servers = getServersByContentId($pdo, $content_id);
}

$first_server_url = !empty($servers) ? $servers[0]['url'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> - CineCraze</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/shaka-player@4.7.11/dist/shaka-player.compiled.js"></script>
</head>
<body class="details-page">

    <header class="main-header">
        <div class="logo"><a href="index.php">CineCraze</a></div>
        <nav class="main-nav">
            <a href="index.php" class="back-link">← Back to Browse</a>
        </nav>
    </header>

    <main>
        <section class="player-section">
            <div class="player-container">
                <!-- Shaka Player will attach to this -->
                <video id="shaka-player" width="100%" height="100%" style="display:none;" controls autoplay></video>

                <!-- Iframe for embeddable content -->
                <iframe id="iframe-player" src="" frameborder="0" allowfullscreen style="display:none;"></iframe>

                <div id="no-player-message" class="no-player" style="display:none;">
                    <p>No video sources available for this content.</p>
                </div>
            </div>
        </section>

        <section class="details-section">
            <h1 class="content-title"><?= htmlspecialchars($item['title']) ?></h1>
            <p class="content-description"><?= nl2br(htmlspecialchars($item['description'])) ?></p>

            <div class="selectors-container">
                <!-- Server Selector -->
                <div class="selector-group">
                    <label for="server-select">Select Server</label>
                    <select id="server-select">
                        <?php if (empty($servers)): ?>
                            <option value="">No servers available</option>
                        <?php else: ?>
                            <?php foreach ($servers as $server): ?>
                                <option value="<?= htmlspecialchars($server['url']) ?>" <?php if (!empty($server['license_url'])) echo 'data-license="'.htmlspecialchars($server['license_url']).'"'; ?>>
                                    <?= htmlspecialchars($server['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($item['content_type'] === 'tv_series'): ?>
                    <!-- Season Selector -->
                    <div class="selector-group">
                        <label for="season-select">Select Season</label>
                        <select id="season-select">
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= $season['id'] ?>">Season <?= $season['season_number'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Episode Selector -->
                    <div class="selector-group">
                        <label for="episode-select">Select Episode</label>
                        <select id="episode-select">
                            <?php foreach ($episodes as $episode): ?>
                                <option value="<?= $episode['id'] ?>" data-servers-url="api/get_servers.php?episode_id=<?= $episode['id'] ?>">
                                    Ep <?= $episode['episode_number'] ?>: <?= htmlspecialchars($episode['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($item['content_type'] === 'tv_series' && !empty($seasons)): ?>
            <div class="seasons-display">
                 <h3>Seasons</h3>
                 <div class="seasons-grid">
                    <?php foreach ($seasons as $season): ?>
                        <div class="season-card" data-season-id="<?= $season['id'] ?>">
                            <img src="<?= htmlspecialchars($season['poster']) ?>" alt="Season <?= $season['season_number'] ?>">
                            <div class="season-title">Season <?= $season['season_number'] ?></div>
                        </div>
                    <?php endforeach; ?>
                 </div>
            </div>
            <?php endif; ?>

        </section>

        <!-- Related Content can be added here later -->

    </main>

    <script>
        // Pass episode data from PHP to JavaScript
        const episodesBySeason = <?= $initial_episodes_data ?>;
    </script>
    <script src="js/details.js"></script>

</body>
</html>
