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
<body class="panel-page" data-page="feedback">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / FEEDBACK</p><h1>Help us make the next trip better.</h1></div>
				<a class="quiet-link" href="my-bookings.php">Your journeys <span aria-hidden="true">→</span></a>
			</header>
			<div class="panel-content">
				<section class="feedback-layout">
					<div class="feedback-context"><p class="panel-eyebrow">YOUR EXPERIENCE MATTERS</p><h2>How was your journey?</h2><p>Choose a completed trip and tell us what went well or what we can improve.</p><span class="feedback-mark" aria-hidden="true">SM / 06</span></div>
					<form class="service-form" data-feedback-form>
						<label class="filter-field" for="feedback-trip">Journey<select id="feedback-trip" name="trip" required><option value="">Loading your journeys…</option></select></label>
						<label class="filter-field" for="feedback-rating">Your rating<select id="feedback-rating" name="rating" required><option value="5">5 · Excellent</option><option value="4">4 · Good</option><option value="3">3 · Fair</option><option value="2">2 · Needs work</option><option value="1">1 · Poor</option></select></label>
						<label class="filter-field" for="feedback-comments">Your feedback<textarea id="feedback-comments" name="comments" rows="5" maxlength="1000" placeholder="Share a few details about your experience" required></textarea></label>
						<p class="panel-feedback" data-feedback-message role="status" aria-live="polite"></p>
						<button class="panel-button panel-button-coral" type="submit" data-feedback-submit>Send feedback <span aria-hidden="true">↗</span></button>
					</form>
				</section>
			</div>
		</main>
	</div>
</body>
</html>
