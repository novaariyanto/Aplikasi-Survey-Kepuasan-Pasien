-- Database untuk Aplikasi Survei Kepuasan Pasien RSUD Soewondo
-- Jalankan script ini di phpMyAdmin atau MySQL client

CREATE DATABASE IF NOT EXISTS survei_kepuasan;
USE survei_kepuasan;

-- Tabel untuk menyimpan daftar pertanyaan survei
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan data pasien dan saran
CREATE TABLE survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomr VARCHAR(20) NOT NULL,
    saran TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan jawaban per pertanyaan
CREATE TABLE survey_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Tabel untuk admin login
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert pertanyaan default
INSERT INTO questions (question_text, is_active) VALUES
('Bagaimana pelayanan perawat selama perawatan?', 1),
('Bagaimana kebersihan ruang rawat inap?', 1),
('Bagaimana keramahan petugas rumah sakit?', 1),
('Bagaimana kenyamanan fasilitas rawat inap?', 1),
('Bagaimana kualitas makanan yang disajikan?', 1),
('Secara keseluruhan, apakah Anda puas dengan pelayanan RS Soewondo?', 1);

-- Insert admin default (username: admin, password: admin123)
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
