<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

header('Content-Type: application/json; charset=utf-8');

try {
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ($method === 'OPTIONS') {
		http_response_code(204);
		exit;
	}
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$passenger = $_SESSION['passenger'] ?? null;
	$passengerId = is_array($passenger)
		? filter_var($passenger['passenger_id'] ?? null, FILTER_VALIDATE_INT)
		: false;
	if ($passengerId === false || $passengerId < 1) {
		sendJsonResponse(401, ['success' => false, 'error' => 'Sign in to manage your bookings.']);
	}
	require_once __DIR__ . '/../config/database-oracle.php';

	if ($method === 'GET') {
		$sql = 'SELECT tk.ticket_id, tk.trip_id, tk.passenger_id, tk.seat_number, tr.status AS trip_status, '
			. 'TO_CHAR(tk.booking_date, \'YYYY-MM-DD"T"HH24:MI:SS\') AS booking_date, tk.fare, tk.status, '
			. 'tr.route_id, tr.vehicle_id, '
			. 'TO_CHAR(tr.departure_time, \'YYYY-MM-DD"T"HH24:MI:SS\') AS departure_time, '
			. 'TO_CHAR(tr.arrival_time, \'YYYY-MM-DD"T"HH24:MI:SS\') AS arrival_time, '
			. 'r.route_name, r.origin_city, r.destination_city, v.capacity '
			. 'FROM TICKET tk JOIN TRIP tr ON tr.trip_id = tk.trip_id '
			. 'JOIN ROUTE r ON r.route_id = tr.route_id JOIN VEHICLE v ON v.vehicle_id = tr.vehicle_id '
			. 'WHERE tk.passenger_id = :passenger_id';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' AND tk.ticket_id = :id';
		}
		$sql .= ' ORDER BY tk.booking_date DESC';
		$statement = oci_parse($conn, $sql);
		oci_bind_by_name($statement, ':passenger_id', $passengerId);
		if ($id !== null) {
			oci_bind_by_name($statement, ':id', $id);
		}
		if (!@oci_execute($statement)) {
			$error = oci_error($statement);
			throw new RuntimeException($error['message'] ?? 'Booking query failed.');
		}

		$tickets = [];
		while (($ticket = oci_fetch_assoc($statement)) !== false) {
			$tickets[] = array_change_key_case($ticket, CASE_LOWER);
		}
		oci_free_statement($statement);

		if ($id !== null && $tickets === []) {
			sendJsonResponse(404, ['success' => false, 'error' => 'Booking not found.']);
		}
		sendJsonResponse(200, ['success' => true, 'data' => $id !== null ? $tickets[0] : $tickets]);
	}

	if ($method !== 'POST') {
		header('Allow: GET, POST, OPTIONS');
		sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	$payload = readJsonPayload();
	$unknownFields = array_diff(array_keys($payload), ['trip_id', 'seat_number']);
	if ($unknownFields !== []) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['trip_id', 'seat_number'] as $field) {
		if (!array_key_exists($field, $payload)) {
			sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$tripId = validateRecordId($payload['trip_id']);
	$seatNumber = validateRecordId($payload['seat_number']);
	$statement = oci_parse($conn, 'BEGIN sp_add_booking(:passenger_id, :trip_id, :seat_number, :ticket_id); END;');
	oci_bind_by_name($statement, ':passenger_id', $passengerId);
	oci_bind_by_name($statement, ':trip_id', $tripId);
	oci_bind_by_name($statement, ':seat_number', $seatNumber);
	$ticketId = null;
	oci_bind_by_name($statement, ':ticket_id', $ticketId, 40);

	if (!@oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
		$error = oci_error($statement);
		$businessErrors = [
			1 => [409, 'That seat is already booked for this trip.'],
			20001 => [422, 'Passenger is not active.'],
			20002 => [422, 'Trip is not open for booking.'],
			20003 => [422, 'Seat number is outside the vehicle capacity.'],
			20004 => [404, 'Passenger or trip was not found.'],
		];
		$code = (int) ($error['code'] ?? 0);
		if (isset($businessErrors[$code])) {
			[$status, $message] = $businessErrors[$code];
			oci_free_statement($statement);
			sendJsonResponse($status, ['success' => false, 'error' => $message]);
		}
		error_log('Booking API error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
		oci_free_statement($statement);
		sendJsonResponse(500, ['success' => false, 'error' => 'Booking could not be created.']);
	}

	oci_free_statement($statement);
	sendJsonResponse(201, ['success' => true, 'data' => ['ticket_id' => $ticketId]]);
} catch (JsonException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
} catch (InvalidArgumentException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Booking API error: ' . $exception->getMessage());
	sendJsonResponse(500, ['success' => false, 'error' => 'Booking operation failed.']);
}
