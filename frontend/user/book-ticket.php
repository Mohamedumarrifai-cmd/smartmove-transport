<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'search';
$tripId = filter_var($_GET['trip_id'] ?? null, FILTER_VALIDATE_INT);
$tripId = $tripId !== false && $tripId > 0 ? $tripId : null;
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#182722">
	<title>Book ticket | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="book" data-trip-id="<?= htmlspecialchars((string) $tripId, ENT_QUOTES, 'UTF-8') ?>">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / RESERVATION</p><h1>Make it yours.</h1></div>
				<a class="quiet-link" href="search-trip.php"><span aria-hidden="true">←</span> Back to trips</a>
			</header>
			<div class="panel-content booking-content">
				<p class="panel-feedback" data-booking-feedback role="status" aria-live="polite"></p>
				<div class="booking-layout" data-booking-layout hidden>
					<section class="booking-trip-summary">
						<p class="panel-eyebrow">YOUR SELECTED JOURNEY</p>
						<h2 data-booking-route>Loading route…</h2>
						<div class="booking-route-line"><span data-booking-origin>—</span><i></i><span data-booking-destination>—</span></div>
						<div class="booking-facts"><div><span>DEPARTS</span><strong data-booking-departure>—</strong></div><div><span>ARRIVES</span><strong data-booking-arrival>—</strong></div></div>
						<div class="booking-price"><span>Fare per seat</span><strong data-booking-fare>—</strong></div>
						<a class="quiet-link" href="search-trip.php"><span aria-hidden="true">←</span> Change journey</a>
					</section>
					<section class="booking-form-section">
						<p class="panel-eyebrow">PASSENGER DETAILS</p>
						<h2>Choose your seat.</h2>
						<p class="booking-helper">You’re booking as <strong><?= htmlspecialchars($passenger['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>. Your seat is confirmed when the booking is complete.</p>
						<form data-booking-form>
							<label for="seat-number">Seat number</label>
							<div class="seat-input-wrap"><span aria-hidden="true">#</span><input id="seat-number" name="seat_number" type="number" min="1" step="1" value="1" required></div>
							<p class="field-hint" data-seat-hint>Seat availability is confirmed when you book.</p>
							<button class="panel-button panel-button-coral booking-submit" type="submit">Confirm booking <span aria-hidden="true">↗</span></button>
						</form>
						<p class="booking-fineprint">Your ticket will appear in My bookings as soon as it’s confirmed.</p>
					</section>
				</div>
				<div class="booking-empty" data-booking-empty <?= $tripId === null ? '' : 'hidden' ?>>
					<span class="empty-index">01</span><h2>No journey selected.</h2><p>Choose a departure first, then come back here to reserve your seat.</p><a class="panel-button panel-button-coral" href="search-trip.php">Find a trip <span aria-hidden="true">↗</span></a>
				</div>
			</div>
		</main>
	</div>
</body>
</html>
