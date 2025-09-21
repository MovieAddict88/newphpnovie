<?php
// setup.php

// --- 1. Configuration and Setup ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Set a longer execution time limit, as parsing the file might take a while.
set_time_limit(300);

$db_path = __DIR__ . '/db/database.sqlite';
$data_json_path = __DIR__ . '/data.json';

// --- 2. Database Connection ---
try {
    // Create (or open) the database file
    $pdo = new PDO('sqlite:' . $db_path);
    // Set attributes for error handling and performance
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    echo "Database connection established successfully.\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- 3. Table Creation ---
function create_tables($pdo) {
    echo "Dropping old tables if they exist...\n";
    $pdo->exec('DROP TABLE IF EXISTS episode_servers;');
    $pdo->exec('DROP TABLE IF EXISTS episodes;');
    $pdo->exec('DROP TABLE IF EXISTS seasons;');
    $pdo->exec('DROP TABLE IF EXISTS servers;');
    $pdo->exec('DROP TABLE IF EXISTS content_genres;');
    $pdo->exec('DROP TABLE IF EXISTS genres;');
    $pdo->exec('DROP TABLE IF EXISTS content;');

    echo "Creating new tables...\n";
    $commands = [
        'CREATE TABLE content (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            poster VARCHAR(255),
            thumbnail VARCHAR(255),
            rating DECIMAL(3, 1),
            year INT,
            duration VARCHAR(50),
            country VARCHAR(100),
            parental_rating VARCHAR(50),
            content_type VARCHAR(20) NOT NULL,
            tmdb_id INT
        );',
        'CREATE TABLE genres (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE
        );',
        'CREATE TABLE content_genres (
            content_id INTEGER,
            genre_id INTEGER,
            FOREIGN KEY(content_id) REFERENCES content(id) ON DELETE CASCADE,
            FOREIGN KEY(genre_id) REFERENCES genres(id) ON DELETE CASCADE,
            PRIMARY KEY (content_id, genre_id)
        );',
        'CREATE TABLE servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_id INTEGER,
            name VARCHAR(255),
            url VARCHAR(255) NOT NULL,
            is_drm BOOLEAN DEFAULT 0,
            license_url VARCHAR(255),
            FOREIGN KEY(content_id) REFERENCES content(id) ON DELETE CASCADE
        );',
        'CREATE TABLE seasons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            series_id INTEGER,
            season_number INT NOT NULL,
            poster VARCHAR(255),
            FOREIGN KEY(series_id) REFERENCES content(id) ON DELETE CASCADE
        );',
        'CREATE TABLE episodes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            season_id INTEGER,
            episode_number INT NOT NULL,
            title VARCHAR(255),
            description TEXT,
            thumbnail VARCHAR(255),
            duration VARCHAR(50),
            FOREIGN KEY(season_id) REFERENCES seasons(id) ON DELETE CASCADE
        );',
        'CREATE TABLE episode_servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            episode_id INTEGER,
            name VARCHAR(255),
            url VARCHAR(255) NOT NULL,
            FOREIGN KEY(episode_id) REFERENCES episodes(id) ON DELETE CASCADE
        );'
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    echo "Tables created successfully.\n";
}

