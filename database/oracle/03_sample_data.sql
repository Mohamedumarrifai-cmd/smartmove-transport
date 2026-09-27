-- SmartMove sample data. Run after 01_tables.sql and 02_sequences.sql.
-- Demo passenger password for these hashes is "password"; never use it in production.

INSERT INTO VEHICLE (vehicle_id, registration_number, make, model, vehicle_type, manufacture_year, capacity, status)
VALUES (1, 'SM-2048', 'Toyota', 'Coaster', 'MINIBUS', 2022, 28, 'AVAILABLE');
INSERT INTO VEHICLE (vehicle_id, registration_number, make, model, vehicle_type, manufacture_year, capacity, status)
VALUES (2, 'SM-3107', 'Scania', 'Touring', 'COACH', 2021, 52, 'AVAILABLE');
INSERT INTO VEHICLE (vehicle_id, registration_number, make, model, vehicle_type, manufacture_year, capacity, status)
VALUES (3, 'SM-1186', 'Isuzu', 'NQR', 'BUS', 2020, 35, 'MAINTENANCE');

INSERT INTO DRIVER (driver_id, full_name, license_number, phone, email, hire_date, status)
VALUES (1, 'Daniel Mensah', 'DL-482901', '+233 24 555 0182', 'daniel.mensah@example.com', DATE '2021-03-15', 'ACTIVE');
INSERT INTO DRIVER (driver_id, full_name, license_number, phone, email, hire_date, status)
VALUES (2, 'Ama Boateng', 'DL-517304', '+233 20 555 0147', 'ama.boateng@example.com', DATE '2022-06-01', 'ACTIVE');
INSERT INTO DRIVER (driver_id, full_name, license_number, phone, email, hire_date, status)
VALUES (3, 'Kofi Asare', 'DL-663218', '+233 27 555 0129', 'kofi.asare@example.com', DATE '2019-11-20', 'ACTIVE');

INSERT INTO ROUTE (route_id, route_name, origin_city, destination_city, distance_km, estimated_duration_minutes)
VALUES (1, 'Accra - Kumasi Express', 'Accra', 'Kumasi', 250.00, 240);
INSERT INTO ROUTE (route_id, route_name, origin_city, destination_city, distance_km, estimated_duration_minutes)
VALUES (2, 'Accra - Cape Coast', 'Accra', 'Cape Coast', 165.50, 180);
INSERT INTO ROUTE (route_id, route_name, origin_city, destination_city, distance_km, estimated_duration_minutes)
VALUES (3, 'Kumasi - Tamale', 'Kumasi', 'Tamale', 385.00, 360);

INSERT INTO PASSENGER (passenger_id, full_name, email, phone, password_hash)
VALUES (1, 'Nana Owusu', 'nana.owusu@example.com', '+233 24 555 0101', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
INSERT INTO PASSENGER (passenger_id, full_name, email, phone, password_hash)
VALUES (2, 'Esi Addo', 'esi.addo@example.com', '+233 20 555 0102', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
INSERT INTO PASSENGER (passenger_id, full_name, email, phone, password_hash)
VALUES (3, 'Kojo Appiah', 'kojo.appiah@example.com', '+233 27 555 0103', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO TRIP (trip_id, route_id, vehicle_id, driver_id, departure_time, arrival_time, fare, status)
VALUES (1, 1, 2, 1, TIMESTAMP '2026-08-12 07:00:00', TIMESTAMP '2026-08-12 11:00:00', 120.00, 'COMPLETED');
INSERT INTO TRIP (trip_id, route_id, vehicle_id, driver_id, departure_time, arrival_time, fare, status)
VALUES (2, 2, 1, 2, TIMESTAMP '2026-08-18 08:30:00', TIMESTAMP '2026-08-18 11:30:00', 75.00, 'COMPLETED');
INSERT INTO TRIP (trip_id, route_id, vehicle_id, driver_id, departure_time, arrival_time, fare, status)
VALUES (3, 3, 2, 3, TIMESTAMP '2026-10-05 06:00:00', TIMESTAMP '2026-10-05 12:00:00', 180.00, 'SCHEDULED');

INSERT INTO TICKET (ticket_id, passenger_id, trip_id, seat_number, booking_date, fare, status)
VALUES (1, 1, 1, 12, TIMESTAMP '2026-08-01 10:15:00', 120.00, 'USED');
INSERT INTO TICKET (ticket_id, passenger_id, trip_id, seat_number, booking_date, fare, status)
VALUES (2, 2, 2, 4, TIMESTAMP '2026-08-10 14:20:00', 75.00, 'USED');
INSERT INTO TICKET (ticket_id, passenger_id, trip_id, seat_number, booking_date, fare, status)
VALUES (3, 3, 3, 21, TIMESTAMP '2026-09-20 09:45:00', 180.00, 'PAID');

INSERT INTO PAYMENT (payment_id, ticket_id, amount, payment_method, transaction_reference, status, paid_at)
VALUES (1, 1, 120.00, 'MOBILE_MONEY', 'MM-20260801-0001', 'COMPLETED', TIMESTAMP '2026-08-01 10:16:00');
INSERT INTO PAYMENT (payment_id, ticket_id, amount, payment_method, transaction_reference, status, paid_at)
VALUES (2, 2, 75.00, 'CARD', 'CD-20260810-0002', 'COMPLETED', TIMESTAMP '2026-08-10 14:21:00');
INSERT INTO PAYMENT (payment_id, ticket_id, amount, payment_method, transaction_reference, status, paid_at)
VALUES (3, 3, 180.00, 'BANK_TRANSFER', 'BT-20260920-0003', 'COMPLETED', TIMESTAMP '2026-09-20 09:47:00');

INSERT INTO MAINTENANCE (maintenance_id, vehicle_id, description, scheduled_date, completed_date, cost, status)
VALUES (1, 3, 'Routine engine and brake inspection', DATE '2026-09-10', DATE '2026-09-11', 1850.00, 'COMPLETED');
INSERT INTO MAINTENANCE (maintenance_id, vehicle_id, description, scheduled_date, cost, status)
VALUES (2, 1, 'Preventive tire rotation and safety check', DATE '2026-10-12', 420.00, 'SCHEDULED');

INSERT INTO FEEDBACK (feedback_id, passenger_id, trip_id, rating, comments, submitted_at)
VALUES (1, 1, 1, 5, 'Comfortable coach and an on-time arrival.', TIMESTAMP '2026-08-12 12:05:00');
INSERT INTO FEEDBACK (feedback_id, passenger_id, trip_id, rating, comments, submitted_at)
VALUES (2, 2, 2, 4, 'Friendly driver and a smooth journey.', TIMESTAMP '2026-08-18 12:10:00');

COMMIT;