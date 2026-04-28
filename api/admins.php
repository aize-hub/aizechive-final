<?php
// ============================================================
//  AizeChive — Admin Accounts API
//  api/admins.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$b      = body();
$id     = (int)($b['id'] ?? $_GET['id'] ?? 0);
$db     = getDB();

requireAdmin();

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $db->query("
        SELECT idadmin, username, is_active, created_date, created_by, modified_by
        FROM admin
        ORDER BY idadmin ASC
    ");
    ok($stmt->fetchAll());
}

// ── POST — Add admin ──────────────────────────────────────────
elseif ($method === 'POST') {
    $username = trim($b['username'] ?? '');
    $password = $b['password']      ?? '';
    $active   = isset($b['is_active']) ? (int)$b['is_active'] : 1;
    $createdBy = $b['created_by'] ?? $_SESSION['admin_user'] ?? 'admin';

    if (!$username || !$password) fail('Username and password are required.');
    if (strlen($password) < 6)    fail('Password must be at least 6 characters.');

    $check = $db->prepare("SELECT idadmin FROM admin WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) fail('Username already exists.');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO admin (username, password, is_active, created_by) VALUES (?, ?, ?, ?)")
       ->execute([$username, $hash, $active, $createdBy]);

    ok(['id' => $db->lastInsertId()], 'Admin account created.');
}

// ── PUT — Update admin ────────────────────────────────────────
elseif ($method === 'PUT') {
    if (!$id) fail('Admin ID required.');
    $username = trim($b['username']  ?? '');
    $password = $b['password']       ?? '';
    $active   = isset($b['is_active']) ? (int)$b['is_active'] : 1;

    if (!$username) fail('Username is required.');

    $check = $db->prepare("SELECT idadmin FROM admin WHERE username = ? AND idadmin != ?");
    $check->execute([$username, $id]);
    if ($check->fetch()) fail('Username already taken.');

    if ($password) {
        if (strlen($password) < 6) fail('Password must be at least 6 characters.');
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("UPDATE admin SET username=?, password=?, is_active=?, modified_by=? WHERE idadmin=?")
           ->execute([$username, $hash, $active, $_SESSION['admin_user'] ?? 'admin', $id]);
    } else {
        $db->prepare("UPDATE admin SET username=?, is_active=?, modified_by=? WHERE idadmin=?")
           ->execute([$username, $active, $_SESSION['admin_user'] ?? 'admin', $id]);
    }

    ok([], 'Admin account updated.');
}

// ── DELETE ────────────────────────────────────────────────────
elseif ($method === 'DELETE') {
    if (!$id) fail('Admin ID required.');
    if ((int)($_SESSION['admin_id'] ?? 0) === $id) fail('You cannot delete your own account.');
    $db->prepare("DELETE FROM admin WHERE idadmin = ?")->execute([$id]);
    ok([], 'Admin account deleted.');
}

else {
    fail('Method not allowed.', 405);
}