// --- 4. Data Parsing and Insertion ---
function parse_and_insert_data($pdo, $json_path) {
    if (!file_exists($json_path)) {
        die("Error: data.json not found at " . $json_path);
    }

    echo "Reading and decoding JSON data...\n";
    $json_data = file_get_contents($json_path);
    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error decoding JSON: " . json_last_error_msg());
    }

    $pdo->beginTransaction();

    $stmt_content = $pdo->prepare("INSERT INTO content (title, description, poster, thumbnail, rating, year, duration, country, parental_rating, content_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_genre = $pdo->prepare("INSERT OR IGNORE INTO genres (name) VALUES (?)");
    $stmt_get_genre_id = $pdo->prepare("SELECT id FROM genres WHERE name = ?");
    $stmt_content_genre = $pdo->prepare("INSERT INTO content_genres (content_id, genre_id) VALUES (?, ?)");
    $stmt_server = $pdo->prepare("INSERT INTO servers (content_id, name, url, is_drm, license_url) VALUES (?, ?, ?, ?, ?)");
    $stmt_season = $pdo->prepare("INSERT INTO seasons (series_id, season_number, poster) VALUES (?, ?, ?)");
    $stmt_episode = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, thumbnail, duration) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_episode_server = $pdo->prepare("INSERT INTO episode_servers (episode_id, name, url) VALUES (?, ?, ?)");

    echo "Starting data insertion...\n";
    foreach ($data['Categories'] as $category) {
        $main_category = $category['MainCategory'];
        echo "Processing Category: $main_category\n";

        $content_type = '';
        if ($main_category === 'Movies') $content_type = 'movie';
        elseif ($main_category === 'TV Series') $content_type = 'tv_series';
        elseif ($main_category === 'Live TV') $content_type = 'live_tv';
        else continue;

        foreach ($category['Entries'] as $entry) {
            // Insert into content table
            $stmt_content->execute([
                $entry['Title'] ?? null,
                $entry['Description'] ?? null,
                $entry['Poster'] ?? null,
                $entry['Thumbnail'] ?? null,
                $entry['Rating'] ?? null,
                $entry['Year'] ?? null,
                $entry['Duration'] ?? null,
                $entry['Country'] ?? null,
                $entry['parentalRating'] ?? null,
                $content_type
            ]);
            $content_id = $pdo->lastInsertId();
            echo "  Inserted '{$entry['Title']}' with ID: $content_id\n";

            // Insert genre
            if (!empty($entry['SubCategory'])) {
                $stmt_genre->execute([$entry['SubCategory']]);
                $stmt_get_genre_id->execute([$entry['SubCategory']]);
                $genre_id = $stmt_get_genre_id->fetchColumn();
                if ($genre_id) {
                    $stmt_content_genre->execute([$content_id, $genre_id]);
                }
            }

            // Insert servers (for Movies and Live TV)
            if (in_array($content_type, ['movie', 'live_tv']) && !empty($entry['Servers'])) {
                foreach ($entry['Servers'] as $server) {
                    $stmt_server->execute([
                        $content_id,
                        $server['name'] ?? null,
                        $server['url'] ?? null,
                        !empty($server['drm']) ? 1 : 0,
                        $server['license'] ?? null
                    ]);
                }
            }

            // Insert seasons and episodes (for TV Series)
            if ($content_type === 'tv_series' && !empty($entry['Seasons'])) {
                foreach ($entry['Seasons'] as $season_data) {
                    $stmt_season->execute([
                        $content_id,
                        $season_data['Season'] ?? null,
                        $season_data['SeasonPoster'] ?? null
                    ]);
                    $season_id = $pdo->lastInsertId();

                    if (!empty($season_data['Episodes'])) {
                        foreach ($season_data['Episodes'] as $episode_data) {
                            $stmt_episode->execute([
                                $season_id,
                                $episode_data['Episode'] ?? null,
                                $episode_data['Title'] ?? null,
                                $episode_data['Description'] ?? null,
                                $episode_data['Thumbnail'] ?? null,
                                $episode_data['Duration'] ?? null,
                            ]);
                            $episode_id = $pdo->lastInsertId();

                            if (!empty($episode_data['Servers'])) {
                                foreach ($episode_data['Servers'] as $server) {
                                    $stmt_episode_server->execute([
                                        $episode_id,
                                        $server['name'] ?? null,
                                        $server['url'] ?? null
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo "Data insertion completed successfully.\n";
}

// --- 5. Execution ---
try {
    create_tables($pdo);
    parse_and_insert_data($pdo, $data_json_path);
    echo "\nSetup finished! The database is ready.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("An error occurred during execution: " . $e->getMessage());
}
?>
