<?php
require_once 'auth.php';
require_login();

require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_content.php?error=Invalid ID');
    exit;
}

$id = $_GET['id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    header('Location: manage_content.php?error=Database connection failed');
    exit;
}

// Because of ON DELETE CASCADE, deleting from content will also delete
// from content_genres, seasons, episodes, and servers.
$stmt = $conn->prepare("DELETE FROM content WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: manage_content.php?success=Content deleted successfully');
} else {
    header('Location: manage_content.php?error=Failed to delete content');
}

$stmt->close();
$conn->close();
?>
