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
    // In a full app, you'd also fetch and populate genres, servers, seasons etc. here for editing.

} elseif (isset($_GET['tmdb_id']) && is_numeric($_GET['tmdb_id'])) { // Import Mode
    require_once 'tmdb_api.php';
    $tmdb = new TMDB_API();
    $tmdb_id = $_GET['tmdb_id'];
    $type = $_GET['type'] ?? 'movie';
    $page_title = 'Import from TMDB';

    if ($type === 'movie') {
        $tmdb_data = $tmdb->get_movie_details($tmdb_id);
        $cat_res = $conn->query("SELECT id FROM categories WHERE name = 'Movies' LIMIT 1");
        $content['category_id'] = $cat_res->fetch_assoc()['id'];
    } else { // 'tv'
        $tmdb_data = $tmdb->get_tv_show_details($tmdb_id);
        $cat_res = $conn->query("SELECT id FROM categories WHERE name = 'TV Series' LIMIT 1");
        $content['category_id'] = $cat_res->fetch_assoc()['id'];
        // Note: Populating seasons/episodes would require significant JS and form name changes
        // For now, we just import the main show data.
        $content['seasons'] = $tmdb_data['seasons'] ?? [];
    }

    // Merge TMDB data into our content array
    if ($tmdb_data) {
        $content['title'] = $tmdb_data['title'];
        $content['description'] = $tmdb_data['description'];
        $content['poster'] = $tmdb_data['poster'];
        $content['year'] = $tmdb_data['year'];
        $content['rating'] = $tmdb_data['rating'];
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

                <!-- Simplified server management for now -->
                <div id="servers-container">
                    <h3>Servers</h3>
                    <div class="form-group">
                       <label>Server Name</label><input type="text" name="servers[0][name]" placeholder="e.g., VidSrc 1080p">
                       <label>Server URL</label><input type="url" name="servers[0][url]" placeholder="https://...">
                       <label>License URL (for DRM)</label><input type="text" name="servers[0][license_url]" placeholder="Optional">
                       <label><input type="checkbox" name="servers[0][is_drm]" value="1"> Is DRM?</label>
                    </div>
                     <!-- A 'add more' button would be here, managed by JS -->
                </div>

                <!-- Full season/episode management for TV Series -->
                <div id="tv-series-fields" style="display:none;">
                    <h3>Seasons & Episodes</h3>
                    <div id="seasons-container">
                    <?php if (!empty($content['seasons'])): ?>
                        <?php foreach ($content['seasons'] as $season_index => $season): ?>
                            <div class="season-block" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                                <h4>Season <?= htmlspecialchars($season['season_number']) ?></h4>
                                <input type="hidden" name="seasons[<?= $season_index ?>][season_number]" value="<?= htmlspecialchars($season['season_number']) ?>">
                                <input type="hidden" name="seasons[<?= $season_index ?>][poster]" value="<?= htmlspecialchars($season['poster']) ?>">

                                <div class="episodes-container">
                                    <?php foreach ($season['episodes'] as $episode_index => $episode): ?>
                                        <div class="episode-block" style="border-left: 3px solid #ccc; padding-left: 15px; margin-top: 15px;">
                                            <h5>Ep <?= htmlspecialchars($episode['episode_number']) ?>: <?= htmlspecialchars($episode['title']) ?></h5>
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][episode_number]" value="<?= htmlspecialchars($episode['episode_number']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][title]" value="<?= htmlspecialchars($episode['title']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][description]" value="<?= htmlspecialchars($episode['description']) ?>">
                                            <input type="hidden" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][thumbnail]" value="<?= htmlspecialchars($episode['thumbnail']) ?>">

                                            <div class="servers-container-episode" style="padding-left: 20px; margin-top: 10px; background: #fff; padding:10px; border-radius:4px;">
                                                <h6>Servers for this episode (add URLs manually)</h6>
                                                <div class="form-group">
                                                    <label>Server 1 Name</label><input type="text" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][0][name]" placeholder="e.g., VidSrc 1080p">
                                                    <label>Server 1 URL</label><input type="url" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][0][url]" placeholder="https://...">
                                                </div>
                                                 <div class="form-group">
                                                    <label>Server 2 Name</label><input type="text" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][1][name]" placeholder="e.g., Backup Server">
                                                    <label>Server 2 URL</label><input type="url" name="seasons[<?= $season_index ?>][episodes][<?= $episode_index ?>][servers][1][url]" placeholder="https://...">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn"><?= $is_edit_mode ? 'Update' : 'Save' ?> Content</button>
            </form>
        </div>
    </div>
</div>
<script>
    // JS to show/hide TV Series fields based on category selection
    document.getElementById('category_id').addEventListener('change', function() {
        // In the DB, 'TV Series' usually gets id 3 after 'Live TV' and 'Movies'
        const isTVSeries = this.options[this.selectedIndex].text.trim() === 'TV Series';
        document.getElementById('tv-series-fields').style.display = isTVSeries ? 'block' : 'none';
    });
    // Trigger change on load for edit mode
    document.getElementById('category_id').dispatchEvent(new Event('change'));
</script>
</body>
</html>
