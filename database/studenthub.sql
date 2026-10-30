-- =====================================================================
-- StudentHub database  (Practical 8 schema + seed data for Practicals 9-14)
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < database/studenthub.sql
-- Demo logins:  admin@studenthub.example / Admin@123
--               aarav.patel@example.com  / Student@123
-- Change these passwords before showing the project to anyone else.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, registrations, remember_tokens, events, students, courses, users;
SET FOREIGN_KEY_CHECKS = 1;

-- Login accounts (authentication + roles)
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','student') NOT NULL DEFAULT 'student',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login    DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- Lookup table (keeps course names in one place: 3rd normal form)
CREATE TABLE courses (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courses_name (name)
) ENGINE=InnoDB;

-- Student records (a student may or may not have a login account)
CREATE TABLE students (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,
  student_code VARCHAR(15) NOT NULL,
  full_name    VARCHAR(60) NOT NULL,
  email        VARCHAR(150) NOT NULL,
  mobile       CHAR(10) NOT NULL,
  course_id    INT UNSIGNED NOT NULL,
  year         TINYINT UNSIGNED NOT NULL,
  gender       ENUM('Male','Female','Other') NOT NULL,
  city         VARCHAR(60) NULL,
  status       ENUM('active','deleted') NOT NULL DEFAULT 'active',  -- soft delete
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_code (student_code),
  UNIQUE KEY uq_students_email (email),
  UNIQUE KEY uq_students_user (user_id),
  KEY idx_students_course (course_id),
  KEY idx_students_status (status),
  CONSTRAINT fk_students_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
  CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT chk_students_year  CHECK (year BETWEEN 1 AND 4)
) ENGINE=InnoDB;

