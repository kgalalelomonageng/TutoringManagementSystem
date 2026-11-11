CREATE DATABASE IF NOT EXISTS tutoring_management_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tutoring_management_system;

CREATE TABLE IF NOT EXISTS administrators (
    admin_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_administrators_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    registration_date DATE NOT NULL,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tutors (
    tutor_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    expertise_subject VARCHAR(100) NOT NULL,
    availability_status ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    PRIMARY KEY (tutor_id),
    UNIQUE KEY uq_tutors_email (email),
    KEY idx_tutors_subject_status (expertise_subject, availability_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programmes (
    programme_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    programme_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    fee_amount DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (programme_id),
    UNIQUE KEY uq_programmes_name (programme_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NOT NULL,
    programme_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    booking_status ENUM('Requested', 'Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Requested',
    PRIMARY KEY (booking_id),
    KEY idx_bookings_student_date (student_id, booking_date),
    KEY idx_bookings_tutor_slot (tutor_id, booking_date, booking_time, booking_status),
    KEY idx_bookings_programme (programme_id),
    CONSTRAINT fk_bookings_student
        FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_bookings_tutor
        FOREIGN KEY (tutor_id) REFERENCES tutors (tutor_id),
    CONSTRAINT fk_bookings_programme
        FOREIGN KEY (programme_id) REFERENCES programmes (programme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    programme_id INT UNSIGNED NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    PRIMARY KEY (payment_id),
    KEY idx_payments_student_date (student_id, payment_date),
    KEY idx_payments_programme (programme_id),
    KEY idx_payments_status_date (payment_status, payment_date),
    CONSTRAINT fk_payments_student
        FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_payments_programme
        FOREIGN KEY (programme_id) REFERENCES programmes (programme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrolments (
    enrolment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id INT UNSIGNED NOT NULL,
    enrolment_date DATE NOT NULL,
    enrolment_status ENUM('Pending', 'Active') NOT NULL DEFAULT 'Pending',
    PRIMARY KEY (enrolment_id),
    UNIQUE KEY uq_enrolments_payment (payment_id),
    CONSTRAINT fk_enrolments_payment
        FOREIGN KEY (payment_id) REFERENCES payments (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tutor_availability (
    availability_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tutor_id INT UNSIGNED NOT NULL,
    available_date DATE NOT NULL,
    available_time TIME NOT NULL,
    availability_status ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    PRIMARY KEY (availability_id),
    UNIQUE KEY uq_tutor_availability (tutor_id, available_date, available_time),
    KEY idx_availability_slot (available_date, available_time, availability_status),
    CONSTRAINT fk_availability_tutor
        FOREIGN KEY (tutor_id) REFERENCES tutors (tutor_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT UNSIGNED NOT NULL,
    recipient_role VARCHAR(20) NOT NULL,
    recipient_id INT UNSIGNED NOT NULL DEFAULT 0,
    notification_message VARCHAR(255) NOT NULL,
    notification_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (notification_id),
    KEY idx_notifications_recipient (recipient_role, recipient_id, read_at, notification_date),
    KEY idx_notifications_booking (booking_id),
    CONSTRAINT fk_notifications_booking
        FOREIGN KEY (booking_id) REFERENCES bookings (booking_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
