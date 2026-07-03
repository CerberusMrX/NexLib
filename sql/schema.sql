CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category_id INT,
    isbn VARCHAR(20) UNIQUE,
    quantity INT DEFAULT 1,
    available INT DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    fine DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Sample Data
INSERT IGNORE INTO categories (name) VALUES ('Computer Science'), ('Fiction'), ('Science'), ('History'), ('Biography');

INSERT IGNORE INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- Password: password
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT IGNORE INTO books (title, author, category_id, isbn, quantity, available) VALUES 
('The Pragmatic Programmer', 'Andrew Hunt', 1, '978-0135957059', 5, 5),
('Clean Code', 'Robert C. Martin', 1, '978-0132350884', 3, 3),
('The Great Gatsby', 'F. Scott Fitzgerald', 2, '978-0743273565', 2, 2);
