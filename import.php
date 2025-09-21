<?php
set_time_limit(300); // Set execution time to 5 minutes to avoid timeouts on large imports
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// --- Read JSON Data ---
$json_data = file_get_contents('data.json');
if ($json_data === false) {
    die("Error: Could not read data.json");
}
$data = json_decode($json_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Invalid JSON in data.json - " . json_last_error_msg());
}

// --- Helper function to get or insert and get ID ---
function get_or_insert_id($conn, $table, $column, $value) {
    $stmt = $conn->prepare("SELECT id FROM `$table` WHERE `$column` = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO `$table` (`$column`) VALUES (?)");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        return $conn->insert_id;
    }
}

echo "Starting data import...<br>";

// --- Data Processing ---
foreach ($data['Categories'] as $category) {
    $main_category_name = $category['MainCategory'];
    $category_id = get_or_insert_id($conn, 'categories', 'name', $main_category_name);
    echo "Processing Category: $main_category_name (ID: $category_id)<br>";

    // Pre-insert all genres for this category
    $genre_ids = [];
    foreach ($category['SubCategories'] as $sub_category_name) {
        if (!empty($sub_category_name)) {
            $genre_ids[$sub_category_name] = get_or_insert_id($conn, 'genres', 'name', $sub_category_name);
        }
    }

    foreach ($category['Entries'] as $entry) {
        echo "  - Processing Entry: {$entry['Title']}<br>";
        $stmt = $conn->prepare("INSERT INTO `content` (category_id, title, description, poster, thumbnail, rating, duration, year, country, parental_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $rating = isset($entry['Rating']) && is_numeric($entry['Rating']) ? $entry['Rating'] : null;
        $year = isset($entry['Year']) && is_numeric($entry['Year']) ? $entry['Year'] : 0;

        $stmt->bind_param("isssssdiss",
            $category_id,
            $entry['Title'],
            $entry['Description'],
            $entry['Poster'],
            $entry['Thumbnail'],
            $rating,
            $entry['Duration'],
            $year,
            $entry['Country'],
            $entry['parentalRating']
        );
        $stmt->execute();
        $content_id = $conn->insert_id;

        if ($content_id == 0) {
            echo "    <strong style='color:red;'>Error inserting content: {$entry['Title']}. Skipping.</strong><br>";
            continue;
        }

        // Link genre
        if (!empty($entry['SubCategory']) && isset($genre_ids[$entry['SubCategory']])) {
            $genre_id = $genre_ids[$entry['SubCategory']];
            $stmt_genre = $conn->prepare("INSERT INTO `content_genres` (content_id, genre_id) VALUES (?, ?)");
            $stmt_genre->bind_param("ii", $content_id, $genre_id);
            $stmt_genre->execute();
        }

        // Handle servers for Movies and Live TV
        if (($main_category_name === 'Movies' || $main_category_name === 'Live TV') && isset($entry['Servers'])) {
            foreach ($entry['Servers'] as $server) {
                $stmt_server = $conn->prepare("INSERT INTO `servers` (parent_id, parent_type, name, url, license_url, is_drm) VALUES (?, 'content', ?, ?, ?, ?)");
                $is_drm = isset($server['drm']) && $server['drm'] === true ? 1 : 0;
                $license = $is_drm ? $server['license'] : null;
                $stmt_server->bind_param("isssi", $content_id, $server['name'], $server['url'], $license, $is_drm);
                $stmt_server->execute();
            }
        }

        // Handle Seasons and Episodes for TV Series
        if ($main_category_name === 'TV Series' && isset($entry['Seasons'])) {
            foreach ($entry['Seasons'] as $season) {
                $stmt_season = $conn->prepare("INSERT INTO `seasons` (content_id, season_number, poster) VALUES (?, ?, ?)");
                $stmt_season->bind_param("iis", $content_id, $season['Season'], $season['SeasonPoster']);
                $stmt_season->execute();
                $season_id = $conn->insert_id;

                if ($season_id > 0 && isset($season['Episodes'])) {
                    foreach ($season['Episodes'] as $episode) {
                        $stmt_episode = $conn->prepare("INSERT INTO `episodes` (season_id, episode_number, title, duration, description, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_episode->bind_param("iissss", $season_id, $episode['Episode'], $episode['Title'], $episode['Duration'], $episode['Description'], $episode['Thumbnail']);
                        $stmt_episode->execute();
                        $episode_id = $conn->insert_id;

                        if ($episode_id > 0 && isset($episode['Servers'])) {
                            foreach ($episode['Servers'] as $server) {
                                $stmt_ep_server = $conn->prepare("INSERT INTO `servers` (parent_id, parent_type, name, url, license_url, is_drm) VALUES (?, 'episode', ?, ?, ?, ?)");
                                $is_drm_ep = isset($server['drm']) && $server['drm'] === true ? 1 : 0;
                                $license_ep = $is_drm_ep ? $server['license'] : null;
                                $stmt_ep_server->bind_param("isssi", $episode_id, $server['name'], $server['url'], $license_ep, $is_drm_ep);
                                $stmt_ep_server->execute();
                            }
                        }
                    }
                }
            }
        }
    }
}

echo "<br><strong>Data import complete.</strong>";
$conn->close();
?>
