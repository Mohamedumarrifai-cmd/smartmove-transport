<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'feedback';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Feedback | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="feedback-info">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / FEEDBACK</p><h1>Help us make the next trip better.</h1></div>
				<a class="quiet-link" href="my-bookings.php">Your journeys <span aria-hidden="true">→</span></a>
			</header>
			<div class="panel-content">
				<section class="feedback-layout">
					<div class="feedback-context"><p class="panel-eyebrow">YOUR EXPERIENCE MATTERS</p><h2>How was your journey?</h2><p>Leave feedback for completed trips, or tell us why a trip did not go ahead. Feedback is available from every trip in your bookings.</p><span class="feedback-mark" aria-hidden="true">SM / 06</span></div>
					<div class="service-form"><p class="booking-helper">Choose any trip to rate your completed journey or share why it was not completed. You can update your feedback later.</p><a class="panel-button panel-button-coral" href="my-bookings.php">Open my journeys <span aria-hidden="true">↗</span></a></div>
				</section>
			</div>
		</main>
	</div>
</body>
</html>
