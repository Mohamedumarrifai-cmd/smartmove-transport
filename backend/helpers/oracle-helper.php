<?php

function tableExists(mixed $connection, string $tableName): bool
{
	$tableName = strtoupper(trim($tableName));
	if (!preg_match('/\A[A-Z][A-Z0-9_$#]{0,127}\z/', $tableName)) {
		return false;
	}

	$sql = 'SELECT 1 FROM USER_TABLES WHERE TABLE_NAME = :table_name';
	$GLOBALS['authSqlSnippet'] = $sql;
	$statement = @oci_parse($connection, $sql);
	if ($statement === false) {
		$error = oci_error($connection);
		$message = is_array($error) ? (string) ($error['message'] ?? 'Oracle table check could not be prepared.') : 'Oracle table check could not be prepared.';
		$code = is_array($error) ? (int) ($error['code'] ?? 0) : 0;
		throw new RuntimeException($message, $code);
	}

	oci_bind_by_name($statement, ':table_name', $tableName, 128);
	if (!@oci_execute($statement)) {
		$error = oci_error($statement);
		$message = is_array($error) ? (string) ($error['message'] ?? 'Oracle table check failed.') : 'Oracle table check failed.';
		$code = is_array($error) ? (int) ($error['code'] ?? 0) : 0;
		oci_free_statement($statement);
		throw new RuntimeException($message, $code);
	}

	$exists = oci_fetch_row($statement) !== false;
	oci_free_statement($statement);
	return $exists;
}