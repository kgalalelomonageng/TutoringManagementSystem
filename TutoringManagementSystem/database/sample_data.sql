USE tutoring_management_system;

-- Fictional assignment sample accounts. Password for every account: Monageng@1
-- Passwords are PHP password_hash() bcrypt hashes; replace before production use.
INSERT IGNORE INTO administrators (first_name, last_name, email, password)
VALUES ('Angel', 'Makhoana', 'angelnteko@gmail.com', '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi');

INSERT IGNORE INTO students
    (first_name, last_name, email, phone, password, registration_date)
VALUES
    ('Koketso', 'Monageng', 'koketsobelsia@gmail.com', '0792956511', '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi', CURDATE()),
    ('Lazarus', 'Monageng', 'lazarus.monageng@gmail.com', '0812815811', '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi', CURDATE());

INSERT IGNORE INTO tutors
    (first_name, last_name, email, password, phone, expertise_subject, availability_status)
VALUES
    ('Gloria', 'Monageng', 'gloriamonageng9@gmail.com', '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi', '0684600289', 'Mathematics', 'Available'),
    ('Maikano', 'Monageng', 'maikanomonageng@gmail.com', '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi', '0793678450', 'Data Science', 'Available');

UPDATE administrators
SET password = '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi'
WHERE email = 'angelnteko@gmail.com';

UPDATE students
SET password = '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi'
WHERE email IN ('koketsobelsia@gmail.com', 'lazarus.monageng@gmail.com');

UPDATE tutors
SET password = '$2y$10$FHOzWqvwjEu2nPyKjRIzUutjQVdFArCBYeCwumOwQMZz19WtCYiPi'
WHERE email IN ('gloriamonageng9@gmail.com', 'maikanomonageng@gmail.com');

INSERT IGNORE INTO programmes (programme_name, description, fee_amount)
VALUES
    ('Mathematics', 'Mathematics tutoring programme.', 1500.00),
    ('Data Science', 'Data Science tutoring programme.', 2000.00);

INSERT INTO payments (student_id, programme_id, amount_paid, payment_date, payment_status)
SELECT s.student_id, p.programme_id, p.fee_amount, CURDATE(), 'Paid'
FROM students s
JOIN programmes p ON p.programme_name = 'Mathematics'
WHERE s.email = 'koketsobelsia@gmail.com'
  AND NOT EXISTS (
      SELECT 1 FROM payments existing
      WHERE existing.student_id = s.student_id
        AND existing.programme_id = p.programme_id
  );

INSERT INTO enrolments (payment_id, enrolment_date, enrolment_status)
SELECT payment.payment_id, CURDATE(), 'Active'
FROM payments payment
JOIN students s ON s.student_id = payment.student_id
WHERE s.email = 'koketsobelsia@gmail.com'
  AND payment.payment_status = 'Paid'
  AND NOT EXISTS (
      SELECT 1 FROM enrolments existing
      WHERE existing.payment_id = payment.payment_id
  );

INSERT INTO payments (student_id, programme_id, amount_paid, payment_date, payment_status)
SELECT s.student_id, p.programme_id, p.fee_amount, CURDATE(), 'Pending'
FROM students s
JOIN programmes p ON p.programme_name = 'Data Science'
WHERE s.email = 'lazarus.monageng@gmail.com'
  AND NOT EXISTS (
      SELECT 1 FROM payments existing
      WHERE existing.student_id = s.student_id
        AND existing.programme_id = p.programme_id
  );

INSERT INTO enrolments (payment_id, enrolment_date, enrolment_status)
SELECT payment.payment_id, CURDATE(), 'Pending'
FROM payments payment
JOIN students s ON s.student_id = payment.student_id
WHERE s.email = 'lazarus.monageng@gmail.com'
  AND payment.payment_status = 'Pending'
  AND NOT EXISTS (
      SELECT 1 FROM enrolments existing
      WHERE existing.payment_id = payment.payment_id
  );

