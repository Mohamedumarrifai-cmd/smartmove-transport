<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$passenger = $_SESSION['passenger'] ?? null;
if (!is_array($passenger) || !isset($passenger['passenger_id'], $passenger['full_name'])) {
	header('Location: ../login.php');
	exit;
}