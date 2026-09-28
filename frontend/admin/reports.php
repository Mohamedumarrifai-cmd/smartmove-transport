<?php
$activePage = 'reports';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Business reports | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/admin-panel.css">
	<script src="../assets/js/admin-panel.js" defer></script>
</head>
<body class="admin-page" data-admin-page="reports">
	<div class="admin-shell">
		<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
		<main class="admin-main">
			<header class="admin-topbar">
				<div><p class="admin-eyebrow">SMARTMOVE / ANALYTICS</p><h1>Business reports</h1></div>
				<button class="admin-button admin-button-quiet" type="button" data-refresh aria-label="Refresh reports">Refresh reports <span aria-hidden="true">&#8635;</span></button>
			</header>
			<div class="admin-content reports-content">
				<section class="admin-page-intro">
					<div><p class="admin-eyebrow">NETWORK PERFORMANCE</p><h2>Read the journeys behind the numbers.</h2><p>Explore route demand, paid ticket revenue, and individual passenger travel history.</p></div>
					<span class="admin-record-count" data-report-updated>READY TO LOAD</span>
				</section>

				<section class="admin-section report-section" aria-labelledby="route-report-title">
					<div class="admin-section-heading"><div><p class="admin-eyebrow">01 / DEMAND</p><h2 id="route-report-title">Most-used routes</h2></div><span class="report-note">Non-cancelled bookings</span></div>
					<div class="admin-table-wrap"><table>
						<thead><tr><th>Rank</th><th>Route</th><th>From</th><th>To</th><th>Bookings</th></tr></thead>
						<tbody data-report-routes><tr><td colspan="5" class="admin-empty">Loading route bookings...</td></tr></tbody>
					</table></div>
				</section>

				<section class="admin-section report-section" aria-labelledby="revenue-report-title">
					<div class="admin-section-heading"><div><p class="admin-eyebrow">02 / REVENUE</p><h2 id="revenue-report-title">Ticket payment revenue</h2></div><span class="report-note">Completed payments by payment date</span></div>
					<form class="report-filter" data-revenue-form>
						<label>From<input type="date" name="start_date" required></label>
						<label>Through<input type="date" name="end_date" required></label>
						<button class="admin-button" type="submit">Run revenue report <span aria-hidden="true">&#8594;</span></button>
					</form>
					<div class="report-total" aria-live="polite"><span>COMPLETED PAYMENT TOTAL</span><strong data-report-revenue-total>—</strong><small data-report-revenue-count>Choose a period to load revenue.</small></div>
				</section>

				<section class="admin-section report-section" aria-labelledby="history-report-title">
					<div class="admin-section-heading"><div><p class="admin-eyebrow">03 / PASSENGERS</p><h2 id="history-report-title">Travel history</h2></div><span class="report-note">Ticket, trip, and route details</span></div>
					<form class="report-filter report-filter-passenger" data-history-form>
						<label for="history-passenger-id">Passenger ID<input id="history-passenger-id" name="passenger_id" type="number" min="1" step="1" inputmode="numeric" placeholder="e.g. 1042" required></label>
						<button class="admin-button" type="submit">View travel history <span aria-hidden="true">&#8594;</span></button>
					</form>
					<div class="admin-table-wrap"><table>
						<thead><tr><th>Departure</th><th>Passenger</th><th>Route</th><th>Seat</th><th>Fare</th><th>Ticket</th><th>Trip</th></tr></thead>
						<tbody data-report-history><tr><td colspan="7" class="admin-empty">Enter a passenger ID to load travel history.</td></tr></tbody>
					</table></div>
				</section>
				<p class="admin-feedback" data-admin-feedback role="status" aria-live="polite"></p>
			</div>
		</main>
	</div>
</body>
</html>
