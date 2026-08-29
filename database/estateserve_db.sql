-- ============================================================
--  EstateServe — database schema + seed data
--  Reconstructed from the application queries.
--  MariaDB / MySQL.  Run:  mysql -uroot < database/estateserve_db.sql
--  All seed accounts use the password:  password123
-- ============================================================

CREATE DATABASE IF NOT EXISTS estateserve_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estateserve_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
--  users
-- ------------------------------------------------------------
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    phone      VARCHAR(30)  NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','resident','provider','delivery') NOT NULL,
    status     ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  services
-- ------------------------------------------------------------
CREATE TABLE services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    ENUM('laundry','car_washing','grocery','house_cleaning',
                     'plumbing','food_delivery','salon') NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    provider_id INT NULL,
    status      ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_provider FOREIGN KEY (provider_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  bookings
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    resident_id  INT NOT NULL,
    service_id   INT NOT NULL,
    provider_id  INT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    address      VARCHAR(255) NOT NULL,
    notes        TEXT,
    status       ENUM('pending','confirmed','in_progress','completed','cancelled')
                 NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_resident FOREIGN KEY (resident_id)
        REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_bookings_service  FOREIGN KEY (service_id)
        REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id)
        REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  payments
-- ------------------------------------------------------------
CREATE TABLE payments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    booking_id       INT NOT NULL,
    resident_id      INT NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    phone            VARCHAR(30) NOT NULL,
    transaction_code VARCHAR(50) NOT NULL,
    status           ENUM('success','pending','failed') NOT NULL DEFAULT 'success',
    paid_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_booking  FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_resident FOREIGN KEY (resident_id)
        REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  reviews
-- ------------------------------------------------------------
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT NOT NULL,
    resident_id INT NOT NULL,
    provider_id INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_booking  FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_resident FOREIGN KEY (resident_id)
        REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_reviews_provider FOREIGN KEY (provider_id)
        REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  notifications
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    VARCHAR(255) NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  SEED DATA   (password for every account:  password123)
-- ============================================================
-- bcrypt hash of "password123"
SET @pw = '$2y$10$OiKh//D6UsMIjr/4IqLTZOBzITRe1BCtIcDqjD2n9qJpmS62uowAi';

INSERT INTO users (id, full_name, email, phone, password, role, status) VALUES
 (1, 'Admin User',      'admin@estateserve.com', '0700000000', @pw, 'admin',    'active'),
 (2, 'Caroline Njeri',  'caroline@example.com',  '0711111111', @pw, 'resident', 'active'),
 (3, 'David Omondi',    'david@example.com',     '0722222222', @pw, 'resident', 'active'),
 (4, 'Peter Kamau',     'peter@example.com',     '0733333333', @pw, 'provider', 'active'),
 (5, 'Grace Wanjiku',   'grace@example.com',     '0744444444', @pw, 'provider', 'active'),
 (6, 'Samuel Otieno',   'samuel@example.com',    '0755555555', @pw, 'provider', 'pending'),
 (7, 'Brian Mutua',     'brian@example.com',     '0766666666', @pw, 'delivery', 'active');

INSERT INTO services (id, name, category, description, price, provider_id, status) VALUES
 (1, 'Sparkle Laundry',        'laundry',        'Wash, dry and fold, collected and delivered to your door.', 500.00, 4, 'available'),
 (2, 'Premium Car Wash',       'car_washing',    'Full exterior and interior clean at your parking spot.',    800.00, 4, 'available'),
 (3, 'FreshMart Grocery Run',  'grocery',        'We shop your list and deliver fresh to your unit.',         300.00, 5, 'available'),
 (4, 'Deep Home Cleaning',     'house_cleaning', 'Professional full clean for any size home.',               1500.00, 5, 'available'),
 (5, 'QuickFix Plumbing',      'plumbing',       'Leaks, fittings, electrical and general repairs.',         1200.00, 4, 'available'),
 (6, 'Hot Meals Delivery',     'food_delivery',  'Hot meals from restaurants delivered fast.',                250.00, 5, 'available'),
 (7, 'Mobile Salon & Barber',  'salon',          'Professional haircuts and styling at your convenience.',    700.00, 5, 'available'),
 (8, 'Ironing Service',        'laundry',        'Crisp ironing, picked up and returned same day.',           400.00, NULL, 'unavailable');

INSERT INTO bookings (id, resident_id, service_id, provider_id, booking_date, booking_time, address, notes, status) VALUES
 (1, 2, 1, 4, '2026-08-20', '10:00:00', 'Block C, Apt 12, Garden Estate', 'Gate code 4321',            'completed'),
 (2, 2, 2, 4, '2026-09-02', '09:30:00', 'Block C, Apt 12, Garden Estate', 'Silver Toyota in bay 7',    'confirmed'),
 (3, 3, 4, 5, '2026-09-05', '14:00:00', 'Block A, Apt 3, Garden Estate',  '',                          'pending'),
 (4, 3, 3, 5, '2026-08-28', '11:00:00', 'Block A, Apt 3, Garden Estate',  'No dairy',                  'in_progress'),
 (5, 2, 7, 5, '2026-08-22', '16:00:00', 'Block C, Apt 12, Garden Estate', 'Fade + beard trim',         'completed'),
 -- orders handled by the delivery user (Brian Mutua, id 7)
 (6, 3, 6, 7, '2026-08-29', '19:00:00', 'Block A, Apt 3, Garden Estate',  'Leave at door',             'confirmed'),
 (7, 2, 3, 7, '2026-08-30', '10:30:00', 'Block C, Apt 12, Garden Estate', 'Call on arrival',           'in_progress');

INSERT INTO payments (id, booking_id, resident_id, amount, phone, transaction_code, status, paid_at) VALUES
 (1, 1, 2,  550.00, '0711111111', 'ES4A2F9B1C', 'success', '2026-08-20 09:12:00'),
 (2, 2, 2,  850.00, '0711111111', 'ES7C1D4E8A', 'success', '2026-08-29 18:40:00'),
 (3, 5, 2,  750.00, '0711111111', 'ES9B3F2A6D', 'success', '2026-08-22 15:20:00'),
 -- payments on the delivery user's orders (revenue for Brian Mutua, id 7)
 (4, 6, 3,  300.00, '0722222222', 'ES2E8A1F5C', 'success', '2026-08-29 18:05:00'),
 (5, 7, 2,  350.00, '0711111111', 'ES6D4B9C3A', 'success', '2026-08-30 09:15:00');

INSERT INTO reviews (id, booking_id, resident_id, provider_id, rating, comment) VALUES
 (1, 1, 2, 4, 5, 'Clothes came back spotless and neatly folded. Highly recommend.'),
 (2, 5, 2, 5, 4, 'Great cut, arrived right on time. Will book again.');

INSERT INTO notifications (user_id, message, is_read) VALUES
 (4, 'New booking confirmed for Premium Car Wash on 02 Sep 2026', 0),
 (5, 'New booking request for Deep Home Cleaning on 05 Sep 2026', 0),
 (2, 'Your booking for Sparkle Laundry is now completed', 1);

-- keep AUTO_INCREMENT counters past the seeded ids
ALTER TABLE users         AUTO_INCREMENT = 8;
ALTER TABLE services      AUTO_INCREMENT = 9;
ALTER TABLE bookings      AUTO_INCREMENT = 8;
ALTER TABLE payments      AUTO_INCREMENT = 6;
ALTER TABLE reviews       AUTO_INCREMENT = 3;
