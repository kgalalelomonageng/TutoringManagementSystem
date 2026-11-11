# Monageng Tutoring submission package

## Local setup

1. Install/start XAMPP Apache and MySQL.
2. In phpMyAdmin, import `database/schema.sql` for a fresh database.
3. Import `database/sample_data.sql` to add the fictional sample accounts and
   records.
4. Copy the project folder to `C:\xampp\htdocs\TutoringManagementSystem` and open `http://localhost/TutoringManagementSystem/`.
5. Sign in with one of the sample accounts below. Their shared password is
   `Monageng@1`.

For the existing local database, back it up first, then run
`database/migrate_submission_features.sql` once and import
`database/sample_data.sql`.
The migration adds tutor password storage and notifications, and normalizes
enrolments to reference the payment record rather than duplicating student and
programme identifiers. It preserves payment IDs, enrolment dates, and statuses.

The local XAMPP connection defaults in `php/db_connect.php` use `root` with an
empty password. Set a dedicated database user and password there before any
non-local deployment.

## Assignment sample accounts

These fictional names and contact details are the sample data supplied for the
assignment. All five sample accounts use the password `Monageng@1`.

| Role | Email |
|---|---|
| Administrator | `angelnteko@gmail.com` |
| Student with active Mathematics enrolment and a requested session | `koketsobelsia@gmail.com` |
| Student with a pending Data Science payment | `lazarus.monageng@gmail.com` |
| Mathematics tutor | `gloriamonageng9@gmail.com` |
| Data Science tutor | `maikanomonageng@gmail.com` |

Every stored password in `database/sample_data.sql` is a PHP `password_hash()`
hash. Re-importing the sample data resets passwords only for those five exact
assignment sample accounts; other accounts are left unchanged.

## Features

- Student registration and student, tutor, and administrator login.
- Role-specific student, tutor, and administrator dashboards.
- Programme payment submission recorded as pending; administrator verification
  activates the related enrolment.
- Session requests restricted to active enrolments and weekdays. Tutors are
  assigned by programme subject and available schedule.
- Tutors manage their own availability and view/confirm/complete or decline
  assigned sessions.
- Students can view or cancel their own sessions. Administrators manage
  students, tutors, programmes, bookings, payments, and availability.
- In-app notifications are recorded for students, assigned tutors, and
  administrators when a session is requested or changed. Each notification
  identifies its programme/session name, date, and time. Email delivery and
  live card charging are not configured; payment is simulated for this
  educational application.
- Prepared SQL statements, password hashing, role checks, CSRF tokens on
  booking/payment/admin/availability changes, server-side validation, and HTML
  escaping.

## Included assignment files

- `database/schema.sql` — fresh-install normalized schema.
- `database/migrate_submission_features.sql` — additive/normalization migration
  for the supplied local application schema.
- `database/sample_data.sql` — assignment sample accounts and records with
  hashed passwords.
- `docs/technical_documentation.pdf` — requirements, architecture, database,
  business rules, security, and test evidence.
- `docs/system_diagrams.pdf` — DFD context and level 1, three use cases, session
  sequence, and ERD.
- `docs/screenshots/` — registration, student payment/booking/dashboard and
  notifications, tutor availability/dashboard, and administrator dashboard
  and booking-management evidence using the assignment sample records.

The legacy `database/tutoring_management_system.sql` is deliberately excluded
from the submission ZIP because it contains plaintext passwords and an
outdated data export. Use `database/schema.sql` and the hashed
`database/sample_data.sql` for a clean install.

Keep `docs/student_details.pdf` separate from the ZIP for upload as the
assignment's student-details document.

## Assignment workflow checklist

1. Sign in as `lazarus.monageng@gmail.com` and inspect the pending Data Science
   payment and enrolment.
2. Sign in as `angelnteko@gmail.com`, verify the pending payment in the
   payment report, and inspect the now-active enrolment.
3. Sign in as `koketsobelsia@gmail.com`; request a weekday slot in the active
   Mathematics programme and view the new session.
4. Sign in as `gloriamonageng9@gmail.com`; confirm the request and later mark the
   session complete. Try cancelling a separate request to demonstrate the
   cancellation state.
5. Inspect in-app notifications for each participant and the administrator.

Sample schedule dates are generated relative to the import date (the next
Monday), so re-import `database/sample_data.sql` when preparing a later
assessment run.


