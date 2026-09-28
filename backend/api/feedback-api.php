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

	$payload = readMongoJsonPayload();
	$allowedFields = ['passengerId', 'tripId', 'routeId', 'vehicleId', 'rating', 'comments'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['passengerId', 'tripId', 'routeId', 'vehicleId', 'rating', 'comments'] as $field) {
		if (!array_key_exists($field, $payload)) {
			sendMongoJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$review = [
		'passengerId' => validateMongoInteger($payload['passengerId'], 'passengerId'),
		'tripId' => validateMongoInteger($payload['tripId'], 'tripId'),
		'routeId' => validateMongoInteger($payload['routeId'], 'routeId'),
		'vehicleId' => validateMongoInteger($payload['vehicleId'], 'vehicleId'),
		'rating' => validateMongoInteger($payload['rating'], 'rating', 1, 5),
		'comments' => $payload['comments'],
		'createdAt' => new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000)),
	];
	if (!is_string($review['comments']) || strlen($review['comments']) > 1000) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Comments must be a string of at most 1000 characters.']);
	}
	if ($reviews->findOne(['passengerId' => $review['passengerId'], 'tripId' => $review['tripId']]) !== null) {
		sendMongoJsonResponse(409, ['success' => false, 'error' => 'A review already exists for this passenger and trip.']);
	}

	$result = $reviews->insertOne($review);
	$review['_id'] = $result->getInsertedId();
	sendMongoJsonResponse(201, ['success' => true, 'data' => normalizeMongoValue($review)]);
});
