<?php
// ============================================================
//  AizeChive — Auth API
//  api/auth.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$b      = body();
$action = $b['action'] ?? $_GET['action'] ?? '';

// ── ADMIN LOGIN ──────────────────────────────────────────────
if ($action === 'login_admin') {
    $username = trim($b['username'] ?? '');
    $password = $b['password'] ?? '';

    if (!$username || !$password) fail('Username and password are required.');

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admin WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        fail('Wrong username or password.', 401);
    }

    $_SESSION['admin_id']   = $admin['idadmin'];
    $_SESSION['admin_user'] = $admin['username'];
    $_SESSION['role']       = 'admin';

    ok([
        'role'     => 'admin',
        'id'       => $admin['idadmin'],
        'username' => $admin['username'],
    ], 'Admin login successful.');
}

// ── BOOKWORM LOGIN ───────────────────────────────────────────
elseif ($action === 'login_bookworm') {
    $email    = trim(strtolower($b['email'] ?? ''));
    $password = $b['password'] ?? '';

    if (!$email || !$password) fail('Email and password are required.');

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        fail('Wrong email or password.', 401);
    }

    $_SESSION['user_id']    = $user['id_username'];
    $_SESSION['user_name']  = $user['fullname'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role']       = 'bookworm';

    ok([
        'role'    => 'bookworm',
        'id'      => $user['id_username'],
        'name'    => $user['fullname'],
        'email'   => $user['email'],
        'contact' => $user['contact'],
    ], 'Welcome, ' . $user['fullname'] . '!');
}

// ── REGISTER ────────────────────────────────────────────────
elseif ($action === 'register') {
    // Accept both 'fullname' and 'name'
    $name     = trim($b['fullname'] ?? $b['name'] ?? '');
    $email    = trim(strtolower($b['email'] ?? ''));
    $contact  = trim($b['contact']  ?? '');
    $password = $b['password'] ?? '';
    $confirm  = $b['confirm']  ?? $password; // confirm optional

    if (!$name || !$email || !$password) fail('Name, email, and password are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Invalid email address.');
    if (strlen($password) < 6) fail('Password must be at least 6 characters.');

    $db    = getDB();
    $check = $db->prepare('SELECT id_username FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) fail('Email is already registered.');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (fullname, email, contact, password) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $contact, $hash]);
    $newId = $db->lastInsertId();

    $_SESSION['user_id']    = $newId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role']       = 'bookworm';

    ok([
        'role'    => 'bookworm',
        'id'      => $newId,
        'name'    => $name,
        'email'   => $email,
        'contact' => $contact,
    ], 'Account created! Welcome, ' . $name . '!');
}

// ── LOGOUT ──────────────────────────────────────────────────
elseif ($action === 'logout') {
    session_destroy();
    ok([], 'Logged out successfully.');
}

// ── WHOAMI ──────────────────────────────────────────────────
elseif ($action === 'whoami') {
    if (!empty($_SESSION['role'])) {
        ok([
            'role'     => $_SESSION['role'],
            'name'     => $_SESSION['user_name']  ?? $_SESSION['admin_user'] ?? '',
            'email'    => $_SESSION['user_email'] ?? '',
            'user_id'  => $_SESSION['user_id']    ?? null,
            'admin_id' => $_SESSION['admin_id']   ?? null,
        ]);
    }
    fail('Not logged in.', 401);
}

else {
    fail('Unknown action.');
}
