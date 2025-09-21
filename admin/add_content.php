<?php
require_once 'auth.php';
require_login();
require_once '../config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Initialize variables
$content = [
    'id' => '', 'title' => '', 'description' => '', 'poster' => '', 'thumbnail' => '',
    'rating' => '', 'duration' => '', 'year' => '', 'country' => '', 'parental_rating' => '',
    'category_id' => '', 'genres' => [], 'servers' => [], 'seasons' => []
];
$page_title = 'Add New Content';
$is_edit_mode = false;

// Fetch all categories and genres for dropdowns
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$genres_result = $conn->query("SELECT * FROM genres ORDER BY name");

// --- Pre-load server templates from data.json ---
$movie_servers_template = [];
$tv_servers_template = [];
$json_data = json_decode(file_get_contents('../data.json'), true);
foreach ($json_data['Categories'] as $category) {
    if ($category['MainCategory'] === 'Movies' && !empty($category['Entries'][0]['Servers'])) {
        $movie_servers_template = $category['Entries'][0]['Servers'];
    }
    if ($category['MainCategory'] === 'TV Series' && !empty($category['Entries'][0]['Seasons'][0]['Episodes'][0]['Servers'])) {
        $tv_servers_template = $category['Entries'][0]['Seasons'][0]['Episodes'][0]['Servers'];
    }
}
// --- End pre-loading ---

$tmdb_import_data = null; // Variable to hold TMDB data for JS

