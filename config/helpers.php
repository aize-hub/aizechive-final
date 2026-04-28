<?php
// ============================================================
//  AizeChive - Shared Helpers
//  config/helpers.php
// ============================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function ok(mixed $data = [], string $message = 'Success'): void {
    respond(['success' => true, 'message' => $message, 'data' => $data]);
}

function fail(string $message, int $code = 400): void {
    respond(['success' => false, 'message' => $message], $code);
}

function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function isBookwormLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        fail('Unauthorized. Admin login required.', 401);
    }
}

function requireBookworm(): void {
    if (!isBookwormLoggedIn()) {
        fail('Unauthorized. Please log in.', 401);
    }
}

function requireUserAccess(int $userId): void {
    if (isAdminLoggedIn()) {
        return;
    }

    if (!isBookwormLoggedIn() || (int)$_SESSION['user_id'] !== $userId) {
        fail('Unauthorized.', 401);
    }
}

function autoMarkOverdue(): void {
    $db = getDB();
    $db->exec("
        UPDATE borrow_records
        SET status = 'Overdue'
        WHERE status = 'Active' AND due_date < CURDATE()
    ");
}
