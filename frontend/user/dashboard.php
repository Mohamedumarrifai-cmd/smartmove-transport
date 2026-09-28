<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Passenger dashboard | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="dashboard">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div class="panel-heading-group"><button class="panel-icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Collapse sidebar" aria-controls="passenger-sidebar" aria-expanded="true"><span class="material-symbols-rounded" aria-hidden="true">left_panel_close</span></button><div><p class="panel-eyebrow">PASSENGER / OVERVIEW</p><h1>Good day, <?= htmlspecialchars(explode(' ', $passenger['full_name'])[0], ENT_QUOTES, 'UTF-8') ?>.</h1></div></div>
				<div class="panel-topbar-actions"><button class="panel-icon-button notification-button" type="button" aria-label="Notifications"><span class="material-symbols-rounded" aria-hidden="true">notifications</span><i aria-hidden="true"></i></button><a class="panel-profile-chip" href="my-bookings.php"><span class="profile-avatar profile-avatar-small"><?= htmlspecialchars(strtoupper(substr($passenger['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><span><?= htmlspecialchars(explode(' ', $passenger['full_name'])[0], ENT_QUOTES, 'UTF-8') ?></span></a><a class="panel-button panel-button-coral" href="search-trip.php">Plan a journey <span aria-hidden="true">↗</span></a></div>
			</header>
			<div class="panel-content" data-dashboard-content>
				<section class="welcome-strip">
					<div><p class="panel-eyebrow panel-eyebrow-light">YOUR SMARTMOVE, AT A GLANCE</p><h2>Where to next?</h2><p>Your trips, tickets, and travel plans, all moving together.</p></div>
					<a class="welcome-link" href="search-trip.php">Find your next trip <span aria-hidden="true">→</span></a>
					<div class="welcome-code" aria-hidden="true">SM / 01</div>
				</section>
				<section class="metric-grid" aria-label="Booking overview">
					<article class="metric-item"><span class="metric-label">ALL BOOKINGS</span><strong data-stat-total>—</strong><small>Since you joined</small></article>
					<article class="metric-item"><span class="metric-label">UPCOMING</span><strong data-stat-upcoming>—</strong><small>Journeys ahead</small></article>
					<article class="metric-item"><span class="metric-label">TICKETS PAID</span><strong data-stat-paid>—</strong><small>Ready when you are</small></article>
				</section>
				<div class="dashboard-columns">
					<section class="panel-section upcoming-section">
						<div class="section-heading"><div><p class="panel-eyebrow">ON THE HORIZON</p><h2>Your next journey</h2></div><a href="my-bookings.php">All bookings <span aria-hidden="true">→</span></a></div>
						<div class="next-trip" data-next-trip><div class="empty-state"><span class="empty-index">01</span><p>Looking for your next departure…</p></div></div>
					</section>
					<section class="panel-section quick-actions-section">
						<div class="section-heading"><div><p class="panel-eyebrow">MAKE IT A GOOD TRIP</p><h2>Quick links</h2></div></div>
						<a class="quick-link" href="search-trip.php"><span class="quick-number">01</span><span><strong>Search departures</strong><small>Find a route and a time</small></span><b aria-hidden="true">↗</b></a>
						<a class="quick-link" href="my-bookings.php"><span class="quick-number">02</span><span><strong>View your tickets</strong><small>See upcoming and past trips</small></span><b aria-hidden="true">↗</b></a>
						<div class="trip-note"><span class="note-pin"></span><span>Keep your booking reference handy when you arrive at the terminal.</span></div>
					</section>
				</div>
				<p class="panel-feedback" data-panel-feedback role="status" aria-live="polite"></p>
			</div>
		</main>
	</div>
</body>
</html>
