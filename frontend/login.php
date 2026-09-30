<?php
require_once __DIR__ . '/includes/session.php';
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
	<meta name="theme-color" content="#102b43">
	<title>Log in | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<link rel="stylesheet" href="assets/css/auth.css">
	<script src="assets/js/auth.js" defer></script>
	<script src="assets/js/auth-animations.js" defer></script>
</head>
<body class="auth-editorial auth-login">
	<main class="auth-shell">
		<aside class="auth-art" aria-labelledby="art-title">
			<a class="auth-brand" href="index.php" aria-label="SmartMove home"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>smartmove</span></a>
			<div class="art-ambient" aria-hidden="true"></div>
			<div class="art-copy"><p class="art-overline">SMARTMOVE / YOUR JOURNEY</p><p class="art-stamp">ALL ABOARD</p><h1 id="art-title">Good things<br><em>go places.</em></h1><p class="art-deck">A familiar seat, a new horizon. Pick up where your next journey begins.</p></div>
			<div class="art-illustration" data-parallax aria-hidden="true">
				<svg class="route-art" viewBox="0 0 600 300" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path class="route-line route-line-back" d="M28 224C121 224 103 115 202 115C286 115 255 238 357 238C453 238 431 76 575 76"/><path class="route-line route-line-front" d="M28 224C121 224 103 115 202 115C286 115 255 238 357 238C453 238 431 76 575 76"/><circle class="route-stop" cx="202" cy="115" r="6"/><circle class="route-stop" cx="357" cy="238" r="6"/><circle class="route-stop route-stop-end" cx="575" cy="76" r="7"/>
					<g class="bus-float" transform="translate(235 142)"><rect width="177" height="80" rx="17" fill="#F7FAFC"/><path d="M17 17C17 11.477 21.477 7 27 7H151C156.523 7 161 11.477 161 17V48H17V17Z" fill="#1A365D"/><path d="M26 17C26 14.239 28.239 12 31 12H71V40H26V17Z" fill="#8FD0D0"/><path d="M79 12H113V40H79V12Z" fill="#8FD0D0"/><path d="M121 12H150C152.761 12 155 14.239 155 17V40H121V12Z" fill="#8FD0D0"/><rect x="19" y="55" width="139" height="7" rx="3.5" fill="#ED8936"/><circle cx="43" cy="78" r="12" fill="#172B40"/><circle cx="43" cy="78" r="5" fill="#CBD4D9"/><circle cx="137" cy="78" r="12" fill="#172B40"/><circle cx="137" cy="78" r="5" fill="#CBD4D9"/></g>
				</svg>
			</div>
			<div class="trust-badge"><span class="trust-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></span><span><strong>Trusted by 10,000+</strong><small>passengers on the move</small></span></div>
			<footer class="art-footer"><span>SMARTMOVE / TRAVEL, CONSIDERED</span><a href="index.php">Back to home <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></footer>
		</aside>
		<section class="auth-panel" aria-labelledby="form-title">
			<div class="panel-topline"><a href="index.php" class="mobile-brand">smart<span>move</span></a><span class="step-label">PASSENGER ACCESS <b>01 / 01</b></span><span class="step-track" aria-hidden="true"><i></i></span></div>
			<div class="form-wrap">
				<p class="form-kicker">GOOD TO HAVE YOU BACK</p><h2 id="form-title"><span>Your next stop</span><br><em>starts here.</em></h2><p class="form-intro">Sign in to pick up where your journey left off.</p>
				<form class="auth-form" data-auth-form data-mode="login" novalidate>
					<div class="field-control" data-reveal-field><input id="email" name="email" type="email" autocomplete="email" placeholder=" " maxlength="254" required><label for="email">Email address</label><i class="field-icon fa-regular fa-envelope" aria-hidden="true"></i><span class="field-underline"></span></div>
					<div class="field-control" data-reveal-field><input id="password" name="password" type="password" autocomplete="current-password" placeholder=" " minlength="8" maxlength="72" required><label for="password">Password</label><i class="field-icon fa-solid fa-lock" aria-hidden="true"></i><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button><span class="field-underline"></span></div>
					<div class="login-aux"><a href="mailto:support@smartmove.local?subject=Password%20reset">Need help signing in?</a></div>
					<p class="form-message" data-form-message role="status" aria-live="polite"></p>
					<button class="submit-button" type="submit"><span data-button-label>Log in</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
				</form>
				<div class="social-proof"><div class="avatar-stack" aria-hidden="true"><span>AK</span><span>JM</span><span>EF</span><span>+</span></div><p><strong>Join 500+ daily commuters</strong><small>making the move with SmartMove</small></p></div>
				<p class="auth-switch">New to SmartMove? <a href="register.php">Create an account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
				<p class="auth-legal">By continuing, you agree to SmartMove’s <a href="#terms">Terms</a> and <a href="#privacy">Privacy Policy</a>.</p>
			</div>
		</section>
	</main>
</body>
</html>
