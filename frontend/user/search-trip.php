<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'search';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#182722">
	<title>Search trips | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="search">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / TRIPS</p><h1>Find your way there.</h1></div>
				<a class="quiet-link" href="my-bookings.php">Your bookings <span aria-hidden="true">→</span></a>
			</header>
			<div class="panel-content">
				<section class="search-intro"><p>Choose a route. We’ll take care of the rest.</p><span class="search-coordinate">05°33' N&nbsp;&nbsp; 00°12' W</span></section>
				<form class="trip-filter" data-trip-search>
					<div class="filter-field"><label for="origin">Leaving from</label><select id="origin" name="origin"><option value="">Any origin</option></select></div>
					<div class="filter-field"><label for="destination">Going to</label><select id="destination" name="destination"><option value="">Any destination</option></select></div>
					<div class="filter-field filter-date"><label for="departure-date">Travel date</label><input id="departure-date" name="date" type="date"></div>
					<button class="panel-button panel-button-coral" type="submit">Search trips <span aria-hidden="true">↗</span></button>
				</form>
				<div class="results-heading"><div><p class="panel-eyebrow">AVAILABLE DEPARTURES</p><h2 data-results-title>Choose a journey</h2></div><span data-results-count class="results-count">Loading…</span></div>
			<p class="panel-feedback" data-search-feedback role="status" aria-live="polite"></p>
			<div class="trip-results" data-trip-results aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span><span class="loading-line"></span></div></div>
			</div>
		</main>
	</div>
</body>
</html>
