<?php

function runMongoApi(array $allowedMethods, callable $handler): void
{
	header('Content-Type: application/json; charset=utf-8');
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ($method === 'OPTIONS') {
		http_response_code(204);
		return;
	}
	if (!in_array($method, $allowedMethods, true)) {
		header('Allow: ' . implode(', ', array_merge($allowedMethods, ['OPTIONS'])));
		sendMongoJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	try {
		require_once __DIR__ . '/../config/database-mongodb.php';
		$handler($mongoDatabase, $method);
	} catch (JsonException $exception) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
	} catch (InvalidArgumentException $exception) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
	} catch (Throwable $exception) {
		error_log('MongoDB API error: ' . $exception->getMessage());
		sendMongoJsonResponse(500, ['success' => false, 'error' => 'Database operation failed.']);
	}
}

function sendMongoJsonResponse(int $statusCode, array $payload): never
{
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	exit;
}

function readMongoJsonPayload(): array
{
	$payload = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
	if (!$payload instanceof stdClass) {
		throw new InvalidArgumentException('Request body must be a JSON object.');
	}
	return get_object_vars($payload);
}

function normalizeMongoValue(mixed $value): mixed
{
	if ($value instanceof MongoDB\BSON\ObjectId) {
		return (string) $value;
	}
	if ($value instanceof MongoDB\BSON\UTCDateTime) {
		return $value->toDateTime()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
	}
	if (is_array($value) || $value instanceof Traversable) {
		$normalized = [];
		foreach ($value as $key => $item) {
			$normalized[$key] = normalizeMongoValue($item);
		}
		return $normalized;
	}
	return $value;
}

function mongoIdFromQuery(mixed $id): MongoDB\BSON\ObjectId|string
{
	if (!is_string($id) || $id === '' || strlen($id) > 100) {
		throw new InvalidArgumentException('A valid id is required.');
	}
	return MongoDB\BSON\ObjectId::isValid($id) ? new MongoDB\BSON\ObjectId($id) : $id;
}

function parseMongoDate(mixed $value, string $field): MongoDB\BSON\UTCDateTime
{
	if (!is_string($value) || trim($value) === '') {
		throw new InvalidArgumentException("{$field} must be a valid date string.");
	}

	try {
		$date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
	} catch (Throwable $exception) {
		throw new InvalidArgumentException("{$field} must be a valid date string.");
	}

	$milliseconds = ($date->getTimestamp() * 1000) + (int) $date->format('v');
	return new MongoDB\BSON\UTCDateTime($milliseconds);
}

function validateMongoInteger(mixed $value, string $field, int $minimum = 1, ?int $maximum = null): int
{
	$integer = filter_var($value, FILTER_VALIDATE_INT);
	if ($integer === false || $integer < $minimum || ($maximum !== null && $integer > $maximum)) {
		throw new InvalidArgumentException("{$field} must be a valid integer.");
	}
	return $integer;
}