<?php
// ============================================================
//  AizeChive — Borrow Records API
//  api/borrows.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$b      = body();
$id     = (int)($b['id'] ?? $_GET['id'] ?? 0);
$action = $b['action'] ?? $_GET['action'] ?? '';
$db     = getDB();

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {
    autoMarkOverdue();

    $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    if ($uid) {
        requireUserAccess($uid);
        $stmt = $db->prepare("
            SELECT br.*, b.Title AS book_title, b.Type AS book_type
            FROM borrow_records br
            JOIN books b ON b.idBooks = br.book_id
            WHERE br.user_id = ?
            ORDER BY br.id DESC
        ");
        $stmt->execute([$uid]);
        ok($stmt->fetchAll());
    }

    requireAdmin();
    $status = $_GET['status'] ?? 'All';
    $sql = "
        SELECT br.*,
               b.Title AS book_title, b.Type AS book_type,
               GREATEST(0, DATEDIFF(CURDATE(), br.due_date)) * 5 AS fine_amount
        FROM borrow_records br
        JOIN books b ON b.idBooks = br.book_id
    ";
    $params = [];
    if ($status !== 'All') {
        $sql     .= ' WHERE br.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY br.id DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    ok($stmt->fetchAll());
}

// ── POST — Borrow a book ──────────────────────────────────────
elseif ($method === 'POST' && $action !== 'return') {
    requireBookworm();
    $bookId  = (int)($b['book_id']       ?? 0);
    $name    = trim($b['borrower_name']  ?? $b['name']    ?? '');
    $email   = trim($b['email']          ?? '');
    $contact = trim($b['contact']        ?? '');
    $due     = $b['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
    $userId  = (int)($_SESSION['user_id'] ?? 0);

    if (!$bookId || !$name || !$email) fail('Book, name, and email are required.');

    $bkStmt = $db->prepare("SELECT * FROM books WHERE idBooks=? AND is_active=1");
    $bkStmt->execute([$bookId]);
    $book = $bkStmt->fetch();
    if (!$book) fail('Book not found.');
    if ($book['Type'] !== 'Physical') fail('Only physical books can be borrowed.');

    $outStmt = $db->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id=? AND status != 'Returned'");
    $outStmt->execute([$bookId]);
    $out = (int)$outStmt->fetchColumn();
    if ($out >= $book['stocks']) fail('No copies available to borrow.');

    $ins = $db->prepare("
        INSERT INTO borrow_records (user_id, book_id, borrower_name, email, contact, date_borrowed, due_date, status)
        VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'Active')
    ");
    $ins->execute([$userId, $bookId, $name, $email, $contact, $due]);

    $newOut = $out + 1;
    if ($newOut >= $book['stocks']) {
        $db->prepare("UPDATE books SET Status='Borrowed' WHERE idBooks=?")->execute([$bookId]);
    }

    $borrowId = (int)$db->lastInsertId();
    $receiptNo = 'BRW-' . date('Y') . '-' . str_pad((string)$borrowId, 5, '0', STR_PAD_LEFT);

    ok([
        'id' => $borrowId,
        'receipt_no' => $receiptNo,
        'book_id' => $bookId,
        'book_title' => $book['Title'],
        'borrower_name' => $name,
        'email' => $email,
        'contact' => $contact,
        'date_borrowed' => date('Y-m-d'),
        'due_date' => $due,
        'status' => 'Active',
    ], 'Borrowed successfully! Due: ' . $due);
}

// ── POST — Mark Returned ──────────────────────────────────────
elseif ($method === 'POST' && $action === 'return') {
    requireAdmin();
    if (!$id) fail('Record ID required.');

    $recStmt = $db->prepare("SELECT * FROM borrow_records WHERE id=?");
    $recStmt->execute([$id]);
    $rec = $recStmt->fetch();
    if (!$rec) fail('Record not found.');
    if ($rec['status'] === 'Returned') fail('Already marked as returned.');

    $db->prepare("UPDATE borrow_records SET status='Returned', date_returned=CURDATE() WHERE id=?")
       ->execute([$id]);

    $stillOut = $db->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id=? AND status != 'Returned'");
    $stillOut->execute([$rec['book_id']]);
    if ((int)$stillOut->fetchColumn() < 1) {
        $db->prepare("UPDATE books SET Status='Available' WHERE idBooks=?")->execute([$rec['book_id']]);
    }

    ok([], 'Marked as returned. Book is now available.');
}

// ── PUT — Update Record (Admin) ───────────────────────────────
elseif ($method === 'PUT') {
    requireAdmin();
    if (!$id) fail('Record ID required.');

    $name    = trim($b['borrower_name'] ?? $b['name']    ?? '');
    $email   = trim($b['email']         ?? '');
    $contact = trim($b['contact']       ?? '');
    $due     = $b['due_date'] ?? '';
    $status  = $b['status']   ?? 'Active';

    if (!in_array($status, ['Active','Overdue','Returned'])) fail('Invalid status.');

    $recStmt = $db->prepare("SELECT * FROM borrow_records WHERE id=?");
    $recStmt->execute([$id]);
    $rec = $recStmt->fetch();
    if (!$rec) fail('Record not found.');

    if ($status === 'Returned' && $rec['status'] !== 'Returned') {
        $db->prepare("UPDATE borrow_records SET status='Returned', date_returned=CURDATE(),
                      borrower_name=?, email=?, contact=?, due_date=? WHERE id=?")
           ->execute([$name, $email, $contact, $due, $id]);
        $stillOut = $db->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id=? AND status != 'Returned'");
        $stillOut->execute([$rec['book_id']]);
        if ((int)$stillOut->fetchColumn() < 1) {
            $db->prepare("UPDATE books SET Status='Available' WHERE idBooks=?")->execute([$rec['book_id']]);
        }
    } else {
        $db->prepare("UPDATE borrow_records SET borrower_name=?, email=?, contact=?, due_date=?, status=? WHERE id=?")
           ->execute([$name, $email, $contact, $due, $status, $id]);
    }

    ok([], 'Record updated.');
}

else {
    fail('Method not allowed.', 405);
}
