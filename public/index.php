<?php
require_once '../src/database.php';
require_once '../src/db_queries.php';

$pdo = get_pdo_connection();
$content = getAllContent($pdo);
$genres = getAllGenres($pdo);
$countries = getAllCountries($pdo);

// Create a list of all years from content, for the filter dropdown
$years = array_unique(array_map(function($item) {
    return $item['year'];
}, $content));
rsort($years); // Sort years in descending order

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze - Watch Movies & TV Shows</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="logo">CineCraze</div>
        <div class="search-bar">
            <input type="text" placeholder="Search movies, TV shows...">
        </div>
        <nav class="main-nav">
            <!-- Add nav items here if needed -->
        </nav>
    </header>

    <main>
        <section class="hero-section">
            <!-- Hero content can be dynamic later -->
            <div class="hero-content">
                <h1>Tagalized Movie Channel (TMC)</h1>
                <p>The first and only 24/7 Tagalized movie channel in the Philippines.</p>
                <button class="play-button">►</button>
            </div>
        </section>

        <section class="browse-section">
            <h2>Browse Content</h2>
            <div class="filters">
                <div class="filter-group">
                    <label for="genre-filter">Genre</label>
                    <select id="genre-filter" name="genre">
                        <option value="">All Genres</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="year-filter">Year</label>
                    <select id="year-filter" name="year">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <?php if($year > 0): ?>
                            <option value="<?= $year ?>"><?= $year ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="country-filter">Country</label>
                    <select id="country-filter" name="country">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country) ?>"><?= htmlspecialchars($country) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sort-filter">Sort By</label>
                    <select id="sort-filter" name="sort">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="rating">Highest Rating</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="content-grid">
            <?php if (empty($content)): ?>
                <p>No content found.</p>
            <?php else: ?>
                <?php foreach ($content as $item): ?>
                    <div class="content-card">
                        <a href="details.php?id=<?= $item['id'] ?>">
                            <img src="<?= htmlspecialchars($item['poster']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            <div class="card-overlay">
                                <span class="card-type"><?= str_replace('_', ' ', strtoupper($item['content_type'])) ?></span>
                                <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                <div class="card-meta">
                                    <span><?= $item['year'] ?></span>
                                    <?php if ($item['rating'] > 0): ?>
                                    <span class="rating">⭐ <?= number_format($item['rating'], 1) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer class="main-footer">
        <nav class="bottom-nav">
            <a href="#" class="nav-item active">All</a>
            <a href="#" class="nav-item">Movie</a>
            <a href="#" class="nav-item">Series</a>
            <a href="#" class="nav-item">Live</a>
            <a href="#" class="nav-item">Watch Later</a>
        </nav>
    </footer>

</body>
</html>
