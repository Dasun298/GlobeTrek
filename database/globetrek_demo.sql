-- GlobeTrek portfolio SAMPLE database.
-- Contains NO users, admins, staff, bookings, inquiries or payment records.
-- All prices and destinations are demonstration data, not live booking offers.
-- The project does not collect or store raw bank/card credentials.
-- Import into a NEW local database. Do not run over real data.
CREATE DATABASE IF NOT EXISTS `globetrek_portfolio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `globetrek_portfolio`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `id_number` varchar(200) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hotel_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `checkin_date` date DEFAULT NULL,
  `checkout_date` date DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `transport_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reply` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(200) DEFAULT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_name` varchar(100) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `vehicle_number` varchar(100) NOT NULL,
  `price` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `destination` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transportation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `price` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'customer',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes and uniqueness constraints for a fresh demo installation.
ALTER TABLE `users` ADD UNIQUE KEY `uq_users_username` (`username`), ADD UNIQUE KEY `uq_users_email` (`email`);
ALTER TABLE `admins` ADD UNIQUE KEY `uq_admins_username` (`username`);
ALTER TABLE `staff` ADD UNIQUE KEY `uq_staff_username` (`username`);
ALTER TABLE `bookings` ADD INDEX `idx_bookings_user` (`user_id`), ADD INDEX `idx_bookings_package` (`package_id`);
ALTER TABLE `hotel_bookings` ADD INDEX `idx_hotels_user` (`user_id`), ADD INDEX `idx_hotels_hotel` (`hotel_id`), ADD INDEX `idx_hotels_transport` (`transport_id`);
ALTER TABLE `payments` ADD INDEX `idx_payments_booking` (`booking_id`);
-- Foreign keys prevent orphan records in the new demo database.
ALTER TABLE `bookings` ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
 ADD CONSTRAINT `fk_booking_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE RESTRICT;
ALTER TABLE `hotel_bookings` ADD CONSTRAINT `fk_hotelbooking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
 ADD CONSTRAINT `fk_hotelbooking_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE RESTRICT,
 ADD CONSTRAINT `fk_hotelbooking_transport` FOREIGN KEY (`transport_id`) REFERENCES `transport` (`id`) ON DELETE RESTRICT;
ALTER TABLE `payments` ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT;
SET FOREIGN_KEY_CHECKS = 1;


-- Public-safe sample catalog data (from the author's provided project).
-- No real account credentials or transaction history are included.


INSERT INTO `packages` (`id`, `package_name`, `destination`, `price`, `details`, `image`) VALUES
(6, 'Ella Adventure Tour', 'Ella', 450.00, 'Beautiful mountains, Nine Arch Bridge, train rides, and waterfalls.', 'photo-1566296314736-6eaac1ca0cb9.avif'),
(7, 'Kandy Cultural Package', 'Kandy', 500.00, 'Temple of the Tooth, traditional dancing, and botanical gardens.', 'f0.jpg'),
(8, 'Sigiriya Rock Fortress Tour', 'Sigiriya', 600.00, 'Ancient rock fortress, village safari, and historical experience.', 'f0.jpg'),
(9, 'Nuwara Eliya Holiday Package', 'Nuwara Eliya', 550.00, 'Tea plantations, cool climate, Gregory Lake, and scenic views.', 'BH-Blog-thumb-10.jpg'),
(10, 'Mirissa Beach Escape', 'Mirissa', 700.00, 'Whale watching, beach parties, surfing, and seafood restaurants.', 'IMG_2023-5-3-222817.webp'),
(11, 'Bentota Luxury Beach Tour', 'Bentota', 750.00, 'Luxury hotels, water sports, boat safaris, and relaxing beaches.', 'birds-eye-view.jpg'),
(12, 'Yala Wildlife Safari', 'Yala', 650.00, 'Leopard safari, elephant watching, and nature adventure.', 'yala-slider1.jpg'),
(13, 'Galle Dutch Fort Tour', 'Galle', 500.00, 'Historic fort city, colonial architecture, cafes, and beaches.', 'Galle-fort-02-1.jpg'),
(14, 'Arugam Bay Surfing Package', 'Arugam Bay', 720.00, 'Surfing paradise with beach activities and nightlife.', '68d8d484e564a5.74689800.webp'),
(15, 'Trincomalee Family Vacation', 'Trincomalee', 680.00, 'Nilaveli Beach, Pigeon Island, snorkeling, and whale watching.', 'Trincomalee-travel-blog.webp'),
(16, 'Riverston', 'Matale', 680.00, 'Riverston is one of the tourist attraction in Knuckles world heritage area. It also called as Riverston Gap and there is a small mountain to climb', 'IMG_7332.JPG'),
(17, 'One Tree Hill', 'Matale', 680.00, 'It is a small hill located in Kandenuwara, Matale in the central province of the country. The hill is known for its scenic views and a single large tree', 'hqdefault.jpg'),
(18, 'One Tree Hill', 'Matale', 680.00, 'It is a small hill located in Kandenuwara, Matale in the central province of the country. The hill is known for its scenic views and a single large tree', 'hqdefault.jpg');

INSERT INTO `hotels` (`id`, `hotel_name`, `location`, `room_type`, `status`, `image`, `price`) VALUES
(2, 'Kandy Lake Hotel', 'Kandy', 'Family Suite', 'Available', 'hotel-thilanka.jpg', '2000'),
(3, 'Sigiriya Palace Resort', 'Sigiriya', 'Luxury Room', 'Available', '1697334540.jfif', '50000'),
(4, 'Nuwara Eliya Grand Hotel', 'Nuwara Eliya', 'Standard Room', 'Available', 'c198bf4b.webp', '5000'),
(5, 'Mirissa Beach Hotel', 'Mirissa', 'Sea View Room', 'Available', '1000x900.jpg', '20000'),
(6, 'Bentota Ocean Resort', 'Bentota', 'Luxury Suite', 'Available', 'nh_bentota_ceysands_resort_homepage_944x510_new.jpg', '1500'),
(7, 'Yala Safari Lodge', 'Yala', 'Safari Cabin', 'Available', 'safari-lodge-yala.jpg', '5000'),
(8, 'Galle Fort Hotel', 'Galle', 'Deluxe Room', 'Available', 'the-heritage-galle-fort.jpg', '2000'),
(9, 'Arugam Bay Surf Resort', 'Arugam Bay', 'Beach Room', 'Available', '303253_120618114304165.jpg', '5000'),
(10, 'Trincomalee Paradise Hotel', 'Trincomalee', 'Family Room', 'Available', '441108358.jpg', '50000'),
(12, 'The Grand Mountain Hotel', 'Matale', NULL, 'Available', '1778947062_unnamed.webp', '5223'),
(13, 'Riverston view gest house', 'Matale', NULL, 'Available', '1778949271_unnamed.jpg', '5223');

INSERT INTO `transport` (`id`, `vehicle_name`, `vehicle_type`, `vehicle_number`, `price`, `status`, `destination`) VALUES
(1, 'Toyota Bus', 'Bus', 'NB-4587', '15000', 'Available', NULL),
(2, 'Luxury Van', 'Van', 'WP-7788', '10000', 'Available', NULL),
(3, 'Mini Car', 'Car', 'CAB-1122', '5000', 'Available', NULL),
(4, 'Toyota Bus', '', '', '15000', 'Available', 'Colombo'),
(5, 'Mini Van', '', '', '12000', 'Available', 'Kandy'),
(6, 'Luxury Car', '', '', '20000', 'Available', 'Galle'),
(7, 'Tourist Bus', '', '', '25000', 'Available', 'Jaffna');

INSERT INTO `transportation` (`id`, `vehicle`, `destination`, `status`, `price`) VALUES
(1, 'Tour Bus', 'Kandy', 'Ready', 'Rs. 5000'),
(2, 'Airport Van', 'Colombo', 'Booked', 'Rs. 7500'),
(3, 'Luxury Car', 'Nuwara Eliya', 'Available', 'Rs. 9000'),
(4, 'Mini Bus', 'Ella', 'Reserved', 'Rs. 12000'),
(5, 'Safari Jeep', 'Yala', 'Ready', 'Rs. 15000'),
(6, 'Private Van', 'Sigiriya', 'Booked', 'Rs. 18000'),
(7, 'Coach Bus', 'Galle', 'Available', 'Rs. 8000'),
(8, 'Travel Car', 'Bentota', 'Reserved', 'Rs. 8000'),
(9, 'Tour Van', 'Jaffna', 'Ready', 'Rs. 8000'),
(13, ' jeep', 'Arugam Bay', 'Available', '5223'),
(14, 'AC Bus', 'Matale', 'Available', '680.00');
