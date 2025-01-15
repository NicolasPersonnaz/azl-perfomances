CREATE DATABASE IF NOT EXISTS calendar_db;

USE calendar_db;

CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL
);

INSERT INTO events (title, start_date) VALUES
('Révision moteur', '2025-02-10'),
('Changement de courroie', '2025-02-15'),
('Entretien général', '2025-03-01')
ON DUPLICATE KEY UPDATE title=VALUES(title), start_date=VALUES(start_date);
