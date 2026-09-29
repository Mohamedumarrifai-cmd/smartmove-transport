<?php
require_once __DIR__ . '/includes/session.php';
$passenger = $_SESSION['passenger'] ?? null;
$firstName = is_array($passenger) && isset($passenger['full_name'])
	? explode(' ', trim($passenger['full_name']))[0]
	: null;
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<meta name="description" content="Book your next intercity journey with SmartMove. Find routes, reserve seats, and keep your travel plans in one place.">
	<title>SmartMove | Your next stop starts here</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/js/main.js" defer></script>
</head>
<body class="landing-page">
	<header class="site-header" data-site-header>
		<a class="brand" href="index.php" aria-label="SmartMove home">
			<span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
			<span>smart<span class="brand-accent">move</span></span>
		</a>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Open navigation" data-menu-toggle>
			<span></span><span></span>
		</button>
		<nav class="main-nav" id="primary-navigation" aria-label="Main navigation" data-navigation>
			<a href="#how-it-works">How it works</a>
			<a href="user/search-trip.php">Routes</a>
			<a href="user/announcements.php">Travel updates</a>
			<div class="mobile-nav-actions">
				<?php if (is_array($passenger)): ?>
					<a class="button button-outline" href="user/dashboard.php">My account</a>
				<?php else: ?>
					<a class="button button-outline" href="login.php">Passenger login</a>
					<a class="button button-primary" href="admin/dashboard.php">Admin login</a>
				<?php endif; ?>
			</div>
		</nav>
		<div class="header-actions">
			<?php if (is_array($passenger)): ?>
				<span class="welcome-name">Hi, <?= htmlspecialchars($firstName ?? 'there', ENT_QUOTES, 'UTF-8') ?></span>
				<a class="button button-outline" href="user/dashboard.php">My account</a>
			<?php else: ?>
				<a class="login-link" href="login.php">Passenger login</a>
				<a class="button button-primary" href="admin/dashboard.php">Admin login</a>
			<?php endif; ?>
		</div>
	</header>

	<main>
		<section class="hero" aria-labelledby="hero-title">
			<div class="hero-image" role="img" aria-label="Intercity coach travelling across a scenic landscape"></div>
			<div class="hero-shade" aria-hidden="true"></div>
			<div class="hero-content">
				<p class="eyebrow hero-eyebrow"><span class="eyebrow-mark" aria-hidden="true"></span> THE JOURNEY STARTS HERE</p>
				<h1 id="hero-title">Go further.<br><span>Arrive ready.</span></h1>
				<p class="hero-copy">Comfortable intercity travel, made simple from the first search to the final stop.</p>
				<div class="hero-actions">
					<a class="button button-primary button-large" href="user/search-trip.php">Book a ticket <span aria-hidden="true">&#8594;</span></a>
					<a class="hero-secondary" href="#how-it-works">See how it works <span aria-hidden="true">&#8595;</span></a>
				</div>
			</div>
			<div class="hero-bottomline" aria-hidden="true"><span>SMARTMOVE / INTERCITY</span><span>YOUR NEXT STOP, MADE SIMPLE</span></div>
			<div class="hero-side-note" aria-hidden="true"><span class="hero-side-rule"></span><span>GHANA, CONNECTED</span></div>
		</section>

		<section class="journey-section" id="how-it-works" aria-labelledby="journey-title">
			<div class="section-heading" data-reveal>
				<div><p class="eyebrow"><span class="eyebrow-mark"></span> A BETTER WAY TO MOVE</p><h2 id="journey-title">Your trip, in good hands.</h2></div>
				<p>Less time sorting details. More time looking forward to where you’re going.</p>
			</div>
			<div class="journey-steps">
				<article class="journey-step" data-reveal>
					<span class="step-number">01</span><span class="step-icon" aria-hidden="true">&#8596;</span>
					<h3>Choose your route</h3><p>Compare departures and find a trip that fits your plans.</p>
				</article>
				<article class="journey-step" data-reveal>
					<span class="step-number">02</span><span class="step-icon" aria-hidden="true">&#9633;</span>
					<h3>Book with ease</h3><p>Pick your seat and keep your ticket details close at hand.</p>
				</article>
				<article class="journey-step" data-reveal>
					<span class="step-number">03</span><span class="step-icon" aria-hidden="true">&#8599;</span>
					<h3>Enjoy the ride</h3><p>Get travel updates and settle in for the journey ahead.</p>
				</article>
			</div>
		</section>

		<section class="closing-band" aria-label="Start planning a trip">
			<div><p class="eyebrow eyebrow-light">READY WHEN YOU ARE</p><h2>Make your next move.</h2></div>
			<a class="button button-light" href="user/search-trip.php">Find a trip <span aria-hidden="true">&#8594;</span></a>
		</section>
	</main>

	<footer class="site-footer">
		<a class="brand brand-footer" href="index.php"><span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span><span>smart<span class="brand-accent">move</span></span></a>
		<p>Better journeys begin with a better plan.</p>
		<a href="login.php">Passenger sign in <span aria-hidden="true">&#8599;</span></a>
	</footer>
</body>
</html>
