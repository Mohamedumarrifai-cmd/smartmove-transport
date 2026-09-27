-- SmartMove stored procedures. Run after 01_tables.sql and 02_sequences.sql.
-- These procedures do not COMMIT; the caller owns transaction boundaries.

CREATE OR REPLACE PROCEDURE sp_add_booking (
    p_passenger_id IN  PASSENGER.passenger_id%TYPE,
    p_trip_id      IN  TRIP.trip_id%TYPE,
    p_seat_number  IN  TICKET.seat_number%TYPE,
    p_ticket_id    OUT TICKET.ticket_id%TYPE
)
AS
    v_passenger_status PASSENGER.status%TYPE;
    v_trip_status      TRIP.status%TYPE;
    v_trip_fare        TRIP.fare%TYPE;
    v_capacity         VEHICLE.capacity%TYPE;
BEGIN
    SELECT status
      INTO v_passenger_status
      FROM PASSENGER
     WHERE passenger_id = p_passenger_id;

    IF v_passenger_status <> 'ACTIVE' THEN
        RAISE_APPLICATION_ERROR(-20001, 'Passenger is not active.');
    END IF;

    SELECT t.status, t.fare, v.capacity
      INTO v_trip_status, v_trip_fare, v_capacity
      FROM TRIP t
      JOIN VEHICLE v ON v.vehicle_id = t.vehicle_id
     WHERE t.trip_id = p_trip_id;

    IF v_trip_status NOT IN ('SCHEDULED', 'BOARDING') THEN
        RAISE_APPLICATION_ERROR(-20002, 'Trip is not open for booking.');
    END IF;

    IF p_seat_number IS NULL OR p_seat_number < 1 OR p_seat_number > v_capacity THEN
        RAISE_APPLICATION_ERROR(-20003, 'Seat number is outside the vehicle capacity.');
    END IF;

    INSERT INTO TICKET (passenger_id, trip_id, seat_number, fare, status)
    VALUES (p_passenger_id, p_trip_id, p_seat_number, v_trip_fare, 'BOOKED')
    RETURNING ticket_id INTO p_ticket_id;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20004, 'Passenger or trip was not found.');
END sp_add_booking;
/

CREATE OR REPLACE PROCEDURE sp_assign_driver_to_trip (
    p_trip_id   IN TRIP.trip_id%TYPE,
    p_driver_id IN DRIVER.driver_id%TYPE
)
AS
    v_trip_status     TRIP.status%TYPE;
    v_departure_time  TRIP.departure_time%TYPE;
    v_arrival_time    TRIP.arrival_time%TYPE;
    v_driver_status   DRIVER.status%TYPE;
    v_conflict_count  PLS_INTEGER;
BEGIN
    SELECT status
      INTO v_driver_status
      FROM DRIVER
     WHERE driver_id = p_driver_id;

    IF v_driver_status <> 'ACTIVE' THEN
        RAISE_APPLICATION_ERROR(-20011, 'Driver is not active.');
    END IF;

    SELECT status, departure_time, arrival_time
      INTO v_trip_status, v_departure_time, v_arrival_time
      FROM TRIP
     WHERE trip_id = p_trip_id
       FOR UPDATE;

    IF v_trip_status <> 'SCHEDULED' THEN
        RAISE_APPLICATION_ERROR(-20012, 'Only scheduled trips can be reassigned.');
    END IF;

    SELECT COUNT(*)
      INTO v_conflict_count
      FROM TRIP
     WHERE driver_id = p_driver_id
       AND trip_id <> p_trip_id
       AND status IN ('SCHEDULED', 'BOARDING', 'IN_PROGRESS')
       AND departure_time < v_arrival_time
       AND arrival_time > v_departure_time;

    IF v_conflict_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20013, 'Driver already has an overlapping trip.');
    END IF;

    UPDATE TRIP
       SET driver_id = p_driver_id
     WHERE trip_id = p_trip_id;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20014, 'Driver or trip was not found.');
END sp_assign_driver_to_trip;
/

CREATE OR REPLACE PROCEDURE sp_process_payment (
    p_ticket_id             IN  TICKET.ticket_id%TYPE,
    p_amount                IN  PAYMENT.amount%TYPE,
    p_payment_method        IN  PAYMENT.payment_method%TYPE,
    p_transaction_reference IN  PAYMENT.transaction_reference%TYPE,
    p_payment_id            OUT PAYMENT.payment_id%TYPE
)
AS
    v_ticket_fare   TICKET.fare%TYPE;
    v_ticket_status TICKET.status%TYPE;
BEGIN
    SELECT fare, status
      INTO v_ticket_fare, v_ticket_status
      FROM TICKET
     WHERE ticket_id = p_ticket_id
       FOR UPDATE;

    IF v_ticket_status NOT IN ('BOOKED', 'PAID') THEN
        RAISE_APPLICATION_ERROR(-20021, 'Ticket is not eligible for payment.');
    END IF;

    IF p_amount IS NULL OR p_amount <> v_ticket_fare THEN
        RAISE_APPLICATION_ERROR(-20022, 'Payment amount must match the ticket fare.');
    END IF;

    IF v_ticket_status = 'PAID' THEN
        RAISE_APPLICATION_ERROR(-20023, 'Ticket has already been paid.');
    END IF;

    INSERT INTO PAYMENT (
        ticket_id, amount, payment_method, transaction_reference, status, paid_at
    )
    VALUES (
        p_ticket_id, p_amount, UPPER(p_payment_method), p_transaction_reference,
        'COMPLETED', SYSTIMESTAMP
    )
    RETURNING payment_id INTO p_payment_id;

    UPDATE TICKET
       SET status = 'PAID'
     WHERE ticket_id = p_ticket_id;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20024, 'Ticket was not found.');
END sp_process_payment;
/