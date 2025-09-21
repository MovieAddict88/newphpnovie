<?php
// src/db_queries.php

function getAllContent($pdo) {
    // Fetches all movies and TV series, along with their primary genre.
    $sql = "
        SELECT
            c.id,
            c.title,
            c.poster,
            c.content_type,
            c.year,
            c.rating,
            g.name as genre_name
        FROM content c
        LEFT JOIN content_genres cg ON c.id = cg.content_id
        LEFT JOIN genres g ON cg.genre_id = g.id
        WHERE c.content_type IN ('movie', 'tv_series')
        GROUP BY c.id
        ORDER BY c.year DESC, c.title ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getAllGenres($pdo) {
    $stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getAllCountries($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT country FROM content WHERE country IS NOT NULL AND country != '' ORDER BY country ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getContentById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getSeasonsBySeriesId($pdo, $series_id) {
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE series_id = ? ORDER BY season_number ASC");
    $stmt->execute([$series_id]);
    return $stmt->fetchAll();
}

function getEpisodesBySeasonId($pdo, $season_id) {
    $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number ASC");
    $stmt->execute([$season_id]);
    return $stmt->fetchAll();
}

function getServersByContentId($pdo, $content_id) {
    $stmt = $pdo->prepare("SELECT * FROM servers WHERE content_id = ? ORDER BY name ASC");
    $stmt->execute([$content_id]);
    return $stmt->fetchAll();
}

function getServersByEpisodeId($pdo, $episode_id) {
    // Note: The original JSON has the same servers for all episodes of a series.
    // In a real scenario, this might be different.
    // This function assumes a more normalized model where each episode has its own servers.
    // The setup script reflects this by creating an `episode_servers` table.
    $stmt = $pdo->prepare("SELECT * FROM episode_servers WHERE episode_id = ? ORDER BY name ASC");
    $stmt->execute([$episode_id]);
    return $stmt->fetchAll();
}

?>
