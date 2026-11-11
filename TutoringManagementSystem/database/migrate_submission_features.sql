USE tutoring_management_system;

ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL AFTER email;

ALTER TABLE enrolments
    DROP FOREIGN KEY fk_enrolments_student,
    DROP FOREIGN KEY fk_enrolments_programme,
    DROP INDEX idx_enrolments_student_status,
    DROP INDEX idx_enrolments_programme,
    DROP COLUMN student_id,
    DROP COLUMN programme_id;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT NOT NULL AUTO_INCREMENT,
    booking_id INT NOT NULL,
    recipient_role VARCHAR(20) NOT NULL,
    recipient_id INT NOT NULL DEFAULT 0,
    notification_message VARCHAR(255) NOT NULL,
    notification_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (notification_id),
    KEY idx_notifications_recipient (recipient_role, recipient_id, read_at, notification_date),
    KEY idx_notifications_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
