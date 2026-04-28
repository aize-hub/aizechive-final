# AizeChive — PHP Backend (Local / Laragon)

MySQL + PHP backend for the AizeChive Smart Digital Library.
Ready to run on **Laragon** with **MySQL Workbench**.

> Xendit payment integration is **not yet connected** — purchases are
> saved directly as Paid for local/demo use. See the Xendit section
> at the bottom when you're ready to go live.

---

## 📁 Folder Structure

```
aizechive-backend/
├── index.html          ← your frontend (place here)
├── config/
│   ├── db.php          ← database credentials
│   ├── helpers.php     ← shared functions
│   └── xendit.php      ← Xendit config (not active yet)
├── api/
│   ├── auth.php        ← login, register, logout
│   ├── books.php       ← book CRUD
│   ├── borrows.php     ← borrow & return
│   ├── billing.php     ← eBook purchase recording
│   ├── members.php     ← member management
│   ├── admins.php      ← admin accounts
│   └── stats.php       ← dashboard stats
├── sql/
│   └── aize_chive.sql  ← full schema + demo data
└── README.md
```

---

## ⚙️ Laragon Setup

### Step 1 — Place files
Copy this entire folder to:
```
C:\laragon\www\aizechive-backend\
```

### Step 2 — Import the database
Open **MySQL Workbench** → connect to `localhost` → run:
```
sql/aize_chive.sql
```
Or use Laragon's built-in HeidiSQL:
Laragon tray → Database → run the SQL file.

### Step 3 — Check db.php
Laragon defaults are already set correctly:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'aize_chive');
define('DB_USER', 'root');
define('DB_PASS', '');   // blank by default in Laragon
```

### Step 4 — Open in browser
```
http://localhost/aizechive-backend
```
or if Laragon pretty URLs are on:
```
http://aizechive-backend.test
```

### Step 5 — Test the API
Open this in your browser to confirm DB is working:
```
http://localhost/aizechive-backend/api/books.php
```
You should see a JSON list of books.

---

## 🔑 Demo Credentials

| Role | Username / Email | Password |
|------|-----------------|----------|
| Admin | `superadmin` | `admin123` |
| Admin | `libadmin1` | `admin123` |
| Bookworm | `mj@email.com` | `pass123` |
| Bookworm | `jose@email.com` | `pass123` |
| Bookworm | `ana@email.com` | `pass123` |
| Bookworm | `luis@email.com` | `pass123` |

---

## 🌐 API Endpoints

### Auth — `api/auth.php`
| Method | `?action=` | Body | Description |
|--------|-----------|------|-------------|
| POST | `login_admin` | `{username, password}` | Admin login |
| POST | `login_bookworm` | `{email, password}` | Bookworm login |
| POST | `register` | `{name, email, contact, password, confirm}` | Register |
| POST | `logout` | — | Logout |
| GET | `whoami` | — | Check session |

### Books — `api/books.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Public | All books + live stock |
| GET | `?id=1` | Public | Single book |
| POST | — | Admin | Add book |
| PUT | `?id=1` | Admin | Update book |
| DELETE | `?id=1` | Admin | Soft delete |

### Borrows — `api/borrows.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Admin | All records + fine calc |
| GET | `?user_id=1` | Bookworm | My records |
| POST | — | Bookworm | Borrow a book |
| POST | `?action=return&id=1` | Admin | Mark returned |
| PUT | `?id=1` | Admin | Edit record |

### Billing — `api/billing.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Admin | All billing records |
| GET | `?user_id=1` | Bookworm | My paid purchases |
| POST | — | Bookworm | Record eBook purchase |
| PUT | `?id=1` | Admin | Edit record |
| DELETE | `?id=1` | Admin | Soft delete |

### Members — `api/members.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Admin | All members |
| GET | `?id=1` | Admin | Member + borrow history |
| PUT | `?id=1` | Admin | Update member |
| DELETE | `?id=1` | Admin | Delete member |

### Admins — `api/admins.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Admin | All admin accounts |
| POST | — | Admin | Add admin |
| PUT | `?id=1` | Admin | Update admin |
| DELETE | `?id=1` | Admin | Delete admin |

### Stats — `api/stats.php`
| Method | Params | Auth | Description |
|--------|--------|------|-------------|
| GET | — | Admin | Dashboard numbers |
| GET | `?user_id=1` | Bookworm | My stats |

---

## 🔌 Frontend Integration (index.html → PHP)

Replace JS arrays with fetch() calls:

```javascript
// Load all books on startup
async function loadBooks() {
    const res  = await fetch('api/books.php');
    const json = await res.json();
    if (json.success) {
        BOOKS = json.data;
        renderAll();
    }
}

// Admin login
async function doAdminLogin() {
    const res  = await fetch('api/auth.php?action=login_admin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: v('lg-user'), password: v('lg-pass') })
    });
    const json = await res.json();
    if (json.success) { currentRole = 'admin'; launchApp(); }
    else toast(json.message, 'er');
}

// Bookworm login
async function doBookwormLogin() {
    const res  = await fetch('api/auth.php?action=login_bookworm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: v('bw-email'), password: v('bw-pass') })
    });
    const json = await res.json();
    if (json.success) {
        currentUser = json.data;
        currentRole = 'bookworm';
        launchApp();
    } else toast(json.message, 'er');
}

// Register
async function doRegister() {
    const res  = await fetch('api/auth.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: v('reg-name'), email: v('reg-email'),
            contact: v('reg-contact'), password: v('reg-pass'), confirm: v('reg-confirm')
        })
    });
    const json = await res.json();
    if (json.success) { currentUser = json.data; launchApp(); }
    else toast(json.message, 'er');
}

// Borrow a book
async function saveBorrow() {
    const res  = await fetch('api/borrows.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id: pendingBorrowId,
            name: v('bw-name'), email: v('bw-email'),
            contact: v('bw-contact'), due_date: v('bw-due')
        })
    });
    const json = await res.json();
    if (json.success) {
        await loadBooks(); await loadBorrows();
        toast('🎀 ' + json.message, 'ok');
        closeM('modal-borrow');
    } else toast(json.message, 'er');
}

// Buy eBook (local — no Xendit yet)
async function processPayment() {
    const res  = await fetch('api/billing.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id: pendingEbookId,
            name: v('eb-name'), email: v('eb-email'), contact: v('eb-contact'),
            mode: selectedPaymentMethod || 'GCash'
        })
    });
    const json = await res.json();
    if (json.success) {
        document.getElementById('eb-success-ref').textContent = 'Ref: ' + json.data.ref;
        // show success screen
    } else toast(json.message, 'er');
}

// Logout
async function logout() {
    await fetch('api/auth.php?action=logout', { method: 'POST' });
    currentRole = null; currentUser = null;
    showRoleGrid();
}
```

---

## 💳 Xendit Integration (Later)

When you're ready to accept real payments:

1. Uncomment `require_once __DIR__ . '/../config/xendit.php';` in `billing.php`
2. Replace the POST section with the Xendit invoice creation code
3. Create `api/webhook.php` to receive payment callbacks
4. Update `XENDIT_SUCCESS_URL` and `XENDIT_FAILURE_URL` in `config/xendit.php`
5. Register the webhook URL in Xendit Dashboard → Settings → Webhooks

The `config/xendit.php` and schema columns (`external_id`, `paid_at`) are
already in place — just needs to be wired up when the time comes.

---

## 🔒 Security Notes

- Passwords are bcrypt hashed via `password_hash()` — never stored plain
- Sessions handle admin vs bookworm access control
- PDO prepared statements on every query — SQL injection safe
- Soft delete on books and billing — data is never lost
