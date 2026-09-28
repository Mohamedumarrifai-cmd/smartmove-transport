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
	<meta name="theme-color" content="#1A365D">
	<title>Book ticket | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="book" data-trip-id="<?= htmlspecialchars((string) $tripId, ENT_QUOTES, 'UTF-8') ?>">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div class="panel-heading-group"><button class="panel-icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Collapse sidebar" aria-controls="passenger-sidebar" aria-expanded="true"><span class="material-symbols-rounded" aria-hidden="true">left_panel_close</span></button><div><p class="panel-eyebrow">PASSENGER / RESERVATION</p><h1>Make it yours.</h1></div></div>
				<div class="panel-topbar-actions"><button class="panel-icon-button notification-button" type="button" aria-label="Notifications"><span class="material-symbols-rounded" aria-hidden="true">notifications</span><i aria-hidden="true"></i></button><a class="panel-profile-chip" href="my-bookings.php"><span class="profile-avatar profile-avatar-small"><?= htmlspecialchars(strtoupper(substr($passenger['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><span><?= htmlspecialchars(explode(' ', $passenger['full_name'])[0], ENT_QUOTES, 'UTF-8') ?></span></a><a class="quiet-link booking-back-link" href="search-trip.php"><span aria-hidden="true">←</span> Back to trips</a></div>
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
						<div class="booking-progress" aria-label="Booking progress"><span class="booking-progress-step is-current" data-booking-progress="1" aria-current="step"><b>1</b><span>Choose seat</span></span><i></i><span class="booking-progress-step" data-booking-progress="2"><b>2</b><span>Review</span></span></div>
						<form data-booking-form>
							<section class="booking-step-panel is-active" data-booking-step="1" aria-labelledby="booking-step-one-title">
								<p class="panel-eyebrow">STEP 1 OF 2</p><h2 id="booking-step-one-title">Choose your seat.</h2>
								<p class="booking-helper">Booking as <strong><?= htmlspecialchars($passenger['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
								<label class="floating-field"><span>Seat number</span><input id="seat-number" name="seat_number" type="number" min="1" step="1" value="1" required><span class="floating-suffix" aria-hidden="true">SEAT</span></label>
								<p class="field-hint" data-seat-hint>Seat availability is confirmed when you book.</p>
								<button class="panel-button panel-button-coral booking-submit" type="button" data-booking-next>Review booking <span aria-hidden="true">&#8594;</span></button>
							</section>
							<section class="booking-step-panel" data-booking-step="2" aria-labelledby="booking-step-two-title" hidden>
								<p class="panel-eyebrow">STEP 2 OF 2</p><h2 id="booking-step-two-title">Review your journey.</h2>
								<div class="booking-review-list"><div><span>PASSENGER</span><strong><?= htmlspecialchars($passenger['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></div><div><span>SELECTED SEAT</span><strong data-review-seat>Seat 1</strong></div><div><span>FARE</span><strong data-review-fare>—</strong></div><div><span>DEPARTURE</span><strong data-review-departure>—</strong></div></div>
								<p class="booking-helper booking-confirm-note">Confirming will reserve the selected seat. Payment is handled separately.</p>
								<div class="booking-step-actions"><button class="panel-button panel-button-quiet" type="button" data-booking-back><span aria-hidden="true">&#8592;</span> Change seat</button><button class="panel-button panel-button-coral" type="submit" data-booking-confirm>Confirm booking <span aria-hidden="true">&#8599;</span></button></div>
							</section>
						</form>
						<p class="booking-fineprint">Your confirmed ticket will appear in My bookings.</p>
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
