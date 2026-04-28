<?php
// ============================================================
//  AizeChive — Shared Helpers
//  config/helpers.php
// ============================================================

require_once __DIR__ . '/db.php';

// ── CORS & JSON headers ──────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Start session once ───────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── JSON responses ───────────────────────────────────────────
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

// ── Read JSON request body ───────────────────────────────────
function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// ── Auth guards ──────────────────────────────────────────────
function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) fail('Unauthorized. Admin login required.', 401);
}
function requireBookworm(): void {
    if (empty($_SESSION['user_id'])) fail('Unauthorized. Please log in.', 401);
}

// ── Auto-mark overdue borrow records ────────────────────────
function autoMarkOverdue(): void {
    $db = getDB();
    $db->exec("
        UPDATE borrow_records
        SET status = 'Overdue'
        WHERE status = 'Active' AND due_date < CURDATE()
    ");
}
