<?php

require_once __DIR__ . '/../helpers/mongodb-api.php';

function getFeedbackPassengerId(): int
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$passenger = $_SESSION['passenger'] ?? null;
	$passengerId = is_array($passenger)
		? filter_var($passenger['passenger_id'] ?? null, FILTER_VALIDATE_INT)
		: false;
	if ($passengerId === false || $passengerId < 1) {
		sendMongoJsonResponse(401, ['success' => false, 'message' => 'Sign in to manage trip feedback.', 'error' => 'Sign in to manage trip feedback.']);
	}
	return $passengerId;
}

function getFeedbackTripStatus(int $passengerId, int $tripId): array
{
	require_once __DIR__ . '/../config/database-oracle.php';
	$statement = @oci_parse(
		$conn,
		"SELECT tr.route_id, tr.vehicle_id, "
		. "CASE "
		. "WHEN UPPER(tk.status) IN ('CANCELLED', 'REFUNDED') THEN 'cancelled' "
		. "WHEN UPPER(tr.status) = 'COMPLETED' THEN 'completed' "
		. "WHEN UPPER(tr.status) IN ('CANCELLED', 'CANCELED') THEN 'cancelled' "
		. "WHEN UPPER(tr.status) = 'IN_PROGRESS' THEN 'ongoing' "
		. "WHEN UPPER(tr.status) IN ('SCHEDULED', 'BOARDING') AND tr.departure_time > SYSTIMESTAMP THEN 'upcoming' "
		. "WHEN tr.arrival_time <= SYSTIMESTAMP THEN 'not_completed' "
		. "ELSE 'ongoing' END AS feedback_status "
		. "FROM TICKET tk JOIN TRIP tr ON tr.trip_id = tk.trip_id "
		. "WHERE tk.passenger_id = :passenger_id AND tk.trip_id = :trip_id AND ROWNUM = 1"
	);
	if ($statement === false) {
		throw new RuntimeException('Trip feedback check could not be prepared.');
	}
	oci_bind_by_name($statement, ':passenger_id', $passengerId);
	oci_bind_by_name($statement, ':trip_id', $tripId);
	if (!@oci_execute($statement)) {
		$error = oci_error($statement);
		oci_free_statement($statement);
		throw new RuntimeException($error['message'] ?? 'Trip feedback check failed.');
	}
	$trip = oci_fetch_assoc($statement);
	oci_free_statement($statement);
	if ($trip === false) {
		sendMongoJsonResponse(404, ['success' => false, 'message' => 'This trip was not found in your bookings.', 'error' => 'This trip was not found in your bookings.']);
	}
	return [
		'trip_status_at_feedback' => strtolower($trip['FEEDBACK_STATUS']),
		'route_id' => (int) $trip['ROUTE_ID'],
		'vehicle_id' => (int) $trip['VEHICLE_ID'],
	];
}

function feedbackDocumentToArray(mixed $document): array
{
	return $document instanceof Traversable ? iterator_to_array($document) : (array) $document;
}

function getFeedbackDocument(MongoDB\Collection $feedback, MongoDB\Collection $legacy, int $userId, int $tripId): ?array
{
	$document = $feedback->findOne(['user_id' => $userId, 'trip_id' => $tripId]);
	if ($document !== null) {
		return feedbackDocumentToArray($document);
	}
	$document = $legacy->findOne(['passengerId' => $userId, 'tripId' => $tripId]);
	if ($document === null) {
		return null;
	}
	$document = feedbackDocumentToArray($document);
	$document['user_id'] = $userId;
	$document['trip_id'] = $tripId;
	$document['comment'] = $document['comments'] ?? '';
	$document['highlights'] = '';
	$document['reason'] = null;
	$document['would_recommend'] = null;
	$document['trip_status_at_feedback'] = 'completed';
	$document['created_at'] = $document['createdAt'] ?? null;
	$document['updated_at'] = $document['createdAt'] ?? null;
	return $document;
}

