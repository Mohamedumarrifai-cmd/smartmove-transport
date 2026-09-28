<?php

require_once __DIR__ . '/../helpers/mongodb-api.php';

runMongoApi(['GET', 'POST'], static function (MongoDB\Database $database, string $method): void {
	$vehicles = $database->selectCollection('VehicleImages');

	if ($method === 'GET') {
		if (isset($_GET['download'])) {
			$filename = $_GET['download'];
			if (!is_string($filename) || !preg_match('/\A[a-zA-Z0-9_-]+\.(?:jpg|png|webp|pdf)\z/i', $filename)) {
				sendMongoJsonResponse(400, ['success' => false, 'error' => 'Invalid document filename.']);
			}
			$relativePath = 'backend/uploads/vehicles/' . $filename;
			$vehicle = $vehicles->findOne(['documents.path' => $relativePath]);
			if ($vehicle === null) {
				sendMongoJsonResponse(404, ['success' => false, 'error' => 'Document not found.']);
			}

			$uploadRoot = realpath(__DIR__ . '/../uploads/vehicles');
			$filePath = $uploadRoot === false ? false : realpath($uploadRoot . DIRECTORY_SEPARATOR . $filename);
			if ($filePath === false || !str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
				sendMongoJsonResponse(404, ['success' => false, 'error' => 'Document file is missing.']);
			}

			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mimeType = $finfo->file($filePath) ?: 'application/octet-stream';
			header('Content-Type: ' . $mimeType);
			header('Content-Length: ' . filesize($filePath));
			header('Content-Disposition: ' . (str_starts_with($mimeType, 'image/') ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
			readfile($filePath);
			exit;
		}

		$filter = [];
		$vehicleId = null;
		if (isset($_GET['vehicleId'])) {
			$vehicleId = validateMongoInteger($_GET['vehicleId'], 'vehicleId');
			$filter['vehicleId'] = $vehicleId;
		}
		$data = [];
		foreach ($vehicles->find($filter, ['sort' => ['vehicleId' => 1]]) as $vehicle) {
			$record = normalizeMongoValue($vehicle);
			if (isset($_GET['kind'])) {
				$kind = $_GET['kind'];
				$record['documents'] = array_values(array_filter(
					$record['documents'] ?? [],
					static fn(array $document): bool => ($document['kind'] ?? null) === $kind
				));
			}
			$data[] = $record;
		}
		if ($vehicleId !== null && $data === []) {
			sendMongoJsonResponse(404, ['success' => false, 'error' => 'Vehicle documents not found.']);
		}
		sendMongoJsonResponse(200, ['success' => true, 'data' => $data]);
	}

	$vehicleId = validateMongoInteger($_POST['vehicleId'] ?? null, 'vehicleId');
	$kind = $_POST['kind'] ?? 'vehicle_image';
	if (!is_string($kind) || !preg_match('/\A[a-z][a-z0-9_-]{0,39}\z/', $kind)) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Document kind is invalid.']);
	}
	$file = $_FILES['file'] ?? null;
	if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'A valid uploaded file is required.']);
	}
	if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'File size must be between 1 byte and 10 MB.']);
	}

	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$extensionByMime = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp',
		'application/pdf' => 'pdf',
	];
	$mimeType = $finfo->file($file['tmp_name']);
	if (!isset($extensionByMime[$mimeType])) {
		sendMongoJsonResponse(400, ['success' => false, 'error' => 'Only JPEG, PNG, WebP, and PDF files are accepted.']);
	}

	$vehicle = $vehicles->findOne(['vehicleId' => $vehicleId]);
	if ($vehicle === null) {
		sendMongoJsonResponse(404, ['success' => false, 'error' => 'Vehicle record not found.']);
	}

	$uploadDirectory = __DIR__ . '/../uploads/vehicles';
	if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
		throw new RuntimeException('Unable to create the vehicle upload directory.');
	}
	$filename = $vehicleId . '-' . bin2hex(random_bytes(16)) . '.' . $extensionByMime[$mimeType];
	$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
	if (!move_uploaded_file($file['tmp_name'], $destination)) {
		throw new RuntimeException('Unable to store the uploaded vehicle document.');
	}

	$relativePath = 'backend/uploads/vehicles/' . $filename;
	$document = [
		'kind' => $kind,
		'path' => $relativePath,
		'uploadedAt' => new MongoDB\BSON\UTCDateTime((int) floor(microtime(true) * 1000)),
	];
	try {
		$update = $vehicles->updateOne(['vehicleId' => $vehicleId], ['$push' => ['documents' => $document]]);
		if ($update->getMatchedCount() === 0) {
			unlink($destination);
			sendMongoJsonResponse(404, ['success' => false, 'error' => 'Vehicle record not found.']);
		}
	} catch (Throwable $exception) {
		if (is_file($destination)) {
			unlink($destination);
		}
		throw $exception;
	}

	sendMongoJsonResponse(201, [
		'success' => true,
		'data' => [
			'vehicleId' => $vehicleId,
			'document' => normalizeMongoValue($document),
			'downloadUrl' => 'document-api.php?download=' . rawurlencode($filename),
		],
	]);
});
