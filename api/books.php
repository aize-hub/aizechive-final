<?php
require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$b      = body();
$id     = (int)($b['id'] ?? $_GET['id'] ?? 0);
$db     = getDB();

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT b.*, GREATEST(0, b.stocks - COUNT(CASE WHEN br.status != 'Returned' THEN 1 END)) AS available_copies, COUNT(CASE WHEN br.status != 'Returned' THEN 1 END) AS borrowed_count FROM books b LEFT JOIN borrow_records br ON br.book_id = b.idBooks WHERE b.idBooks = ? AND b.is_active = 1 GROUP BY b.idBooks");
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        if (!$book) fail('Book not found.', 404);
        ok($book);
    }
    $stmt = $db->query("SELECT b.*, GREATEST(0, b.stocks - COUNT(CASE WHEN br.status != 'Returned' THEN 1 END)) AS available_copies, COUNT(CASE WHEN br.status != 'Returned' THEN 1 END) AS borrowed_count FROM books b LEFT JOIN borrow_records br ON br.book_id = b.idBooks WHERE b.is_active = 1 GROUP BY b.idBooks ORDER BY b.idBooks ASC");
    ok($stmt->fetchAll());
}
elseif ($method === 'POST') {
    requireAdmin();
    $title  = trim($b['Title']    ?? $b['title']   ?? '');
    $author = trim($b['Author']   ?? $b['author']  ?? '');
    $cat    = trim($b['Category'] ?? $b['cat']     ?? 'General');
    $type   = $b['Type']    ?? $b['type']    ?? 'Physical';
    $bc     = trim($b['Barcode']  ?? $b['bc']      ?? '');
    $url    = trim($b['URL']      ?? $b['url']     ?? '');
    $cover  = trim($b['cover_url'] ?? '');
    $price  = (float)($b['price'] ?? 0);
    $stocks = $type === 'Physical' ? max(1,(int)($b['stocks'] ?? 1)) : 0;
    $status = $b['Status'] ?? $b['status'] ?? 'Available';
    if (!$title || !$author) fail('Title and Author are required.');
    if (!in_array($type, ['Physical','Digital'])) fail('Invalid book type.');
    if ($type === 'Physical' && !$bc) {
        $count = $db->query('SELECT COUNT(*) FROM books')->fetchColumn();
        $bc = 'ISBN-'.str_pad($count+1,3,'0',STR_PAD_LEFT);
    }
    if ($type === 'Digital') $bc = null;
    $db->prepare("INSERT INTO books (Title,Author,Category,Type,Barcode,URL,cover_url,Status,stocks,price,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$title,$author,$cat,$type,$bc,$url,$cover,$status,$stocks,$price,$_SESSION['admin_user']??'admin']);
    ok(['id'=>$db->lastInsertId()],'Book added successfully.');
}
elseif ($method === 'PUT') {
    requireAdmin();
    if (!$id) fail('Book ID is required.');
    $title  = trim($b['Title']    ?? $b['title']   ?? '');
    $author = trim($b['Author']   ?? $b['author']  ?? '');
    $cat    = trim($b['Category'] ?? $b['cat']     ?? 'General');
    $type   = $b['Type']    ?? $b['type']    ?? 'Physical';
    $bc     = trim($b['Barcode']  ?? $b['bc']      ?? '');
    $url    = trim($b['URL']      ?? $b['url']     ?? '');
    $cover  = trim($b['cover_url'] ?? '');
    $price  = (float)($b['price'] ?? 0);
    $stocks = $type === 'Physical' ? max(1,(int)($b['stocks'] ?? 1)) : 0;
    if (!$title || !$author) fail('Title and Author are required.');
    if (!in_array($type, ['Physical','Digital'])) fail('Invalid book type.');
    if ($type === 'Physical' && !$bc) {
        $count = $db->query('SELECT COUNT(*) FROM books')->fetchColumn();
        $bc = 'ISBN-'.str_pad($count+1,3,'0',STR_PAD_LEFT);
    }
    if ($type === 'Digital') $bc = null;
    $outStmt = $db->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id=? AND status!='Returned'");
    $outStmt->execute([$id]);
    $out = (int)$outStmt->fetchColumn();
    $status = ($type==='Physical' && $out>=$stocks) ? 'Borrowed' : 'Available';
    $db->prepare("UPDATE books SET Title=?,Author=?,Category=?,Type=?,Barcode=?,URL=?,cover_url=?,stocks=?,price=?,Status=?,modified_by=? WHERE idBooks=? AND is_active=1")
       ->execute([$title,$author,$cat,$type,$bc,$url,$cover,$stocks,$price,$status,$_SESSION['admin_user']??'admin',$id]);
    ok([],'Book updated.');
}
elseif ($method === 'DELETE') {
    requireAdmin();
    if (!$id) fail('Book ID is required.');
    $db->prepare("UPDATE books SET is_active=0 WHERE idBooks=?")->execute([$id]);
    ok([],'Book removed from inventory.');
}
else { fail('Method not allowed.',405); }
