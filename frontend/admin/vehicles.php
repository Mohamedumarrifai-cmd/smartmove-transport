<?php
$activePage = 'vehicles';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Vehicles | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/admin-panel.css"><script src="../assets/js/admin-panel.js" defer></script>
</head>
<body class="admin-page" data-admin-page="vehicles">
	<div class="admin-shell"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?><main class="admin-main">
		<header class="admin-topbar"><div><p class="admin-eyebrow">FLEET / INVENTORY</p><h1>Vehicles</h1></div><a class="admin-button admin-button-quiet" href="dashboard.php">Operations overview <span aria-hidden="true">→</span></a></header>
		<div class="admin-content"><section class="admin-page-intro"><div><p class="admin-eyebrow">FLEET REGISTER</p><h2>Manage vehicles</h2><p>Add vehicles, update fleet details, or retire a record.</p></div><span class="admin-record-count" data-record-count>— VEHICLES</span></section>
			<div class="admin-workspace"><section class="admin-form-panel"><p class="admin-eyebrow" data-form-eyebrow>NEW VEHICLE</p><h2 data-form-title>Add to the fleet</h2><form data-entity-form>
				<input type="hidden" name="vehicle_id"><label>Registration number<input name="registration_number" maxlength="20" required></label><div class="form-grid"><label>Make<input name="make" maxlength="60" required></label><label>Model<input name="model" maxlength="60" required></label></div>
				<div class="form-grid"><label>Type<select name="vehicle_type" required><option value="BUS">Bus</option><option value="MINIBUS">Minibus</option><option value="COACH">Coach</option><option value="VAN">Van</option></select></label><label>Capacity<input name="capacity" type="number" min="1" max="300" required></label></div>
				<div class="form-grid"><label>Manufacture year<input name="manufacture_year" type="number" min="1950" max="2100"></label><label>Status<select name="status"><option>AVAILABLE</option><option>IN_SERVICE</option><option>MAINTENANCE</option><option>RETIRED</option></select></label></div>
				<div class="form-actions"><button class="admin-button" type="submit" data-submit-label>Save vehicle</button><button class="admin-text-button" type="button" data-form-reset hidden>Cancel edit</button></div><p class="admin-form-message" data-form-message role="status" aria-live="polite"></p>
			</form></section>
			<section class="admin-section admin-table-section"><div class="admin-section-heading"><div><p class="admin-eyebrow">REGISTERED FLEET</p><h2>Vehicle records</h2></div><button class="admin-text-button" type="button" data-refresh>Refresh</button></div><div class="admin-table-wrap"><table><thead><tr><th>Registration</th><th>Vehicle</th><th>Type</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead><tbody data-entity-table><tr><td colspan="6" class="admin-empty">Loading vehicles…</td></tr></tbody></table></div></section></div>
		</div>
	</main></div>
</body>
</html>
