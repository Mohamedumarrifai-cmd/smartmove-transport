<?php

require_once __DIR__ . '/constants.php';

try {
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
	throw new RuntimeException('Oracle database connection failed.', 0, $exception);
}
