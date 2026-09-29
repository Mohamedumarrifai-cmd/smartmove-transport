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
	<meta name="description" content="Move smarter with SmartMove. Find your route, book your seat, and travel better across Ghana.">
	<title>SmartMove | Move smarter. Travel better.</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/reel-landing.css">
	<script src="assets/js/reel-scroll.js" defer></script>
</head>
<body class="reel-page">
	<header class="reel-header">
		<a class="reel-brand" href="index.php" aria-label="SmartMove home"><span class="reel-brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>smart<span>move</span></span></a>
		<button class="reel-menu-toggle" type="button" aria-expanded="false" aria-controls="reel-navigation" aria-label="Open navigation" data-menu-toggle><span></span><span></span></button>
		<nav class="reel-navigation" id="reel-navigation" aria-label="Main navigation" data-navigation>
			<a href="#slide-about" data-nav-link>Why SmartMove</a>
			<a href="#slide-impact" data-nav-link>Our impact</a>
			<a href="#slide-process" data-nav-link>How it works</a>
			<a href="user/announcements.php">Travel updates</a>
		</nav>
		<?php if (is_array($passenger)): ?>
			<a class="header-account" href="user/dashboard.php">Hi, <?= htmlspecialchars($firstName ?? 'there', ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&#8599;</span></a>
		<?php else: ?>
			<a class="header-account" href="login.php">Passenger login <span aria-hidden="true">&#8599;</span></a>
		<?php endif; ?>
	</header>

	<nav class="reel-dots" aria-label="Page sections" data-reel-dots>
		<button type="button" data-slide-to="0" aria-label="Go to slide 1: Welcome" aria-controls="slide-welcome" aria-current="true"><span></span></button>
		<button type="button" data-slide-to="1" aria-label="Go to slide 2: Why SmartMove" aria-controls="slide-about"><span></span></button>
		<button type="button" data-slide-to="2" aria-label="Go to slide 3: Our impact" aria-controls="slide-impact"><span></span></button>
		<button type="button" data-slide-to="3" aria-label="Go to slide 4: How it works" aria-controls="slide-process"><span></span></button>
		<button type="button" data-slide-to="4" aria-label="Go to slide 5: Get started" aria-controls="slide-start"><span></span></button>
	</nav>

	<main class="reel-viewport" data-reel-viewport tabindex="0" aria-label="SmartMove introduction">
		<section class="reel-slide reel-hero is-active" id="slide-welcome" data-slide aria-labelledby="hero-title">
			<div class="hero-photo" role="img" aria-label="An intercity coach on a scenic road"></div><div class="hero-colorwash" aria-hidden="true"></div><div class="hero-grain" aria-hidden="true"></div>
			<div class="hero-content">
				<p class="eyebrow eyebrow-light"><span class="eyebrow-mark"></span> GHANA, CONNECTED</p>
				<h1 id="hero-title">Move smarter.<br><span>Travel better.</span></h1>
				<p class="hero-copy">Find your route, reserve your seat, and keep your travel details together. A better journey starts with a clear plan.</p>
				<div class="hero-glass"><div><span class="glass-index">SMARTMOVE / INTERCITY</span><strong>Your next stop starts here.</strong></div><div class="hero-glass-actions"><a class="action-button" href="user/search-trip.php">Book a ticket <span aria-hidden="true">&#8599;</span></a><a class="admin-link" href="admin/dashboard.php">Admin login <span aria-hidden="true">&#8599;</span></a></div></div>
			</div>
			<div class="hero-caption" aria-hidden="true"><span>01 / 05</span><span>THE JOURNEY STARTS HERE</span></div>
			<a class="scroll-cue" href="#slide-about" aria-label="Scroll to Why SmartMove"><span aria-hidden="true">&#8595;</span></a>
		</section>

		<section class="reel-slide feature-slide" id="slide-about" data-slide aria-labelledby="about-title">
			<div class="slide-orbit orbit-one" aria-hidden="true"></div><div class="slide-orbit orbit-two" aria-hidden="true"></div>
			<div class="slide-inner feature-inner">
				<div class="slide-heading" data-reveal="up"><p class="eyebrow"><span class="eyebrow-mark"></span> MADE FOR THE WAY YOU TRAVEL</p><h2 id="about-title">Why <span>SmartMove?</span></h2><p>Less guesswork, more good miles. Everything you need to plan a confident trip.</p></div>
				<div class="feature-grid">
					<article class="feature-card glass-card" data-reveal="left"><span class="feature-icon" aria-hidden="true">&#8635;</span><span class="feature-index">01 / UPDATES</span><h3>Travel updates</h3><p>Stay informed with service notices before you head to the terminal.</p></article>
					<article class="feature-card glass-card" data-reveal="up"><span class="feature-icon" aria-hidden="true">&#9638;</span><span class="feature-index">02 / BOOKING</span><h3>Digital booking</h3><p>Find a departure, choose your seat, and keep ticket details close.</p></article>
					<article class="feature-card glass-card" data-reveal="right"><span class="feature-icon" aria-hidden="true">&#10003;</span><span class="feature-index">03 / JOURNEY</span><h3>Trusted journeys</h3><p>Review trip, vehicle, and driver details in one place.</p></article>
				</div>
				<a class="text-link" href="user/announcements.php">See travel updates <span aria-hidden="true">&#8599;</span></a>
			</div>
			<div class="slide-caption"><span>02 / 05</span><span>THOUGHTFUL TRAVEL, FROM THE START</span></div>
		</section>

		<section class="reel-slide impact-slide" id="slide-impact" data-slide aria-labelledby="impact-title">
			<div class="impact-lines" aria-hidden="true"></div>
			<div class="slide-inner impact-inner">
				<div class="slide-heading slide-heading-light" data-reveal="up"><p class="eyebrow eyebrow-light"><span class="eyebrow-mark"></span> THE NETWORK AT A GLANCE</p><h2 id="impact-title">Going places.<br><span>Together.</span></h2><p>Every route is another way to bring people and places closer.</p></div>
				<div class="stats-grid" data-counter-group>
					<article class="stat-block" data-reveal="left"><strong class="stat-number" data-count="500" data-suffix="+">0</strong><span>Vehicles</span><small>Ready for the road</small></article>
					<article class="stat-block" data-reveal="up"><strong class="stat-number" data-count="10" data-suffix="K+">0</strong><span>Passengers</span><small>Journeys made easier</small></article>
					<article class="stat-block" data-reveal="right"><strong class="stat-number" data-count="50" data-suffix="+">0</strong><span>Routes</span><small>Connecting destinations</small></article>
				</div>
			</div>
			<div class="slide-caption slide-caption-light"><span>03 / 05</span><span>MORE THAN A WAY TO GET THERE</span></div>
		</section>

		<section class="reel-slide process-slide" id="slide-process" data-slide aria-labelledby="process-title">
			<div class="slide-inner process-inner">
				<div class="slide-heading" data-reveal="up"><p class="eyebrow"><span class="eyebrow-mark"></span> SIMPLE BY DESIGN</p><h2 id="process-title">Three steps.<br><span>One good journey.</span></h2></div>
				<div class="process-track">
					<article class="process-step" data-reveal="left"><span class="process-number">01</span><span class="process-icon" aria-hidden="true">&#8981;</span><h3>Search</h3><p>Choose where you’re going and find a departure that works.</p></article>
					<span class="process-connector" aria-hidden="true"></span>
					<article class="process-step" data-reveal="up"><span class="process-number">02</span><span class="process-icon" aria-hidden="true">&#9638;</span><h3>Book</h3><p>Pick your seat and confirm your trip in a few clear steps.</p></article>
					<span class="process-connector" aria-hidden="true"></span>
					<article class="process-step" data-reveal="right"><span class="process-number">03</span><span class="process-icon" aria-hidden="true">&#8599;</span><h3>Travel</h3><p>Keep your booking details handy and enjoy the ride ahead.</p></article>
				</div>
				<a class="action-button action-button-dark" href="user/search-trip.php">Find your route <span aria-hidden="true">&#8599;</span></a>
			</div>
			<div class="slide-caption"><span>04 / 05</span><span>YOUR JOURNEY, MADE SIMPLE</span></div>
		</section>

		<section class="reel-slide start-slide" id="slide-start" data-slide aria-labelledby="start-title">
			<div class="start-backdrop" aria-hidden="true"></div>
			<div class="start-content" data-reveal="up"><p class="eyebrow eyebrow-light"><span class="eyebrow-mark"></span> READY WHEN YOU ARE</p><h2 id="start-title">Ready to<br><span>move?</span></h2><p>Your next journey is only a few steps away.</p><a class="action-button" href="<?= is_array($passenger) ? 'user/dashboard.php' : 'register.php' ?>">Get started <span aria-hidden="true">&#8599;</span></a></div>
			<footer class="reel-footer">
				<a class="reel-brand reel-brand-footer" href="index.php" aria-label="SmartMove home"><span class="reel-brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>smart<span>move</span></span></a>
				<p>Better journeys begin with a better plan.</p>
				<nav class="footer-links" aria-label="Footer links"><a href="user/search-trip.php">Routes</a><a href="user/announcements.php">Travel updates</a><a href="mailto:support@smartmove.local">Contact</a></nav>
				<div class="social-links" aria-label="Social media"><a href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram">ig</a><a href="https://www.facebook.com/" target="_blank" rel="noreferrer" aria-label="Facebook">f</a><a href="https://www.linkedin.com/" target="_blank" rel="noreferrer" aria-label="LinkedIn">in</a></div>
				<small>&copy; <?= date('Y') ?> SmartMove Transport</small>
			</footer>
			<div class="slide-caption slide-caption-light"><span>05 / 05</span><span>YOUR NEXT STOP STARTS HERE</span></div>
		</section>
	</main>
</body>
</html>
