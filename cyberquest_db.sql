-- CyberQuest database schema (MySQL 8 / MariaDB 10.5+)
-- Import first, then import seed.sql for the lesson content.

CREATE DATABASE IF NOT EXISTS cyberquest_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cyberquest_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_module_progress;
SET FOREIGN_KEY_CHECKS = 1;

-- Accounts
CREATE TABLE users (
  id            INT NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- One module = one security topic. is_published = 0 shows as "Coming soon".
CREATE TABLE modules (
  id                INT NOT NULL AUTO_INCREMENT,
  title             VARCHAR(100) NOT NULL,
  slug              VARCHAR(100) NOT NULL,
  description       TEXT,
  difficulty        ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
  estimated_minutes INT NOT NULL DEFAULT 20,
  order_no          INT NOT NULL DEFAULT 1,
  is_published      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_modules_slug (slug)
) ENGINE=InnoDB;

-- A lesson holds trusted HTML content and optionally a simulation (sim_key
-- matches a function in public/assets/js/sims.js).
CREATE TABLE lessons (
  id        INT NOT NULL AUTO_INCREMENT,
  module_id INT NOT NULL,
  title     VARCHAR(150) NOT NULL,
  content   MEDIUMTEXT NOT NULL,
  sim_key   VARCHAR(30) DEFAULT NULL,
  order_no  INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lessons_order (module_id, order_no),
  CONSTRAINT fk_lessons_module FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- mcq: options is a JSON array and the answer is the option index ("0".."3").
-- text: the answer is free text. Both store SHA-256(lowercase(trim(answer))).
CREATE TABLE challenges (
  id             INT NOT NULL AUTO_INCREMENT,
  lesson_id      INT NOT NULL,
  challenge_type ENUM('mcq','text') NOT NULL DEFAULT 'text',
  question       TEXT NOT NULL,
  options        JSON DEFAULT NULL,
  hint           TEXT,
  answer_hash    CHAR(64) NOT NULL,
  points         INT NOT NULL DEFAULT 10,
  order_no       INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_challenges_lesson (lesson_id, order_no),
  CONSTRAINT fk_challenges_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE,
  CONSTRAINT chk_mcq_options CHECK (challenge_type = 'text' OR options IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id           INT NOT NULL AUTO_INCREMENT,
  user_id      INT NOT NULL,
  module_id    INT NOT NULL,
  status       ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
  started_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enrollment (user_id, module_id),
  KEY idx_enrollments_module (module_id),
  CONSTRAINT fk_enrollments_user   FOREIGN KEY (user_id)   REFERENCES users (id)   ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_module FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Every attempt is logged. The composite index serves the "solved?" and rate-limit queries.
CREATE TABLE submissions (
  id               INT NOT NULL AUTO_INCREMENT,
  user_id          INT NOT NULL,
  challenge_id     INT NOT NULL,
  submitted_answer VARCHAR(255) NOT NULL,
  is_correct       TINYINT(1) NOT NULL,
  submitted_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_submissions_lookup (user_id, challenge_id, is_correct, submitted_at),
  KEY idx_submissions_challenge (challenge_id),
  CONSTRAINT fk_submissions_user      FOREIGN KEY (user_id)      REFERENCES users (id)      ON DELETE CASCADE,
  CONSTRAINT fk_submissions_challenge FOREIGN KEY (challenge_id) REFERENCES challenges (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One certificate per user per module; the 16-character code is public for verification.
CREATE TABLE certificates (
  id               INT NOT NULL AUTO_INCREMENT,
  user_id          INT NOT NULL,
  module_id        INT NOT NULL,
  certificate_code CHAR(16) NOT NULL,
  issued_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_certificate_code (certificate_code),
  UNIQUE KEY uq_certificate_user_module (user_id, module_id),
  KEY idx_certificates_module (module_id),
  CONSTRAINT fk_certificates_user   FOREIGN KEY (user_id)   REFERENCES users (id)   ON DELETE CASCADE,
  CONSTRAINT fk_certificates_module FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Per-enrollment progress: total challenges in the module vs. distinct ones solved.
CREATE VIEW v_module_progress AS
SELECT e.user_id,
       e.module_id,
       (SELECT COUNT(*)
          FROM challenges c JOIN lessons l ON l.id = c.lesson_id
         WHERE l.module_id = e.module_id) AS total_challenges,
       (SELECT COUNT(DISTINCT s.challenge_id)
          FROM submissions s
          JOIN challenges c ON c.id = s.challenge_id
          JOIN lessons l ON l.id = c.lesson_id
         WHERE s.user_id = e.user_id AND l.module_id = e.module_id AND s.is_correct = 1) AS solved_challenges
FROM enrollments e;
