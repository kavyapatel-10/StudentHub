# Practical map: files and screenshots

| P | Topic | Main files | Screenshots / submission items |
|---|---|---|---|
| 1 | Setup, sitemap, wireframe | `README.md`, `docs/ER_DIAGRAM.md` | requirements, sitemap, wireframes, GitHub link |
| 2 | Semantic HTML | `includes/header.php`, `includes/footer.php`, all pages | page screenshots, `docs/ACCESSIBILITY_CHECKLIST.md` |
| 3 | Responsive CSS | `css/style.css` | mobile, tablet, desktop screenshots |
| 4 | JS interactivity | `js/script.js` | FAQ, modal, slider, banner, menu, theme demo |
| 5 | Form validation | `js/validation.js`, `register.php` | valid and invalid input screenshots, test cases |
| 6 | Fetch + JSON | `data/*.json`, `js/utils.js`, `events.js`, `students.js`, `faq.js` | search, filter, sort, pagination, error state |
| 7 | PHP form processing | `process_contact.php` (CSV), `process_feedback.php` (JSON), `admin/messages.php` | stored CSV/JSON file, success/error messages |
| 8 | MySQL + connection | `database/studenthub.sql`, `includes/db.php`, `includes/db_pdo.php`, `test_connection.php` | SQL dump, db.php, connection test screenshot |
| 9 | Secure registration | `register.php` | valid, invalid and duplicate cases; hashed password in phpMyAdmin |
| 10 | Login / sessions / RBAC | `login.php`, `logout.php`, `includes/auth.php` | login ok, failed, logout, timeout, unauthorized (403) |
| 11 | Student CRUD | `admin/students.php`, `student_form.php`, `students_export.php`, `includes/repo.php` | CRUD screenshots, CSV export, soft delete, test cases |
| 12 | Event CRUD + upload | `admin/events.php`, `event_form.php`, `includes/repo.php` | add/edit/delete, wrong type, too big, poster shown |
| 13 | JSON API + AJAX | `api/*.php`, `admin/students_ajax.php`, `js/students-ajax.js` | Network tab (status codes), AJAX demo |
| 14 | Admin dashboard | `admin/dashboard.php`, `admin/users.php` | stats, chart, audit log, role-based menus |
| 15 | Integration | whole project, `docs/` | final GitHub link, SQL dump, README, test report, demo |

## Viva quick answers
- **Why prepared statements?** SQL and data travel separately, so input can never change the query (stops SQL injection).
- **Why `password_hash`?** It is slow, salted and one-way. `password_verify` checks a login without storing the password.
- **Why regenerate the session id at login?** It prevents session fixation.
- **Why a CSRF token?** Another website cannot make the browser submit a form or API write without the secret token.
- **Why validate on both client and server?** Client checks are for convenience; the server is the only one that can be trusted.
- **How is the poster upload safe?** The real MIME type and image content are checked, size is limited, and the file gets a random name.
- **MySQLi vs PDO?** Both support prepared statements. MySQLi is MySQL-only; PDO works with many databases. See `includes/db.php` and `includes/db_pdo.php`.
