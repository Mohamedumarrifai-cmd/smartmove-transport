<?php

require_once __DIR__ . '/constants.php';

try {
	if (!function_exists('oci_connect')) {
		throw new RuntimeException('OCI8 is unavailable in this PHP runtime. Enable the OCI8 extension in the php.ini used by XAMPP and restart Apache.');
	}

	// DB_ORACLE_CONNECTION_STRING uses Oracle Easy Connect; the charset keeps passenger names and emails in UTF-8.
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
	throw new RuntimeException('Oracle database connection failed. ' . $exception->getMessage(), 0, $exception);
}
