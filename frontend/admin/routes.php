<?php
$activePage = 'routes';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Routes | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/admin-panel.css"><script src="../assets/js/admin-panel.js" defer></script>
</head>
<body class="admin-page" data-admin-page="routes"><div class="admin-shell"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?><main class="admin-main">
	<header class="admin-topbar"><div><p class="admin-eyebrow">NETWORK / ROUTES</p><h1>Routes</h1></div><a class="admin-button admin-button-quiet" href="trips.php">Schedule a trip <span aria-hidden="true">→</span></a></header>
	<div class="admin-content"><section class="admin-page-intro"><div><p class="admin-eyebrow">SERVICE NETWORK</p><h2>Manage routes</h2><p>Maintain city pairs, travel distance, and route availability.</p></div><span class="admin-record-count" data-record-count>— ROUTES</span></section>
	<div class="admin-workspace"><section class="admin-form-panel"><p class="admin-eyebrow" data-form-eyebrow>NEW ROUTE</p><h2 data-form-title>Create a route</h2><form data-entity-form>
		<input type="hidden" name="route_id"><label>Route name<input name="route_name" maxlength="120" required></label><div class="form-grid"><label>Origin city<input name="origin_city" maxlength="80" required></label><label>Destination city<input name="destination_city" maxlength="80" required></label></div>
		<div class="form-grid"><label>Distance (km)<input name="distance_km" type="number" min="0.01" step="0.01" required></label><label>Duration (minutes)<input name="estimated_duration_minutes" type="number" min="1" required></label></div><label>Availability<select name="is_active"><option value="Y">Active</option><option value="N">Inactive</option></select></label>
		<div class="form-actions"><button class="admin-button" type="submit" data-submit-label>Save route</button><button class="admin-text-button" type="button" data-form-reset hidden>Cancel edit</button></div><p class="admin-form-message" data-form-message role="status" aria-live="polite"></p>
	</form></section><section class="admin-section admin-table-section"><div class="admin-section-heading"><div><p class="admin-eyebrow">ROUTE DIRECTORY</p><h2>Route records</h2></div><button class="admin-text-button" type="button" data-refresh>Refresh</button></div><div class="admin-table-wrap"><table><thead><tr><th>Route</th><th>Origin</th><th>Destination</th><th>Distance</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead><tbody data-entity-table><tr><td colspan="7" class="admin-empty">Loading routes…</td></tr></tbody></table></div></section></div>
	</div></main></div></body></html>
