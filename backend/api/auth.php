<?php
/**
 * SmartMove - Authentication API
 * Handles passenger registration, login, and logout.
 *
 * Endpoint: backend/api/auth.php
 * Method: POST (JSON body)
 * Actions: register, login, logout
 */

// ============================================================
// CORS HEADERS (must be set BEFORE any output)
// ============================================================
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// INCLUDES
// ============================================================
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/oracle-helper.php';

$GLOBALS['authSqlSnippet'] = null;

// ============================================================
// HELPER: Send JSON response
// ============================================================
function sendJson(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    if (($data['success'] ?? true) === false) {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $data['error_type'] = $data['error_type'] ?? 'ApplicationError';
        $data['message'] = $data['message'] ?? $data['error'] ?? 'Request failed.';
        $data['error'] = $data['error'] ?? $data['message'];
        $data['file'] = $data['file'] ?? basename($caller['file'] ?? __FILE__);
        $data['line'] = $data['line'] ?? ($caller['line'] ?? __LINE__);
        $data['sql_snippet'] = $data['sql_snippet'] ?? ($GLOBALS['authSqlSnippet'] ?? null);
        logAuthError(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// ============================================================
// HELPER: Log errors to file
// ============================================================
function logAuthError(string $message): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/auth-error.log';
    $timestamp = date('c');
    @file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function prepareAuthStatement(mixed $connection, string $sql): mixed
{
    $GLOBALS['authSqlSnippet'] = $sql;
    $statement = @oci_parse($connection, $sql);
    if ($statement === false) {
        $error = oci_error($connection);
        throw new RuntimeException($error['message'] ?? 'Oracle could not prepare the SQL statement.', (int) ($error['code'] ?? 0));
    }
    return $statement;
}

function executeAuthStatement(mixed $statement, ?int $mode = null): void
{
    $executionMode = $mode ?? OCI_COMMIT_ON_SUCCESS;
    if (!@oci_execute($statement, $executionMode)) {
        $error = oci_error($statement);
        throw new RuntimeException($error['message'] ?? 'Oracle could not execute the SQL statement.', (int) ($error['code'] ?? 0));
    }
}

// ============================================================
// HELPER: Read JSON input
// ============================================================
function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ============================================================
// MAIN LOGIC
// ============================================================
try {
    // Get action from POST, GET, or JSON body
    $input = getJsonInput();
    $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

    if (empty($action)) {
        sendJson(400, [
            'success' => false,
            'error_type' => 'ValidationError',
            'message' => 'No action specified. Use action=register, login, or logout.'
        ]);
    }

    // --------------------------------------------------------
    // ACTION: REGISTER
    // --------------------------------------------------------
    if ($action === 'register') {
        $fullName = trim($input['full_name'] ?? $_POST['full_name'] ?? '');
        $phone    = trim($input['phone'] ?? $_POST['phone'] ?? '');
        $email    = trim($input['email'] ?? $_POST['email'] ?? '');
        $password = $input['password'] ?? $_POST['password'] ?? '';

        // Validate inputs
        $errors = [];
        if (empty($fullName)) $errors[] = 'Full name is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        if (!empty($errors)) {
            sendJson(400, [
                'success' => false,
                'error_type' => 'ValidationError',
                'message' => implode(' ', $errors)
            ]);
        }

        // Load the connection only inside the error-handled request path.
        require_once __DIR__ . '/../config/database-oracle.php';
        $conn = getOracleConnection();

        // Check if PASSENGER table exists
        if (!tableExists($conn, 'PASSENGER')) {
            throw new RuntimeException('PASSENGER table does not exist. Please run database/oracle/01_tables.sql first.');
        }

        // Check if email already exists
        $checkSql = 'SELECT COUNT(*) AS CNT FROM PASSENGER WHERE LOWER(email) = LOWER(:email)';
        $checkStmt = prepareAuthStatement($conn, $checkSql);
        try {
            oci_bind_by_name($checkStmt, ':email', $email);
            executeAuthStatement($checkStmt);
            $checkRow = oci_fetch_assoc($checkStmt);
        } finally {
            oci_free_statement($checkStmt);
        }

        if ($checkRow && (int)$checkRow['CNT'] > 0) {
            sendJson(409, [
                'success' => false,
                'error_type' => 'DuplicateError',
                'message' => 'This email is already registered. Please login instead.'
            ]);
        }

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new passenger
        $insertSql = 'INSERT INTO PASSENGER (passenger_id, full_name, email, phone, password_hash)
                      VALUES (seq_passenger_id.NEXTVAL, :full_name, :email, :phone, :password_hash)';
        $insertStmt = prepareAuthStatement($conn, $insertSql);
        try {
            oci_bind_by_name($insertStmt, ':full_name', $fullName);
            oci_bind_by_name($insertStmt, ':email', $email);
            oci_bind_by_name($insertStmt, ':phone', $phone);
            oci_bind_by_name($insertStmt, ':password_hash', $passwordHash);
            executeAuthStatement($insertStmt);
        } finally {
            oci_free_statement($insertStmt);
        }

        sendJson(201, [
            'success' => true,
            'message' => 'Account created successfully! Please login.',
            'data' => [
                'full_name' => $fullName,
                'email' => $email
            ]
        ]);
    }

    // --------------------------------------------------------
    // ACTION: LOGIN
    // --------------------------------------------------------
    if ($action === 'login') {
        $email    = trim($input['email'] ?? $_POST['email'] ?? '');
        $password = $input['password'] ?? $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            sendJson(400, [
                'success' => false,
                'error_type' => 'ValidationError',
                'message' => 'Email and password are required.'
            ]);
        }

        require_once __DIR__ . '/../config/database-oracle.php';
        $conn = getOracleConnection();

        if (!tableExists($conn, 'PASSENGER')) {
            throw new RuntimeException('PASSENGER table does not exist. Please run database/oracle/01_tables.sql first.');
        }

        $sql = 'SELECT passenger_id, full_name, email, password_hash
                FROM PASSENGER WHERE LOWER(email) = LOWER(:email)';
        $stmt = prepareAuthStatement($conn, $sql);
        try {
            oci_bind_by_name($stmt, ':email', $email);
            executeAuthStatement($stmt);
            $user = oci_fetch_assoc($stmt);
        } finally {
            oci_free_statement($stmt);
        }

        if (!$user) {
            sendJson(401, [
                'success' => false,
                'error_type' => 'AuthError',
                'message' => 'Invalid email or password.'
            ]);
        }

        if (!password_verify($password, $user['PASSWORD_HASH'])) {
            sendJson(401, [
                'success' => false,
                'error_type' => 'AuthError',
                'message' => 'Invalid email or password.'
            ]);
        }

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params(SESSION_COOKIE_PARAMS);
            session_start();
        }
        $passenger = [
            'passenger_id' => (int) $user['PASSENGER_ID'],
            'full_name' => $user['FULL_NAME'],
            'email' => $user['EMAIL'],
            'role' => 'passenger'
        ];
        $_SESSION['passenger'] = $passenger;
        $_SESSION['passenger_id'] = $user['PASSENGER_ID'];
        $_SESSION['full_name']    = $user['FULL_NAME'];
        $_SESSION['email']        = $user['EMAIL'];
        $_SESSION['role']         = 'passenger';
        session_regenerate_id(true);

        sendJson(200, [
            'success' => true,
            'message' => 'Login successful.',
            'user' => $passenger,
            'data' => [
                'passenger_id' => $user['PASSENGER_ID'],
                'full_name'    => $user['FULL_NAME'],
                'email'        => $user['EMAIL'],
                'role'         => 'passenger',
                'redirect'     => 'user/dashboard.php'
            ]
        ]);
    }

    // --------------------------------------------------------
    // ACTION: LOGOUT
    // --------------------------------------------------------
    if ($action === 'logout') {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params(SESSION_COOKIE_PARAMS);
            session_start();
        }
        session_unset();
        session_destroy();

        sendJson(200, [
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    // --------------------------------------------------------
    // Unknown action
    // --------------------------------------------------------
    sendJson(400, [
        'success' => false,
        'error_type' => 'ValidationError',
        'message' => "Unknown action: '$action'. Use register, login, or logout."
    ]);

} catch (Throwable $e) {
	$message = $e->getMessage();
        if (str_contains($message, 'PASSENGER table does not exist')) {
            $errorType = 'SchemaError';
        } elseif (preg_match('/ORA-\d{5}/i', $message) === 1 || (int) $e->getCode() > 0) {
            $errorType = 'OracleError';
        } else {
            $errorType = get_class($e);
        }

	// Return the exact exception location and SQL for actionable debugging.
    sendJson(500, [
        'success' => false,
        'error_type' => $errorType,
        'message' => $message,
        'error' => $message,
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'sql_snippet' => $GLOBALS['authSqlSnippet'] ?? null
    ]);
}