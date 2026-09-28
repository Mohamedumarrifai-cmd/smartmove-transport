<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

header('Content-Type: application/json; charset=utf-8');

try {
	require_once __DIR__ . '/../config/database-oracle.php';
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ($method === 'OPTIONS') {
		http_response_code(204);
		exit;
	}

	if ($method === 'GET') {
		$sql = 'SELECT * FROM PAYMENT';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' WHERE payment_id = :id';
		}
		$statement = oci_parse($conn, $sql);
		if ($id !== null) {
			oci_bind_by_name($statement, ':id', $id);
		}
		if (!@oci_execute($statement)) {
			$error = oci_error($statement);
			throw new RuntimeException($error['message'] ?? 'Payment query failed.');
		}

		$payments = [];
		while (($payment = oci_fetch_assoc($statement)) !== false) {
			$payments[] = array_change_key_case($payment, CASE_LOWER);
		}
		oci_free_statement($statement);

		if ($id !== null && $payments === []) {
			sendJsonResponse(404, ['success' => false, 'error' => 'Payment not found.']);
		}
		sendJsonResponse(200, ['success' => true, 'data' => $id !== null ? $payments[0] : $payments]);
	}

	if ($method !== 'POST') {
		header('Allow: GET, POST, OPTIONS');
		sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	$payload = readJsonPayload();
	$allowedFields = ['ticket_id', 'amount', 'payment_method', 'transaction_reference'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['ticket_id', 'amount', 'payment_method'] as $field) {
		if (!array_key_exists($field, $payload) || $payload[$field] === '') {
			sendJsonResponse(400, ['success' => false, 'error' => "Missing required field: {$field}"]);
		}
	}

	$ticketId = validateRecordId($payload['ticket_id']);
	$amount = $payload['amount'];
	if (!is_string($amount) && !is_int($amount) && !is_float($amount)) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Amount must be a positive number with at most two decimal places.']);
	}
	$amount = (string) $amount;
	if (!preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/D', $amount) || (float) $amount <= 0) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Amount must be a positive number with at most two decimal places.']);
	}

	if (!is_string($payload['payment_method'])) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Payment method must be a string.']);
	}
	$paymentMethod = strtoupper(trim($payload['payment_method']));
	$allowedMethods = ['CARD', 'CASH', 'BANK_TRANSFER', 'MOBILE_MONEY'];
	if (!in_array($paymentMethod, $allowedMethods, true)) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Unsupported payment method.']);
	}

	$transactionReference = $payload['transaction_reference'] ?? null;
	if ($transactionReference !== null && (!is_string($transactionReference) || strlen($transactionReference) > 100)) {
		sendJsonResponse(400, ['success' => false, 'error' => 'Transaction reference must be a string of at most 100 characters.']);
	}

	$statement = oci_parse($conn, 'BEGIN sp_process_payment(:ticket_id, :amount, :payment_method, :transaction_reference, :payment_id); END;');
	oci_bind_by_name($statement, ':ticket_id', $ticketId);
	oci_bind_by_name($statement, ':amount', $amount);
	oci_bind_by_name($statement, ':payment_method', $paymentMethod);
	oci_bind_by_name($statement, ':transaction_reference', $transactionReference, 100);
	$paymentId = null;
	oci_bind_by_name($statement, ':payment_id', $paymentId, 40);

	if (!@oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
		$error = oci_error($statement);
		$businessErrors = [
			1 => [409, 'Transaction reference has already been used.'],
			20021 => [422, 'Ticket is not eligible for payment.'],
			20022 => [422, 'Payment amount must match the ticket fare.'],
			20023 => [409, 'Ticket has already been paid.'],
			20024 => [404, 'Ticket was not found.'],
		];
		$code = (int) ($error['code'] ?? 0);
		if (isset($businessErrors[$code])) {
			[$status, $message] = $businessErrors[$code];
			oci_free_statement($statement);
			sendJsonResponse($status, ['success' => false, 'error' => $message]);
		}
		error_log('Payment API error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
		oci_free_statement($statement);
		sendJsonResponse(500, ['success' => false, 'error' => 'Payment could not be processed.']);
	}

	oci_free_statement($statement);
	sendJsonResponse(201, ['success' => true, 'data' => ['payment_id' => $paymentId]]);
} catch (JsonException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => 'Request body must contain valid JSON.']);
} catch (InvalidArgumentException $exception) {
	sendJsonResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Payment API error: ' . $exception->getMessage());
	sendJsonResponse(500, ['success' => false, 'error' => 'Payment operation failed.']);
}
