-- Database: unimanager

CREATE DATABASE IF NOT EXISTS `unimanager` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unimanager`;

-- Roles
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL -- e.g. user, admin, organizer
);

INSERT INTO roles (name) VALUES ('user'), ('organizer'), ('admin');

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL DEFAULT 1,
  name VARCHAR(150),
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30),
  university VARCHAR(255),
  logo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_active TINYINT DEFAULT 1,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Events
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizer_id INT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  location VARCHAR(255),
  date DATE,
  time TIME,
  seats INT DEFAULT 100,
  booked INT DEFAULT 0,
  image VARCHAR(255),
  status ENUM('pending','published','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id)
);

-- Bookings
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  seats_reserved INT DEFAULT 1,
  seat_number VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('confirmed','cancelled') DEFAULT 'confirmed',
  FOREIGN KEY (event_id) REFERENCES events(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Reviews
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Password resets (store tokens)
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings (key-value)
CREATE TABLE settings (
  k VARCHAR(100) PRIMARY KEY,
  v TEXT
);

INSERT INTO settings (k,v) VALUES
('site_name','University Events'),
('base_url','http://localhost/unimanager');
