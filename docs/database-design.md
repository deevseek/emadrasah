# Database Design

```mermaid
erDiagram
  users ||--o{ role_user : has
  roles ||--o{ role_user : assigned
  roles ||--o{ permission_role : grants
  permissions ||--o{ permission_role : included
  users ||--o{ login_histories : records
  users ||--o{ activity_logs : performs
  academic_years ||--o{ semesters : contains
  school_profiles ||--o{ activity_logs : audited
  school_settings ||--o{ activity_logs : audited
```

Tabel fondasi: users, roles, permissions, permission_role, role_user, school_profiles, academic_years, semesters, school_settings, login_histories, activity_logs, cache, sessions, jobs.
