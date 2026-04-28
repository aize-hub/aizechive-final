USE aize_chive;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS billing;
DROP TABLE IF EXISTS borrow_records;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS admin;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admin (
  idadmin      INT AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(80)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  is_active    TINYINT(1)   DEFAULT 1,
  created_date VARCHAR(20)  DEFAULT NULL,
  created_by   VARCHAR(80)  DEFAULT 'system',
  modified_by  VARCHAR(80)  DEFAULT NULL
);

CREATE TABLE users (
  id_username  INT AUTO_INCREMENT PRIMARY KEY,
  fullname     VARCHAR(120) NOT NULL,
  email        VARCHAR(120) NOT NULL UNIQUE,
  contact      VARCHAR(30)  DEFAULT NULL,
  password     VARCHAR(255) NOT NULL,
  created_date DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  idBooks      INT AUTO_INCREMENT PRIMARY KEY,
  Title        VARCHAR(200) NOT NULL,
  Author       VARCHAR(120) NOT NULL,
  Category     VARCHAR(80)  DEFAULT 'General',
  Type         ENUM('Physical','Digital') DEFAULT 'Physical',
  Barcode      VARCHAR(60)  DEFAULT NULL,
  URL          VARCHAR(255) DEFAULT NULL,
  cover_url    VARCHAR(255) DEFAULT NULL,
  Status       ENUM('Available','Borrowed') DEFAULT 'Available',
  stocks       INT          DEFAULT 1,
  price        DECIMAL(10,2) DEFAULT 0.00,
  is_active    TINYINT(1)   DEFAULT 1,
  created_date DATETIME     DEFAULT CURRENT_TIMESTAMP,
  created_by   VARCHAR(80)  DEFAULT 'admin',
  modified_by  VARCHAR(80)  DEFAULT NULL
);

CREATE TABLE borrow_records (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT          DEFAULT NULL,
  book_id       INT          NOT NULL,
  borrower_name VARCHAR(120) NOT NULL,
  email         VARCHAR(120) NOT NULL,
  contact       VARCHAR(30)  DEFAULT NULL,
  date_borrowed DATE         NOT NULL,
  due_date      DATE         NOT NULL,
  date_returned DATE         DEFAULT NULL,
  status        ENUM('Active','Overdue','Returned') DEFAULT 'Active',
  created_date  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(idBooks) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id_username) ON DELETE SET NULL
);

CREATE TABLE billing (
  idbilling         INT AUTO_INCREMENT PRIMARY KEY,
  external_id       VARCHAR(100) UNIQUE DEFAULT NULL,
  user_id           INT          DEFAULT NULL,
  book_id           INT          DEFAULT NULL,
  member_name       VARCHAR(120) NOT NULL,
  member_email      VARCHAR(120) DEFAULT NULL,
  book_title        VARCHAR(200) NOT NULL,
  billing_date      DATE         NOT NULL,
  amount            DECIMAL(10,2) DEFAULT 0.00,
  mode_of_payment   VARCHAR(60)  DEFAULT 'GCash',
  reference_number  VARCHAR(100) DEFAULT NULL,
  status_of_payment ENUM('Paid','Pending','Expired') DEFAULT 'Pending',
  paid_at           DATETIME     DEFAULT NULL,
  is_active         TINYINT(1)   DEFAULT 1,
  created_date      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id_username) ON DELETE SET NULL,
  FOREIGN KEY (book_id) REFERENCES books(idBooks) ON DELETE SET NULL
);

-- ── DEMO DATA ────────────────────────────────────────────────
-- Admin password = "admin123"
INSERT INTO admin (username, password) VALUES
('superadmin', '$2y$10$97zFNJlict0y2chrQmDNVu.RbmH8Csp6EeYYVhFFDtC2eT8xJYsma');
