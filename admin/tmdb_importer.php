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
            <div class="form-container">
                <form action="tmdb_importer.php" method="GET">
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

            <?php if (!empty($search_results)): ?>
            <h2>Search Results</h2>
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
</body>
</html>
