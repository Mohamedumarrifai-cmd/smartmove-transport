<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'bookings';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>My bookings | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="bookings">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / YOUR TRIPS</p><h1>Every mile, remembered.</h1></div>
				<a class="panel-button panel-button-coral" href="search-trip.php">Book another trip <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<section class="booking-page-intro"><div><p class="panel-eyebrow">TRAVEL HISTORY</p><h2>Your journeys</h2><p>Everything booked, in one place.</p></div><div class="booking-count" data-history-count>— <span>TRIPS</span></div></section>
				<div class="booking-controls">
					<div class="filter-tabs" role="group" aria-label="Filter bookings">
						<button class="filter-tab is-active" type="button" data-booking-filter="all" aria-pressed="true">All trips</button>
						<button class="filter-tab" type="button" data-booking-filter="upcoming" aria-pressed="false">Upcoming</button>
						<button class="filter-tab" type="button" data-booking-filter="past" aria-pressed="false">Past</button>
					</div>
					<span class="sort-label">Newest booking first</span>
				</div>
				<p class="panel-feedback" data-history-feedback role="status" aria-live="polite"></p>
				<div class="booking-list" data-bookings-list aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
			</div>
		</main>
	</div>
</body>
</html>
