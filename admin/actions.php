<?php
require_once 'auth.php';
require_login();
require_once '../config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    header('Location: manage_content.php?error=db_conn_fail');
    exit;
}

if (!isset($_POST['action'])) {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'add_content':
        $stmt = $conn->prepare("INSERT INTO content (title, category_id, description, poster, year, rating) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssd",
            $_POST['title'],
            $_POST['category_id'],
            $_POST['description'],
            $_POST['poster'],
            $_POST['year'],
            $_POST['rating']
        );

        if ($stmt->execute()) {
            $content_id = $conn->insert_id;
            // Simplified server handling
            if (!empty($_POST['servers'][0]['url'])) {
                 $server = $_POST['servers'][0];
                 $is_drm = isset($server['is_drm']) ? 1 : 0;
                 $stmt_server = $conn->prepare("INSERT INTO servers (parent_id, parent_type, name, url, license_url, is_drm) VALUES (?, 'content', ?, ?, ?, ?)");
                 $stmt_server->bind_param("isssi", $content_id, $server['name'], $server['url'], $server['license_url'], $is_drm);
                 $stmt_server->execute();
            }
            header('Location: manage_content.php?success=add_ok');
        } else {
            header('Location: add_content.php?error=add_fail');
        }
        $stmt->close();
        break;

    case 'update_content':
        if (!isset($_POST['id'])) {
            header('Location: manage_content.php?error=no_id');
            exit;
        }
        $id = $_POST['id'];

        $stmt = $conn->prepare("UPDATE content SET title = ?, category_id = ?, description = ?, poster = ?, year = ?, rating = ? WHERE id = ?");
        $stmt->bind_param("sisssdi",
            $_POST['title'],
            $_POST['category_id'],
            $_POST['description'],
            $_POST['poster'],
            $_POST['year'],
            $_POST['rating'],
            $id
        );

        if ($stmt->execute()) {
            // Simplified server handling: delete old and insert new
            $conn->query("DELETE FROM servers WHERE parent_id = $id AND parent_type = 'content'");
            if (!empty($_POST['servers'][0]['url'])) {
                 $server = $_POST['servers'][0];
                 $is_drm = isset($server['is_drm']) ? 1 : 0;
                 $stmt_server = $conn->prepare("INSERT INTO servers (parent_id, parent_type, name, url, license_url, is_drm) VALUES (?, 'content', ?, ?, ?, ?)");
                 $stmt_server->bind_param("isssi", $id, $server['name'], $server['url'], $server['license_url'], $is_drm);
                 $stmt_server->execute();
            }
            header('Location: manage_content.php?success=update_ok');
        } else {
            header('Location: add_content.php?id=' . $id . '&error=update_fail');
        }
        $stmt->close();
        break;

    default:
        header('Location: index.php');
        break;
}

$conn->close();
?>
