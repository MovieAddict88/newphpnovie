<?php
require_once 'auth.php';
require_login();

require_once '../config.php';
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$content_list = [];
if ($conn->connect_error) {
    $error = "DB Connection Error: " . $conn->connect_error;
} else {
    $sql = "SELECT c.id, c.title, c.year, c.poster, cat.name as category_name
            FROM content c
            JOIN categories cat ON c.category_id = cat.id
            ORDER BY c.id DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $content_list[] = $row;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Content - CineCraze Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="logo">CineCraze Admin</div>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="manage_content.php" class="active">Manage Content</a></li>
                <li><a href="add_content.php">Add New Content</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <a href="logout.php">Logout</a>
            </div>
            <div class="page-content">
                <h1>Manage Content</h1>

                <?php if (isset($error)): ?>
                    <p style="color:red;"><?= $error ?></p>
                <?php else: ?>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content_list as $content): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($content['poster']) ?>" alt="Poster"></td>
                            <td><?= htmlspecialchars($content['title']) ?></td>
                            <td><?= htmlspecialchars($content['category_name']) ?></td>
                            <td><?= htmlspecialchars($content['year']) ?></td>
                            <td class="actions">
                                <a href="add_content.php?id=<?= $content['id'] ?>">Edit</a>
                                <a href="delete_content.php?id=<?= $content['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($content_list)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No content found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
