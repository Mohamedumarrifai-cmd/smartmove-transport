-- SmartMove Transport Management System schema.
-- Run on a clean Oracle schema before 02_sequences.sql and 03_sample_data.sql.

CREATE TABLE VEHICLE (
	vehicle_id          NUMBER(10)       NOT NULL,
	registration_number VARCHAR2(20)     NOT NULL,
	make                VARCHAR2(60)     NOT NULL,
	model               VARCHAR2(60)     NOT NULL,
	vehicle_type        VARCHAR2(20)     NOT NULL,
	manufacture_year    NUMBER(4),
	capacity            NUMBER(3)        NOT NULL,
	status              VARCHAR2(20)     DEFAULT 'AVAILABLE' NOT NULL,
	created_at          TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_vehicle PRIMARY KEY (vehicle_id),
	CONSTRAINT uq_vehicle_registration UNIQUE (registration_number),
	CONSTRAINT ck_vehicle_type CHECK (vehicle_type IN ('BUS', 'MINIBUS', 'COACH', 'VAN')),
	CONSTRAINT ck_vehicle_year CHECK (manufacture_year IS NULL OR manufacture_year BETWEEN 1950 AND 2100),
	CONSTRAINT ck_vehicle_capacity CHECK (capacity BETWEEN 1 AND 300),
	CONSTRAINT ck_vehicle_status CHECK (status IN ('AVAILABLE', 'IN_SERVICE', 'MAINTENANCE', 'RETIRED'))
);

CREATE TABLE DRIVER (
	driver_id       NUMBER(10)       NOT NULL,
	full_name       VARCHAR2(120)    NOT NULL,
	license_number  VARCHAR2(40)     NOT NULL,
	phone           VARCHAR2(25)     NOT NULL,
	email           VARCHAR2(254)    NOT NULL,
	hire_date       DATE             DEFAULT SYSDATE NOT NULL,
	status          VARCHAR2(20)     DEFAULT 'ACTIVE' NOT NULL,
	created_at      TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_driver PRIMARY KEY (driver_id),
	CONSTRAINT uq_driver_license UNIQUE (license_number),
	CONSTRAINT uq_driver_email UNIQUE (email),
	CONSTRAINT ck_driver_status CHECK (status IN ('ACTIVE', 'INACTIVE', 'ON_LEAVE'))
);

CREATE TABLE ROUTE (
	route_id                    NUMBER(10)       NOT NULL,
	route_name                  VARCHAR2(120)    NOT NULL,
	origin_city                 VARCHAR2(80)     NOT NULL,
	destination_city            VARCHAR2(80)     NOT NULL,
	distance_km                 NUMBER(8,2)      NOT NULL,
	estimated_duration_minutes  NUMBER(5)        NOT NULL,
	is_active                   CHAR(1)          DEFAULT 'Y' NOT NULL,
	created_at                  TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_route PRIMARY KEY (route_id),
	CONSTRAINT ck_route_endpoints CHECK (UPPER(origin_city) <> UPPER(destination_city)),
	CONSTRAINT ck_route_distance CHECK (distance_km > 0),
	CONSTRAINT ck_route_duration CHECK (estimated_duration_minutes > 0),
	CONSTRAINT ck_route_active CHECK (is_active IN ('Y', 'N'))
);

CREATE TABLE PASSENGER (
	passenger_id    NUMBER(10)       NOT NULL,
	full_name       VARCHAR2(120)    NOT NULL,
	email           VARCHAR2(254)    NOT NULL,
	phone           VARCHAR2(25)     NOT NULL,
	password_hash   VARCHAR2(255)    NOT NULL,
	registered_at   TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	status          VARCHAR2(20)     DEFAULT 'ACTIVE' NOT NULL,
	CONSTRAINT pk_passenger PRIMARY KEY (passenger_id),
	CONSTRAINT uq_passenger_email UNIQUE (email),
	CONSTRAINT ck_passenger_status CHECK (status IN ('ACTIVE', 'INACTIVE', 'SUSPENDED'))
);

CREATE TABLE TRIP (
	trip_id         NUMBER(10)       NOT NULL,
	route_id        NUMBER(10)       NOT NULL,
	vehicle_id      NUMBER(10)       NOT NULL,
	driver_id       NUMBER(10)       NOT NULL,
	departure_time  TIMESTAMP        NOT NULL,
	arrival_time    TIMESTAMP        NOT NULL,
	fare            NUMBER(10,2)     NOT NULL,
	status          VARCHAR2(20)     DEFAULT 'SCHEDULED' NOT NULL,
	created_at      TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_trip PRIMARY KEY (trip_id),
	CONSTRAINT fk_trip_route FOREIGN KEY (route_id) REFERENCES ROUTE (route_id),
	CONSTRAINT fk_trip_vehicle FOREIGN KEY (vehicle_id) REFERENCES VEHICLE (vehicle_id),
	CONSTRAINT fk_trip_driver FOREIGN KEY (driver_id) REFERENCES DRIVER (driver_id),
	CONSTRAINT ck_trip_times CHECK (arrival_time > departure_time),
	CONSTRAINT ck_trip_fare CHECK (fare >= 0),
	CONSTRAINT ck_trip_status CHECK (status IN ('SCHEDULED', 'BOARDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'))
);