UPDATE payments payment
JOIN students s ON s.student_id = payment.student_id
JOIN programmes p ON p.programme_id = payment.programme_id
SET payment.payment_status = 'Paid'
WHERE s.email = 'koketsobelsia@gmail.com'
  AND p.programme_name = 'Mathematics';

UPDATE enrolments enrolment
JOIN payments payment ON payment.payment_id = enrolment.payment_id
JOIN students s ON s.student_id = payment.student_id
JOIN programmes p ON p.programme_id = payment.programme_id
SET enrolment.enrolment_status = 'Active'
WHERE s.email = 'koketsobelsia@gmail.com'
  AND p.programme_name = 'Mathematics';

UPDATE payments payment
JOIN students s ON s.student_id = payment.student_id
JOIN programmes p ON p.programme_id = payment.programme_id
SET payment.payment_status = 'Pending'
WHERE s.email = 'lazarus.monageng@gmail.com'
  AND p.programme_name = 'Data Science';

UPDATE enrolments enrolment
JOIN payments payment ON payment.payment_id = enrolment.payment_id
JOIN students s ON s.student_id = payment.student_id
JOIN programmes p ON p.programme_id = payment.programme_id
SET enrolment.enrolment_status = 'Pending'
WHERE s.email = 'lazarus.monageng@gmail.com'
  AND p.programme_name = 'Data Science';

INSERT IGNORE INTO tutor_availability (tutor_id, available_date, available_time, availability_status)
SELECT tutor_id, DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), '14:00:00', 'Available'
FROM tutors
WHERE email = 'gloriamonageng9@gmail.com';

INSERT IGNORE INTO tutor_availability (tutor_id, available_date, available_time, availability_status)
SELECT tutor_id, DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), '15:00:00', 'Available'
FROM tutors
WHERE email = 'gloriamonageng9@gmail.com';

INSERT IGNORE INTO tutor_availability (tutor_id, available_date, available_time, availability_status)
SELECT tutor_id, DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), '15:00:00', 'Available'
FROM tutors
WHERE email = 'maikanomonageng@gmail.com';

INSERT INTO bookings
    (student_id, tutor_id, programme_id, booking_date, booking_time, booking_status)
SELECT s.student_id, t.tutor_id, p.programme_id,
       DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY),
       '14:00:00', 'Requested'
FROM students s
JOIN tutors t ON t.email = 'gloriamonageng9@gmail.com'
JOIN programmes p ON p.programme_name = 'Mathematics'
JOIN enrolments e
  ON e.enrolment_status = 'Active'
JOIN payments paid
  ON paid.payment_id = e.payment_id
 AND paid.student_id = s.student_id
 AND paid.programme_id = p.programme_id
WHERE s.email = 'koketsobelsia@gmail.com'
  AND NOT EXISTS (
      SELECT 1 FROM bookings existing
      WHERE existing.student_id = s.student_id
        AND existing.tutor_id = t.tutor_id
        AND existing.booking_date = DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY)
        AND existing.booking_time = '14:00:00'
  );

INSERT INTO notifications (booking_id, recipient_role, recipient_id, notification_message)
SELECT b.booking_id, recipients.recipient_role,
       CASE recipients.recipient_role
           WHEN 'student' THEN b.student_id
           WHEN 'tutor' THEN b.tutor_id
           ELSE 0
       END,
       'Session is awaiting tutor confirmation.'
FROM bookings b
JOIN students s ON s.student_id = b.student_id
JOIN tutors t ON t.tutor_id = b.tutor_id
CROSS JOIN (
    SELECT 'student' AS recipient_role
    UNION ALL SELECT 'tutor'
    UNION ALL SELECT 'admin'
) recipients
WHERE s.email = 'koketsobelsia@gmail.com'
  AND t.email = 'gloriamonageng9@gmail.com'
  AND b.booking_date = DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY)
  AND b.booking_time = '14:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM notifications existing
      WHERE existing.booking_id = b.booking_id
        AND existing.recipient_role = recipients.recipient_role
  );
