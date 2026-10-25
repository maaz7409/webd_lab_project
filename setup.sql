-- Department Notice and Task Board (NCSM301 Mini Project, P9)
-- Run this whole script once in the phpMyAdmin SQL tab (with no database selected).
-- To start again from scratch, first run: DROP DATABASE notice_board;

CREATE DATABASE notice_board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE notice_board;

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    login_id      VARCHAR(20)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL DEFAULT 'student',
    CHECK (role IN ('student', 'staff'))
);

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(30) NOT NULL UNIQUE
);

-- A notice with requires_ack = 1 is a "task": students must acknowledge it,
-- optionally before due_date. is_active = 0 hides a notice from the board.
CREATE TABLE notices (
    notice_id    INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150) NOT NULL,
    body         TEXT         NOT NULL,
    category_id  INT          NOT NULL,
    posted_by    INT          NOT NULL,
    due_date     DATE         NULL,
    requires_ack TINYINT(1)   NOT NULL DEFAULT 0,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories (category_id),
    FOREIGN KEY (posted_by) REFERENCES users (user_id)
);

-- One row per (user, notice) once the user has read it.
-- The primary key makes it impossible to record the same pair twice.
CREATE TABLE notice_reads (
    user_id         INT       NOT NULL,
    notice_id       INT       NOT NULL,
    read_at         TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    PRIMARY KEY (user_id, notice_id),
    FOREIGN KEY (user_id) REFERENCES users (user_id),
    FOREIGN KEY (notice_id) REFERENCES notices (notice_id)
);

INSERT INTO categories (name) VALUES
    ('Academic'),
    ('Laboratory'),
    ('Event'),
    ('Placement'),
    ('General');

-- Every demo account uses the password: notice123
INSERT INTO users (login_id, full_name, password_hash, role) VALUES
    ('office',   'Department Office', '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'staff'),
    ('labcoord', 'Lab Coordinator',   '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'staff'),
    ('24JE9001', 'Riya Verma',        '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    ('24JE9002', 'Aman Gupta',        '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    ('24JE9003', 'Neha Singh',        '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    -- Group 16 members (replace the names with your own)
    ('24JE0831', 'Group 16 Member 1', '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    ('24JE0873', 'Group 16 Member 2', '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    ('24JE0560', 'Group 16 Member 3', '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student'),
    ('24JE1033', 'Group 16 Member 4', '$2y$10$IqP/rZaVcXFRPe7jzcMpXuOcGmWrw5tf77lGnXhxU6wwoSRQvuJae', 'student');

-- Dates are relative to the day the script is run, so the demo always has
-- upcoming and overdue tasks.
INSERT INTO notices (title, body, category_id, posted_by, due_date, requires_ack, is_active, created_at) VALUES
    ('Submit Lab 10 record files',
     'Upload the completed Lab 10 practice task (all PHP files and styles.css) as a single ZIP file. Name the file with your admission number.',
     2, 2, CURDATE() + INTERVAL 3 DAY, 1, 1, NOW() - INTERVAL 1 DAY),
    ('Mid-semester answer script display',
     'Answer scripts for the mid-semester examination will be shown in the department seminar hall between 2:00 PM and 4:00 PM. Bring your institute ID card.',
     1, 1, NULL, 0, 1, NOW() - INTERVAL 2 DAY),
    ('Pre-placement talk: registration required',
     'A software company will hold a pre-placement talk for pre-final year students. Acknowledge this notice to confirm that you have registered on the placement portal.',
     4, 1, CURDATE() + INTERVAL 6 DAY, 1, 1, NOW() - INTERVAL 2 DAY),
    ('Course feedback form',
     'Fill in the course feedback form for all theory and laboratory courses of this semester. Acknowledge once you have submitted it.',
     1, 1, CURDATE() - INTERVAL 1 DAY, 1, 1, NOW() - INTERVAL 5 DAY),
    ('Tech fest volunteers meeting',
     'Students who volunteered for the department tech fest should meet in Room 204 on Friday at 5:30 PM to receive their duties.',
     3, 1, NULL, 0, 1, NOW() - INTERVAL 3 DAY),
    ('Lab session rescheduled',
     'The Wednesday afternoon Web Development Laboratory session for Section B is moved to Thursday morning this week. The lab quiz will be held as usual.',
     2, 2, NULL, 0, 1, NOW() - INTERVAL 4 HOUR),
    ('Hackathon team registration',
     'Form teams of four and register for the inter-department hackathon. One member per team should acknowledge this notice after registering.',
     3, 1, CURDATE() + INTERVAL 10 DAY, 1, 1, NOW() - INTERVAL 6 DAY),
    ('Resume review session',
     'The placement cell will review resumes of interested students. Bring two printed copies of your resume.',
     4, 1, NULL, 0, 1, NOW() - INTERVAL 7 DAY),
    ('Library timings during examinations',
     'The central library will remain open until 11:00 PM on all days during the end-semester examinations.',
     5, 1, NULL, 0, 1, NOW() - INTERVAL 8 DAY),
    ('Old holiday notice',
     'This notice has been deactivated and is visible only in the management interface.',
     5, 1, NULL, 0, 0, NOW() - INTERVAL 30 DAY);

-- A few existing read/acknowledged states so the demo is not empty.
INSERT INTO notice_reads (user_id, notice_id, read_at, acknowledged_at) VALUES
    (3, 2, NOW() - INTERVAL 1 DAY, NULL),
    (3, 7, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
    (4, 1, NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 12 HOUR),
    (4, 9, NOW() - INTERVAL 7 DAY, NULL);
