<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'httponly' => true,
		'samesite' => 'Lax',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	]);
	session_start();
}

function sendAuthResponse(int $statusCode, array $payload): never
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	exit;
}

function readAuthPayload(): array
{
	$payload = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
	if (!$payload instanceof stdClass) {
		throw new InvalidArgumentException('Request body must be a JSON object.');
	}
	return get_object_vars($payload);
}

try {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
		header('Allow: POST');
		sendAuthResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	$payload = readAuthPayload();
	$action = $payload['action'] ?? null;
	if (!in_array($action, ['login', 'register'], true)) {
		sendAuthResponse(400, ['success' => false, 'error' => 'Action must be login or register.']);
	}

	$email = $payload['email'] ?? null;
	$password = $payload['password'] ?? null;
	if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
		sendAuthResponse(400, ['success' => false, 'error' => 'Enter a valid email address.']);
	}
	if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
		sendAuthResponse(400, ['success' => false, 'error' => 'Password must be between 8 and 72 characters.']);
	}
	$email = strtolower(trim($email));

	require_once __DIR__ . '/../config/database-oracle.php';

	if ($action === 'register') {
		$fullName = $payload['full_name'] ?? null;
		$phone = $payload['phone'] ?? null;
		if (!is_string($fullName) || trim($fullName) === '' || strlen(trim($fullName)) > 120) {
			sendAuthResponse(400, ['success' => false, 'error' => 'Name is required and must be at most 120 characters.']);
		}
		if (!is_string($phone) || trim($phone) === '' || strlen(trim($phone)) > 25) {
			sendAuthResponse(400, ['success' => false, 'error' => 'Phone number is required and must be at most 25 characters.']);
		}

		$passwordHash = password_hash($password, PASSWORD_DEFAULT);
		$statement = oci_parse(
			$conn,
			'INSERT INTO PASSENGER (full_name, email, phone, password_hash) '
			. 'VALUES (:full_name, :email, :phone, :password_hash) RETURNING passenger_id INTO :passenger_id'
		);
		oci_bind_by_name($statement, ':full_name', $fullName, 120);
		oci_bind_by_name($statement, ':email', $email, 254);
		oci_bind_by_name($statement, ':phone', $phone, 25);
		oci_bind_by_name($statement, ':password_hash', $passwordHash, 255);
		$passengerId = null;
		oci_bind_by_name($statement, ':passenger_id', $passengerId, 40);

		if (!@oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
			$error = oci_error($statement);
			oci_free_statement($statement);
			if ((int) ($error['code'] ?? 0) === 1) {
				sendAuthResponse(409, ['success' => false, 'error' => 'An account with that email already exists.']);
			}
			error_log('Registration API error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
			sendAuthResponse(500, ['success' => false, 'error' => 'Account could not be created.']);
		}
		oci_free_statement($statement);
		$user = [
			'passenger_id' => (int) $passengerId,
			'full_name' => trim($fullName),
			'email' => $email,
		];
	} else {
		$statement = oci_parse(
			$conn,
			'SELECT passenger_id, full_name, email, password_hash, status '
			. 'FROM PASSENGER WHERE LOWER(email) = :email'
		);
		oci_bind_by_name($statement, ':email', $email, 254);
		if (!@oci_execute($statement)) {
			$error = oci_error($statement);
			oci_free_statement($statement);
			throw new RuntimeException($error['message'] ?? 'Login query failed.');
		}
		$passenger = oci_fetch_assoc($statement);
		oci_free_statement($statement);

		if ($passenger === false || !password_verify($password, $passenger['PASSWORD_HASH']) || $passenger['STATUS'] !== 'ACTIVE') {
			sendAuthResponse(401, ['success' => false, 'error' => 'Email or password is incorrect.']);
		}
		$user = [
			'passenger_id' => (int) $passenger['PASSENGER_ID'],
			'full_name' => $passenger['FULL_NAME'],
			'email' => $passenger['EMAIL'],
		];
	}

	session_regenerate_id(true);
	$_SESSION['passenger'] = $user;
	sendAuthResponse(200, ['success' => true, 'message' => 'Authentication successful.', 'user' => $user]);
} catch (JsonException $exception) {
	sendAuthResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
} catch (InvalidArgumentException $exception) {
	sendAuthResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Authentication API error: ' . $exception->getMessage());
	sendAuthResponse(500, ['success' => false, 'error' => 'Authentication service is unavailable.']);
}
