<?php

require_once __DIR__ . '/../helpers/mongodb-api.php';

runMongoApi(['GET', 'POST', 'PUT', 'DELETE'], static function (MongoDB\Database $database, string $method): void {
	$announcements = $database->selectCollection('TravelAnnouncements');

	if ($method === 'GET') {
		if (isset($_GET['id'])) {
			$id = mongoIdFromQuery($_GET['id']);
			$announcement = $announcements->findOne(['_id' => $id]);
			if ($announcement === null) {
				sendMongoJsonResponse(404, ['success' => false, 'error' => 'Announcement not found.']);
			}
			sendMongoJsonResponse(200, ['success' => true, 'data' => normalizeMongoValue($announcement)]);
		}

		$filter = [];
		if (isset($_GET['active'])) {
			if (!in_array($_GET['active'], ['true', 'false'], true)) {
				sendMongoJsonResponse(400, ['success' => false, 'error' => 'active must be true or false.']);
			}
			$filter['active'] = $_GET['active'] === 'true';
		}
		if (isset($_GET['routeId'])) {
			$filter['routeId'] = validateMongoInteger($_GET['routeId'], 'routeId');
		}
		$limit = isset($_GET['limit']) ? validateMongoInteger($_GET['limit'], 'limit', 1, 100) : 100;
		$data = [];
		foreach ($announcements->find($filter, ['sort' => ['publishedAt' => -1], 'limit' => $limit]) as $announcement) {
			$data[] = normalizeMongoValue($announcement);
		}
		sendMongoJsonResponse(200, ['success' => true, 'data' => $data]);
	}

	if ($method === 'POST') {
		$payload = readMongoJsonPayload();
		$allowedFields = ['title', 'message', 'routeId', 'publishedAt', 'expiresAt', 'active'];
		$unknownFields = array_diff(array_keys($payload), $allowedFields);
		if ($unknownFields !== []) {
			sendMongoJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
		}
		$announcement = createAnnouncementDocument($payload);
		validateAnnouncementDateRange($announcement);
		$result = $announcements->insertOne($announcement);
		$announcement['_id'] = $result->getInsertedId();
		sendMongoJsonResponse(201, ['success' => true, 'data' => normalizeMongoValue($announcement)]);
	}

	$id = mongoIdFromQuery($_GET['id'] ?? null);
	if ($method === 'PUT') {
		$payload = readMongoJsonPayload();
		$allowedFields = ['title', 'message', 'routeId', 'publishedAt', 'expiresAt', 'active'];
		$unknownFields = array_diff(array_keys($payload), $allowedFields);
		if ($unknownFields !== []) {
			sendMongoJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
		}
		if ($payload === []) {
			sendMongoJsonResponse(400, ['success' => false, 'error' => 'At least one field is required.']);
		}
		$existing = $announcements->findOne(['_id' => $id]);
		if ($existing === null) {
			sendMongoJsonResponse(404, ['success' => false, 'error' => 'Announcement not found.']);
		}
		$updated = createAnnouncementDocument($payload, false);
		validateAnnouncementDateRange($updated, $existing);
		$result = $announcements->updateOne(['_id' => $id], ['$set' => $updated]);
		if ($result->getMatchedCount() === 0) {
			sendMongoJsonResponse(404, ['success' => false, 'error' => 'Announcement not found.']);
		}
		$announcement = $announcements->findOne(['_id' => $id]);
		sendMongoJsonResponse(200, ['success' => true, 'data' => normalizeMongoValue($announcement)]);
	}

	$result = $announcements->deleteOne(['_id' => $id]);
	if ($result->getDeletedCount() === 0) {
		sendMongoJsonResponse(404, ['success' => false, 'error' => 'Announcement not found.']);
	}
	sendMongoJsonResponse(200, ['success' => true, 'message' => 'Announcement deleted.']);
});

function validateAnnouncementDateRange(array $document, mixed $existing = null): void
{
	$publishedAt = $document['publishedAt'] ?? ($existing['publishedAt'] ?? null);
	$expiresAt = array_key_exists('expiresAt', $document)
		? $document['expiresAt']
		: ($existing['expiresAt'] ?? null);
	if ($publishedAt instanceof MongoDB\BSON\UTCDateTime && $expiresAt instanceof MongoDB\BSON\UTCDateTime && $expiresAt->toDateTime() <= $publishedAt->toDateTime()) {
		throw new InvalidArgumentException('expiresAt must be later than publishedAt.');
	}
}

function createAnnouncementDocument(array $payload, bool $requireTitleAndMessage = true): array
{
	$document = [];
	foreach (['title', 'message'] as $field) {
		if (!array_key_exists($field, $payload)) {
			if ($requireTitleAndMessage) {
				throw new InvalidArgumentException("Missing required field: {$field}");
			}
			continue;
		}
		$value = $payload[$field];
		$maximumLength = $field === 'title' ? 200 : 5000;
		if (!is_string($value) || trim($value) === '' || strlen($value) > $maximumLength) {
			throw new InvalidArgumentException("{$field} must be a non-empty string of at most {$maximumLength} characters.");
		}
		$document[$field] = trim($value);
	}

	if (array_key_exists('routeId', $payload)) {
		$document['routeId'] = $payload['routeId'] === null ? null : validateMongoInteger($payload['routeId'], 'routeId');
	}
	if (array_key_exists('publishedAt', $payload)) {
		$document['publishedAt'] = parseMongoDate($payload['publishedAt'], 'publishedAt');
	} elseif ($requireTitleAndMessage) {
		$document['publishedAt'] = new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000));
	}
	if (array_key_exists('expiresAt', $payload)) {
		$document['expiresAt'] = $payload['expiresAt'] === null ? null : parseMongoDate($payload['expiresAt'], 'expiresAt');
	}
	if (array_key_exists('active', $payload)) {
		if (!is_bool($payload['active'])) {
			throw new InvalidArgumentException('active must be a boolean.');
		}
		$document['active'] = $payload['active'];
	} elseif ($requireTitleAndMessage) {
		$document['active'] = true;
	}

	return $document;
}
