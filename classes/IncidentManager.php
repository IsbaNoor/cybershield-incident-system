<?php
require_once __DIR__ . '/../includes/db.php';

class IncidentManager {
    public static function create(int $userId, string $title, string $description, string $category, string $severity): int {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO incidents (title, description, reported_by, assigned_category, assigned_severity, ai_category, ai_severity)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $userId, $category, $severity, $category, $severity]);
        return (int)$db->lastInsertId();
    }

    public static function getByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM incidents WHERE reported_by = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM incidents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function updateStatus(int $id, string $newStatus): void {
        $db = getDB();
        $allowed = ['New','Investigating','Resolved','Closed'];
        if (!in_array($newStatus, $allowed)) {
            throw new InvalidArgumentException("Invalid status");
        }
        $stmt = $db->prepare("UPDATE incidents SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }

    public static function addComment(int $incidentId, int $userId, string $comment): void {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO incident_comments (incident_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$incidentId, $userId, $comment]);
    }

    public static function getAll(): array {
        $db = getDB();
        $stmt = $db->query("SELECT i.*, u.full_name as reporter_name FROM incidents i
                            JOIN users u ON i.reported_by = u.id ORDER BY i.created_at DESC");
        return $stmt->fetchAll();
    }
}