function validateFeedbackValues(array $payload, string $status, ?array $existing = null): array
{
	$comment = array_key_exists('comment', $payload) ? $payload['comment'] : ($existing['comment'] ?? '');
	if (!is_string($comment) || strlen($comment) > 1000) {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Comment must be at most 1000 characters.', 'error' => 'Comment must be at most 1000 characters.']);
	}
	$comment = trim($comment);
	$highlights = array_key_exists('highlights', $payload) ? $payload['highlights'] : ($existing['highlights'] ?? '');
	if (!is_string($highlights) || strlen($highlights) > 1000) {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Highlights must be at most 1000 characters.', 'error' => 'Highlights must be at most 1000 characters.']);
	}
	$highlights = trim($highlights);

	$ratingValue = array_key_exists('rating', $payload) ? $payload['rating'] : ($existing['rating'] ?? null);
	$rating = null;
	if ($ratingValue !== null && $ratingValue !== '') {
		$rating = validateMongoInteger($ratingValue, 'rating', 1, 5);
	}
	$reason = array_key_exists('reason', $payload) ? $payload['reason'] : ($existing['reason'] ?? null);
	$wouldRecommend = array_key_exists('would_recommend', $payload) ? $payload['would_recommend'] : ($existing['would_recommend'] ?? null);

	if ($status === 'completed') {
		if ($rating === null) {
			sendMongoJsonResponse(400, ['success' => false, 'message' => 'A rating from 1 to 5 is required for a completed trip.', 'error' => 'A rating from 1 to 5 is required for a completed trip.']);
		}
		if (!is_bool($wouldRecommend)) {
			sendMongoJsonResponse(400, ['success' => false, 'message' => 'Please tell us whether you would recommend this trip.', 'error' => 'Please tell us whether you would recommend this trip.']);
		}
		$reason = null;
	} else {
		$allowedReasons = ['change_of_plans', 'weather', 'health', 'budget', 'other'];
		if (!is_string($reason) || !in_array($reason, $allowedReasons, true)) {
			sendMongoJsonResponse(400, ['success' => false, 'message' => 'Choose a reason for the trip not being completed.', 'error' => 'Choose a reason for the trip not being completed.']);
		}
		if ($comment === '') {
			sendMongoJsonResponse(400, ['success' => false, 'message' => 'A comment is required for a trip that was not completed.', 'error' => 'A comment is required for a trip that was not completed.']);
		}
		$wouldRecommend = null;
	}

	return [
		'rating' => $rating,
		'comment' => $comment,
		'reason' => $reason,
		'would_recommend' => $wouldRecommend,
		'highlights' => $highlights,
	];
}

function makeFeedbackDocument(array $values, int $userId, int $tripId, array $trip, MongoDB\BSON\UTCDateTime $createdAt): array
{
	$now = new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000));
	return [
		'user_id' => $userId,
		'trip_id' => $tripId,
		'trip_status_at_feedback' => $trip['trip_status_at_feedback'],
		'rating' => $values['rating'],
		'comment' => $values['comment'],
		'reason' => $values['reason'],
		'would_recommend' => $values['would_recommend'],
		'highlights' => $values['highlights'],
		'created_at' => $createdAt,
		'updated_at' => $now,
		'route_id' => $trip['route_id'],
		'vehicle_id' => $trip['vehicle_id'],
		'passengerId' => $userId,
		'tripId' => $tripId,
		'routeId' => $trip['route_id'],
		'vehicleId' => $trip['vehicle_id'],
		'comments' => $values['comment'],
		'createdAt' => $createdAt,
		'updatedAt' => $now,
	];
}

