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
		$ticketIds = null;
		if (isset($_GET['tickets'])) {
			$ticketList = $_GET['tickets'];
			if (!is_string($ticketList) || trim($ticketList) === '') {
				sendJsonResponse(400, ['success' => false, 'error' => 'A comma-separated ticket list is required.']);
			}
			$ticketParts = explode(',', $ticketList);
			if (count($ticketParts) > 6) {
				sendJsonResponse(400, ['success' => false, 'error' => 'No more than six tickets can be fetched at once.']);
			}
			$ticketIds = [];
			foreach ($ticketParts as $ticketPart) {
				$ticketIds[] = validateRecordId(trim($ticketPart));
			}
			if (count(array_unique($ticketIds)) !== count($ticketIds)) {
				sendJsonResponse(400, ['success' => false, 'error' => 'Ticket IDs must be unique.']);
			}
			$ticketBinds = [];
			foreach ($ticketIds as $index => $ticketId) {
				$bindName = ':ticket_' . $index;
				$ticketBinds[] = $bindName;
			}
			$sql .= ' AND tk.ticket_id IN (' . implode(', ', $ticketBinds) . ')';
		} elseif ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' AND tk.ticket_id = :id';
		}
		$sql .= $ticketIds !== null ? ' ORDER BY tk.seat_number' : ' ORDER BY tk.booking_date DESC';
		$statement = oci_parse($conn, $sql);
		if ($statement === false) {
			throw new RuntimeException('Booking query could not be prepared.');
		}
		oci_bind_by_name($statement, ':passenger_id', $passengerId);
		if (isset($ticketIds)) {
			foreach ($ticketIds as $index => $ticketId) {
				oci_bind_by_name($statement, ':ticket_' . $index, $ticketIds[$index]);
			}
			unset($ticketId);
		} elseif ($id !== null) {
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
		if ($ticketIds !== null) {
			if (count($tickets) !== count($ticketIds)) {
				sendJsonResponse(404, ['success' => false, 'error' => 'One or more tickets were not found.']);
			}
			$totalAmount = array_sum(array_map(static fn(array $ticket): float => (float) $ticket['fare'], $tickets));
			$seats = array_map(static fn(array $ticket): int => (int) $ticket['seat_number'], $tickets);
			sendJsonResponse(200, [
				'success' => true,
				'message' => 'Booking details loaded.',
				'data' => ['tickets' => $tickets, 'total_amount' => $totalAmount, 'seats' => $seats],
			]);
		}
		sendJsonResponse(200, ['success' => true, 'data' => $id !== null ? $tickets[0] : $tickets]);
	}

	if ($method !== 'POST') {
		header('Allow: GET, POST, OPTIONS');
		sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	$payload = readJsonPayload();
	$action = $payload['action'] ?? null;
	if ($action === 'create_multi') {
		$unknownFields = array_diff(array_keys($payload), ['action', 'trip_id', 'number_of_seats']);
		if ($unknownFields !== []) {
			sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
		}
		foreach (['trip_id', 'number_of_seats'] as $field) {
			if (!array_key_exists($field, $payload)) {
				sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
			}
		}
		$tripId = validateRecordId($payload['trip_id']);
		$numberOfSeats = filter_var($payload['number_of_seats'], FILTER_VALIDATE_INT);
		if ($numberOfSeats === false || $numberOfSeats < 1 || $numberOfSeats > 6) {
			sendJsonResponse(400, ['success' => false, 'error' => 'Number of seats must be between 1 and 6.']);
		}

		$passengerStatement = oci_parse($conn, 'SELECT status FROM PASSENGER WHERE passenger_id = :passenger_id');
		oci_bind_by_name($passengerStatement, ':passenger_id', $passengerId);
		if (!@oci_execute($passengerStatement)) {
			$error = oci_error($passengerStatement);
			throw new RuntimeException($error['message'] ?? 'Passenger status could not be checked.');
		}
		$passengerRow = oci_fetch_assoc($passengerStatement);
		oci_free_statement($passengerStatement);
		if ($passengerRow === false || strtoupper($passengerRow['STATUS']) !== 'ACTIVE') {
			sendJsonResponse(422, ['success' => false, 'error' => 'Passenger is not active.']);
		}

		$tripStatement = oci_parse(
			$conn,
			'SELECT tr.fare, tr.status, v.capacity FROM TRIP tr '
			. 'JOIN VEHICLE v ON v.vehicle_id = tr.vehicle_id '
			. 'WHERE tr.trip_id = :trip_id FOR UPDATE OF tr.status'
		);
		oci_bind_by_name($tripStatement, ':trip_id', $tripId);
		if (!@oci_execute($tripStatement)) {
			$error = oci_error($tripStatement);
			throw new RuntimeException($error['message'] ?? 'Trip could not be checked.');
		}
		$trip = oci_fetch_assoc($tripStatement);
		oci_free_statement($tripStatement);
		if ($trip === false) {
			sendJsonResponse(404, ['success' => false, 'error' => 'Trip was not found.']);
		}
		if (!in_array(strtoupper($trip['STATUS']), ['SCHEDULED', 'BOARDING'], true)) {
			sendJsonResponse(422, ['success' => false, 'error' => 'Trip is not open for booking.']);
		}

		$capacity = (int) $trip['CAPACITY'];
		$seatStatement = oci_parse($conn, 'SELECT seat_number FROM TICKET WHERE trip_id = :trip_id');
		oci_bind_by_name($seatStatement, ':trip_id', $tripId);
		if (!@oci_execute($seatStatement)) {
			$error = oci_error($seatStatement);
			throw new RuntimeException($error['message'] ?? 'Seat availability could not be checked.');
		}
		$usedSeats = [];
		while (($seatRow = oci_fetch_assoc($seatStatement)) !== false) {
			$usedSeats[(int) $seatRow['SEAT_NUMBER']] = true;
		}
		oci_free_statement($seatStatement);
		$seats = [];
		for ($seatNumber = 1; $seatNumber <= $capacity && count($seats) < $numberOfSeats; $seatNumber++) {
			if (!isset($usedSeats[$seatNumber])) {
				$seats[] = $seatNumber;
			}
		}
		if (count($seats) !== $numberOfSeats) {
			sendJsonResponse(409, ['success' => false, 'error' => 'Not enough seats are available for this trip.']);
		}

		$ticketIds = [];
		$sequenceStatement = oci_parse($conn, 'SELECT SEQ_TICKET_ID.NEXTVAL AS TICKET_ID FROM DUAL');
		for ($index = 0; $index < $numberOfSeats; $index++) {
			if (!@oci_execute($sequenceStatement)) {
				$error = oci_error($sequenceStatement);
				throw new RuntimeException($error['message'] ?? 'Ticket ID could not be generated.');
			}
			$sequenceRow = oci_fetch_assoc($sequenceStatement);
			if ($sequenceRow === false) {
				throw new RuntimeException('Ticket ID could not be generated.');
			}
			$ticketIds[] = (int) $sequenceRow['TICKET_ID'];
		}
		oci_free_statement($sequenceStatement);

		$insertParts = [];
		$bindValues = [];
		foreach ($ticketIds as $index => $ticketId) {
			$insertParts[] = 'INTO TICKET (ticket_id, passenger_id, trip_id, seat_number, fare, status) '
				. "VALUES (:ticket_id_{$index}, :passenger_id_{$index}, :trip_id_{$index}, :seat_number_{$index}, :fare_{$index}, 'BOOKED')";
			$bindValues[':ticket_id_' . $index] = $ticketId;
			$bindValues[':passenger_id_' . $index] = $passengerId;
			$bindValues[':trip_id_' . $index] = $tripId;
			$bindValues[':seat_number_' . $index] = $seats[$index];
			$bindValues[':fare_' . $index] = $trip['FARE'];
		}
		$insertSql = 'INSERT ALL ' . implode(' ', $insertParts) . ' SELECT 1 FROM DUAL';
		$insertStatement = oci_parse($conn, $insertSql);
		foreach ($bindValues as $bindName => $bindValue) {
			oci_bind_by_name($insertStatement, $bindName, $bindValues[$bindName]);
		}
		if (!@oci_execute($insertStatement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($insertStatement);
			oci_rollback($conn);
			error_log('Booking API multi-seat error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
			oci_free_statement($insertStatement);
			if ((int) ($error['code'] ?? 0) === 1) {
				sendJsonResponse(409, ['success' => false, 'error' => 'A seat was just booked. Please try again.']);
			}
			sendJsonResponse(500, ['success' => false, 'error' => 'Booking could not be created.']);
		}
		oci_free_statement($insertStatement);
		sendJsonResponse(201, [
			'success' => true,
			'message' => 'Seats reserved.',
			'data' => [
				'ticket_ids' => $ticketIds,
				'total_amount' => (float) $trip['FARE'] * $numberOfSeats,
				'seats' => $seats,
			],
		]);
	}
	if ($action !== null) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unsupported booking action.']);
	}
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
