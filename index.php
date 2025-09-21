<?php
require_once 'config.php';
// Fetch genres for the filter dropdown
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$genres = [];
if ($conn->connect_error) {
    // Handle connection error gracefully
    error_log("DB connection error: " . $conn->connect_error);
} else {
    $result = $conn->query("SELECT name FROM genres ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $genres[] = $row['name'];
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze - Watch Movies & TV Shows</title>
    <link rel="stylesheet" href="static/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body id="main-page">

    <header class="main-header">
        <div class="logo">CineCraze</div>
        <div class="search-box">
            <input type="text" placeholder="Search movies, TV shows...">
            <i class="fas fa-search"></i>
        </div>
        <div class="user-icon">
            <i class="fas fa-user-circle fa-2x"></i>
        </div>
    </header>

    <div class="featured-content" style="background-image: url('https://image.tmdb.org/t/p/original/1RICxzeoNCAO5NpcRMIgg1XT6fm.jpg');">
        <div class="featured-info">
            <h2>Jurassic World Rebirth</h2>
            <p>Five years after the events of Jurassic World Dominion, a covert team is sent on a top-secret mission to secure genetic material from massive dinosaurs.</p>
        </div>
    </div>

    <main class="container">
        <div class="filter-bar">
            <select id="genre-filter">
                <option value="">All Genres</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="year-filter">
                <option value="">All Years</option>
                <!-- Years can be populated dynamically if needed -->
            </select>
            <select id="sort-filter">
                <option value="year_desc">Newest First</option>
                <option value="rating_desc">Top Rated</option>
                <option value="title_asc">A-Z</option>
            </select>
        </div>

        <section id="content-section">
            <h2 id="content-title">Movies</h2>
            <div id="content-grid" class="content-grid">
                <!-- Content will be loaded here by app.js -->
            </div>
        </section>
    </main>

    <nav class="bottom-nav">
        <ul>
            <li><a href="#" class="category-link active" data-category="Movies"><i class="fas fa-film"></i><span>Movies</span></a></li>
            <li><a href="#" class="category-link" data-category="TV Series"><i class="fas fa-tv"></i><span>Series</span></a></li>
            <li><a href="#" class="category-link" data-category="Live TV"><i class="fas fa-broadcast-tower"></i><span>Live</span></a></li>
            <li><a href="#"><i class="fas fa-bookmark"></i><span>Watch Later</span></a></li>
        </ul>
    </nav>

    <script src="static/app.js"></script>
</body>
</html>
