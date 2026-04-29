<?php
/**
 * CampusConnect - AJAX Search API Endpoint
 * Returns JSON-formatted search results for study resources.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

try {
    $pdo = get_db_connection();

    $q = trim($_GET['q'] ?? '');
    $subject = trim($_GET['subject'] ?? '');

    $sql = "SELECT id, title, subject, description, file_name, file_size, file_type, created_at FROM resources WHERE 1=1";
    $params = [];

    if ($q !== '') {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    if ($subject !== '') {
        $sql .= " AND subject = ?";
        $params[] = $subject;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 30";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    $formatted = array_map(function($item) {
        return [
            'id'             => (int)$item['id'],
            'title'          => $item['title'],
            'subject'        => $item['subject'],
            'description'    => $item['description'],
            'file_name'      => $item['file_name'],
            'file_size'      => (int)$item['file_size'],
    echo json_encode(['success' => true, 'data' => []]);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }