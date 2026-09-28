<?php

function sendJsonResponse(int $statusCode, array $payload): never
{
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	exit;
}

function handleOracleCrud(string $table, string $idColumn, array $fields, array $requiredFields = []): void
{
	header('Content-Type: application/json; charset=utf-8');

	try {
		require_once __DIR__ . '/../config/database-oracle.php';
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		if ($method === 'OPTIONS') {
			http_response_code(204);
			return;
		}

		if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
			header('Allow: GET, POST, PUT, DELETE, OPTIONS');
			sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
		}

		if ($method === 'GET') {
			$sql = "SELECT * FROM {$table}";
			$id = $_GET['id'] ?? null;
			if ($id !== null) {
				$id = validateRecordId($id);
				$sql .= " WHERE {$idColumn} = :id";
			}

			$statement = oci_parse($conn, $sql);
			if ($id !== null) {
				oci_bind_by_name($statement, ':id', $id);
			}
			oci_execute($statement);

			$records = [];
			while (($record = oci_fetch_assoc($statement)) !== false) {
				$records[] = array_change_key_case($record, CASE_LOWER);
			}
			oci_free_statement($statement);

			if ($id !== null && $records === []) {
				sendJsonResponse(404, ['success' => false, 'error' => 'Record not found.']);
			}
			sendJsonResponse(200, ['success' => true, 'data' => $id !== null ? $records[0] : $records]);
		}

		$payload = in_array($method, ['POST', 'PUT'], true) ? readJsonPayload() : [];
		$id = $payload[$idColumn] ?? $_GET['id'] ?? null;
		unset($payload[$idColumn]);
		$unknownFields = array_diff(array_keys($payload), $fields);
		if ($unknownFields !== []) {
			sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
		}

		if ($method === 'POST') {
			foreach ($requiredFields as $field) {
				if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
					sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
				}
			}
			if ($payload === []) {
				sendJsonResponse(400, ['success' => false, 'error' => 'At least one field is required.']);
			}

			$columns = array_keys($payload);
			$placeholders = array_map(static fn(string $field): string => ':' . $field, $columns);
			$sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ') RETURNING ' . $idColumn . ' INTO :new_id';
			$statement = oci_parse($conn, $sql);
			$bindValues = bindStatementValues($statement, $payload);
			$newId = null;
			oci_bind_by_name($statement, ':new_id', $newId, 40);
			oci_execute($statement, OCI_COMMIT_ON_SUCCESS);
			oci_free_statement($statement);
			sendJsonResponse(201, ['success' => true, 'data' => [$idColumn => $newId]]);
		}

		$id = validateRecordId($id);

		if ($method === 'PUT') {
			if ($payload === []) {
				sendJsonResponse(400, ['success' => false, 'error' => 'At least one field is required.']);
			}

			$assignments = [];
			foreach (array_keys($payload) as $field) {
				$assignments[] = "{$field} = :{$field}";
			}
			$sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . " WHERE {$idColumn} = :record_id";
			$statement = oci_parse($conn, $sql);
			$bindValues = bindStatementValues($statement, $payload);
			oci_bind_by_name($statement, ':record_id', $id);
			oci_execute($statement, OCI_COMMIT_ON_SUCCESS);
			$updated = oci_num_rows($statement);
			oci_free_statement($statement);

			if ($updated === 0) {
				sendJsonResponse(404, ['success' => false, 'error' => 'Record not found.']);
			}
			sendJsonResponse(200, ['success' => true, 'message' => 'Record updated.']);
		}

		$statement = oci_parse($conn, "DELETE FROM {$table} WHERE {$idColumn} = :id");
		oci_bind_by_name($statement, ':id', $id);
		oci_execute($statement, OCI_COMMIT_ON_SUCCESS);
		$deleted = oci_num_rows($statement);
		oci_free_statement($statement);

		if ($deleted === 0) {
			sendJsonResponse(404, ['success' => false, 'error' => 'Record not found.']);
		}
		sendJsonResponse(200, ['success' => true, 'message' => 'Record deleted.']);
	} catch (JsonException $exception) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
	} catch (InvalidArgumentException $exception) {
		sendJsonResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
	} catch (Throwable $exception) {
		error_log('Oracle API error: ' . $exception->getMessage());
		sendJsonResponse(500, ['success' => false, 'error' => 'Database operation failed.']);
	}
}

function readJsonPayload(): array
{
	$payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
	if (!is_array($payload) || array_is_list($payload)) {
		throw new InvalidArgumentException('Request body must be a JSON object.');
	}
	return $payload;
}

function validateRecordId(mixed $id): int
{
	$validatedId = filter_var($id, FILTER_VALIDATE_INT);
	if ($validatedId === false || $validatedId < 1) {
		throw new InvalidArgumentException('A positive integer id is required.');
	}
	return $validatedId;
}

function bindStatementValues(mixed $statement, array $values): array
{
	$boundValues = [];
	foreach ($values as $field => $value) {
		$boundValues[$field] = $value;
		oci_bind_by_name($statement, ':' . $field, $boundValues[$field]);
	}
	return $boundValues;
}