<?php

require_once __DIR__ . '/constants.php';

try {
	$autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
	if (!is_file($autoloadPath)) {
		throw new RuntimeException('Composer autoloader not found. Run composer install.');
	}

	require_once $autoloadPath;

	$mongoClient = new MongoDB\Client(DB_MONGODB_URI);
	$mongoDatabase = $mongoClient->selectDatabase(DB_MONGODB_DATABASE);
	$mongoDatabase->command(['ping' => 1])->toArray();
} catch (Throwable $exception) {
	error_log('MongoDB connection failed: ' . $exception->getMessage());
	throw new RuntimeException('MongoDB connection failed.', 0, $exception);
}
