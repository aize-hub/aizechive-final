<?php
require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$b      = body();
$id     = (int)($b['id'] ?? $_GET['id'] ?? 0);
$db     = getDB();

// Always returns a zero-padded Y-m-d string
function fmtDate(?string $d): string {
    if (!$d) return date('Y-m-d');
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
}

if ($method === 'GET') {
    $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    if ($uid) {
        requireUserAccess($uid);
        $stmt = $db->prepare("SELECT b.*, COALESCE(b.billing_date, DATE(b.created_date)) AS effective_date, bk.Title AS book_title_full, bk.URL AS book_url FROM billing b LEFT JOIN books bk ON bk.idBooks = b.book_id WHERE b.user_id=? AND b.status_of_payment='Paid' AND b.is_active=1 ORDER BY b.idbilling DESC");
        $stmt->execute([$uid]);
        ok($stmt->fetchAll());
    }
    requireAdmin();
    $status = $_GET['status'] ?? 'All';
    $sql = "SELECT b.*, COALESCE(b.billing_date, DATE(b.created_date)) AS effective_date, bk.Type AS book_type, bk.URL AS book_url FROM billing b LEFT JOIN books bk ON bk.idBooks=b.book_id WHERE b.is_active=1";
    $params = [];
    if ($status !== 'All') { $sql .= ' AND b.status_of_payment=?'; $params[] = $status; }
    $sql .= ' ORDER BY b.idbilling DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    ok($stmt->fetchAll());
}
elseif ($method === 'POST') {
    requireBookworm();
    $bookId    = (int)($b['book_id']         ?? 0);
    $name      = trim($b['member_name']       ?? $b['name']  ?? '');
    $email     = trim(strtolower($b['member_email'] ?? $b['email'] ?? ''));
    $contact   = trim($b['contact']           ?? '');
    $mode      = trim($b['mode_of_payment']   ?? $b['mode']  ?? 'GCash');
    $ref       = trim($b['reference_number']  ?? $b['ref']   ?? '');
    $amount    = (float)($b['amount']         ?? 0);
    $date      = fmtDate($b['billing_date']   ?? null);   // ← always zero-padded
    $userId    = (int)($_SESSION['user_id'] ?? 0);
    $bookTitle = trim($b['book_title']        ?? '');

    if (!$name || !$email) fail('Name and email are required.');
    if (!$ref) { $ref = 'REF-'.strtoupper(bin2hex(random_bytes(4))); }

    if ($bookId) {
        $bkStmt = $db->prepare("SELECT * FROM books WHERE idBooks=? AND is_active=1");
        $bkStmt->execute([$bookId]);
        $book = $bkStmt->fetch();
        if ($book) {
            $bookTitle = $bookTitle ?: $book['Title'];
            $amount    = $amount    ?: (float)$book['price'];
        }

        $dupSql = "
            SELECT idbilling
            FROM billing
            WHERE book_id = ?
              AND status_of_payment = 'Paid'
              AND is_active = 1
              AND (
                (? IS NOT NULL AND user_id = ?)
                OR LOWER(member_email) = ?
              )
            LIMIT 1
        ";
        $dup = $db->prepare($dupSql);
        $dup->execute([$bookId, $userId, $userId, $email]);
        if ($dup->fetch()) fail('You already own this eBook.');
    }

    $db->prepare("INSERT INTO billing (user_id,book_id,member_name,member_email,book_title,billing_date,amount,mode_of_payment,reference_number,status_of_payment,paid_at) VALUES (?,?,?,?,?,?,?,?,?,'Paid',NOW())")
       ->execute([$userId,$bookId,$name,$email,$bookTitle,$date,$amount,$mode,$ref]);

    ok(['id'=>$db->lastInsertId(),'reference_number'=>$ref],'Payment recorded! Ref: '.$ref);
}
elseif ($method === 'PUT') {
    requireAdmin();
    if (!$id) fail('Billing ID required.');
    $name   = trim($b['member_name']      ?? '');
    $bkttl  = trim($b['book_title']       ?? '');
    $date   = fmtDate($b['billing_date']  ?? null);       // ← always zero-padded
    $amount = (float)($b['amount']        ?? 0);
    $mode   = trim($b['mode_of_payment']  ?? $b['mode']  ?? 'GCash');
    $ref    = trim($b['reference_number'] ?? $b['ref']   ?? '');
    $status = $b['status_of_payment']     ?? $b['stat']  ?? 'Paid';
    if (!in_array($status,['Paid','Pending','Expired'])) fail('Invalid status.');
    $paidAt = null;
    if ($status === 'Paid') {
        $row = $db->prepare("SELECT paid_at FROM billing WHERE idbilling=?");
        $row->execute([$id]);
        $paidAt = $row->fetchColumn() ?: date('Y-m-d H:i:s');
    }
    $db->prepare("UPDATE billing SET member_name=?,book_title=?,billing_date=?,amount=?,mode_of_payment=?,reference_number=?,status_of_payment=?,paid_at=? WHERE idbilling=? AND is_active=1")
       ->execute([$name,$bkttl,$date,$amount,$mode,$ref,$status,$paidAt,$id]);
    ok([],'Billing record updated.');
}
elseif ($method === 'DELETE') {
    requireAdmin();
    if (!$id) fail('Billing ID required.');
    $db->prepare("UPDATE billing SET is_active=0 WHERE idbilling=?")->execute([$id]);
    ok([],'Billing record deleted.');
}
else { fail('Method not allowed.',405); }
