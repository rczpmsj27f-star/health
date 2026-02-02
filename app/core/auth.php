<?php

class Auth {

    public static function requireLogin() {
        session_start();
        if (empty($_SESSION['user_id'])) {
            header("Location: /login.php");
            exit;
        }
    }

    public static function isAdmin() {
        if (empty($_SESSION['user_id'])) return false;

        require __DIR__ . '/../config/database.php';

        $stmt = $pdo->prepare("
            SELECT r.role_name
            FROM user_role_map m
            JOIN user_roles r ON r.id = m.role_id
            WHERE m.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return in_array('admin', $roles);
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo "Access denied.";
            exit;
        }
    }
}
