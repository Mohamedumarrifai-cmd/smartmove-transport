<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

header('Content-Type: application/json; charset=utf-8');

try {
	require_once __DIR__ . '/../config/database-oracle.php';
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ($method === 'OPTIONS') {
		http_response_code(204);
		exit;
	}

	if ($method === 'GET') {
		$sql = 'SELECT * FROM TICKET';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' WHERE ticket_id = :id';
		}
		$statement = oci_parse($conn, $sql);
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
	$unknownFields = array_diff(array_keys($payload), ['passenger_id', 'trip_id', 'seat_number']);
	if ($unknownFields !== []) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['passenger_id', 'trip_id', 'seat_number'] as $field) {
		if (!array_key_exists($field, $payload)) {
			sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$passengerId = validateRecordId($payload['passenger_id']);
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