CREATE TABLE events (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  category    VARCHAR(30) NOT NULL,
  venue       VARCHAR(120) NOT NULL,
  event_date  DATE NOT NULL,
  poster      VARCHAR(80) NULL,
  status      ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_date (event_date),
  KEY idx_events_category (category),
  CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Many-to-many: which student registered for which event
CREATE TABLE registrations (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  event_id      INT UNSIGNED NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_registration (student_id, event_id),
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- Remember-me tokens (only a SHA-256 hash of the token is stored)
CREATE TABLE remember_tokens (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  selector   CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_remember_selector (selector),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Important admin/user actions (Practical 14)
CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(40) NOT NULL,
  entity     VARCHAR(30) NOT NULL,
  entity_id  INT UNSIGNED NULL,
  details    VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------- seed data ----------------------------
INSERT INTO users (id, email, password_hash, role, status) VALUES
(1, 'admin@studenthub.example', '$2y$10$BbpQj8BrwDtgGTGcumLlwel1UxVBXeKLKfPcIo6rPTk644NF0qqS6', 'admin', 'active'),
(2, 'aarav.patel@example.com', '$2y$10$Ss6cxm90oALoHdk3bzblf.Yw8/OMhaEXUONigYfYcpX1U8CZpZwze', 'student', 'active');

INSERT INTO courses (id, name) VALUES (1, 'Computer Science'), (2, 'CSE - AI & ML'), (3, 'Electronics');

INSERT INTO students (user_id, student_code, full_name, email, mobile, course_id, year, gender, city, status) VALUES
(2, 'STU2026001', 'Aarav Patel', 'aarav.patel@example.com', '7447712782', 2, 2, 'Male', 'Vadodara', 'active'),
(NULL, 'STU2026002', 'Diya Shah', 'diya.shah@example.com', '6214706269', 1, 1, 'Female', 'Anand', 'active'),
(NULL, 'STU2026003', 'Rohan Mehta', 'rohan.mehta@example.com', '7277113153', 3, 3, 'Male', 'Ahmedabad', 'active'),
(NULL, 'STU2026004', 'Ananya Joshi', 'ananya.joshi@example.com', '7221286835', 2, 2, 'Female', 'Surat', 'active'),
(NULL, 'STU2026005', 'Kabir Desai', 'kabir.desai@example.com', '8689156345', 1, 4, 'Male', 'Nadiad', 'active'),
(NULL, 'STU2026006', 'Isha Trivedi', 'isha.trivedi@example.com', '6786579303', 2, 1, 'Female', 'Vadodara', 'active'),
(NULL, 'STU2026007', 'Vivaan Parmar', 'vivaan.parmar@example.com', '8171816127', 3, 2, 'Male', 'Rajkot', 'active'),
(NULL, 'STU2026008', 'Riya Chauhan', 'riya.chauhan@example.com', '7701033337', 1, 3, 'Female', 'Anand', 'active'),
(NULL, 'STU2026009', 'Aditya Solanki', 'aditya.solanki@example.com', '9577964601', 2, 3, 'Male', 'Bharuch', 'active'),
(NULL, 'STU2026010', 'Meera Bhatt', 'meera.bhatt@example.com', '6227167048', 3, 1, 'Female', 'Ahmedabad', 'active'),
(NULL, 'STU2026011', 'Karan Vyas', 'karan.vyas@example.com', '9958043599', 1, 2, 'Male', 'Vadodara', 'active'),
(NULL, 'STU2026012', 'Nisha Rathod', 'nisha.rathod@example.com', '9885475575', 2, 4, 'Female', 'Surat', 'active'),
(NULL, 'STU2026013', 'Harsh Pandya', 'harsh.pandya@example.com', '8189679476', 3, 4, 'Male', 'Nadiad', 'active'),
(NULL, 'STU2026014', 'Pooja Thakkar', 'pooja.thakkar@example.com', '6482679660', 1, 1, 'Female', 'Rajkot', 'active'),
(NULL, 'STU2026015', 'Yash Gohil', 'yash.gohil@example.com', '7734931155', 2, 2, 'Male', 'Anand', 'active'),
(NULL, 'STU2026016', 'Sanjay Kapadia', 'sanjay.kapadia@example.com', '8616734638', 1, 3, 'Male', 'Vadodara', 'active'),
(NULL, 'STU2026017', 'Tanvi Modi', 'tanvi.modi@example.com', '9407086833', 2, 1, 'Female', 'Surat', 'active'),
(NULL, 'STU2026018', 'Dhruv Amin', 'dhruv.amin@example.com', '7714799593', 3, 2, 'Male', 'Anand', 'active'),
(NULL, 'STU2026019', 'Kavya Raval', 'kavya.raval@example.com', '6630248772', 1, 4, 'Female', 'Ahmedabad', 'active'),
(NULL, 'STU2026020', 'Manav Brahmbhatt', 'manav.brahmbhatt@example.com', '7928489459', 2, 3, 'Male', 'Nadiad', 'active');

INSERT INTO events (title, description, category, venue, event_date, poster, status, created_by) VALUES
('Web Development Bootcamp', 'Hands-on session covering HTML, CSS, JavaScript and PHP basics for beginners.', 'Workshop', 'Computer Lab 2', '2026-11-05', NULL, 'open', 1),
('Git and GitHub Workshop', 'Learn version control, commits, branches and how to publish a project on GitHub.', 'Workshop', 'Computer Lab 1', '2026-11-08', NULL, 'open', 1),
('Annual Sports Day', 'Track and field events, relay races and team games for all departments.', 'Sports', 'College Ground', '2026-11-12', NULL, 'closed', 1),
('AI and Machine Learning Seminar', 'An industry expert explains how machine learning is used in real products.', 'Seminar', 'Main Auditorium', '2026-11-18', NULL, 'open', 1),
('Inter-College Cricket Tournament', 'Knockout cricket tournament with teams from nearby colleges.', 'Sports', 'Cricket Ground', '2026-11-20', NULL, 'closed', 1),
('Photography Contest', 'Submit your best campus photograph and win exciting prizes.', 'Cultural', 'Art Gallery', '2026-11-25', NULL, 'open', 1),
('Career Guidance Session', 'Placement cell and alumni share tips on internships and off-campus hiring.', 'Seminar', 'Seminar Hall B', '2026-11-30', NULL, 'open', 1),
('Tech Quiz Challenge', 'Team quiz on programming, computer science fundamentals and current technology.', 'Technical', 'Seminar Hall A', '2026-12-01', NULL, 'open', 1),
('Cultural Fest 2026', 'Music, dance, drama and art performances by students.', 'Cultural', 'Open Air Theatre', '2026-12-05', NULL, 'open', 1),
('Blood Donation Camp', 'Voluntary blood donation camp organised with a local hospital.', 'Social', 'Medical Room', '2026-12-08', NULL, 'open', 1),
('Hackathon 24', 'A 24-hour team hackathon to build creative solutions to campus problems.', 'Technical', 'Innovation Lab', '2026-12-12', NULL, 'open', 1),
('Resume Building Workshop', 'Learn to write a clear, one-page resume and a strong LinkedIn profile.', 'Workshop', 'Placement Cell', '2026-12-15', NULL, 'open', 1),
('Annual Day Celebration', 'Awards, performances and a look back at the year''s achievements.', 'Cultural', 'Main Auditorium', '2027-01-10', NULL, 'open', 1),
('Robotics Expo', 'Student-built robots and automation projects on display with live demos.', 'Technical', 'Mechanical Block', '2027-01-18', NULL, 'open', 1),
('Alumni Meet 2027', 'Former students return to meet current students and share their journeys.', 'Social', 'Conference Hall', '2027-02-06', NULL, 'open', 1);

INSERT INTO registrations (student_id, event_id, registered_at) VALUES
(4, 1, '2026-10-09 11:14:00'),
(5, 12, '2026-08-22 19:57:00'),
(18, 2, '2026-10-14 08:01:00'),
(3, 4, '2026-08-17 17:01:00'),
(18, 4, '2026-10-21 19:34:00'),
(14, 4, '2026-09-19 12:51:00'),
(1, 3, '2026-10-14 13:17:00'),
(5, 4, '2026-09-04 09:24:00'),
(4, 6, '2026-09-20 12:51:00'),
(2, 12, '2026-09-18 09:59:00'),
(13, 2, '2026-10-10 18:39:00'),
(12, 10, '2026-08-23 09:02:00'),
(8, 5, '2026-08-28 11:55:00'),
(4, 7, '2026-09-15 18:53:00'),
(12, 3, '2026-09-12 11:42:00'),
(9, 12, '2026-10-21 09:38:00'),
(6, 9, '2026-10-08 10:29:00'),
(13, 5, '2026-10-23 16:14:00'),
(11, 1, '2026-08-27 08:51:00'),
(11, 7, '2026-09-03 11:58:00'),
(19, 12, '2026-09-07 18:31:00'),
(13, 11, '2026-09-05 12:08:00'),
(8, 12, '2026-10-18 12:47:00'),
(19, 7, '2026-10-13 13:14:00'),
(5, 9, '2026-09-03 20:03:00'),
(4, 3, '2026-10-06 20:43:00'),
(14, 10, '2026-08-13 14:38:00'),
(15, 9, '2026-09-18 08:43:00');

INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES
(1, 'seed', 'database', NULL, 'Initial seed data imported', '127.0.0.1');
