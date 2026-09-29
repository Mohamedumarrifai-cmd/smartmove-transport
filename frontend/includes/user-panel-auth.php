<?php

require_once __DIR__ . '/session.php';

$passenger = $_SESSION['passenger'] ?? null;
if (!is_array($passenger) || !isset($passenger['passenger_id'], $passenger['full_name'])) {
	header('Location: ../login.php');
	exit;
}