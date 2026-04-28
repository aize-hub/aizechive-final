# AizeChive PHP Backend

MySQL + PHP backend for the AizeChive Smart Digital Library.
Ready to run on Laragon with MySQL Workbench.

eBook purchases are currently recorded locally as paid transactions for demo use.

## Folder Structure

```text
aizechive-final/
|-- index.html
|-- config/
|   |-- db.php
|   `-- helpers.php
|-- api/
|   |-- admins.php
|   |-- auth.php
|   |-- billing.php
|   |-- books.php
|   |-- borrows.php
|   |-- members.php
|   `-- stats.php
|-- sql/
|   `-- aize_chive.sql
`-- ebooks-epub/
```

## Local Setup

1. Place the project in `C:\laragon\www\aizechive-final\`.
2. Import [sql/aize_chive.sql](C:\laragon\www\aizechive-final\sql\aize_chive.sql) into MySQL.
3. Confirm the database values in [config/db.php](C:\laragon\www\aizechive-final\config\db.php).
4. Open `http://localhost/aizechive-final/`.

## Demo Credentials

- Admin: `superadmin` / `admin123`

## API Notes

- `api/auth.php` handles login, registration, logout, and session checks.
- `api/books.php` exposes public reads and admin-only write actions.
- `api/borrows.php` allows bookworms to manage their own borrow flow and admins to manage returns and records.
- `api/billing.php` allows bookworms to create purchases and admins to review or edit billing records.
- `api/members.php`, `api/admins.php`, and `api/stats.php` are protected routes.

## Security Notes

- Passwords are stored using `password_hash()`.
- Write endpoints now require the correct session role.
- Queries use PDO prepared statements.
