-- Yuvalay MakerSpace Database Schema

CREATE DATABASE IF NOT EXISTS `yuvalay_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `yuvalay_db`;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `mobile` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'member', 'volunteer', 'mentor') DEFAULT 'member',
  
  -- Profile / Academic Information
  `dob` DATE NULL,
  `gender` VARCHAR(20) NULL,
  `college` VARCHAR(255) NULL,
  `branch` VARCHAR(150) NULL,
  `semester` VARCHAR(20) NULL,
  `student_id` VARCHAR(100) NULL,
  
  -- Professional Information
  `occupation` VARCHAR(100) NULL,
  `skills` TEXT NULL,
  `experience_level` VARCHAR(50) NULL,
  
  -- Status (Approved/Suspended/Pending)
  `status` ENUM('pending', 'approved', 'suspended') DEFAULT 'approved',
  `email_verified` TINYINT(1) DEFAULT 0,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `events`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL, -- 'Workshops', 'Hackathons', 'Training Programs', 'Meetups', etc.
  `banner_image` VARCHAR(255) NULL,
  `event_date` DATE NOT NULL,
  `event_time` TIME NOT NULL,
  `venue` VARCHAR(255) NOT NULL,
  `organizer` VARCHAR(150) DEFAULT 'Yuvalay MakerSpace',
  `capacity` INT NOT NULL,
  `available_seats` INT NOT NULL,
  `registration_deadline` DATETIME NOT NULL,
  `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
  
  -- Rich Modal Content
  `agenda` TEXT NULL,
  `speakers` TEXT NULL,
  `requirements` TEXT NULL,
  `google_map_url` TEXT NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `event_registrations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `registration_id` VARCHAR(50) NOT NULL UNIQUE, -- e.g. YMS-EVT-XXXXX
  `status` ENUM('Registered', 'Attended', 'Cancelled') DEFAULT 'Registered',
  `attendance_status` ENUM('absent', 'present') DEFAULT 'absent',
  `answers` TEXT NULL, -- JSON formatted responses to Event questions
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Table `attendance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `registration_id` INT NOT NULL,
  `checked_in_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `checked_in_by` INT NULL,
  
  FOREIGN KEY (`registration_id`) REFERENCES `event_registrations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`checked_in_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Table `resources`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL, -- 'Electronics', 'Robotics', '3D Printing', etc.
  `file_url` VARCHAR(255) NOT NULL,
  `thumbnail_url` VARCHAR(255) NULL,
  `author` VARCHAR(150) NOT NULL,
  `upload_date` DATE NOT NULL,
  `downloads_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `gallery`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `media_url` VARCHAR(255) NOT NULL,
  `media_type` ENUM('image', 'video') DEFAULT 'image',
  `caption` VARCHAR(255) NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `testimonials`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(100) NOT NULL,
  `text` TEXT NOT NULL,
  `rating` INT DEFAULT 5,
  `image_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `contact_messages`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(20) NULL,
  `subject` VARCHAR(255) NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `site_settings`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `category` VARCHAR(50) DEFAULT 'General'
);

-- -----------------------------------------------------
-- Table `homepage_slides`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `homepage_slides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image_url` VARCHAR(255) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `subtitle` VARCHAR(255) NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table `audit_logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Table `custom_pages`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `status` ENUM('draft', 'published') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
