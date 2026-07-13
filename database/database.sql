-- ============================================================
--  LMS MVP  |  Database: test_project
--  Run this entire script in phpMyAdmin > SQL tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS test_project
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE test_project;

-- ----------------------------------------------------------
-- 1. USERS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120)  NOT NULL,
  email       VARCHAR(180)  NOT NULL UNIQUE,
  password    VARCHAR(255)  NOT NULL,
  role        ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 2. COURSES
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200)  NOT NULL,
  description  TEXT,
  created_by   INT           NOT NULL,          -- admin user id
  is_published TINYINT(1)    NOT NULL DEFAULT 0,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 3. COURSE MODULES / FILES
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_modules (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  course_id   INT           NOT NULL,
  title       VARCHAR(200)  NOT NULL,
  content     TEXT,
  file_path   VARCHAR(300),
  sort_order  INT           DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 4. ENROLLMENTS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  course_id   INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enrollment (user_id, course_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 5. ASSIGNMENTS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS assignments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  course_id     INT           NOT NULL,
  instructor_id INT           NOT NULL,
  title         VARCHAR(200)  NOT NULL,
  instructions  TEXT,
  due_date      DATETIME,
  points        INT           DEFAULT 100,
  is_published  TINYINT(1)    NOT NULL DEFAULT 0,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id)     REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (instructor_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 6. SUBMISSIONS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS submissions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id   INT  NOT NULL,
  student_id      INT  NOT NULL,
  text_answer     TEXT,
  file_path       VARCHAR(300),
  submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  score           INT,
  feedback        TEXT,
  graded_at       TIMESTAMP NULL,
  UNIQUE KEY uq_submission (assignment_id, student_id),
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id)    REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- SEED DATA  — default admin account
-- password: Admin@1234   (bcrypt hash)
-- ----------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
('Super Admin',
 'admin@lms.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin');
-- NOTE: The hash above is bcrypt of "password"
-- Change it immediately after first login via Manage Users.

-- ============================================================
--  LMS MVP  |  PATCH: Course Progress Tracking
--  Run this in phpMyAdmin > SQL tab (on top of the original schema)
-- ============================================================

USE test_project;

-- Tracks which modules a student has marked as complete
CREATE TABLE IF NOT EXISTS module_progress (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  module_id   INT NOT NULL,
  course_id   INT NOT NULL,
  completed   TINYINT(1)  NOT NULL DEFAULT 1,
  completed_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_progress (user_id, module_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)          ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES course_modules(id)  ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id)         ON DELETE CASCADE
) ENGINE=InnoDB;

