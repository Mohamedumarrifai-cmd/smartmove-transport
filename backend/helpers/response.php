<?php

function sendResponse(int $status_code, mixed $data): never
{
	http_response_code($status_code);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(
		['success' => true, 'data' => $data],
		JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
	);
	exit;
}

function sendError(int $status_code, string $message): never
{
	http_response_code($status_code);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(
		['success' => false, 'error' => $message],
		JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
	);
	exit;
}