if (isset($_GET['id']) && is_numeric($_GET['id'])) { // Edit Mode
    $is_edit_mode = true;
    $page_title = 'Edit Content';
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $content = array_merge($content, $result->fetch_assoc());
    $content['id'] = $id;
    // Fetch associated data
    $cat_res = $conn->query("SELECT name FROM categories WHERE id = " . $content['category_id']);
    $category_name = $cat_res->fetch_assoc()['name'];

    if ($category_name === 'Movies' || $category_name === 'Live TV') {
        $server_res = $conn->query("SELECT * FROM servers WHERE parent_id = $id AND parent_type = 'content'");
        while ($row = $server_res->fetch_assoc()) {
            $content['servers'][] = $row;
        }
    } elseif ($category_name === 'TV Series') {
        $season_res = $conn->query("SELECT * FROM seasons WHERE content_id = $id ORDER BY season_number ASC");
        while ($season = $season_res->fetch_assoc()) {
            $season_id = $season['id'];
            $episodes = [];
            $episode_res = $conn->query("SELECT * FROM episodes WHERE season_id = $season_id ORDER BY episode_number ASC");
            while ($episode = $episode_res->fetch_assoc()) {
                $episode_id = $episode['id'];
                $servers = [];
                $server_res = $conn->query("SELECT * FROM servers WHERE parent_id = $episode_id AND parent_type = 'episode'");
                 while ($server = $server_res->fetch_assoc()) {
                    $servers[] = $server;
                }
                $episode['servers'] = $servers;
                $episodes[] = $episode;
            }
            $season['episodes'] = $episodes;
            $content['seasons'][] = $season;
        }
    }

} elseif (isset($_GET['tmdb_id']) && is_numeric($_GET['tmdb_id'])) { // Import Mode
    require_once 'tmdb_api.php';
    $tmdb = new TMDB_API();
    $tmdb_id = $_GET['tmdb_id'];
    $type = $_GET['type'] ?? 'movie';
    $page_title = 'Import from TMDB';

    if ($type === 'movie') {
        $tmdb_data = $tmdb->get_movie_details($tmdb_id);
        if ($tmdb_data) {
            $cat_res = $conn->query("SELECT id FROM categories WHERE name = 'Movies' LIMIT 1");
            $content['category_id'] = $cat_res->fetch_assoc()['id'];
            $content['servers'] = $movie_servers_template; // Pre-fill servers
            $tmdb_import_data = $tmdb_data;
        }
    } else { // 'tv'
        $tmdb_data = $tmdb->get_tv_show_details($tmdb_id);
        if ($tmdb_data) {
            $cat_res = $conn->query("SELECT id FROM categories WHERE name = 'TV Series' LIMIT 1");
            $content['category_id'] = $cat_res->fetch_assoc()['id'];
            // We don't pre-fill the form here, the JS will do it.
            // But we pass the data to the JS.
            $tmdb_import_data = $tmdb_data;
        }
    }

    // Merge TMDB data into our content array
    if ($tmdb_data) {
        $content['title'] = $tmdb_data['title'];
        $content['description'] = $tmdb_data['description'];
        $content['poster'] = $tmdb_data['poster'];
        $content['year'] = $tmdb_data['year'];
        $content['rating'] = $tmdb_data['rating'];
        // Note: seasons are not put into the $content array directly anymore for import
        // JS will handle the dynamic creation
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - CineCraze Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="admin-wrapper">
    <div class="sidebar">
        <div class="logo">CineCraze Admin</div>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="manage_content.php">Manage Content</a></li>
            <li><a href="add_content.php" class="active">Add New Content</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header"><a href="logout.php">Logout</a></div>
        <div class="page-content">
            <h1><?= $page_title ?></h1>
            <form action="actions.php" method="POST" class="form-container">
                <input type="hidden" name="id" value="<?= htmlspecialchars($content['id']) ?>">
                <input type="hidden" name="action" value="<?= $is_edit_mode ? 'update_content' : 'add_content' ?>">

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($content['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php while ($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $content['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($content['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="poster">Poster URL</label>
                    <input type="url" id="poster" name="poster" value="<?= htmlspecialchars($content['poster']) ?>">
                </div>

                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" value="<?= htmlspecialchars($content['year']) ?>">
                </div>

                <div class="form-group">
                    <label for="rating">Rating</label>
                    <input type="text" id="rating" name="rating" value="<?= htmlspecialchars($content['rating']) ?>">
                </div>

                <!-- Server management for Movies/LiveTV -->
                <div id="servers-container" style="display:none;">
                    <h3>Servers</h3>
                    <div id="movie-servers-list">
                        <?php if (!empty($content['servers'])): ?>
                            <?php foreach ($content['servers'] as $server_index => $server): ?>
                                <div class="server-item" id="server-<?= $server_index ?>">
                                    <h6>Server
                                        <button type="button" class="btn-remove" onclick="document.getElementById('server-<?= $server_index ?>').remove()">X</button>
                                    </h6>
                                    <div class="form-group">
                                        <label>Name</label><input type="text" name="servers[<?= $server_index ?>][name]" value="<?= htmlspecialchars($server['name']) ?>">
                                        <label>URL</label><input type="url" name="servers[<?= $server_index ?>][url]" value="<?= htmlspecialchars($server['url']) ?>">
                                        <label>License URL</label><input type="text" name="servers[<?= $server_index ?>][license_url]" value="<?= htmlspecialchars($server['license_url'] ?? '') ?>">
                                        <label><input type="checkbox" name="servers[<?= $server_index ?>][is_drm]" value="1" <?= !empty($server['is_drm']) ? 'checked' : '' ?>> Is DRM?</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: // Provide a blank one for new entries ?>
                            <div class="server-item" id="server-0">
                                <h6>Server
                                    <button type="button" class="btn-remove" onclick="document.getElementById('server-0').remove()">X</button>
                                </h6>
                                <div class="form-group">
                                    <label>Name</label><input type="text" name="servers[0][name]">
                                    <label>URL</label><input type="url" name="servers[0][url]">
                                    <label>License URL</label><input type="text" name="servers[0][license_url]">
                                    <label><input type="checkbox" name="servers[0][is_drm]" value="1"> Is DRM?</label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-movie-server-btn" class="btn">+ Add Server</button>
                </div>

                <!-- Full season/episode management for TV Series -->
                <div id="tv-series-fields" style="display:none;">
                    <h3>Seasons & Episodes</h3>
                    <div id="seasons-container">
                    <?php if (!empty($content['seasons'])): ?>
                        <?php foreach ($content['seasons'] as $season_index => $season): ?>
                            <div class="season-block" id="season-<?= $season_index ?>">
                                <h4>Season <?= htmlspecialchars($season['season_number']) ?>
                                    <button type="button" class="btn-remove" onclick="document.getElementById('season-<?= $season_index ?>').remove()">X</button>
                                </h4>
                                <input type="hidden" name="seasons[<?= $season_index ?>][season_number]" value="<?= htmlspecialchars($season['season_number']) ?>">
                                <input type="hidden" name="seasons[<?= $season_index ?>][poster]" value="<?= htmlspecialchars($season['poster']) ?>">

                                <div class="episodes-container">
                                    <?php if(!empty($season['episodes'])) foreach ($season['episodes'] as $episode_index => $episode): ?>
                                        <div class="episode-block" id="season-<?= $season_index ?>-episode-<?= $episode_index ?>">
                                            <h5>Ep <?= htmlspecialchars($episode['episode_number']) ?>: <?= htmlspecialchars($episode['title']) ?>
                                                <button type="button" class="btn-remove" onclick="document.getElementById('season-<?= $season_index ?>-episode-<?= $episode_index ?>').remove()">X</button>
                                            </h5>
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][episode_number]" value="<?= htmlspecialchars($episode['episode_number']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][title]" value="<?= htmlspecialchars($episode['title']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][description]" value="<?= htmlspecialchars($episode['description']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][thumbnail]" value="<?= htmlspecialchars($episode['thumbnail']) ?>">

                                            <div class="servers-container-episode">
                                                <h6>Servers for this episode</h6>
                                                <?php if(!empty($episode['servers'])) foreach($episode['servers'] as $server_index => $server): ?>
                                                <div class="server-item" id="season-<?= $season_index ?>-episode-<?= $episode_index ?>-server-<?= $server_index ?>">
                                                    <h6>Server
                                                       <button type="button" class="btn-remove" onclick="document.getElementById('season-<?= $season_index ?>-episode-<?= $episode_index ?>-server-<?= $server_index ?>').remove()">X</button>
                                                    </h6>
                                                    <div class="form-group">
                                                        <label>Name</label><input type="text" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][<?= $server_index ?>][name]" value="<?= htmlspecialchars($server['name']) ?>" >
                                                        <label>URL</label><input type="url" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][<?= $server_index ?>][url]" value="<?= htmlspecialchars($server['url']) ?>">
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                             <button type="button" class="btn btn-secondary add-server-btn-episode" data-season-index="<?= $season_index ?>" data-episode-index="<?= $episode_index ?>">+ Add Server</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary add-episode-btn" data-season-index="<?= $season_index ?>">+ Add Episode</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                    <button type="button" id="add-season-btn" class="btn">+ Add Season</button>
                </div>

                <button type="submit" class="btn"><?= $is_edit_mode ? 'Update' : 'Save' ?> Content</button>
                <a href="manage_content.php" class="btn btn-secondary" style="margin-left: 10px;">Go Back</a>
            </form>
        </div>
    </div>
</div>
<script>
// Pass PHP data to JS
const tmdbImportData = <?= json_encode($tmdb_import_data) ?>;
const serverTemplates = {
    movie: <?= json_encode($movie_servers_template) ?>,
    tv: <?= json_encode($tv_servers_template) ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const tvSeriesFields = document.getElementById('tv-series-fields');
    const serversContainer = document.getElementById('servers-container');

    function toggleFields() {
        const selectedCategoryText = categorySelect.options[categorySelect.selectedIndex].text.trim();
        if (selectedCategoryText === 'TV Series') {
            tvSeriesFields.style.display = 'block';
            serversContainer.style.display = 'none';
        } else if (selectedCategoryText === 'Movies' || selectedCategoryText === 'Live TV') {
            tvSeriesFields.style.display = 'none';
            serversContainer.style.display = 'block';
        } else {
            tvSeriesFields.style.display = 'none';
            serversContainer.style.display = 'none';
        }
    }

    categorySelect.addEventListener('change', toggleFields);
    toggleFields(); // Initial check

    // --- Dynamic Movie/LiveTV Servers ---
    const movieServersList = document.getElementById('movie-servers-list');
    let movieServerCounter = movieServersList.querySelectorAll('.server-item').length;
    document.getElementById('add-movie-server-btn').addEventListener('click', function() {
        const serverId = `server-${movieServerCounter}`;
        const serverHtml = `
            <div class="server-item" id="${serverId}">
                <h6>New Server <button type="button" class="btn-remove" onclick="document.getElementById('${serverId}').remove()">X</button></h6>
                <div class="form-group">
                    <label>Name</label><input type="text" name="servers[${movieServerCounter}][name]" required>
                    <label>URL</label><input type="url" name="servers[${movieServerCounter}][url]" required>
                    <label>License URL</label><input type="text" name="servers[${movieServerCounter}][license_url]">
                    <label><input type="checkbox" name="servers[${movieServerCounter}][is_drm]" value="1"> Is DRM?</label>
                </div>
            </div>`;
        movieServersList.insertAdjacentHTML('beforeend', serverHtml);
        movieServerCounter++;
    });


    // --- Dynamic Seasons and Episodes ---
    const seasonsContainer = document.getElementById('seasons-container');
    let seasonCounter = seasonsContainer.querySelectorAll('.season-block').length;

    document.getElementById('add-season-btn').addEventListener('click', function() {
        const seasonId = `new-season-${seasonCounter}`;
        const seasonHtml = `
            <div class="season-block" id="${seasonId}">
                <h4>New Season <button type="button" class="btn-remove" onclick="document.getElementById('${seasonId}').remove()">X</button></h4>
                <div class="form-group">
                    <label>Season Number</label>
                    <input type="number" name="seasons[${seasonCounter}][season_number]" required>
                </div>
                <div class="episodes-container"></div>
                <button type="button" class="btn btn-secondary add-episode-btn" data-season-index="${seasonCounter}">+ Add Episode</button>
            </div>`;
        seasonsContainer.insertAdjacentHTML('beforeend', seasonHtml);
        seasonCounter++;
    });

    seasonsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('add-episode-btn')) {
            const btn = e.target;
            const seasonIndex = btn.dataset.seasonIndex;
            const episodesContainer = btn.previousElementSibling;
            let episodeCounter = episodesContainer.querySelectorAll('.episode-block').length;
            const episodeId = `s${seasonIndex}-ep${episodeCounter}`;

            const episodeHtml = `
                <div class="episode-block" id="${episodeId}">
                    <h5>New Episode <button type="button" class="btn-remove" onclick="document.getElementById('${episodeId}').remove()">X</button></h5>
                    <div class="form-group"><label>Episode Number</label><input type="number" name="seasons[${seasonIndex}][episodes][${episodeCounter}][episode_number]" required></div>
                    <div class="form-group"><label>Episode Title</label><input type="text" name="seasons[${seasonIndex}][episodes][${episodeCounter}][title]" required></div>
                    <div class="servers-container-episode"></div>
                    <button type="button" class="btn btn-secondary add-server-btn-episode" data-season-index="${seasonIndex}" data-episode-index="${episodeCounter}">+ Add Server</button>
                </div>`;
            episodesContainer.insertAdjacentHTML('beforeend', episodeHtml);
        }

        if (e.target && e.target.classList.contains('add-server-btn-episode')) {
            const btn = e.target;
            const seasonIndex = btn.dataset.seasonIndex;
            const episodeIndex = btn.dataset.episodeIndex;
            const serverContainer = btn.previousElementSibling;
            let serverCounter = serverContainer.querySelectorAll('.server-item').length;
            const serverId = `s${seasonIndex}-ep${episodeIndex}-sv${serverCounter}`;

            const serverHtml = `
                <div class="server-item" id="${serverId}">
                    <h6>New Server <button type="button" class="btn-remove" onclick="document.getElementById('${serverId}').remove()">X</button></h6>
                    <div class="form-group"><label>Name</label><input type="text" name="seasons[${seasonIndex}][episodes][${episodeIndex}][servers][${serverCounter}][name]" required></div>
                    <div class="form-group"><label>URL</label><input type="url" name="seasons[${seasonIndex}][episodes][${episodeIndex}][servers][${serverCounter}][url]" required></div>
                </div>`;
            serverContainer.insertAdjacentHTML('beforeend', serverHtml);
        }
    });

    // --- TMDB Import Population ---
    function populateTvShowForm(showData) {
        if (!showData || !showData.seasons) return;
        seasonsContainer.innerHTML = ''; // Clear existing
        let seasonIndex = 0;
        showData.seasons.forEach(season => {
            const seasonId = `season-${seasonIndex}`;
            const seasonHtml = `
                <div class="season-block" id="${seasonId}">
                    <h4>Season ${season.season_number}
                        <button type="button" class="btn-remove" onclick="document.getElementById('${seasonId}').remove()">X</button>
                    </h4>
                    <input type="hidden" name="seasons[${seasonIndex}][season_number]" value="${season.season_number}">
                    <input type="hidden" name="seasons[${seasonIndex}][poster]" value="${season.poster || ''}">
                    <div class="episodes-container"></div>
                </div>`;
            seasonsContainer.insertAdjacentHTML('beforeend', seasonHtml);
            const episodesContainer = seasonsContainer.querySelector(`#${seasonId} .episodes-container`);
            populateEpisodesForSeason(episodesContainer, seasonIndex, season.episodes);
            seasonIndex++;
        });
        seasonCounter = seasonIndex; // Update global counter
    }

    function populateEpisodesForSeason(container, seasonIndex, episodes) {
        let episodeIndex = 0;
        episodes.forEach(episode => {
            const episodeId = `s${seasonIndex}-ep${episodeIndex}`;
            const episodeHtml = `
                <div class="episode-block" id="${episodeId}">
                    <h5>Ep ${episode.episode_number}: ${episode.title}
                        <button type="button" class="btn-remove" onclick="document.getElementById('${episodeId}').remove()">X</button>
                    </h5>
                    <input type="hidden" name="seasons[${seasonIndex}][episodes][${episodeIndex}][episode_number]" value="${episode.episode_number}">
                    <input type="hidden" name="seasons[${seasonIndex}][episodes][${episodeIndex}][title]" value="${episode.title}">
                    <input type="hidden" name="seasons[${seasonIndex}][episodes][${episodeIndex}][description]" value="${episode.description || ''}">
                    <input type="hidden" name="seasons[${seasonIndex}][episodes][${episodeIndex}][thumbnail]" value="${episode.thumbnail || ''}">
                    <div class="servers-container-episode">
                        <h6>Servers for this episode</h6>
                        ${generateServerInputs(`seasons[${seasonIndex}][episodes][${episodeIndex}][servers]`, serverTemplates.tv, `s${seasonIndex}-ep${episodeIndex}-sv`)}
                    </div>
                </div>`;
            container.insertAdjacentHTML('beforeend', episodeHtml);
            episodeIndex++;
        });
    }

    function generateServerInputs(namePrefix, servers, idPrefix) {
        let html = '';
        let serverIndex = 0;
        servers.forEach(server => {
            const serverId = `${idPrefix}-${serverIndex}`;
            const isDrmChecked = server.drm || server.is_drm ? 'checked' : '';
            html += `
                <div class="server-item" id="${serverId}">
                    <h6>Server <button type="button" class="btn-remove" onclick="document.getElementById('${serverId}').remove()">X</button></h6>
                    <div class="form-group">
                        <label>Name</label><input type="text" name="${namePrefix}[${serverIndex}][name]" value="${server.name || ''}">
                        <label>URL</label><input type="url" name="${namePrefix}[${serverIndex}][url]" value="${server.url || ''}">
                        <label>License URL</label><input type="text" name="${namePrefix}[${serverIndex}][license_url]" value="${server.license || server.license_url || ''}">
                        <label><input type="checkbox" name="${namePrefix}[${serverIndex}][is_drm]" value="1" ${isDrmChecked}> Is DRM?</label>
                    </div>
                </div>`;
            serverIndex++;
        });
        return html;
    }

    if (tmdbImportData) {
        const selectedCategoryText = categorySelect.options[categorySelect.selectedIndex].text.trim();
        if (selectedCategoryText === 'TV Series') {
            populateTvShowForm(tmdbImportData);
        }
        toggleFields(); // Ensure correct section is shown after population
    }
});
</script>
</body>
</html>
