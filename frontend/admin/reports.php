<?php
$activePage = 'reports';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reports | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/admin-panel.css"><script src="../assets/js/admin-panel.js" defer></script></head>
<body class="admin-page" data-admin-page="reports"><div class="admin-shell"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?><main class="admin-main">
	<header class="admin-topbar"><div><p class="admin-eyebrow">SMARTMOVE / ANALYTICS</p><h1>Reports</h1></div><button class="admin-button admin-button-quiet" type="button" data-refresh>Refresh data <span aria-hidden="true">↻</span></button></header>
	<div class="admin-content"><section class="admin-page-intro"><div><p class="admin-eyebrow">NETWORK SNAPSHOT</p><h2>Operations report</h2><p>Current totals from the route, fleet, driver, trip, and ticket registers.</p></div><span class="admin-record-count" data-report-updated>LIVE DATA</span></section>
		<section class="admin-metrics admin-report-metrics"><article><span>TRIPS RECORDED</span><strong data-report-stat="trips">—</strong><small>All statuses</small></article><article><span>BOOKINGS</span><strong data-report-stat="bookings">—</strong><small>Tickets issued</small></article><article><span>BOOKED VALUE</span><strong data-report-stat="value">—</strong><small>Sum of ticket fares</small></article><article><span>FLEET UTILIZATION</span><strong data-report-stat="fleet">—</strong><small>Vehicles assigned to trips</small></article></section>
		<section class="admin-section"><div class="admin-section-heading"><div><p class="admin-eyebrow">TRIP STATUS</p><h2>Schedule breakdown</h2></div></div><div class="admin-table-wrap"><table><thead><tr><th>Status</th><th>Trip count</th><th>Share</th></tr></thead><tbody data-report-status><tr><td colspan="3" class="admin-empty">Loading report data…</td></tr></tbody></table></div></section>
		<p class="admin-feedback" data-admin-feedback role="status" aria-live="polite"></p>
	</div></main></div></body></html>
