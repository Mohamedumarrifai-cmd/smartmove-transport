<?php
/**
 * SmartMove - Global Configuration Constants
 * 
 * This file defines all global constants used across the application.
 * Values can be overridden using environment variables (useful for production).
 */

// ============================================================
// SITE CONFIGURATION
// ============================================================
define('SITE_NAME', getenv('SITE_NAME') !== false ? getenv('SITE_NAME') : 'SmartMove');
define('SITE_URL', rtrim(getenv('SITE_URL') !== false ? getenv('SITE_URL') : 'http://localhost/SmartMove/smartmove-transport/frontend', '/'));

// ============================================================
// ORACLE DATABASE CONFIGURATION
// ============================================================
define('DB_ORACLE_HOST', getenv('DB_ORACLE_HOST') !== false ? getenv('DB_ORACLE_HOST') : 'localhost');
define('DB_ORACLE_PORT', getenv('DB_ORACLE_PORT') !== false ? getenv('DB_ORACLE_PORT') : '1521');
define(
	'DB_ORACLE_SERVICE',
	getenv('DB_ORACLE_SERVICE') !== false
		? getenv('DB_ORACLE_SERVICE')
		: (getenv('DB_ORACLE_SERVICE_NAME') !== false ? getenv('DB_ORACLE_SERVICE_NAME') : 'XEPDB1')
);
define('DB_ORACLE_SERVICE_NAME', DB_ORACLE_SERVICE);
define('DB_ORACLE_USERNAME', getenv('DB_ORACLE_USERNAME') !== false ? getenv('DB_ORACLE_USERNAME') : 'smartmove');
define('DB_ORACLE_PASSWORD', getenv('DB_ORACLE_PASSWORD') !== false ? getenv('DB_ORACLE_PASSWORD') : 'smartmove123');

// Optional: Full connection string override (used if environment variable is set)
$oracleConnectionOverride = getenv('DB_ORACLE_CONNECTION_STRING');
define(
	'DB_ORACLE_CONNECTION_STRING',
	$oracleConnectionOverride !== false && $oracleConnectionOverride !== ''
		? $oracleConnectionOverride
		: sprintf('//%s:%s/%s', DB_ORACLE_HOST, DB_ORACLE_PORT, DB_ORACLE_SERVICE)
);
define('DB_ORACLE_CHARSET', getenv('DB_ORACLE_CHARSET') !== false ? getenv('DB_ORACLE_CHARSET') : 'AL32UTF8');

// ============================================================
// MONGODB CONFIGURATION
// ============================================================
define('DB_MONGODB_URI', getenv('DB_MONGODB_URI') !== false ? getenv('DB_MONGODB_URI') : 'mongodb://127.0.0.1:27017');
define('DB_MONGODB_DATABASE', getenv('DB_MONGODB_DATABASE') !== false ? getenv('DB_MONGODB_DATABASE') : 'smartmove_db');

// ============================================================
// SESSION CONFIGURATION
// ============================================================
define('SESSION_NAME', getenv('SESSION_NAME') !== false ? getenv('SESSION_NAME') : 'PHPSESSID');
define('SESSION_COOKIE_PARAMS', [
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	'httponly' => true,
	'samesite' => 'Lax',
]);