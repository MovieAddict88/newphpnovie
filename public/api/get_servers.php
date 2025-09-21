<?php
header('Content-Type: application/json');

require_once '../../src/database.php';
require_once '../../src/db_queries.php';

if (!isset($_GET['episode_id']) || !is_numeric($_GET['episode_id'])) {
    echo json_encode(['error' => 'Episode ID is missing or invalid.']);
    exit;
}

$episode_id = (int)$_GET['episode_id'];
$pdo = get_pdo_connection();
$servers = getServersByEpisodeId($pdo, $episode_id);

if ($servers === false) {
    echo json_encode(['error' => 'Could not fetch servers.']);
    exit;
}

echo json_encode($servers);
?>
