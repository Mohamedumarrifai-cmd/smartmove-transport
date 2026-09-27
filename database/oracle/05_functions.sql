-- SmartMove scalar functions. Run after 01_tables.sql.

CREATE OR REPLACE FUNCTION fn_route_total_revenue (
    p_route_id IN ROUTE.route_id%TYPE
)
RETURN NUMBER
AS
    v_revenue NUMBER(14,2);
BEGIN
    SELECT NVL(SUM(p.amount), 0)
      INTO v_revenue
      FROM PAYMENT p
      JOIN TICKET tk ON tk.ticket_id = p.ticket_id
      JOIN TRIP tr ON tr.trip_id = tk.trip_id
     WHERE tr.route_id = p_route_id
       AND p.status = 'COMPLETED';

    RETURN v_revenue;
END fn_route_total_revenue;
/

CREATE OR REPLACE FUNCTION fn_passenger_booking_count (
    p_passenger_id IN PASSENGER.passenger_id%TYPE
)
RETURN NUMBER
AS
    v_booking_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_booking_count
      FROM TICKET
     WHERE passenger_id = p_passenger_id
       AND status <> 'CANCELLED';

    RETURN v_booking_count;
END fn_passenger_booking_count;
/