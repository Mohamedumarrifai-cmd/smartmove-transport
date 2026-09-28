<?php
$activePage = 'feedback';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Passenger feedback | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/admin-panel.css"><script src="../assets/js/admin-panel.js" defer></script></head>
<body class="admin-page" data-admin-page="feedback"><div class="admin-shell"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?><main class="admin-main">
	<header class="admin-topbar"><div><p class="admin-eyebrow">PASSENGER EXPERIENCE</p><h1>Feedback</h1></div><button class="admin-button admin-button-quiet" type="button" data-refresh>Refresh feedback <span aria-hidden="true">↻</span></button></header>
	<div class="admin-content"><section class="admin-page-intro"><div><p class="admin-eyebrow">PASSENGER REVIEWS</p><h2>Listen and learn</h2><p>Recent ratings and comments submitted by passengers.</p></div><span class="admin-record-count" data-feedback-count>— REVIEWS</span></section>
		<section class="admin-section"><div class="admin-table-wrap"><table><thead><tr><th>Rating</th><th>Passenger</th><th>Trip / route</th><th>Comment</th><th>Received</th></tr></thead><tbody data-feedback-table><tr><td colspan="5" class="admin-empty">Loading feedback…</td></tr></tbody></table></div></section>
		<p class="admin-feedback" data-admin-feedback role="status" aria-live="polite"></p>
	</div></main></div></body></html>