CREATE TABLE TICKET (
	ticket_id       NUMBER(10)       NOT NULL,
	passenger_id    NUMBER(10)       NOT NULL,
	trip_id         NUMBER(10)       NOT NULL,
	seat_number     NUMBER(3)        NOT NULL,
	booking_date    TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	fare            NUMBER(10,2)     NOT NULL,
	status          VARCHAR2(20)     DEFAULT 'BOOKED' NOT NULL,
	CONSTRAINT pk_ticket PRIMARY KEY (ticket_id),
	CONSTRAINT fk_ticket_passenger FOREIGN KEY (passenger_id) REFERENCES PASSENGER (passenger_id),
	CONSTRAINT fk_ticket_trip FOREIGN KEY (trip_id) REFERENCES TRIP (trip_id),
	CONSTRAINT uq_ticket_trip_seat UNIQUE (trip_id, seat_number),
	CONSTRAINT ck_ticket_seat CHECK (seat_number > 0),
	CONSTRAINT ck_ticket_fare CHECK (fare >= 0),
	CONSTRAINT ck_ticket_status CHECK (status IN ('BOOKED', 'PAID', 'CANCELLED', 'USED', 'REFUNDED'))
);

CREATE TABLE PAYMENT (
	payment_id          NUMBER(10)       NOT NULL,
	ticket_id           NUMBER(10)       NOT NULL,
	amount              NUMBER(10,2)     NOT NULL,
	payment_method      VARCHAR2(20)     NOT NULL,
	transaction_reference VARCHAR2(100),
	status              VARCHAR2(20)     DEFAULT 'PENDING' NOT NULL,
	paid_at             TIMESTAMP,
	created_at          TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_payment PRIMARY KEY (payment_id),
	CONSTRAINT fk_payment_ticket FOREIGN KEY (ticket_id) REFERENCES TICKET (ticket_id),
	CONSTRAINT uq_payment_transaction UNIQUE (transaction_reference),
	CONSTRAINT ck_payment_amount CHECK (amount > 0),
	CONSTRAINT ck_payment_method CHECK (payment_method IN ('CARD', 'CASH', 'BANK_TRANSFER', 'MOBILE_MONEY')),
	CONSTRAINT ck_payment_status CHECK (status IN ('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED'))
);

CREATE TABLE MAINTENANCE (
	maintenance_id  NUMBER(10)       NOT NULL,
	vehicle_id      NUMBER(10)       NOT NULL,
	description     VARCHAR2(500)    NOT NULL,
	scheduled_date  DATE             NOT NULL,
	completed_date  DATE,
	cost            NUMBER(10,2)     DEFAULT 0 NOT NULL,
	status          VARCHAR2(20)     DEFAULT 'SCHEDULED' NOT NULL,
	created_at      TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_maintenance PRIMARY KEY (maintenance_id),
	CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES VEHICLE (vehicle_id),
	CONSTRAINT ck_maintenance_dates CHECK (completed_date IS NULL OR completed_date >= scheduled_date),
	CONSTRAINT ck_maintenance_cost CHECK (cost >= 0),
	CONSTRAINT ck_maintenance_status CHECK (status IN ('SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'))
);

CREATE TABLE FEEDBACK (
	feedback_id     NUMBER(10)       NOT NULL,
	passenger_id    NUMBER(10)       NOT NULL,
	trip_id         NUMBER(10)       NOT NULL,
	rating          NUMBER(1)        NOT NULL,
	comments        VARCHAR2(1000),
	submitted_at    TIMESTAMP        DEFAULT SYSTIMESTAMP NOT NULL,
	CONSTRAINT pk_feedback PRIMARY KEY (feedback_id),
	CONSTRAINT fk_feedback_passenger FOREIGN KEY (passenger_id) REFERENCES PASSENGER (passenger_id),
	CONSTRAINT fk_feedback_trip FOREIGN KEY (trip_id) REFERENCES TRIP (trip_id),
	CONSTRAINT uq_feedback_passenger_trip UNIQUE (passenger_id, trip_id),
	CONSTRAINT ck_feedback_rating CHECK (rating BETWEEN 1 AND 5)
);


SELECT table_name FROM user_tables ORDER BY table_name;
