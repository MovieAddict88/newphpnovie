<?php
require_once 'auth.php';
require_login();
require_once 'tmdb_api.php';

$search_results = [];
$search_query = '';
$search_type = 'movie';

if (isset($_GET['query'])) {
    $tmdb = new TMDB_API();
    $search_query = $_GET['query'];
    $search_type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    $search_results = $tmdb->search($search_query, $search_type);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TMDB Importer - CineCraze Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-results { list-style: none; padding: 0; }
        .result-item { display: flex; align-items: center; background: #fff; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .result-item img { width: 60px; margin-right: 15px; border-radius: 4px; }
        .result-item .info { flex-grow: 1; }
        .result-item .info h4 { margin: 0 0 5px 0; }
        .result-item .info p { margin: 0; color: #666; }
        .tab-nav { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .tab-nav button { background: #eee; border: none; padding: 10px 15px; cursor: pointer; border-radius: 5px 5px 0 0; margin-right: 5px; }
        .tab-nav button.active { background: #fff; border: 1px solid #ddd; border-bottom: none; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <div class="sidebar">
        <div class="logo">CineCraze Admin</div>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="manage_content.php">Manage Content</a></li>
            <li><a href="add_content.php">Add New Content</a></li>
            <li><a href="tmdb_importer.php" class="active">TMDB Importer</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header"><a href="logout.php">Logout</a></div>
        <div class="page-content">
            <h1>Import from TMDB</h1>

            <div class="tab-nav">
                <button class="active" onclick="showTab('generate')">Auto Generate</button>
                <button onclick="showTab('search')">Search Mode</button>
            </div>

            <!-- Auto-generation Tab -->
            <div id="generate" class="tab-content active">
                <h2>Regional Generation</h2>
                <div class="form-container">
                    <form action="auto_generate.php" method="GET">
                        <div class="form-group">
                            <label for="gen-type">Content Type</label>
                            <select id="gen-type" name="type">
                                <option value="movie">Movies</option>
                                <option value="tv">TV Series</option>
                                <option value="both">Both Movies and Series</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gen-year">Year</label>
                            <select id="gen-year" name="year">
                                <option value="">Any Year</option>
                                <?php for ($y = date('Y'); $y >= 1950; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gen-region">Regional</label>
                            <select id="gen-region" name="region">
                                <option value="hollywood">Hollywood</option>
                                <option value="k-drama">K-Drama</option>
                                <option value="c-drama">C-drama</option>
                                <option value="japanese">Japanese</option>
                                <option value="pinoy">Pinoy</option>
                                <option value="thai">Thai</option>
                                <option value="indian">Indian</option>
                                <option value="turkey">Turkey</option>
                                <option value="korean-variety">Korean Variety show</option>
                                <option value="anime">Anime</option>
                                <option value="cartoon-family">Cartoon/Family</option>
                            </select>
                        </div>
                        <button type="submit" class="btn">Generate</button>
                    </form>
                </div>
            </div>

            <!-- Search Mode Tab -->
            <div id="search" class="tab-content">
                <h2>Search Mode</h2>
                <div class="form-container">
                    <form action="tmdb_importer.php" method="GET">
                        <input type="hidden" name="tab" value="search">
                        <div class="form-group">
                            <label for="query">Search Title</label>
                            <input type="text" id="query" name="query" value="<?= htmlspecialchars($search_query) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><input type="radio" name="type" value="movie" <?= $search_type === 'movie' ? 'checked' : '' ?>> Movie</label>
                            <label><input type="radio" name="type" value="tv" <?= $search_type === 'tv' ? 'checked' : '' ?>> TV Show</label>
                        </div>
                        <button type="submit" class="btn">Search</button>
                    </form>
                </div>
            </div>


            <?php if (!empty($search_results)): ?>
            <h2 style="margin-top: 20px;">Search Results</h2>
            <ul class="search-results">
                <?php foreach ($search_results as $result): ?>
                <li class="result-item">
                    <img src="https://image.tmdb.org/t/p/w200/<?= $result['poster_path'] ?>" alt="Poster">
                    <div class="info">
                        <h4><?= htmlspecialchars($result['title'] ?? $result['name']) ?></h4>
                        <p>Year: <?= htmlspecialchars(substr($result['release_date'] ?? $result['first_air_date'] ?? '', 0, 4)) ?></p>
                    </div>
                    <a href="add_content.php?tmdb_id=<?= $result['id'] ?>&type=<?= $search_type ?>" class="btn">Import</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    function showTab(tabName) {
        // Hide all tab content
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));

        // Deactivate all tab buttons
        const buttons = document.querySelectorAll('.tab-nav button');
        buttons.forEach(btn => btn.classList.remove('active'));

        // Show the selected tab and activate its button
        document.getElementById(tabName).classList.add('active');
        document.querySelector(`.tab-nav button[onclick="showTab('${tabName}')"]`).classList.add('active');
    }

    // On page load, check if we should be on the search tab
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'search') {
            showTab('search');
        } else {
            showTab('generate');
        }
    });
</script>
</body>
</html>
