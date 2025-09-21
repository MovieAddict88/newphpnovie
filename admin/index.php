<?php
require_once 'auth.php';
require_login();

require_once '../config.php';
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Fetch stats
$movies_count = 0;
$series_count = 0;
$livetv_count = 0;

if ($conn->connect_error) {
    // Gracefully handle error
    $error = "DB Connection Error: " . $conn->connect_error;
} else {
    $sql_movies = "SELECT COUNT(c.id) as count FROM content c JOIN categories cat ON c.category_id = cat.id WHERE cat.name = 'Movies'";
    $movies_count = $conn->query($sql_movies)->fetch_assoc()['count'];

    $sql_series = "SELECT COUNT(c.id) as count FROM content c JOIN categories cat ON c.category_id = cat.id WHERE cat.name = 'TV Series'";
    $series_count = $conn->query($sql_series)->fetch_assoc()['count'];

    $sql_livetv = "SELECT COUNT(c.id) as count FROM content c JOIN categories cat ON c.category_id = cat.id WHERE cat.name = 'Live TV'";
    $livetv_count = $conn->query($sql_livetv)->fetch_assoc()['count'];
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CineCraze</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="logo">CineCraze Admin</div>
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="manage_content.php">Manage Content</a></li>
                <li><a href="add_content.php">Add New Content</a></li>
                <!-- Add more links as features are added -->
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <a href="logout.php">Logout</a>
            </div>
            <div class="page-content">
                <h1>Dashboard</h1>
                <?php if (isset($error)): ?>
                    <p style="color:red;"><?= $error ?></p>
                <?php else: ?>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Movies</h3>
                        <p class="stat-number"><?= $movies_count ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total TV Series</h3>
                        <p class="stat-number"><?= $series_count ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Live TV Channels</h3>
                        <p class="stat-number"><?= $livetv_count ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <a href="add_content.php" class="btn">Add New Movie/Series</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
