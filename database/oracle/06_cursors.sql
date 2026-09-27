-- Explicit cursor report of trips for a route. Run after 01_tables.sql.

CREATE OR REPLACE PROCEDURE sp_list_trips_for_route (
    p_route_id IN ROUTE.route_id%TYPE
)
AS
    CURSOR c_route_trips (cp_route_id ROUTE.route_id%TYPE) IS
        SELECT t.trip_id,
               t.departure_time,
               t.arrival_time,
               t.status,
               t.fare,
               v.registration_number,
               d.full_name AS driver_name
          FROM TRIP t
          JOIN VEHICLE v ON v.vehicle_id = t.vehicle_id
          JOIN DRIVER d ON d.driver_id = t.driver_id
         WHERE t.route_id = cp_route_id
         ORDER BY t.departure_time;

    v_route_name       ROUTE.route_name%TYPE;
    v_trip_id          TRIP.trip_id%TYPE;
    v_departure_time   TRIP.departure_time%TYPE;
    v_arrival_time     TRIP.arrival_time%TYPE;
    v_status           TRIP.status%TYPE;
    v_fare             TRIP.fare%TYPE;
    v_registration     VEHICLE.registration_number%TYPE;
    v_driver_name      DRIVER.full_name%TYPE;
    v_trip_count       PLS_INTEGER := 0;
BEGIN
    SELECT route_name
      INTO v_route_name
      FROM ROUTE
     WHERE route_id = p_route_id;

    DBMS_OUTPUT.PUT_LINE('Trips for route: ' || v_route_name);
    OPEN c_route_trips(p_route_id);
    LOOP
        FETCH c_route_trips
         INTO v_trip_id, v_departure_time, v_arrival_time, v_status, v_fare,
              v_registration, v_driver_name;
        EXIT WHEN c_route_trips%NOTFOUND;

        v_trip_count := v_trip_count + 1;
        DBMS_OUTPUT.PUT_LINE(
            'Trip ' || v_trip_id
            || ' | departure=' || TO_CHAR(v_departure_time, 'YYYY-MM-DD HH24:MI')
            || ' | arrival=' || TO_CHAR(v_arrival_time, 'YYYY-MM-DD HH24:MI')
            || ' | status=' || v_status
            || ' | fare=' || TO_CHAR(v_fare, 'FM999999990.00')
            || ' | vehicle=' || v_registration
            || ' | driver=' || v_driver_name
        );
    END LOOP;
    CLOSE c_route_trips;

    IF v_trip_count = 0 THEN
        DBMS_OUTPUT.PUT_LINE('No trips are scheduled for this route.');
    END IF;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        IF c_route_trips%ISOPEN THEN
            CLOSE c_route_trips;
        END IF;
        RAISE_APPLICATION_ERROR(-20031, 'Route was not found.');
    WHEN OTHERS THEN
        IF c_route_trips%ISOPEN THEN
            CLOSE c_route_trips;
        END IF;
        RAISE;
END sp_list_trips_for_route;
/