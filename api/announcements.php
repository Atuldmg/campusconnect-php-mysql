<?php

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

try {
    $pdo = get_db_connection();
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.content, a.category, a.is_pinned, a.created_at, u.name as author_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    $data = array_map(function($ann) {
        return [
            'id'          => (int)$ann['id'],
            'title'       => $ann['title'],
            'content'     => $ann['content'],
            'category'    => $ann['category'],
            'is_pinned'   => (bool)$ann['is_pinned'],
            'author'      => $ann['author_name'] ?? 'Administration',
            'created_at'  => $ann['created_at'],
            'time_ago'    => time_ago($ann['created_at']),
        ];
    }, $announcements);

    echo json_encode([
        'status' => 'success',
        'count'  => count($data),
        'data'   => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to load announcements: ' . $e->getMessage(),
    ]);
}
