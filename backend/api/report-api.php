<?php

header('Content-Type: application/json; charset=utf-8');

function sendReportResponse(int $statusCode, array $payload): never
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	exit;
}

function reportQueryRows(mixed $connection, string $sql, array $bindings = []): array
{
	$statement = @oci_parse($connection, $sql);
	if ($statement === false) {
		$error = oci_error($connection);
		throw new RuntimeException($error['message'] ?? 'Report query could not be prepared.');
	}

	foreach ($bindings as $placeholder => &$value) {
		if (!@oci_bind_by_name($statement, $placeholder, $value)) {
			$error = oci_error($statement);
			oci_free_statement($statement);
			throw new RuntimeException($error['message'] ?? 'Report query parameter could not be bound.');
		}
	}
	unset($value);

	if (!@oci_execute($statement)) {
		$error = oci_error($statement);
		oci_free_statement($statement);
		throw new RuntimeException($error['message'] ?? 'Report query failed.');
	}

	$rows = [];
	while (($row = oci_fetch_assoc($statement)) !== false) {
		$rows[] = array_change_key_case($row, CASE_LOWER);
	}
	oci_free_statement($statement);
	return $rows;
}

function reportDate(mixed $value, string $field): string
{
	if (!is_string($value)) {
		throw new InvalidArgumentException("{$field} must be a date in YYYY-MM-DD format.");
	}
	$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
	$errors = DateTimeImmutable::getLastErrors();
	if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
		throw new InvalidArgumentException("{$field} must be a valid date in YYYY-MM-DD format.");
	}
	return $value;
}

function reportPassengerId(mixed $value): int
{
	$id = filter_var($value, FILTER_VALIDATE_INT);
	if ($id === false || $id < 1) {
		throw new InvalidArgumentException('Passenger ID must be a positive integer.');
	}
	return $id;
}

try {
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	if ($method === 'OPTIONS') {
		http_response_code(204);
		exit;
	}
	if ($method !== 'GET') {
		header('Allow: GET, OPTIONS');
		sendReportResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
	}

	require_once __DIR__ . '/../config/database-oracle.php';
	$report = $_GET['report'] ?? '';

	if ($report === 'route-popularity') {
		$rows = reportQueryRows(
			$conn,
			'SELECT r.route_id, r.route_name, r.origin_city, r.destination_city, COUNT(t.ticket_id) AS booking_count '
			. 'FROM ROUTE r JOIN TRIP tr ON tr.route_id = r.route_id '
			. 'JOIN TICKET t ON t.trip_id = tr.trip_id '
			. "WHERE t.status <> 'CANCELLED' "
			. 'GROUP BY r.route_id, r.route_name, r.origin_city, r.destination_city '
			. 'ORDER BY booking_count DESC, r.route_id'
		);
		sendReportResponse(200, ['success' => true, 'data' => $rows]);
	}

	if ($report === 'revenue') {
		$startDate = reportDate($_GET['start_date'] ?? null, 'Start date');
		$endDate = reportDate($_GET['end_date'] ?? null, 'End date');
		if ($startDate > $endDate) {
			throw new InvalidArgumentException('Start date must be on or before end date.');
		}
		$endExclusive = (new DateTimeImmutable($endDate))->modify('+1 day')->format('Y-m-d');
		$rows = reportQueryRows(
			$conn,
			"SELECT NVL(SUM(p.amount), 0) AS total_revenue, COUNT(p.payment_id) AS payment_count "
			. 'FROM PAYMENT p JOIN TICKET t ON t.ticket_id = p.ticket_id '
			. "WHERE p.status = 'COMPLETED' "
			. "AND p.paid_at >= TO_TIMESTAMP(:start_date, 'YYYY-MM-DD') "
			. "AND p.paid_at < TO_TIMESTAMP(:end_date, 'YYYY-MM-DD')",
			[':start_date' => $startDate, ':end_date' => $endExclusive]
		);
		sendReportResponse(200, ['success' => true, 'data' => $rows[0] ?? ['total_revenue' => 0, 'payment_count' => 0]]);
	}

	if ($report === 'passenger-history') {
		$passengerId = reportPassengerId($_GET['passenger_id'] ?? null);
		$rows = reportQueryRows(
			$conn,
			'SELECT p.passenger_id, p.full_name AS passenger_name, p.email AS passenger_email, '
			. 't.ticket_id, TO_CHAR(t.booking_date, \'YYYY-MM-DD"T"HH24:MI:SS\') AS booking_date, '
			. 't.seat_number, t.fare AS ticket_fare, t.status AS ticket_status, tr.trip_id, '
			. 'TO_CHAR(tr.departure_time, \'YYYY-MM-DD"T"HH24:MI:SS\') AS departure_time, '
			. 'TO_CHAR(tr.arrival_time, \'YYYY-MM-DD"T"HH24:MI:SS\') AS arrival_time, '
			. 'tr.status AS trip_status, r.route_id, r.route_name, r.origin_city, r.destination_city '
			. 'FROM PASSENGER p JOIN TICKET t ON t.passenger_id = p.passenger_id '
			. 'JOIN TRIP tr ON tr.trip_id = t.trip_id JOIN ROUTE r ON r.route_id = tr.route_id '
			. 'WHERE p.passenger_id = :passenger_id '
			. 'ORDER BY tr.departure_time DESC, t.ticket_id',
			[':passenger_id' => $passengerId]
		);
		sendReportResponse(200, ['success' => true, 'data' => $rows]);
	}

	sendReportResponse(400, ['success' => false, 'error' => 'Choose a supported report.']);
} catch (InvalidArgumentException $exception) {
	sendReportResponse(400, ['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
	error_log('Report API error: ' . $exception->getMessage());
	sendReportResponse(500, ['success' => false, 'error' => 'Report data is currently unavailable.']);
}
