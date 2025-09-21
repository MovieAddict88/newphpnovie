<?php
// src/database.php

function get_pdo_connection() {
    $db_path = __DIR__ . '/../db/database.sqlite';

    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // In a real application, you'd want to log this error, not just die
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
