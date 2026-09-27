-- SmartMove business reports.
-- Edit the date values, then run in SQL*Plus or SQL Developer with substitution enabled.
DEFINE report_start_date = '2026-01-01 00:00:00'
DEFINE report_end_date   = '2027-01-01 00:00:00'

-- 1. Most frequently booked routes. Cancelled tickets are excluded.
SELECT r.route_id,
       r.route_name,
       r.origin_city,
       r.destination_city,
       COUNT(t.ticket_id) AS booking_count
  FROM ROUTE r
  JOIN TRIP tr ON tr.route_id = r.route_id
  JOIN TICKET t ON t.trip_id = tr.trip_id
 WHERE t.status <> 'CANCELLED'
 GROUP BY r.route_id, r.route_name, r.origin_city, r.destination_city
 ORDER BY booking_count DESC, r.route_id;

-- 2. Revenue from completed ticket payments in [start, end).
-- The end timestamp is exclusive, so adjacent periods do not overlap.
SELECT NVL(SUM(p.amount), 0) AS total_revenue,
       COUNT(p.payment_id) AS completed_payment_count,
       TO_TIMESTAMP('&report_start_date', 'YYYY-MM-DD HH24:MI:SS') AS period_start,
       TO_TIMESTAMP('&report_end_date', 'YYYY-MM-DD HH24:MI:SS') AS period_end_exclusive
  FROM PAYMENT p
  JOIN TICKET t ON t.ticket_id = p.ticket_id
 WHERE p.status = 'COMPLETED'
   AND p.paid_at >= TO_TIMESTAMP('&report_start_date', 'YYYY-MM-DD HH24:MI:SS')
   AND p.paid_at <  TO_TIMESTAMP('&report_end_date', 'YYYY-MM-DD HH24:MI:SS');

-- 3. Passenger travel/booking history, including trip, route, and ticket status.
SELECT p.passenger_id,
       p.full_name AS passenger_name,
       p.email AS passenger_email,
       t.ticket_id,
       t.booking_date,
       t.seat_number,
       t.fare AS ticket_fare,
       t.status AS ticket_status,
       tr.trip_id,
       tr.departure_time,
       tr.arrival_time,
       tr.status AS trip_status,
       r.route_id,
       r.route_name,
       r.origin_city,
       r.destination_city
  FROM PASSENGER p
  JOIN TICKET t ON t.passenger_id = p.passenger_id
  JOIN TRIP tr ON tr.trip_id = t.trip_id
  JOIN ROUTE r ON r.route_id = tr.route_id
 ORDER BY p.passenger_id, tr.departure_time DESC, t.ticket_id;