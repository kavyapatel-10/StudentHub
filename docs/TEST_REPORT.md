# Test report

Run each test yourself, then fill the **Actual result** and **Pass/Fail** columns and attach a screenshot.

| # | Practical | Test | Input / steps | Expected result | Actual result | Pass/Fail |
|---|---|---|---|---|---|---|
| 1 | 5 | Invalid mobile | `12345` | Error: 10 digits starting with 6-9 | | |
| 2 | 5 | Weak password | `abc123` | Error + "Weak" strength bar | | |
| 3 | 5 | Password mismatch | different confirm password | Error: passwords do not match | | |
| 4 | 5 | Terms unchecked | submit without ticking | Error: accept the terms | | |
| 5 | 6 | Search events | type `cricket` | Only the cricket event is shown | | |
| 6 | 6 | Pagination | click page 2 | Next 6 events shown, page 2 highlighted | | |
| 7 | 6 | Error state | rename `data/events.json` and stop Apache API | Error message with Retry button | | |
| 8 | 7 | Contact form valid | name, email, 10+ char message | Success message, row added to `storage/contacts.csv` | | |
| 9 | 7 | Contact form invalid | empty fields | Errors beside the fields, nothing saved | | |
| 10 | 8 | DB connection | open `test_connection.php` as admin | MySQLi and PDO both green | | |
| 11 | 9 | Valid registration | all fields valid | Redirect to login, row in `users` with hashed password | | |
| 12 | 9 | Duplicate email | register `aarav.patel@example.com` | Error: already registered | | |
| 13 | 9 | Duplicate student ID | use `STU2026001` | Error: already registered | | |
| 14 | 10 | Login success | admin / student demo account | Redirected to the matching dashboard | | |
| 15 | 10 | Login failure | wrong password | "Invalid email or password" | | |
| 16 | 10 | Logout | click Logout | Logged out message, dashboard needs login again | | |
| 17 | 10 | Unauthorized access | log in as student, open `admin/dashboard.php` | 403 Access denied | | |
| 18 | 10 | Session timeout | set `SESSION_TIMEOUT` to 60, wait | Redirect to login with expiry message | | |
| 19 | 10 | Remember me | tick box, delete session cookie, reload | Still logged in | | |
| 20 | 11 | Add student | valid data | Success message, student in the list | | |
| 21 | 11 | Search / filter | search `patel`, filter course | Matching rows only | | |
| 22 | 11 | Edit student | change city | Success message, new value shown | | |
| 23 | 11 | Delete student | click Delete, confirm | Moved to deleted list; Restore brings it back | | |
| 24 | 11 | CSV export | click Export CSV | File downloads with the filtered rows | | |
| 25 | 12 | Add event + poster | valid JPG/PNG under 2 MB | Event saved, poster shown | | |
| 26 | 12 | Wrong file type | upload a `.txt` or fake image | Error: only JPG, PNG or WEBP | | |
| 27 | 12 | File too large | image over 2 MB | Error: poster too large | | |
| 28 | 12 | Delete event | click Delete, confirm | Event and poster file removed | | |
| 29 | 13 | API list | open `api/students.php` while logged in as admin | JSON with `success`, `data`, `meta` | | |
| 30 | 13 | API without login | open it in a private window | 401 JSON message | | |
| 31 | 13 | AJAX add / edit / delete | use `admin/students_ajax.php` | Table updates without page reload | | |
| 32 | 13 | AJAX search | type quickly | One request after typing pauses (Network tab) | | |
| 33 | 14 | Dashboard stats | open admin dashboard | Counts match phpMyAdmin | | |
| 34 | 14 | Audit log | create / update / delete a student | New rows appear in the audit log | | |
| 35 | 14 | Deactivate user | admin -> Users -> Deactivate | That user can no longer log in | | |
| 36 | 15 | Navigation | click every menu item (guest, student, admin) | No broken links | | |
| 37 | 15 | Responsive | 360px, 768px, 1280px widths | Layout adapts, no sideways scrolling | | |
