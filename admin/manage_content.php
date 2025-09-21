<?php
require_once 'auth.php';
require_login();

require_once '../config.php';
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$content_list = [];
$search_term = $_GET['search'] ?? '';

if ($conn->connect_error) {
    $error = "DB Connection Error: " . $conn->connect_error;
} else {
    $sql = "SELECT c.id, c.title, c.year, c.poster, cat.name as category_name
            FROM content c
            JOIN categories cat ON c.category_id = cat.id";

    if (!empty($search_term)) {
        $sql .= " WHERE c.title LIKE ?";
        $stmt = $conn->prepare($sql);
        $search_param = "%" . $search_term . "%";
        $stmt->bind_param("s", $search_param);
    } else {
        $sql .= " ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

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
                <div class="page-header">
                    <h1>Manage Content</h1>
                    <div class="header-actions">
                        <form action="manage_content.php" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Search by title..." value="<?= htmlspecialchars($search_term) ?>">
                            <button type="submit" class="btn">Search</button>
                        </form>
                        <a href="add_content.php" class="btn">Add New Content</a>
                    </div>
                </div>

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
