<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'announcements';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Travel updates | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="announcements">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / TRAVEL UPDATES</p><h1>Good to know before you go.</h1></div>
				<a class="panel-button panel-button-coral" href="search-trip.php">Explore trips <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<section class="page-intro-line"><div><p class="panel-eyebrow">FROM THE SMARTMOVE TEAM</p><h2>Latest travel updates</h2><p>Service notices and information for your next journey.</p></div></section>
				<p class="panel-feedback" data-announcement-feedback role="status" aria-live="polite"></p>
				<div class="announcement-list" data-announcement-list aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
			</div>
		</main>
	</div>
</body>
</html>
