# StudentHub

A student portal built step by step for **AIUE203 - Web Development Frameworks** (CHARUSAT, Semester 3, 2026-27).
Stack: HTML5, CSS3, JavaScript (ES6 modules, Fetch API), PHP 7.4+/8.x, MySQL/MariaDB, Apache (XAMPP).

## Features
- Responsive, accessible pages with dark mode, FAQ accordion, modal, slider and notification banner
- Registration with JavaScript **and** PHP validation, password strength meter, `password_hash()`
- Secure login/logout, sessions, role-based access (admin / student), session timeout, remember-me
- Student management CRUD (search, filter, pagination, CSV export, soft delete, restore)
- Event management CRUD with poster upload validation, student event registration
- JSON API + Fetch/AJAX CRUD (no page reload), admin dashboard, audit log

## Installation (XAMPP on Windows)
1. Copy this folder to `C:\xampp\htdocs\studenthub`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open <http://localhost/phpmyadmin> -> **Import** -> choose `database/studenthub.sql` -> **Go**.
   (It creates the `studenthub` database, all tables and seed data.)
4. If your MySQL root user has a password, edit `includes/config.php`.
5. Open <http://localhost/studenthub/>.

Linux/macOS: `mysql -u root < database/studenthub.sql` then `php -S localhost:8000` inside the folder.

## Demo accounts (change them after the demo)
| Role | Email | Password |
|---|---|---|
| Admin | admin@studenthub.example | Admin@123 |
| Student | aarav.patel@example.com | Student@123 |

## Folder structure
```
studenthub/
├── index.php, about.php, faq.php, events.php, students.php   public pages
├── contact.php, feedback.php                                 forms (P7)
├── process_contact.php, process_feedback.php                 CSV / JSON storage (P7)
├── register.php, login.php, logout.php                       P9, P10
├── dashboard.php, profile.php, register_event.php            student area
├── test_connection.php                                       P8 connection test
├── admin/      dashboard, students (CRUD), events (CRUD), users, messages, AJAX page
├── api/        students.php (full CRUD), events.php (read), _bootstrap.php
├── includes/   config, db (MySQLi), db_pdo, helpers, auth, repo, header, footer
├── css/ js/ data/                                            front-end + P6 JSON files
├── database/studenthub.sql                                   schema + seed data
├── storage/                                                  CSV/JSON written by P7 (not public)
├── uploads/posters/                                          event posters (P12)
└── docs/                                                     test report, checklists, ER diagram, deployment
```

## Practical map
See `docs/PRACTICAL_MAP.md` for which files belong to which practical and which screenshots to take.

## Security summary
Prepared statements everywhere (no string-built SQL) - `password_hash()` / `password_verify()` - session id
regenerated on login - CSRF tokens on every POST and API write - output escaped with `htmlspecialchars` -
role checks on every admin page and API - upload type/size/content checks with random file names -
login attempt limit - remember-me stores only a hash of the token - audit log of important actions.

## Notes
- Session timeout is 15 minutes (`SESSION_TIMEOUT` in `includes/config.php`). Set it to `60` to demonstrate it.
- A remember-me cookie signs the user in again after a timeout. That is how remember-me is meant to work;
  untick it to demonstrate the timeout.
- The "Directory" page (`students.php`) shows the sample data from `data/students.json` (Practical 6).
  Real student records are managed by admins in `admin/students.php`.
