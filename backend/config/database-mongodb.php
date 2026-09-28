<?php

require_once __DIR__ . '/constants.php';

try {
	$autoloadCandidates = [
		dirname(__DIR__, 2) . '/vendor/autoload.php',
		dirname(__DIR__) . '/vendor/autoload.php',
	];
	$autoloadPath = null;
	foreach ($autoloadCandidates as $candidate) {
		if (is_file($candidate)) {
			$autoloadPath = $candidate;
			break;
		}
	}
	if ($autoloadPath === null) {
		throw new RuntimeException('Composer autoloader not found. Run composer install.');
	}

	require_once $autoloadPath;

	if (!class_exists(MongoDB\Client::class)) {
		throw new RuntimeException('MongoDB PHP library not found. Run composer install.');
	}

	$mongoClient = new MongoDB\Client(DB_MONGODB_URI);
	$mongoDatabase = $mongoClient->selectDatabase(DB_MONGODB_DATABASE);
	$mongoDatabase->command(['ping' => 1])->toArray();
} catch (Throwable $exception) {
	error_log('MongoDB connection failed: ' . $exception->getMessage());
	throw new RuntimeException('MongoDB connection failed. Check the MongoDB PHP extension, Composer library, and DB_MONGODB_* settings.', 0, $exception);
}

return $mongoClient;
