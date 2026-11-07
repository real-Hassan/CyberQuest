# CyberQuest

A browser-based cybersecurity learning platform built with PHP, MySQL and vanilla JavaScript (LAMP).
Students enroll in modules, read a short lesson, experiment with an **in-browser simulation**, then solve
multiple-choice and text challenges. Finishing every challenge in a module issues a verifiable certificate.

No virtual machines are used. Every "attack" runs inside a client-side simulation, so nothing real is exploited.

Course project for Web Technologies. Authors: Muhammad Bilal Khan, Muhammad Hassan Farooq.

## Modules

| # | Module | Difficulty | Simulation |
|---|--------|-----------|------------|
| 1 | Linux Basics | Easy | Terminal with a fake filesystem (`ls`, `cd`, `cat`, `grep`, hidden files) |
| 2 | SQL Injection | Easy | Login form that shows the built query and the result table; secure-mode toggle |
| 3 | Cross-Site Scripting | Easy | Guestbook showing the HTML sent and what the browser would do; escape toggle |
| 4 | Password Brute Force | Easy | Dictionary attack against a login; lockout toggle |
| 5 | Directory Traversal | Medium | File viewer that resolves `../`; realpath-check toggle |
| 6 | CSRF | Medium | Bank page plus malicious page; CSRF-token toggle and request log |

Command Injection, File Upload Attacks and Network Scanning Basics are listed as **Coming soon**
(rows in `modules` with `is_published = 0`).

Each lesson has 4 challenges (2 multiple choice, 2 text answer) with hints: 24 challenges, 305 points in total.

## Look and feel

- Light and dark themes: follows the system setting by default, with a toggle (top bar, or top-right on login pages). The choice is saved in `localStorage`.
- Colors, radii and gradients are CSS variables at the top of `public/assets/css/styles.css`; change them there to re-theme the whole site.
- Motion is light (fade-ins, hover lifts, progress fills, XP toast, confetti on module completion) and switches off automatically for users with "reduce motion" enabled.
- Fonts (Nunito, JetBrains Mono) load from Google Fonts and fall back to system fonts when offline.
- Module card icons and colors come from `module_art()` in `includes/functions.php` (no database column needed).

## Requirements

- PHP 8.1+ with the `pdo_mysql` extension
- MySQL 8.0 or MariaDB 10.5+ (needs `JSON` and `CHECK` support)
- Apache (or any server) with the document root set to `public/`

## Project structure

```
CyberQuest/
├── cyberquest_db.sql        Schema (tables + progress view). DROPS existing tables.
├── seed.sql                 Modules, lessons and challenges
├── config/db.php            PDO connection
├── includes/
│   ├── auth.php             Sessions, login guard, CSRF helpers
│   ├── functions.php        Helpers: escaping, answer hashing, progress, certificates
│   ├── header.php           Shared layout (sidebar + top bar)
│   └── footer.php
└── public/                  Web root
    ├── login.php, register.php, logout.php
    ├── index.php            Dashboard
    ├── modules.php          Catalog (published + coming soon)
    ├── module.php           Module overview, enroll, lesson list
    ├── enroll.php           POST: enroll in a module
    ├── lesson.php           Lesson text + simulation + challenges
    ├── submit_flag.php      POST (JSON): check an answer, log it, complete module
    ├── certificates.php     My certificates
    ├── certificate.php      Printable certificate (owner only)
    ├── verify.php           Public certificate check
    └── assets/
        ├── css/styles.css
        └── js/
            ├── theme.js     Light/dark theme (loaded in <head>)
            ├── main.js      Answer submission, XP toast, confetti, simulation start-up
            └── sims.js      The six simulations
```

## Setup

### 1. Install packages (Ubuntu example)

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server
```

### 2. Create the database user

Choose a password and use the **same** one in `config/db.php`.

```sql
CREATE USER 'cyberquest_user'@'localhost' IDENTIFIED BY 'CHANGE_ME';
```

### 3. Import the schema and the content

```bash
sudo mysql < cyberquest_db.sql      # creates cyberquest_db and all tables
sudo mysql < seed.sql               # adds modules, lessons, challenges
```

Then grant access (the database exists after step 3):

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON cyberquest_db.* TO 'cyberquest_user'@'localhost';
FLUSH PRIVILEGES;
```

> Re-importing `cyberquest_db.sql` drops every table, including users and progress.
> To change content only, edit and re-run `seed.sql` after clearing the content tables (see "Reset content" below).

