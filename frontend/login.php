<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
if (isset($_SESSION['passenger'])) {
	header('Location: index.php');
	exit;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#172421">
	<title>Log in | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/3d.css">
	<script type="module" src="assets/js/three-scene.js"></script>
	<script src="assets/js/auth.js" defer></script>
</head>
<body class="auth-page">
	<header class="auth-header">
		<a class="brand brand-light" href="index.php" aria-label="SmartMove home"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>smartmove</span></a>
		<a class="auth-back" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
	</header>
	<main class="auth-layout">
		<aside class="auth-art" data-three-scene="auth" aria-label="SmartMove coach on the road">
			<div class="auth-art-text"><p class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Your next stop</p><h1>Starts right<br>here.</h1></div>
			<div class="auth-scene-note glass-panel"><span class="live-dot"></span><span>YOUR JOURNEY, IN GOOD HANDS</span><span class="note-mark">SM / 01</span></div>
			<div class="scene-fallback" aria-hidden="true"><div class="fallback-coach"><span></span><i></i><i></i></div></div>
		</aside>
		<section class="auth-form-side">
			<div class="auth-form-wrap">
				<p class="eyebrow"><span class="eyebrow-dot"></span> Welcome back</p>
				<h2>Good to see you.</h2>
				<p class="auth-subtitle">Sign in to pick up where your journey left off.</p>
				<form class="auth-form" data-auth-form data-mode="login" novalidate>
					<label for="email">Email address</label>
					<input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="254" required>
					<div class="password-label"><label for="password">Password</label><a href="mailto:support@smartmove.local?subject=Password%20reset">Need help?</a></div>
					<input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" minlength="8" maxlength="72" required>
					<p class="form-message" data-form-message role="status" aria-live="polite"></p>
					<button class="button button-coral button-submit" type="submit"><span data-button-label>Log in</span><span class="button-arrow" aria-hidden="true">↗</span></button>
				</form>
				<p class="auth-switch">New to SmartMove? <a href="register.php">Create an account <span aria-hidden="true">→</span></a></p>
				<p class="auth-legal">By continuing, you agree to SmartMove’s <a href="#terms">Terms</a> and <a href="#privacy">Privacy Policy</a>.</p>
			</div>
			<div class="auth-form-foot"><span>SMARTMOVE / PASSENGER</span><span>01 — 02</span></div>
		</section>
	</main>
</body>
</html>
