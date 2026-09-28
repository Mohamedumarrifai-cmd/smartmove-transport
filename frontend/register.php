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
	<title>Create account | SmartMove</title>
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
	<main class="auth-layout register-layout">
		<aside class="auth-art" data-three-scene="auth" aria-label="SmartMove coach on the road">
			<div class="auth-art-text"><p class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> All aboard</p><h1>Good things<br>go places.</h1></div>
			<div class="auth-art-stamp">SMARTMOVE<br><span>TRAVEL, CONSIDERED</span></div>
			<div class="scene-fallback" aria-hidden="true"><div class="fallback-coach"><span></span><i></i><i></i></div></div>
		</aside>
		<section class="auth-form-side">
			<div class="auth-form-wrap register-form-wrap">
				<p class="eyebrow"><span class="eyebrow-dot"></span> Your journey starts here</p>
				<h2>Let’s get you moving.</h2>
				<p class="auth-subtitle">Create your passenger account. The road is yours.</p>
				<form class="auth-form" data-auth-form data-mode="register" novalidate>
					<div class="field-pair">
						<div class="field-group"><label for="full-name">Full name</label><input id="full-name" name="full_name" type="text" autocomplete="name" placeholder="Your name" maxlength="120" required></div>
						<div class="field-group"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+233 ..." maxlength="25" required></div>
					</div>
					<label for="email">Email address</label>
					<input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="254" required>
					<label for="password">Create password</label>
					<input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" maxlength="72" required>
					<p class="password-hint">Use 8 to 72 characters.</p>
					<p class="form-message" data-form-message role="status" aria-live="polite"></p>
					<button class="button button-coral button-submit" type="submit"><span data-button-label>Create account</span><span class="button-arrow" aria-hidden="true">↗</span></button>
				</form>
				<p class="auth-switch">Already travel with us? <a href="login.php">Log in <span aria-hidden="true">→</span></a></p>
				<p class="auth-legal">By creating an account, you agree to SmartMove’s <a href="#terms">Terms</a> and <a href="#privacy">Privacy Policy</a>.</p>
			</div>
			<div class="auth-form-foot"><span>SMARTMOVE / PASSENGER</span><span>02 — 02</span></div>
		</section>
	</main>
</body>
</html>
