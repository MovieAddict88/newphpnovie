<?php
// Set content type to JSON and allow cross-origin requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed.']));
}
$conn->set_charset("utf8mb4");

// --- Get Request Parameters ---
$category_name = isset($_GET['category']) ? $_GET['category'] : null;
$genre_name = isset($_GET['genre']) ? $_GET['genre'] : null;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'year_desc';

// --- Build SQL Query ---
$sql = "SELECT c.*, cat.name as category_name FROM `content` c
        JOIN `categories` cat ON c.category_id = cat.id";
$params = [];
$types = '';

// Join with genres if filtering by genre
if ($genre_name) {
    $sql .= " JOIN `content_genres` cg ON c.id = cg.content_id
              JOIN `genres` g ON cg.genre_id = g.id";
}

$sql .= " WHERE 1=1";

// Filter by category
if ($category_name) {
    $sql .= " AND cat.name = ?";
    $params[] = $category_name;
    $types .= 's';
}

// Filter by genre
if ($genre_name) {
    $sql .= " AND g.name = ?";
    $params[] = $genre_name;
    $types .= 's';
}

// Add sorting
switch ($sort) {
    case 'rating_desc':
        $sql .= " ORDER BY c.rating DESC";
        break;
    case 'year_asc':
        $sql .= " ORDER BY c.year ASC";
        break;
    case 'title_asc':
        $sql .= " ORDER BY c.title ASC";
        break;
    case 'title_desc':
        $sql .= " ORDER BY c.title DESC";
        break;
    case 'year_desc':
    default:
        $sql .= " ORDER BY c.year DESC";
        break;
}

// Add limit and offset for pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// --- Execute Query ---
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(['error' => 'Query preparation failed: ' . $conn->error]));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- Fetch and Return Results ---
$content = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $content[] = $row;
    }
} else {
    die(json_encode(['error' => 'Query execution failed: ' . $stmt->error]));
}

echo json_encode($content);

// --- Cleanup ---
$stmt->close();
$conn->close();
?>