runMongoApi(['GET', 'POST', 'PUT'], static function (MongoDB\Database $database, string $method): void {
	$feedback = $database->selectCollection('TripFeedback');
	$legacy = $database->selectCollection('PassengerReviews');
	$feedback->createIndex(['user_id' => 1, 'trip_id' => 1], [
		'unique' => true,
		'name' => 'unique_user_trip_feedback',
	]);
	$action = $_GET['action'] ?? null;

	if ($method === 'GET' && $action === 'get') {
		$userId = getFeedbackPassengerId();
		$tripId = validateMongoInteger($_GET['trip_id'] ?? null, 'trip_id');
		$trip = getFeedbackTripStatus($userId, $tripId);
		$document = getFeedbackDocument($feedback, $legacy, $userId, $tripId);
		sendMongoJsonResponse(200, [
			'success' => true,
			'message' => 'Trip feedback loaded.',
			'data' => [
				'trip_id' => $tripId,
				'trip_status_at_feedback' => $trip['trip_status_at_feedback'],
				'feedback' => $document === null ? null : normalizeMongoValue($document),
			],
		]);
	}

	if ($method === 'GET' && $action === null) {
		$limit = isset($_GET['limit']) ? validateMongoInteger($_GET['limit'], 'limit', 1, 100) : 100;
		$legacyFilter = [];
		$feedbackFilter = [];
		foreach (['passengerId' => 'user_id', 'tripId' => 'trip_id', 'routeId' => 'route_id', 'vehicleId' => 'vehicle_id'] as $legacyField => $feedbackField) {
			if (isset($_GET[$legacyField])) {
				$value = validateMongoInteger($_GET[$legacyField], $legacyField);
				$legacyFilter[$legacyField] = $value;
				$feedbackFilter[$feedbackField] = $value;
			}
		}
		$data = [];
		foreach ($feedback->find($feedbackFilter, ['sort' => ['created_at' => -1], 'limit' => $limit]) as $review) {
			$review = normalizeMongoValue($review);
			$review['passengerId'] = $review['user_id'];
			$review['tripId'] = $review['trip_id'];
			$review['routeId'] = $review['route_id'];
			$review['comments'] = $review['comment'];
			$review['createdAt'] = $review['created_at'];
			$data[] = $review;
		}
		$legacyLimit = max(0, $limit - count($data));
		if ($legacyLimit > 0) {
			foreach ($legacy->find($legacyFilter, ['sort' => ['createdAt' => -1], 'limit' => $legacyLimit]) as $review) {
				$data[] = normalizeMongoValue($review);
			}
		}
		usort($data, static fn(array $first, array $second): int => strcmp(
			(string) ($second['created_at'] ?? $second['createdAt'] ?? ''),
			(string) ($first['created_at'] ?? $first['createdAt'] ?? '')
		));
		sendMongoJsonResponse(200, ['success' => true, 'message' => 'Feedback list loaded.', 'data' => $data]);
	}

	if ($method === 'GET') {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Unsupported feedback action.', 'error' => 'Unsupported feedback action.']);
	}
	if (!in_array($action, ['create', 'update'], true) || ($method === 'POST' && $action !== 'create') || ($method === 'PUT' && $action !== 'update')) {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Use POST ?action=create or PUT ?action=update&id=….', 'error' => 'Use POST ?action=create or PUT ?action=update&id=….']);
	}

	$userId = getFeedbackPassengerId();
	$payload = readMongoJsonPayload();
	$allowedFields = ['trip_id', 'rating', 'comment', 'reason', 'would_recommend', 'highlights'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Unknown field: ' . reset($unknownFields), 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}

	if ($method === 'POST') {
		$tripId = validateMongoInteger($payload['trip_id'] ?? null, 'trip_id');
		$trip = getFeedbackTripStatus($userId, $tripId);
		$existing = getFeedbackDocument($feedback, $legacy, $userId, $tripId);
		if ($existing !== null) {
			sendMongoJsonResponse(409, ['success' => false, 'message' => 'Feedback already exists. Open it from your bookings to update it.', 'error' => 'Feedback already exists. Open it from your bookings to update it.']);
		}
		$values = validateFeedbackValues($payload, $trip['trip_status_at_feedback']);
		$createdAt = new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000));
		$document = makeFeedbackDocument($values, $userId, $tripId, $trip, $createdAt);
		try {
			$result = $feedback->insertOne($document);
		} catch (MongoDB\Driver\Exception\BulkWriteException $exception) {
			if ($exception->getCode() === 11000) {
				sendMongoJsonResponse(409, ['success' => false, 'message' => 'Feedback already exists. Open it from your bookings to update it.', 'error' => 'Feedback already exists. Open it from your bookings to update it.']);
			}
			throw $exception;
		}
		$document['_id'] = $result->getInsertedId();
		sendMongoJsonResponse(201, ['success' => true, 'message' => 'Feedback saved.', 'data' => normalizeMongoValue($document)]);
	}

	$id = mongoIdFromQuery($_GET['id'] ?? null);
	$existing = $feedback->findOne(['_id' => $id, 'user_id' => $userId]);
	$isLegacy = false;
	if ($existing === null) {
		$existing = $legacy->findOne(['_id' => $id, 'passengerId' => $userId]);
		$isLegacy = $existing !== null;
	}
	if ($existing === null) {
		sendMongoJsonResponse(404, ['success' => false, 'message' => 'Feedback was not found.', 'error' => 'Feedback was not found.']);
	}
	$existing = feedbackDocumentToArray($existing);
	$tripId = (int) ($existing['trip_id'] ?? $existing['tripId'] ?? 0);
	if (isset($payload['trip_id']) && validateMongoInteger($payload['trip_id'], 'trip_id') !== $tripId) {
		sendMongoJsonResponse(400, ['success' => false, 'message' => 'Feedback cannot be moved to another trip.', 'error' => 'Feedback cannot be moved to another trip.']);
	}
	$trip = getFeedbackTripStatus($userId, $tripId);
	$existing['comment'] = $existing['comment'] ?? $existing['comments'] ?? '';
	$existing['highlights'] = $existing['highlights'] ?? '';
	$existing['reason'] = $existing['reason'] ?? null;
	$existing['would_recommend'] = $existing['would_recommend'] ?? null;
	$values = validateFeedbackValues($payload, $trip['trip_status_at_feedback'], $existing);
	$createdAt = $existing['created_at'] ?? $existing['createdAt'] ?? new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000));
	$document = makeFeedbackDocument($values, $userId, $tripId, $trip, $createdAt);

	if ($isLegacy) {
		$result = $feedback->insertOne($document);
		$document['_id'] = $result->getInsertedId();
		$legacy->deleteOne(['_id' => $id, 'passengerId' => $userId]);
	} else {
		$feedback->updateOne(['_id' => $id, 'user_id' => $userId], ['$set' => $document]);
		$document['_id'] = $id;
	}
	sendMongoJsonResponse(200, ['success' => true, 'message' => 'Feedback updated.', 'data' => normalizeMongoValue($document)]);
});
