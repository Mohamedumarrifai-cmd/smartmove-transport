<?php

require_once __DIR__ . '/constants.php';

try {
	if (!function_exists('oci_connect')) {
		throw new RuntimeException('The PHP OCI8 extension is not enabled. Enable OCI8 in XAMPP and restart Apache.');
	}

	$conn = @oci_connect(
		DB_ORACLE_USERNAME,
		DB_ORACLE_PASSWORD,
		DB_ORACLE_CONNECTION_STRING,
		DB_ORACLE_CHARSET
	);

	if ($conn === false) {
		$error = oci_error();
		throw new RuntimeException($error['message'] ?? 'Unknown Oracle connection error.');
	}
} catch (Throwable $exception) {
	error_log('Oracle database connection failed: ' . $exception->getMessage());
	throw new RuntimeException('Oracle database connection failed. Check the OCI8 extension and DB_ORACLE_* settings.', 0, $exception);
}
