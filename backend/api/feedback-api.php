<?php

require_once __DIR__ . '/../helpers/mongodb-api.php';

runMongoApi(['GET', 'POST'], static function (MongoDB\Database $database, string $method): void {
	$reviews = $database->selectCollection('PassengerReviews');

	if ($method === 'GET') {
		$filter = [];
		foreach (['passengerId', 'tripId', 'routeId', 'vehicleId'] as $field) {
			if (isset($_GET[$field])) {
				$filter[$field] = validateMongoInteger($_GET[$field], $field);
			}
		}
		$limit = isset($_GET['limit']) ? validateMongoInteger($_GET['limit'], 'limit', 1, 100) : 100;
		$data = [];
		foreach ($reviews->find($filter, ['sort' => ['createdAt' => -1], 'limit' => $limit]) as $review) {
			$data[] = normalizeMongoValue($review);
		}
		sendMongoJsonResponse(200, ['success' => true, 'data' => $data]);
	}
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$passenger = $_SESSION['passenger'] ?? null;
	$passengerId = is_array($passenger)
		? filter_var($passenger['passenger_id'] ?? null, FILTER_VALIDATE_INT)
		: false;
	if ($passengerId === false || $passengerId < 1) {
		sendMongoJsonResponse(401, ['success' => false, 'error' => 'Sign in to send feedback.']);
	}

	$payload = readMongoJsonPayload();
	$allowedFields = ['tripId', 'routeId', 'vehicleId', 'rating', 'comments'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['tripId', 'routeId', 'vehicleId', 'rating', 'comments'] as $field) {
		if (!array_key_exists($field, $payload)) {
			sendMongoJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$review = [
		'passengerId' => $passengerId,
		'tripId' => validateMongoInteger($payload['tripId'], 'tripId'),
		'routeId' => validateMongoInteger($payload['routeId'], 'routeId'),
		'vehicleId' => validateMongoInteger($payload['vehicleId'], 'vehicleId'),
		'rating' => validateMongoInteger($payload['rating'], 'rating', 1, 5),
		'comments' => $payload['comments'],
		'createdAt' => new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000)),
	];
	if (!is_string($review['comments']) || trim($review['comments']) === '' || strlen($review['comments']) > 1000) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Comments must be non-empty and at most 1000 characters.']);
	}
	$review['comments'] = trim($review['comments']);

	require_once __DIR__ . '/../config/database-oracle.php';
	$bookingStatement = @oci_parse(
		$conn,
		"SELECT tr.route_id, tr.vehicle_id FROM TICKET tk "
		. "JOIN TRIP tr ON tr.trip_id = tk.trip_id "
		. "WHERE tk.passenger_id = :passenger_id AND tk.trip_id = :trip_id "
		. "AND tk.status IN ('PAID', 'USED') AND tr.status = 'COMPLETED'"
	);
	if ($bookingStatement === false) {
		throw new RuntimeException('Feedback booking check could not be prepared.');
	}
	oci_bind_by_name($bookingStatement, ':passenger_id', $passengerId);
	oci_bind_by_name($bookingStatement, ':trip_id', $review['tripId']);
	if (!@oci_execute($bookingStatement)) {
		$error = oci_error($bookingStatement);
		oci_free_statement($bookingStatement);
		throw new RuntimeException($error['message'] ?? 'Feedback booking check failed.');
	}
	$completedTrip = oci_fetch_assoc($bookingStatement);
	oci_free_statement($bookingStatement);
	if ($completedTrip === false) {
		sendMongoJsonResponse(403, ['success' => false, 'error' => 'Feedback is available after one of your trips is completed.']);
	}
	if ((int) $completedTrip['ROUTE_ID'] !== $review['routeId'] || (int) $completedTrip['VEHICLE_ID'] !== $review['vehicleId']) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Trip details do not match your booking.']);
	}
	if ($reviews->findOne(['passengerId' => $review['passengerId'], 'tripId' => $review['tripId']]) !== null) {
		sendMongoJsonResponse(409, ['success' => false, 'error' => 'A review already exists for this passenger and trip.']);
	}

	$result = $reviews->insertOne($review);
	$review['_id'] = $result->getInsertedId();
	sendMongoJsonResponse(201, ['success' => true, 'data' => normalizeMongoValue($review)]);
});
