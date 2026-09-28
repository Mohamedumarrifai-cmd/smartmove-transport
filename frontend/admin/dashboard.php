<?php
$activePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Operations overview | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/admin-panel.css">
	<script src="../assets/js/admin-panel.js" defer></script>
</head>
<body class="admin-page" data-admin-page="dashboard">
	<div class="admin-shell">
		<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
		<main class="admin-main">
			<header class="admin-topbar"><div><p class="admin-eyebrow">SMARTMOVE / OPERATIONS</p><h1>Dashboard</h1></div><a class="admin-button" href="trips.php">Schedule a trip <span aria-hidden="true">↗</span></a></header>
			<div class="admin-content">
				<section class="admin-welcome"><p class="admin-eyebrow">THE NETWORK, TODAY</p><h2>Keep every journey moving.</h2><p>Live counts from the fleet, routes, and scheduled departures.</p></section>
				<section class="admin-metrics" aria-label="Operations summary">
					<article><span>FLEET VEHICLES</span><strong data-admin-stat="vehicles">—</strong><small>Registered vehicles</small></article>
					<article><span>ACTIVE ROUTES</span><strong data-admin-stat="routes">—</strong><small>Available for scheduling</small></article>
					<article><span>UPCOMING TRIPS</span><strong data-admin-stat="trips">—</strong><small>Scheduled departures</small></article>
					<article><span>DRIVERS</span><strong data-admin-stat="drivers">—</strong><small>Across the operation</small></article>
				</section>
				<section class="admin-section"><div class="admin-section-heading"><div><p class="admin-eyebrow">NEXT DEPARTURES</p><h2>Upcoming trips</h2></div><a href="trips.php">Manage trips <span aria-hidden="true">→</span></a></div><div class="admin-table-wrap"><table><thead><tr><th>Trip</th><th>Route</th><th>Departure</th><th>Fare</th><th>Status</th></tr></thead><tbody data-dashboard-trips><tr><td colspan="5" class="admin-empty">Loading operational data…</td></tr></tbody></table></div></section>
				<p class="admin-feedback" data-admin-feedback role="status" aria-live="polite"></p>
			</div>
		</main>
	</div>
</body>
</html>
