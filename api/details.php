<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

// --- Validate Input ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'A valid content ID is required.']);
    exit;
}
$content_id = (int)$_GET['id'];

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}
$conn->set_charset("utf8mb4");

// --- Fetch Main Content ---
$stmt = $conn->prepare("SELECT c.*, cat.name as category_name FROM `content` c JOIN `categories` cat ON c.category_id = cat.id WHERE c.id = ?");
$stmt->bind_param("i", $content_id);
$stmt->execute();
$result = $stmt->get_result();
$content = $result->fetch_assoc();

if (!$content) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Content not found.']);
    exit;
}

// --- Fetch Associated Data ---

// Fetch Genres
$stmt_genres = $conn->prepare("SELECT g.name FROM `genres` g JOIN `content_genres` cg ON g.id = cg.genre_id WHERE cg.content_id = ?");
$stmt_genres->bind_param("i", $content_id);
$stmt_genres->execute();
$result_genres = $stmt_genres->get_result();
$genres = [];
while ($row = $result_genres->fetch_assoc()) {
    $genres[] = $row['name'];
}
$content['genres'] = $genres;

// Fetch Servers (for Movies/Live TV)
if ($content['category_name'] === 'Movies' || $content['category_name'] === 'Live TV') {
    $stmt_servers = $conn->prepare("SELECT name, url, license_url, is_drm FROM `servers` WHERE parent_id = ? AND parent_type = 'content'");
    $stmt_servers->bind_param("i", $content_id);
    $stmt_servers->execute();
    $result_servers = $stmt_servers->get_result();
    $servers = [];
    while ($row = $result_servers->fetch_assoc()) {
        $servers[] = $row;
    }
    $content['servers'] = $servers;
}

// Fetch Seasons and Episodes (for TV Series)
if ($content['category_name'] === 'TV Series') {
    $stmt_seasons = $conn->prepare("SELECT id, season_number, poster FROM `seasons` WHERE content_id = ? ORDER BY season_number ASC");
    $stmt_seasons->bind_param("i", $content_id);
    $stmt_seasons->execute();
    $result_seasons = $stmt_seasons->get_result();

    $seasons_array = [];
    while ($season_row = $result_seasons->fetch_assoc()) {
        $season_id = $season_row['id'];

        // Fetch Episodes for the season
        $stmt_episodes = $conn->prepare("SELECT id, episode_number, title, duration, description, thumbnail FROM `episodes` WHERE season_id = ? ORDER BY episode_number ASC");
        $stmt_episodes->bind_param("i", $season_id);
        $stmt_episodes->execute();
        $result_episodes = $stmt_episodes->get_result();

        $episodes_array = [];
        while ($episode_row = $result_episodes->fetch_assoc()) {
            $episode_id = $episode_row['id'];

            // Fetch Servers for the episode
            $stmt_ep_servers = $conn->prepare("SELECT name, url, license_url, is_drm FROM `servers` WHERE parent_id = ? AND parent_type = 'episode'");
            $stmt_ep_servers->bind_param("i", $episode_id);
            $stmt_ep_servers->execute();
            $result_ep_servers = $stmt_ep_servers->get_result();
            $episode_servers = [];
            while ($server_row = $result_ep_servers->fetch_assoc()) {
                $episode_servers[] = $server_row;
            }
            $episode_row['servers'] = $episode_servers;
            $episodes_array[] = $episode_row;
        }
        $season_row['episodes'] = $episodes_array;
        $seasons_array[] = $season_row;
    }
    $content['seasons'] = $seasons_array;
}


// --- Return Final JSON ---
echo json_encode($content);

// --- Cleanup ---
$stmt->close();
$stmt_genres->close();
if (isset($stmt_servers)) $stmt_servers->close();
if (isset($stmt_seasons)) $stmt_seasons->close();
if (isset($stmt_episodes)) $stmt_episodes->close();
if (isset($stmt_ep_servers)) $stmt_ep_servers->close();
$conn->close();
?>
