<?php
require_once __DIR__ . '/../includes/db.php';

class UserManager {
    public static function register(string $fullName, string $email, string $password): int {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$fullName, $email, $hash]);
        return (int)$db->lastInsertId();
    }

    public static function login(string $email, string $password): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        return null;
    }

    public static function getUserById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}