<?php
require_once 'auth.php';
require_login();
require_once '../config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    header('Location: manage_content.php?error=db_conn_fail');
    exit;
}
$conn->set_charset("utf8mb4");

if (!isset($_POST['action'])) {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'add_content':
    case 'update_content':
        $is_update = ($action === 'update_content');

        // --- Insert or Update main content ---
        if ($is_update) {
            if (!isset($_POST['id'])) {
                header('Location: manage_content.php?error=no_id');
                exit;
            }
            $content_id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE content SET title = ?, category_id = ?, description = ?, poster = ?, year = ?, rating = ? WHERE id = ?");
            $stmt->bind_param("sisssdi", $_POST['title'], $_POST['category_id'], $_POST['description'], $_POST['poster'], $_POST['year'], $_POST['rating'], $content_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO content (title, category_id, description, poster, year, rating) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssd", $_POST['title'], $_POST['category_id'], $_POST['description'], $_POST['poster'], $_POST['year'], $_POST['rating']);
        }

        if (!$stmt->execute()) {
            $error_msg = $is_update ? 'update_fail' : 'add_fail';
            header('Location: add_content.php?id=' . ($_POST['id'] ?? '') . '&error=' . $error_msg);
            exit;
        }

        if (!$is_update) {
            $content_id = $conn->insert_id;
        }

        // --- Clear old related data for updates ---
        if ($is_update) {
            // Clear old servers (for movies/live tv)
            $conn->query("DELETE FROM servers WHERE parent_id = $content_id AND parent_type = 'content'");
            // Clear old seasons (which cascades to episodes and their servers)
            $conn->query("DELETE FROM seasons WHERE content_id = $content_id");
        }

        // --- Handle Servers (for Movies/Live TV) ---
        $category_id = $_POST['category_id'];
        $cat_name_res = $conn->query("SELECT name FROM categories WHERE id = $category_id");
        $category_name = $cat_name_res->fetch_assoc()['name'];

        if (($category_name === 'Movies' || $category_name === 'Live TV') && !empty($_POST['servers'][0]['url'])) {
             $server = $_POST['servers'][0];
             $is_drm = isset($server['is_drm']) ? 1 : 0;
             $stmt_server = $conn->prepare("INSERT INTO servers (parent_id, parent_type, name, url, license_url, is_drm) VALUES (?, 'content', ?, ?, ?, ?)");
             $stmt_server->bind_param("isssi", $content_id, $server['name'], $server['url'], $server['license_url'], $is_drm);
             $stmt_server->execute();
        }

        // --- Handle Seasons and Episodes (for TV Series) ---
        if ($category_name === 'TV Series' && isset($_POST['seasons'])) {
            foreach ($_POST['seasons'] as $season_data) {
                $stmt_season = $conn->prepare("INSERT INTO seasons (content_id, season_number, poster) VALUES (?, ?, ?)");
                $stmt_season->bind_param("iis", $content_id, $season_data['season_number'], $season_data['poster']);
                $stmt_season->execute();
                $season_id = $conn->insert_id;

                if ($season_id > 0 && isset($season_data['episodes'])) {
                    foreach ($season_data['episodes'] as $episode_data) {
                        $stmt_episode = $conn->prepare("INSERT INTO episodes (season_id, episode_number, title, description, thumbnail) VALUES (?, ?, ?, ?, ?)");
                        $stmt_episode->bind_param("iisss", $season_id, $episode_data['episode_number'], $episode_data['title'], $episode_data['description'], $episode_data['thumbnail']);
                        $stmt_episode->execute();
                        $episode_id = $conn->insert_id;

                        if ($episode_id > 0 && isset($episode_data['servers'])) {
                            foreach ($episode_data['servers'] as $server_data) {
                                if (!empty($server_data['url'])) {
                                    $stmt_ep_server = $conn->prepare("INSERT INTO servers (parent_id, parent_type, name, url) VALUES (?, 'episode', ?, ?)");
                                    $stmt_ep_server->bind_param("iss", $episode_id, $server_data['name'], $server_data['url']);
                                    $stmt_ep_server->execute();
                                }
                            }
                        }
                    }
                }
            }
        }

        $success_msg = $is_update ? 'update_ok' : 'add_ok';
        header('Location: manage_content.php?success=' . $success_msg);
        break;

    default:
        header('Location: index.php');
        break;
}

$conn->close();
?>
