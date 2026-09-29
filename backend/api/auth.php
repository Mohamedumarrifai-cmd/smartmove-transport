<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/constants.php';

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

function applyAuthCorsHeaders(): void
{
	$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
	if (!is_string($origin) || $origin === '') {
		return;
	}

	$allowedOrigins = [];
	$siteOrigin = parse_url(SITE_URL);
	if (is_array($siteOrigin) && isset($siteOrigin['scheme'], $siteOrigin['host'])) {
		$allowedOrigins[] = strtolower($siteOrigin['scheme'] . '://' . $siteOrigin['host'] . (isset($siteOrigin['port']) ? ':' . $siteOrigin['port'] : ''));
	}
	if (isset($_SERVER['HTTP_HOST'])) {
		$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$allowedOrigins[] = strtolower($requestScheme . '://' . $_SERVER['HTTP_HOST']);
	}
	$configuredOrigins = getenv('CORS_ALLOWED_ORIGINS');
	if (is_string($configuredOrigins) && $configuredOrigins !== '') {
		$allowedOrigins = array_merge($allowedOrigins, array_map('trim', explode(',', $configuredOrigins)));
	}

	if (!in_array(strtolower(rtrim($origin, '/')), array_map(static fn(string $value): string => strtolower(rtrim($value, '/')), $allowedOrigins), true)) {
		sendAuthResponse(403, ['success' => false, 'error' => 'This origin is not allowed to access the authentication service.']);
	}

	header('Access-Control-Allow-Origin: ' . $origin);
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Accept');
	header('Vary: Origin');
}

function startAuthSession(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}

	session_name(SESSION_NAME);
	session_set_cookie_params(SESSION_COOKIE_PARAMS);
	session_start();
}

function validateAuthEmail(mixed $value): string
{
	if (!is_string($value)) {
		throw new InvalidArgumentException('Enter a valid email address.');
	}
	$email = strtolower(trim($value));
	if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
		throw new InvalidArgumentException('Enter a valid email address.');
	}
	return $email;
}

function validateAuthPassword(mixed $value): string
{
	if (!is_string($value) || strlen($value) < 8 || strlen($value) > 72) {
		throw new InvalidArgumentException('Password must be between 8 and 72 characters.');
	}
	return $value;
}

function validatePassengerPhone(mixed $value): string
{
	if (!is_string($value)) {
		throw new InvalidArgumentException('Enter a valid phone number.');
	}
	$phone = trim($value);
	$digits = preg_replace('/\D/', '', $phone);
	if (strlen($phone) > 25 || strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/\A\+?[0-9][0-9\s().-]*\z/D', $phone)) {
		throw new InvalidArgumentException('Enter a valid phone number.');
	}
	return $phone;
}

function createPassenger(array $payload, mixed $connection): array
{
	$fullName = $payload['full_name'] ?? null;
	if (!is_string($fullName) || trim($fullName) === '' || strlen(trim($fullName)) > 120) {
		throw new InvalidArgumentException('Name is required and must be at most 120 characters.');
	}
	$fullName = trim($fullName);
	$phone = validatePassengerPhone($payload['phone'] ?? null);
	$email = validateAuthEmail($payload['email'] ?? null);
	$password = validateAuthPassword($payload['password'] ?? null);

	$check = @oci_parse($connection, 'SELECT passenger_id FROM PASSENGER WHERE LOWER(email) = :email');
	if ($check === false) {
		throw new RuntimeException('Passenger email check could not be prepared.');
	}
	oci_bind_by_name($check, ':email', $email, 254);
	if (!@oci_execute($check)) {
		$error = oci_error($check);
		oci_free_statement($check);
		throw new RuntimeException($error['message'] ?? 'Passenger email check failed.');
	}
	$existingPassenger = oci_fetch_assoc($check);
	oci_free_statement($check);
	if ($existingPassenger !== false) {
		throw new DomainException('An account with that email already exists.');
	}

	$passwordHash = password_hash($password, PASSWORD_BCRYPT);
	if (!is_string($passwordHash)) {
		throw new RuntimeException('Password could not be secured.');
	}
	$statement = @oci_parse(
		$connection,
		'INSERT INTO PASSENGER (full_name, email, phone, password_hash) '
		. 'VALUES (:full_name, :email, :phone, :password_hash) RETURNING passenger_id INTO :passenger_id'
	);
	if ($statement === false) {
		throw new RuntimeException('Passenger account insert could not be prepared.');
	}

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
			throw new DomainException('An account with that email already exists.');
		}
		throw new RuntimeException($error['message'] ?? 'Passenger account insert failed.');
	}
	oci_free_statement($statement);

	return [
		'passenger_id' => (int) $passengerId,
		'full_name' => $fullName,
		'email' => $email,
		'role' => 'passenger',
	];
}

