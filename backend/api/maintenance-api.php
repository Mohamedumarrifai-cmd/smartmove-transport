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
		$sql = 'SELECT * FROM MAINTENANCE';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' WHERE maintenance_id = :id';
		}
		$statement = oci_parse($conn, $sql);
		if ($id !== null) {
			oci_bind_by_name($statement, ':id', $id);
		}
		if (!@oci_execute($statement)) {
			$error = oci_error($statement);
			throw new RuntimeException($error['message'] ?? 'Maintenance query failed.');
		}

		$records = [];
		while (($record = oci_fetch_assoc($statement)) !== false) {
			$records[] = array_change_key_case($record, CASE_LOWER);
		}
		oci_free_statement($statement);

		if ($id !== null && $records === []) {
			sendJsonResponse(404, ['success' => false, 'error' => 'Maintenance record not found.']);
		}
		sendJsonResponse(200, ['success' => true, 'data' => $id !== null ? $records[0] : $records]);
	}

	if ($method !== 'POST') {
		header('Allow: GET, POST, OPTIONS');
		sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	$payload = readJsonPayload();
	$allowedFields = ['vehicle_id', 'description', 'scheduled_date', 'completed_date', 'cost', 'status'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['vehicle_id', 'description', 'scheduled_date'] as $field) {
		if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
			sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$vehicleId = validateRecordId($payload['vehicle_id']);
	$description = $payload['description'];
	if (!is_string($description) || trim($description) === '' || strlen($description) > 500) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Description must be a non-empty string of at most 500 characters.']);
	}
	$scheduledDate = validateApiDate($payload['scheduled_date'], 'scheduled_date');
	$completedDate = null;
	if (array_key_exists('completed_date', $payload) && $payload['completed_date'] !== null && $payload['completed_date'] !== '') {
		$completedDate = validateApiDate($payload['completed_date'], 'completed_date');
		if ($completedDate < $scheduledDate) {
			sendJsonResponse(400, ['success' => false, 'error' => 'completed_date cannot be earlier than scheduled_date.']);
		}
	}

	$cost = $payload['cost'] ?? 0;
	if (!is_numeric($cost) || !is_finite((float) $cost) || (float) $cost < 0) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Cost must be a non-negative number.']);
	}
	$statusValue = $payload['status'] ?? 'SCHEDULED';
	if (!is_string($statusValue)) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Status must be a string.']);
	}
	$status = strtoupper($statusValue);
	$allowedStatuses = ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];
	if (!in_array($status, $allowedStatuses, true)) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unsupported maintenance status.']);
	}
	if ($status === 'COMPLETED' && $completedDate === null) {
		sendJsonResponse(400, ['success' => false, 'error' => 'completed_date is required when status is COMPLETED.']);
	}

	$sql = 'INSERT INTO MAINTENANCE (vehicle_id, description, scheduled_date, completed_date, cost, status) '
		. 'VALUES (:vehicle_id, :description, TO_DATE(:scheduled_date, \'YYYY-MM-DD\'), '
		. 'TO_DATE(:completed_date, \'YYYY-MM-DD\'), :cost, :status) '
		. 'RETURNING maintenance_id INTO :maintenance_id';
	$statement = oci_parse($conn, $sql);
	oci_bind_by_name($statement, ':vehicle_id', $vehicleId);
	oci_bind_by_name($statement, ':description', $description, 500);
	oci_bind_by_name($statement, ':scheduled_date', $scheduledDate, 10);
	oci_bind_by_name($statement, ':completed_date', $completedDate, 10);
	oci_bind_by_name($statement, ':cost', $cost);
	oci_bind_by_name($statement, ':status', $status, 20);
	$maintenanceId = null;
	oci_bind_by_name($statement, ':maintenance_id', $maintenanceId, 40);

	if (!@oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
		$error = oci_error($statement);
		if ((int) ($error['code'] ?? 0) === 2291) {
			oci_free_statement($statement);
			sendJsonResponse(404, ['success' => false, 'error' => 'Vehicle was not found.']);
		}
		error_log('Maintenance API error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
		oci_free_statement($statement);
		sendJsonResponse(500, ['success' => false, 'error' => 'Maintenance record could not be created.']);
	}

	oci_free_statement($statement);
	sendJsonResponse(201, ['success' => true, 'data' => ['maintenance_id' => $maintenanceId]]);
} catch (JsonException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
} catch (InvalidArgumentException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Maintenance API error: ' . $exception->getMessage());
	sendJsonResponse(500, ['success' => false, 'error' => 'Maintenance operation failed.']);
}

function validateApiDate(mixed $value, string $field): string
{
	if (!is_string($value)) {
		throw new InvalidArgumentException("{$field} must use YYYY-MM-DD format.");
	}
	$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
	$errors = DateTimeImmutable::getLastErrors();
	if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
		throw new InvalidArgumentException("{$field} must use YYYY-MM-DD format.");
	}
	return $value;
}
