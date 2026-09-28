<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$passenger = $_SESSION['passenger'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f1f3ec">
	<meta name="description" content="Thoughtful intercity travel, with every detail moving in the right direction.">
	<title>SmartMove | Travel, considered</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/3d.css">
	<script type="module" src="assets/js/three-scene.js"></script>
</head>
<body class="landing-page">
	<header class="site-header">
		<a class="brand" href="index.php" aria-label="SmartMove home">
			<span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
			<span>smartmove</span>
		</a>
		<nav class="main-nav" aria-label="Main navigation">
			<a href="#journeys">Our approach</a>
			<a href="user/search-trip.php">Find a trip</a>
		</nav>
		<div class="header-actions">
			<?php if (is_array($passenger)): ?>
				<span class="welcome-name">Hi, <?= htmlspecialchars(explode(' ', $passenger['full_name'])[0], ENT_QUOTES, 'UTF-8') ?></span>
				<a class="button button-dark button-small" href="user/dashboard.php">My account <span class="button-arrow" aria-hidden="true">↗</span></a>
			<?php else: ?>
				<a class="login-link" href="login.php">Log in</a>
				<a class="button button-dark button-small" href="register.php">Create account <span class="button-arrow" aria-hidden="true">↗</span></a>
			<?php endif; ?>
		</div>
	</header>

	<main>
		<section class="hero" aria-labelledby="hero-title">
			<div class="hero-texture" aria-hidden="true"></div>
			<div class="hero-scene" data-three-scene="hero" aria-label="Animated three-dimensional coach traveling along a route">
				<div class="scene-fallback" aria-hidden="true"><div class="fallback-coach"><span></span><i></i><i></i></div></div>
			</div>
			<div class="hero-copy">
				<p class="eyebrow"><span class="eyebrow-dot"></span> Travel, considered</p>
				<h1 id="hero-title">SmartMove<br><span>moves with you.</span></h1>
				<p class="hero-description">A calmer way to get there. Find your next connection, settle in, and leave the little details to us.</p>
				<div class="hero-actions">
					<a class="button button-coral" href="user/search-trip.php">Find your trip <span class="button-arrow" aria-hidden="true">↗</span></a>
					<a class="text-action" href="register.php">Join SmartMove <span aria-hidden="true">→</span></a>
				</div>
				<div class="hero-footnote"><span class="footnote-line"></span><span>Good journeys start before departure.</span></div>
			</div>
			<div class="scene-caption glass-panel">
				<div class="caption-top"><span class="live-dot"></span> ON THE ROAD <span class="caption-route">ROUTE 01</span></div>
				<div class="caption-journey"><span>Accra</span><span class="journey-track"><i></i></span><span>Kumasi</span></div>
				<div class="caption-meta"><span>Departures, made simple</span><span>06:45 <b>AM</b></span></div>
			</div>
			<div class="hero-vertical-label" aria-hidden="true">01 / THE JOURNEY IS YOURS</div>
		</section>

		<section class="journey-band" id="journeys" aria-labelledby="journey-title">
			<div class="journey-heading">
				<p class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> A better way between here and there</p>
				<h2 id="journey-title">Make room for<br><span>the good part.</span></h2>
			</div>
			<div class="journey-details">
				<p>Less second-guessing. More time looking out the window. SmartMove brings your trip, ticket, and travel updates into one easy rhythm.</p>
				<a class="button button-lime" href="user/search-trip.php">Explore departures <span class="button-arrow" aria-hidden="true">↗</span></a>
			</div>
			<div class="journey-index" aria-hidden="true">SM<span> / 01</span></div>
		</section>

		<section class="service-row" aria-label="Travel with SmartMove">
			<div class="service-label">THE SMARTMOVE WAY</div>
			<div class="service-item"><span>01</span><strong>Choose your route</strong><small>Clear options, easy booking</small></div>
			<div class="service-item"><span>02</span><strong>Keep your plans close</strong><small>Tickets and updates together</small></div>
			<div class="service-item"><span>03</span><strong>Enjoy the ride</strong><small>We’ll take it from here</small></div>
		</section>
	</main>

	<footer class="site-footer"><a class="brand brand-footer" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>smartmove</span></a><span>Thoughtful travel, from the first mile.</span><a href="login.php">Passenger sign in <span aria-hidden="true">↗</span></a></footer>
</body>
</html>