function authenticatePassenger(string $email, string $password, mixed $connection): array
{
	$statement = @oci_parse(
		$connection,
		'SELECT passenger_id, full_name, email, password_hash, status '
		. 'FROM PASSENGER WHERE LOWER(email) = :email'
	);
	if ($statement === false) {
		throw new RuntimeException('Passenger login query could not be prepared.');
	}
	oci_bind_by_name($statement, ':email', $email, 254);
	if (!@oci_execute($statement)) {
		$error = oci_error($statement);
		oci_free_statement($statement);
		throw new RuntimeException($error['message'] ?? 'Passenger login query failed.');
	}
	$passenger = oci_fetch_assoc($statement);
	oci_free_statement($statement);

	if ($passenger === false || !password_verify($password, $passenger['PASSWORD_HASH']) || $passenger['STATUS'] !== 'ACTIVE') {
		throw new DomainException('Email or password is incorrect.');
	}

	return [
		'passenger_id' => (int) $passenger['PASSENGER_ID'],
		'full_name' => $passenger['FULL_NAME'],
		'email' => $passenger['EMAIL'],
		'role' => 'passenger',
	];
}

applyAuthCorsHeaders();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
	http_response_code(204);
	exit;
}
if ($method !== 'POST') {
	header('Allow: POST, OPTIONS');
	sendAuthResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
}

try {
	$payload = readAuthPayload();
	$action = $payload['action'] ?? $_POST['action'] ?? $_GET['action'] ?? null;
	if (!is_string($action)) {
		throw new InvalidArgumentException('Action must be login, register, or logout.');
	}

	switch ($action) {
		case 'logout':
			startAuthSession();
			$_SESSION = [];
			if (ini_get('session.use_cookies')) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', [
					'expires' => time() - 42000,
					'path' => $params['path'],
					'domain' => $params['domain'],
					'secure' => $params['secure'],
					'httponly' => $params['httponly'],
					'samesite' => $params['samesite'] ?? 'Lax',
				]);
			}
			session_destroy();
			sendAuthResponse(200, ['success' => true, 'message' => 'You have been signed out.']);

		case 'register':
			$email = validateAuthEmail($payload['email'] ?? null);
			$password = validateAuthPassword($payload['password'] ?? null);
			startAuthSession();
			require __DIR__ . '/../config/database-oracle.php';
			$user = createPassenger($payload, $conn);
			break;

		case 'login':
			$email = validateAuthEmail($payload['email'] ?? null);
			$password = validateAuthPassword($payload['password'] ?? null);
			startAuthSession();
			require __DIR__ . '/../config/database-oracle.php';
			$user = authenticatePassenger($email, $password, $conn);
			break;

		default:
			throw new InvalidArgumentException('Action must be login, register, or logout.');
	}

	session_regenerate_id(true);
	$_SESSION['passenger'] = $user;
	$_SESSION['passenger_id'] = $user['passenger_id'];
	$_SESSION['full_name'] = $user['full_name'];
	$_SESSION['role'] = $user['role'];
	sendAuthResponse(200, [
		'success' => true,
		'message' => 'Authentication successful.',
		'passenger_id' => $user['passenger_id'],
		'user' => $user,
	]);
} catch (JsonException $exception) {
	sendAuthResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
} catch (DomainException $exception) {
	$statusCode = $exception->getMessage() === 'An account with that email already exists.' ? 409 : 401;
	sendAuthResponse($statusCode, ['success' => false, 'error' => $exception->getMessage()]);
} catch (InvalidArgumentException $exception) {
	sendAuthResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Authentication API error: ' . $exception->getMessage());
	sendAuthResponse(500, ['success' => false, 'error' => 'Authentication service is unavailable.']);
}
