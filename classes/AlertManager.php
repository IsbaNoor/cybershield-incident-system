<?php
require_once __DIR__ . '/../includes/db.php';

class AlertManager {
    public static function createAlert(string $title, string $message, string $severity, int $createdBy): int {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO alerts (title, message, severity, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $message, $severity, $createdBy]);
        return (int)$db->lastInsertId();
    }

    public static function getNewAlerts(int $lastId = 0): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM alerts WHERE id > ? ORDER BY id ASC LIMIT 20");
        $stmt->execute([$lastId]);
        return $stmt->fetchAll();
    }
}