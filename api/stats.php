<?php
// ============================================================
//  AizeChive — Stats / Dashboard API
//  api/stats.php
//
//  GET /api/stats.php              → admin dashboard stats
//  GET /api/stats.php?user_id=1    → bookworm dashboard stats
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$db  = getDB();
$uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Auto-flag overdue records
autoMarkOverdue();

if ($uid) {
    // ── Bookworm dashboard ────────────────────────────────────
    $borrows = $db->prepare("
        SELECT
            SUM(status = 'Active')   AS active,
            SUM(status = 'Overdue')  AS overdue,
            SUM(status = 'Returned') AS returned
        FROM borrow_records WHERE user_id = ?
    ");
    $borrows->execute([$uid]);
    $bStats = $borrows->fetch();

    $paid = $db->prepare("SELECT COUNT(*) FROM billing WHERE user_id=? AND status_of_payment='Paid'");
    $paid->execute([$uid]);

    ok([
        'active'   => (int)$bStats['active'],
        'overdue'  => (int)$bStats['overdue'],
        'returned' => (int)$bStats['returned'],
        'paid'     => (int)$paid->fetchColumn(),
    ]);
}

// ── Admin dashboard ───────────────────────────────────────────
$books   = $db->query("SELECT COUNT(*) FROM books WHERE is_active=1")->fetchColumn();
$phy     = $db->query("SELECT COUNT(*) FROM books WHERE is_active=1 AND Type='Physical'")->fetchColumn();
$dig     = $db->query("SELECT COUNT(*) FROM books WHERE is_active=1 AND Type='Digital'")->fetchColumn();
$borrowed = $db->query("SELECT COUNT(*) FROM books WHERE Status='Borrowed' AND is_active=1")->fetchColumn();
$overdue = $db->query("SELECT COUNT(*) FROM borrow_records WHERE status='Overdue'")->fetchColumn();
$members = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$revenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM billing WHERE status_of_payment='Paid' AND is_active=1")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM billing WHERE status_of_payment='Pending' AND is_active=1")->fetchColumn();

// Available physical books (have at least 1 copy not borrowed)
$availPhy = $db->query("
    SELECT COUNT(DISTINCT b.idBooks)
    FROM books b
    WHERE b.Type='Physical' AND b.is_active=1
      AND (b.stocks - (
            SELECT COUNT(*) FROM borrow_records br
            WHERE br.book_id=b.idBooks AND br.status != 'Returned'
          )) > 0
")->fetchColumn();

// Books by category
$cats = $db->query("
    SELECT Category, Type, COUNT(*) AS cnt
    FROM books WHERE is_active=1
    GROUP BY Category, Type
    ORDER BY Category
")->fetchAll();

ok([
    'total_books'     => (int)$books,
    'physical'        => (int)$phy,
    'digital'         => (int)$dig,
    'borrowed'        => (int)$borrowed,
    'available_phy'   => (int)$availPhy,
    'overdue'         => (int)$overdue,
    'members'         => (int)$members,
    'revenue'         => (float)$revenue,
    'pending_billing' => (int)$pending,
    'by_category'     => $cats,
]);
