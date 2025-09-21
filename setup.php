<?php
// Include the database configuration file
require_once 'config.php';

// --- Database Connection ---
// Create a new mysqli object to connect to MySQL server
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Create Database ---
// SQL to create the database if it doesn't already exist
$sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database '" . DB_NAME . "' created or already exists successfully.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database for use
$conn->select_db(DB_NAME);

// --- Table Creation ---
// Set foreign key checks to 0 to avoid issues with table creation order
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// SQL for 'categories' table
$sql_categories = "CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'genres' table
$sql_genres = "CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'content' table
$sql_content = "CREATE TABLE IF NOT EXISTS `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `poster` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `parental_rating` varchar(50) DEFAULT NULL,
  `tmdb_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `content_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'content_genres' pivot table
$sql_content_genres = "CREATE TABLE IF NOT EXISTS `content_genres` (
  `content_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`content_id`,`genre_id`),
  KEY `genre_id` (`genre_id`),
  CONSTRAINT `content_genres_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `content_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'seasons' table
$sql_seasons = "CREATE TABLE IF NOT EXISTS `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL,
  `season_number` int(11) NOT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `tmdb_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id_season_number` (`content_id`,`season_number`),
  CONSTRAINT `seasons_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'episodes' table
$sql_episodes = "CREATE TABLE IF NOT EXISTS `episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `episode_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `description` text,
  `thumbnail` varchar(255) DEFAULT NULL,
  `tmdb_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `season_id` (`season_id`),
  CONSTRAINT `episodes_ibfk_1` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL for 'servers' table
$sql_servers = "CREATE TABLE IF NOT EXISTS `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `parent_type` enum('content','episode') NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `license_url` text DEFAULT NULL,
  `is_drm` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_idx` (`parent_id`,`parent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Array of table creation queries
$queries = [
    "categories" => $sql_categories,
    "genres" => $sql_genres,
    "content" => $sql_content,
    "content_genres" => $sql_content_genres,
    "seasons" => $sql_seasons,
    "episodes" => $sql_episodes,
    "servers" => $sql_servers
];

// Execute each query and report status
foreach ($queries as $table => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '{$table}' created successfully.<br>";
    } else {
        echo "Error creating table '{$table}': " . $conn->error . "<br>";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo "Database setup complete.";

// Close the connection
$conn->close();
?>
