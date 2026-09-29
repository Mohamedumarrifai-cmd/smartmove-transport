<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

header('Content-Type: application/json; charset=utf-8');

try {
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ($method === 'OPTIONS') {
		http_response_code(204);
		exit;
	}
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$passenger = $_SESSION['passenger'] ?? null;
	$passengerId = is_array($passenger)
		? filter_var($passenger['passenger_id'] ?? null, FILTER_VALIDATE_INT)
		: false;
	if ($passengerId === false || $passengerId < 1) {
		sendJsonResponse(401, ['success' => false, 'error' => 'Sign in to access your payments.']);
	}
	require_once __DIR__ . '/../config/database-oracle.php';

	if ($method === 'GET') {
		$sql = 'SELECT p.* FROM PAYMENT p JOIN TICKET tk ON tk.ticket_id = p.ticket_id WHERE tk.passenger_id = :passenger_id';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' AND p.payment_id = :id';
		}
		$statement = oci_parse($conn, $sql);
		oci_bind_by_name($statement, ':passenger_id', $passengerId);
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
	$ownershipStatement = @oci_parse($conn, 'SELECT ticket_id FROM TICKET WHERE ticket_id = :ticket_id AND passenger_id = :passenger_id');
	if ($ownershipStatement === false) {
		throw new RuntimeException('Ticket ownership check could not be prepared.');
	}
	oci_bind_by_name($ownershipStatement, ':ticket_id', $ticketId);
	oci_bind_by_name($ownershipStatement, ':passenger_id', $passengerId);
	if (!@oci_execute($ownershipStatement)) {
		$error = oci_error($ownershipStatement);
		oci_free_statement($ownershipStatement);
		throw new RuntimeException($error['message'] ?? 'Ticket ownership check failed.');
	}
	$ownedTicket = oci_fetch_assoc($ownershipStatement);
	oci_free_statement($ownershipStatement);
	if ($ownedTicket === false) {
		sendJsonResponse(404, ['success' => false, 'error' => 'Ticket was not found.']);
	}
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
