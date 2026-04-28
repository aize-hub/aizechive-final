<?php
// ============================================================
//  AizeChive — Members API
//  api/members.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$b      = body();
$id     = (int)($b['id'] ?? $_GET['id'] ?? 0);
$db     = getDB();

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {
    $qid = (int)($_GET['id'] ?? 0);
    if ($qid) {
        $stmt = $db->prepare("SELECT id_username, fullname, email, contact, created_date FROM users WHERE id_username=?");
        $stmt->execute([$qid]);
        $member = $stmt->fetch();
        if (!$member) fail('Member not found.', 404);

        $hist = $db->prepare("
            SELECT br.*, b.Title AS book_title, b.Type AS book_type
            FROM borrow_records br
            JOIN books b ON b.idBooks = br.book_id
            WHERE br.user_id = ?
            ORDER BY br.id DESC
        ");
        $hist->execute([$qid]);
        $member['borrow_history'] = $hist->fetchAll();
        ok($member);
    }

    $stmt = $db->query("
        SELECT u.id_username, u.fullname, u.email, u.contact, u.created_date,
               COUNT(br.id) AS total_borrows,
               SUM(CASE WHEN br.status IN ('Active','Overdue') THEN 1 ELSE 0 END) AS active_borrows
        FROM users u
        LEFT JOIN borrow_records br ON br.user_id = u.id_username
        GROUP BY u.id_username
        ORDER BY u.id_username ASC
    ");
    ok($stmt->fetchAll());
}

// ── PUT — Update member ───────────────────────────────────────
elseif ($method === 'PUT') {
    if (!$id) fail('Member ID required.');

    $name    = trim($b['fullname'] ?? '');
    $email   = trim($b['email']   ?? '');
    $contact = trim($b['contact'] ?? '');

    if (!$name || !$email) fail('Name and email are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Invalid email address.');

    $check = $db->prepare("SELECT id_username FROM users WHERE email=? AND id_username != ?");
    $check->execute([$email, $id]);
    if ($check->fetch()) fail('Email is already used by another account.');

    $db->prepare("UPDATE users SET fullname=?, email=?, contact=? WHERE id_username=?")
       ->execute([$name, $email, $contact, $id]);

    ok([], 'Member updated.');
}

// ── DELETE ────────────────────────────────────────────────────
elseif ($method === 'DELETE') {
    if (!$id) fail('Member ID required.');

    $check = $db->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id=? AND status IN ('Active','Overdue')");
    $check->execute([$id]);
    if ((int)$check->fetchColumn() > 0) {
        fail('Cannot delete member with active or overdue borrows. Resolve them first.');
    }

    $db->prepare("DELETE FROM users WHERE id_username=?")->execute([$id]);
    ok([], 'Member deleted.');
}

else {
    fail('Method not allowed.', 405);
}