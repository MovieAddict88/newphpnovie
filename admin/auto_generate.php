<?php
require_once 'auth.php';
require_login();
require_once 'tmdb_api.php';

$results = [];
$gen_type = $_GET['type'] ?? 'movie';
$gen_year = $_GET['year'] ?? null;
$gen_region = $_GET['region'] ?? 'hollywood';

$tmdb = new TMDB_API();

// We will implement the discover_content method in a later step.
// For now, this will likely fail, but we are building the structure.
if ($gen_type === 'movie' || $gen_type === 'both') {
    // This method doesn't exist yet, we will create it in step 4
    $movie_results = $tmdb->discover_content('movie', $gen_year, $gen_region);
    $results = array_merge($results, $movie_results);
}
if ($gen_type === 'tv' || $gen_type === 'both') {
    // This method doesn't exist yet, we will create it in step 4
    $tv_results = $tmdb->discover_content('tv', $gen_year, $gen_region);
    $results = array_merge($results, $tv_results);
}

// Simple sort by popularity descending
usort($results, function($a, $b) {
    return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generated Content - CineCraze Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-results { list-style: none; padding: 0; }
        .result-item { display: flex; align-items: center; background: #fff; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .result-item img { width: 60px; margin-right: 15px; border-radius: 4px; }
        .result-item .info { flex-grow: 1; }
        .result-item .info h4 { margin: 0 0 5px 0; }
        .result-item .info p { margin: 0; color: #666; }
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
            <h1>Generated Results</h1>
            <a href="tmdb_importer.php" class="btn btn-secondary" style="margin-bottom: 20px;">&laquo; Back to Generator</a>

            <?php if (!empty($results)): ?>
            <ul class="search-results">
                <?php foreach ($results as $result):
                    $type = isset($result['title']) ? 'movie' : 'tv';
                ?>
                <li class="result-item">
                    <?php if(!empty($result['poster_path'])): ?>
                    <img src="https://image.tmdb.org/t/p/w200/<?= $result['poster_path'] ?>" alt="Poster">
                    <?php else: ?>
                    <img src="https://via.placeholder.com/60x90.png?text=No+Image" alt="No Poster">
                    <?php endif; ?>
                    <div class="info">
                        <h4><?= htmlspecialchars($result['title'] ?? $result['name']) ?></h4>
                        <p>Year: <?= htmlspecialchars(substr($result['release_date'] ?? $result['first_air_date'] ?? '', 0, 4)) ?> | Popularity: <?= htmlspecialchars($result['popularity'] ?? 'N/A') ?></p>
                    </div>
                    <a href="add_content.php?tmdb_id=<?= $result['id'] ?>&type=<?= $type ?>" class="btn">Import</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No results found for the selected criteria. The `discover_content` method in `tmdb_api.php` may not be implemented yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
