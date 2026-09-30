<?php

require_once __DIR__ . '/constants.php';

// Return the request-wide cached Oracle connection, creating it only once.
function getOracleConnection()
{
	// A static variable retains this connection across calls during the current PHP request.
	static $connection = null;

	// Reuse the live request connection instead of opening another Oracle session.
	if ($connection !== null) {
		return $connection;
	}

	try {
		// Fail with an actionable setup message before calling an unavailable OCI8 function.
		if (!function_exists('oci_connect')) {
			throw new Exception('OCI8 PHP extension is not enabled. Open D:\\xampp\\php\\php.ini and uncomment extension=oci8_19, then restart Apache.');
		}

		// Read connection credentials from the project's centralized environment-aware constants.
		$host = DB_ORACLE_HOST;
		$port = DB_ORACLE_PORT;
		$service = DB_ORACLE_SERVICE;
		$username = DB_ORACLE_USERNAME;
		$password = DB_ORACLE_PASSWORD;

		// Build Oracle Easy Connect dynamically from host, port, and service name.
		$connectionString = $host . ':' . $port . '/' . $service;

		// Open the Oracle connection using the required UTF-8 database character set.
		$connection = @oci_connect($username, $password, $connectionString, 'AL32UTF8');

		// Convert OCI8's connection-level error into an exception with its original Oracle message.
		if ($connection === false) {
			$error = oci_error();
			$message = is_array($error) ? (string) ($error['message'] ?? '') : '';
			$oracleCode = is_array($error) ? (int) ($error['code'] ?? 0) : 0;
			if (trim($message) === '') {
				$message = 'Unknown Oracle connection error.';
			}
			throw new Exception($message, $oracleCode);
		}

		// Return the newly opened connection for the current request.
		return $connection;
	} catch (Throwable $exception) {
		// Preserve the original message and cause so callers can report or log the exact failure.
		throw new Exception($exception->getMessage(), (int) $exception->getCode(), $exception);
	}
}

// Preserve the legacy include contract used by existing APIs that reference the $conn variable.
$conn = getOracleConnection();
