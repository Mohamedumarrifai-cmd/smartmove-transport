<aside class="admin-sidebar">
	<a class="admin-brand" href="dashboard.php"><span class="admin-brand-mark" aria-hidden="true">S</span><span>smartmove<small>OPERATIONS</small></span></a>
	<p class="admin-nav-label">MANAGEMENT</p>
	<nav class="admin-nav" aria-label="Admin navigation">
		<a class="<?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="dashboard.php">Overview</a>
		<a class="<?= $activePage === 'vehicles' ? 'is-active' : '' ?>" href="vehicles.php">Vehicles</a>
		<a class="<?= $activePage === 'routes' ? 'is-active' : '' ?>" href="routes.php">Routes</a>
		<a class="<?= $activePage === 'trips' ? 'is-active' : '' ?>" href="trips.php">Trips</a>
		<a class="<?= $activePage === 'reports' ? 'is-active' : '' ?>" href="reports.php">Reports</a>
		<a class="<?= $activePage === 'feedback' ? 'is-active' : '' ?>" href="feedback.php">Feedback</a>
	</nav>
	<div class="admin-sidebar-foot"><span>TRANSPORT OPERATIONS</span><a href="../index.php">SmartMove home <span aria-hidden="true">↗</span></a></div>
</aside>