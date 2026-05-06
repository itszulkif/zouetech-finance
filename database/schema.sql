CREATE DATABASE IF NOT EXISTS zouetech_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zouetech_finance;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  percentage DECIMAL(5,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partner_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partner_id INT UNSIGNED NOT NULL UNIQUE,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_partner_users_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS income (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  amount DECIMAL(14,2) NOT NULL,
  type ENUM('distributed','company_only','external_source') NOT NULL,
  source ENUM('normal','external') NOT NULL DEFAULT 'normal',
  income_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  transaction_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_income_type_created (type, created_at)
);

CREATE TABLE IF NOT EXISTS partner_shares (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partner_id INT UNSIGNED NOT NULL,
  income_id INT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_partner_shares_partner (partner_id),
  INDEX idx_partner_shares_income (income_id),
  CONSTRAINT fk_partner_shares_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
  CONSTRAINT fk_partner_shares_income FOREIGN KEY (income_id) REFERENCES income(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  amount DECIMAL(14,2) NOT NULL,
  type ENUM('company','partner') NOT NULL,
  payment_mode ENUM('partner_pay','company_pay') NOT NULL DEFAULT 'partner_pay',
  partner_id INT UNSIGNED NULL,
  transaction_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_expenses_type_created (type, created_at),
  INDEX idx_expenses_partner (partner_id),
  CONSTRAINT fk_expenses_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ledger (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partner_id INT UNSIGNED NOT NULL,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ledger_partner_created (partner_id, created_at),
  CONSTRAINT fk_ledger_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
);
