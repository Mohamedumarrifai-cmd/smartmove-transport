<?php
$activePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Operations overview | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/admin-panel.css">
	<script src="../assets/js/admin-panel.js" defer></script>
</head>
<body class="admin-page" data-admin-page="dashboard">
	<div class="admin-shell">
		<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
		<main class="admin-main">
			<header class="admin-topbar"><div class="admin-heading-group"><button class="admin-icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Collapse sidebar" aria-controls="admin-sidebar" aria-expanded="true"><span class="material-symbols-rounded" aria-hidden="true">left_panel_close</span></button><div><p class="admin-eyebrow">SMARTMOVE / OPERATIONS</p><h1>Dashboard</h1></div></div><div class="admin-topbar-actions"><button class="admin-icon-button notification-button" type="button" aria-label="Notifications"><span class="material-symbols-rounded" aria-hidden="true">notifications</span><i aria-hidden="true"></i></button><span class="admin-profile-chip"><span class="admin-avatar">SM</span><span>Operations<small>Administrator</small></span></span><a class="admin-button" href="trips.php">Schedule a trip <span aria-hidden="true">↗</span></a></div></header>
			<div class="admin-content">
				<section class="admin-welcome"><p class="admin-eyebrow">THE NETWORK, TODAY</p><h2>Keep every journey moving.</h2><p>Live operations across your fleet, routes, and scheduled trips.</p><a class="admin-welcome-link" href="reports.php">Open business reports <span aria-hidden="true">&#8594;</span></a></section>
				<section class="admin-metrics" aria-label="Operations summary">
					<article class="stat-card"><span class="stat-icon material-symbols-rounded" aria-hidden="true">directions_bus</span><span class="stat-label">FLEET VEHICLES</span><strong data-admin-stat="vehicles">—</strong><small data-admin-trend="vehicles">Registered vehicles</small></article>
					<article class="stat-card"><span class="stat-icon material-symbols-rounded" aria-hidden="true">route</span><span class="stat-label">ACTIVE ROUTES</span><strong data-admin-stat="routes">—</strong><small data-admin-trend="routes">Available for scheduling</small></article>
					<article class="stat-card"><span class="stat-icon material-symbols-rounded" aria-hidden="true">schedule</span><span class="stat-label">UPCOMING TRIPS</span><strong data-admin-stat="trips">—</strong><small data-admin-trend="trips">Scheduled departures</small></article>
					<article class="stat-card"><span class="stat-icon material-symbols-rounded" aria-hidden="true">badge</span><span class="stat-label">DRIVERS</span><strong data-admin-stat="drivers">—</strong><small data-admin-trend="drivers">Across the operation</small></article>
				</section>
				<section class="admin-section admin-bookings-section"><div class="admin-section-heading"><div><p class="admin-eyebrow">LATEST ACTIVITY</p><h2>Recent bookings</h2></div><a href="bookings.php">All bookings <span aria-hidden="true">&#8594;</span></a></div><div class="admin-table-wrap"><table><thead><tr><th>Ticket</th><th>Passenger</th><th>Route</th><th>Booked</th><th>Fare</th><th>Status</th><th>Actions</th></tr></thead><tbody data-dashboard-bookings data-bookings-endpoint="../../backend/api/admin-bookings-api.php"><tr><td colspan="7" class="admin-empty">Connect the admin bookings API to load this feed.</td></tr></tbody></table></div></section>
				<p class="admin-feedback" data-admin-feedback role="status" aria-live="polite"></p>
				<dialog class="admin-booking-dialog" data-booking-dialog aria-labelledby="booking-dialog-title"><div class="dialog-heading"><div><p class="admin-eyebrow">BOOKING RECORD</p><h2 id="booking-dialog-title">Ticket details</h2></div><button class="admin-icon-button" type="button" data-dialog-close aria-label="Close details"><span class="material-symbols-rounded" aria-hidden="true">close</span></button></div><dl data-dialog-details></dl></dialog>
			</div>
		</main>
	</div>
</body>
</html>