### 4. Point the web server at `public/`

Apache virtual host example (`/etc/apache2/sites-available/cyberquest.conf`):

```apache
<VirtualHost *:80>
    ServerName cyberquest.local
    DocumentRoot /var/www/CyberQuest/public
    <Directory /var/www/CyberQuest/public>
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite cyberquest && sudo systemctl reload apache2
```

For quick local testing you can skip Apache: `cd public && php -S localhost:8000`.

### 5. Open the site

Visit the site, register an account, enroll in a module and start the first lesson.

## How it works

- **Content in the database.** Lessons hold trusted HTML. `lessons.sim_key` picks a simulation from `sims.js`.
- **Answers are hashed.** `answer_hash = SHA-256(lowercase(trim(answer)))`. Multiple-choice answers are the option index (`0` to `3`).
- **Progress comes from the view** `v_module_progress` (total challenges vs. distinct correct submissions per enrollment).
- **Completion** happens in `finish_module_if_done()`: when all challenges in a module are solved, the enrollment becomes
  `completed` and a 16-character certificate code is issued (`UNIQUE` on code and on user + module).

## Useful SQL

Make a user an admin:

```sql
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

Hash an answer for a new challenge (must match what PHP computes):

```sql
SELECT SHA2(LOWER(TRIM('CQ{my_new_flag}')), 256);
```

Add a text challenge to an existing lesson:

```sql
INSERT INTO challenges (lesson_id, challenge_type, question, hint, answer_hash, points, order_no)
VALUES (2, 'text', 'Your question?', 'Your hint', SHA2(LOWER(TRIM('answer')), 256), 10, 5);
```

Add a multiple-choice challenge (the answer is the index of the correct option):

```sql
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no)
VALUES (2, 'mcq', 'Question?', JSON_ARRAY('Option A', 'Option B', 'Option C', 'Option D'),
        'Hint', SHA2('1', 256), 10, 6);   -- correct = Option B
```

Publish a "coming soon" module (after adding its lessons):

```sql
UPDATE modules SET is_published = 1 WHERE slug = 'command-injection';
```

Leaderboard:

```sql
SELECT u.username, SUM(c.points) AS points
FROM (SELECT DISTINCT user_id, challenge_id FROM submissions WHERE is_correct = 1) s
JOIN challenges c ON c.id = s.challenge_id
JOIN users u ON u.id = s.user_id
GROUP BY u.id ORDER BY points DESC;
```

Reset one user's progress:

```sql
DELETE FROM certificates WHERE user_id = 1;
DELETE FROM submissions  WHERE user_id = 1;
DELETE FROM enrollments  WHERE user_id = 1;
```

Reset content only (keeps users and their accounts; also removes progress because of the foreign keys):

```sql
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE certificates; TRUNCATE submissions; TRUNCATE enrollments;
TRUNCATE challenges; TRUNCATE lessons; TRUNCATE modules;
SET FOREIGN_KEY_CHECKS = 1;
-- then: SOURCE seed.sql;
```

## Adding a new module

1. Insert a row in `modules` and capture the id (`SET @m = LAST_INSERT_ID();`).
2. Insert a row in `lessons` with the HTML content and a `sim_key` (or `NULL` for no simulation).
3. Insert its challenges (see queries above).
4. For a new simulation, add `S.yourkey = (root) => { ... }` to `public/assets/js/sims.js`.
   Always escape user input before inserting it into the DOM (`esc()` helper).

## Security notes

- Passwords: `password_hash()` / `password_verify()` (bcrypt)
- All queries use PDO prepared statements
- CSRF tokens on every state-changing form (login, register, logout, enroll, answers)
- Output escaped with `htmlspecialchars` (`e()`); lesson HTML is the only trusted, unescaped content
- Answers stored as SHA-256 hashes, compared with `hash_equals`
- Wrong answers are rate-limited (10 per challenge per minute)
- Enrollment is required to open lessons and submit answers
- The simulations never execute user input: payloads are only pattern-matched and displayed

Before publishing the repository: move the database password out of `config/db.php` (for example into an
environment variable or an ignored config file) and keep `.git` out of any shared zip.

## Known limitations

- The simulators are simplified models, not real databases or browsers (for example, the SQL evaluator supports `=`, `AND`, `OR` and comments only).
- Answers for the simulation-based challenges can be found by reading `sims.js`; this is acceptable for a learning tool.
- No password reset, email verification or admin panel yet.
- Only one lesson per module so far; the schema already supports many.
