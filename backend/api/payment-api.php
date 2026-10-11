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
		sendJsonResponse(401, ['success' => false, 'message' => 'Sign in to access your payments.', 'error' => 'Sign in to access your payments.']);
	}
	require_once __DIR__ . '/../config/database-oracle.php';

	if ($method === 'GET') {
		$sql = 'SELECT p.payment_id, p.ticket_id, p.amount, p.payment_method, p.transaction_reference, p.status, '
			. 'TO_CHAR(p.paid_at, \'YYYY-MM-DD"T"HH24:MI:SS\') AS paid_at '
			. 'FROM PAYMENT p JOIN TICKET tk ON tk.ticket_id = p.ticket_id WHERE tk.passenger_id = :passenger_id';
		$id = $_GET['id'] ?? null;
		if ($id !== null) {
			$id = validateRecordId($id);
			$sql .= ' AND p.payment_id = :id';
		}
		$sql .= ' ORDER BY p.created_at DESC';
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
			sendJsonResponse(404, ['success' => false, 'message' => 'Payment not found.', 'error' => 'Payment not found.']);
		}
		sendJsonResponse(200, [
			'success' => true,
			'message' => 'Payment details loaded.',
			'data' => $id !== null ? $payments[0] : $payments,
		]);
	}

	if ($method !== 'POST') {
		header('Allow: GET, POST, OPTIONS');
		sendJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.', 'error' => 'Method not allowed.']);
	}

	$action = $_GET['action'] ?? 'create';
	if ($action !== 'create') {
		sendJsonResponse(400, ['success' => false, 'message' => 'Unsupported payment action.', 'error' => 'Unsupported payment action.']);
	}
	$payload = readJsonPayload();
	$allowedFields = ['ticket_ids', 'ticket_id', 'amount', 'payment_method', 'transaction_reference'];
	$unknownFields = array_diff(array_keys($payload), $allowedFields);
	if ($unknownFields !== []) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Unknown field: ' . reset($unknownFields), 'error' => 'Unknown field: ' . reset($unknownFields)]);
	}
	foreach (['amount', 'payment_method'] as $field) {
		if (!array_key_exists($field, $payload) || $payload[$field] === '') {
			sendJsonResponse(400, ['success' => false, 'message' => "Missing required field: {$field}", 'error' => "Missing required field: {$field}"]);
		}
	}
	if (isset($payload['ticket_ids'])) {
		if (!is_array($payload['ticket_ids']) || $payload['ticket_ids'] === [] || count($payload['ticket_ids']) > 6) {
			sendJsonResponse(400, ['success' => false, 'message' => 'One to six ticket IDs are required.', 'error' => 'One to six ticket IDs are required.']);
		}
		$ticketIds = array_map('validateRecordId', $payload['ticket_ids']);
	} elseif (isset($payload['ticket_id'])) {
		$ticketIds = [validateRecordId($payload['ticket_id'])];
	} else {
		sendJsonResponse(400, ['success' => false, 'message' => 'At least one ticket ID is required.', 'error' => 'At least one ticket ID is required.']);
	}
	if (count(array_unique($ticketIds)) !== count($ticketIds)) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Ticket IDs must be unique.', 'error' => 'Ticket IDs must be unique.']);
	}

	$amount = $payload['amount'];
	if ((!is_string($amount) && !is_int($amount) && !is_float($amount))
		|| !preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/D', (string) $amount)
		|| (float) $amount <= 0
	) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Amount must be a positive number with at most two decimal places.', 'error' => 'Amount must be a positive number with at most two decimal places.']);
	}
	$amount = number_format((float) $amount, 2, '.', '');

	if (!is_string($payload['payment_method'])) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Payment method must be a string.', 'error' => 'Payment method must be a string.']);
	}
	$paymentMethod = strtoupper(trim($payload['payment_method']));
	$allowedMethods = ['CARD', 'CASH', 'BANK_TRANSFER', 'MOBILE_MONEY'];
	if (!in_array($paymentMethod, $allowedMethods, true)) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Unsupported payment method.', 'error' => 'Unsupported payment method.']);
	}

	$transactionReference = $payload['transaction_reference'] ?? null;
	if ($transactionReference !== null && (!is_string($transactionReference) || strlen($transactionReference) > 100)) {
		sendJsonResponse(400, ['success' => false, 'message' => 'Transaction reference must be a string of at most 100 characters.', 'error' => 'Transaction reference must be a string of at most 100 characters.']);
	}
	if ($transactionReference === null || trim($transactionReference) === '') {
		$transactionReference = 'SM-' . strtoupper(bin2hex(random_bytes(8)));
	}

	$ticketBinds = [];
	foreach ($ticketIds as $index => $ticketId) {
		$ticketBinds[] = ':ticket_' . $index;
	}
	$ticketSql = 'SELECT ticket_id, trip_id, fare, status FROM TICKET '
		. 'WHERE passenger_id = :passenger_id AND ticket_id IN (' . implode(', ', $ticketBinds) . ') FOR UPDATE';
	$ticketStatement = oci_parse($conn, $ticketSql);
	oci_bind_by_name($ticketStatement, ':passenger_id', $passengerId);
	foreach ($ticketIds as $index => $ticketId) {
		oci_bind_by_name($ticketStatement, ':ticket_' . $index, $ticketIds[$index]);
	}
	if (!@oci_execute($ticketStatement)) {
		$error = oci_error($ticketStatement);
		throw new RuntimeException($error['message'] ?? 'Ticket details could not be checked.');
	}
	$tickets = [];
	$totalAmount = 0.0;
	$tripId = null;
	while (($ticket = oci_fetch_assoc($ticketStatement)) !== false) {
		$ticket = array_change_key_case($ticket, CASE_LOWER);
		if (strtoupper($ticket['status']) !== 'BOOKED') {
			oci_free_statement($ticketStatement);
			sendJsonResponse(409, ['success' => false, 'message' => 'One or more tickets are not eligible for payment.', 'error' => 'One or more tickets are not eligible for payment.']);
		}
		if ($tripId !== null && (int) $ticket['trip_id'] !== $tripId) {
			oci_free_statement($ticketStatement);
			sendJsonResponse(422, ['success' => false, 'message' => 'All tickets in a payment must belong to the same trip.', 'error' => 'All tickets in a payment must belong to the same trip.']);
		}
		$tripId = (int) $ticket['trip_id'];
		$totalAmount += (float) $ticket['fare'];
		$tickets[(int) $ticket['ticket_id']] = $ticket;
	}
	oci_free_statement($ticketStatement);
	if (count($tickets) !== count($ticketIds)) {
		sendJsonResponse(404, ['success' => false, 'message' => 'One or more tickets were not found.', 'error' => 'One or more tickets were not found.']);
	}
	$totalAmount = number_format($totalAmount, 2, '.', '');
	if ($amount !== $totalAmount) {
		sendJsonResponse(422, ['success' => false, 'message' => 'Payment amount must match the total ticket fare.', 'error' => 'Payment amount must match the total ticket fare.']);
	}

	$firstTicketId = $ticketIds[0];
	$paymentId = null;
	$sql = 'BEGIN '
		. 'INSERT INTO PAYMENT (payment_id, ticket_id, amount, payment_method, transaction_reference, status, paid_at) '
		. 'VALUES (SEQ_PAYMENT_ID.NEXTVAL, :payment_ticket_id, :amount, :payment_method, :transaction_reference, \'COMPLETED\', SYSTIMESTAMP) '
		. 'RETURNING payment_id INTO :payment_id; '
		. 'UPDATE TICKET SET status = \'PAID\' WHERE passenger_id = :passenger_id '
		. 'AND ticket_id IN (' . implode(', ', $ticketBinds) . '); '
		. 'END;';
	$statement = oci_parse($conn, $sql);
	oci_bind_by_name($statement, ':payment_ticket_id', $firstTicketId);
	oci_bind_by_name($statement, ':amount', $totalAmount);
	oci_bind_by_name($statement, ':payment_method', $paymentMethod);
	oci_bind_by_name($statement, ':transaction_reference', $transactionReference, 100);
	oci_bind_by_name($statement, ':payment_id', $paymentId, 40);
	oci_bind_by_name($statement, ':passenger_id', $passengerId);
	foreach ($ticketIds as $index => $ticketId) {
		oci_bind_by_name($statement, ':ticket_' . $index, $ticketIds[$index]);
	}

	if (!@oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
		$error = oci_error($statement);
		$code = (int) ($error['code'] ?? 0);
		oci_free_statement($statement);
		if ($code === 1) {
			sendJsonResponse(409, ['success' => false, 'message' => 'Transaction reference has already been used.', 'error' => 'Transaction reference has already been used.']);
		}
		error_log('Payment API error: ' . ($error['message'] ?? 'Unknown Oracle error.'));
		sendJsonResponse(500, ['success' => false, 'message' => 'Payment could not be processed.', 'error' => 'Payment could not be processed.']);
	}

	oci_free_statement($statement);
	sendJsonResponse(201, [
		'success' => true,
		'message' => 'Payment completed.',
		'data' => ['payment_id' => $paymentId, 'ticket_ids' => $ticketIds, 'amount' => $totalAmount],
	]);
} catch (JsonException $exception) {
	sendJsonResponse(400, ['success' => false, 'message' => 'Request body must contain valid JSON.', 'error' => 'Request body must contain valid JSON.']);
} catch (InvalidArgumentException $exception) {
	sendJsonResponse(400, ['success' => false, 'message' => $exception->getMessage(), 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Payment API error: ' . $exception->getMessage());
	sendJsonResponse(500, ['success' => false, 'message' => 'Payment operation failed.', 'error' => 'Payment operation failed.']);
}
