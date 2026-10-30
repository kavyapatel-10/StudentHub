# Security checklist

Tick each box after you have checked it yourself on your machine.

| Done | Check | Where it is implemented |
|---|---|---|
| [ ] | SQL injection: all queries use prepared statements | `includes/db.php`, `includes/repo.php` |
| [ ] | Try `' OR 1=1 --` in the student search: no extra rows | `admin/students.php` |
| [ ] | Passwords stored with `password_hash` (see `users.password_hash` in phpMyAdmin) | `register.php` |
| [ ] | Login uses `password_verify`, same message for wrong email or password | `login.php` |
| [ ] | Session id regenerated after login | `includes/auth.php` (`login_user`) |
| [ ] | Cookies are HttpOnly and SameSite=Lax (browser dev tools -> Application -> Cookies) | `includes/auth.php` |
| [ ] | Session timeout works (set `SESSION_TIMEOUT` to 60) | `includes/auth.php` (`boot_auth`) |
| [ ] | Student opening `admin/dashboard.php` gets 403 | `require_role()` |
| [ ] | Logged-out user opening `dashboard.php` is redirected to login | `require_login()` |
| [ ] | POST without CSRF token is rejected (400 / 403) | `require_post_csrf()`, `api/_bootstrap.php` |
| [ ] | XSS: enter `<script>alert(1)</script>` as a name in the contact form; admin Messages page shows it as text | `e()` helper |
| [ ] | Upload: a `.php` file renamed to `.png` is rejected | `handle_poster_upload()` |
| [ ] | Upload: file over 2 MB is rejected | `handle_poster_upload()` |
| [ ] | `uploads/.htaccess` blocks script execution; `storage/` and `includes/` are not browsable | `.htaccess` files |
| [ ] | Remember-me stores only a hash of the token | `remember_tokens` table |
| [ ] | Five wrong passwords lock login for 5 minutes | `login.php` |
| [ ] | Important actions appear in `audit_logs` | `audit_log()` |
| [ ] | Default demo passwords changed before the final demo | `database/studenthub.sql` |
