# ER diagram

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "has profile"
    USERS ||--o{ REMEMBER_TOKENS : owns
    USERS ||--o{ AUDIT_LOGS : performs
    USERS ||--o{ EVENTS : creates
    COURSES ||--o{ STUDENTS : "enrolled in"
    STUDENTS ||--o{ REGISTRATIONS : makes
    EVENTS ||--o{ REGISTRATIONS : receives

    USERS { int id PK  string email UK  string password_hash  enum role  enum status  datetime last_login }
    COURSES { int id PK  string name UK }
    STUDENTS { int id PK  int user_id FK  string student_code UK  string full_name  string email UK  string mobile  int course_id FK  int year  enum gender  string city  enum status }
    EVENTS { int id PK  string title  text description  string category  string venue  date event_date  string poster  enum status  int created_by FK }
    REGISTRATIONS { int id PK  int student_id FK  int event_id FK  datetime registered_at }
    REMEMBER_TOKENS { int id PK  int user_id FK  string selector UK  string token_hash  datetime expires_at }
    AUDIT_LOGS { bigint id PK  int user_id FK  string action  string entity  int entity_id  string details  string ip_address }
```

Normalisation notes: course names live only in `courses` (3NF); the many-to-many link between students and events is the
`registrations` table with a UNIQUE (student_id, event_id) pair; login data (`users`) is separate from profile data (`students`).

## REST-style route plan
| Method | Route | Purpose |
|---|---|---|
| GET | `api/students.php` | list (search, filter, pagination) |
| GET | `api/students.php?id=5` | one student |
| POST | `api/students.php` | create |
| PUT | `api/students.php?id=5` | update |
| DELETE | `api/students.php?id=5` | soft delete |
| GET | `api/events.php` | list events (public) |
