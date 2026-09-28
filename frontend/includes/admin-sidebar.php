<aside class="admin-sidebar" id="admin-sidebar">
	<a class="admin-brand" href="dashboard.php"><span class="admin-brand-mark" aria-hidden="true">S</span><span class="admin-brand-copy">smartmove<small>OPERATIONS</small></span></a>
	<p class="admin-nav-label">MANAGEMENT</p>
	<nav class="admin-nav" aria-label="Admin navigation">
		<a class="<?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="dashboard.php"><span class="admin-nav-mark" aria-hidden="true">01</span><span class="admin-nav-label-text">Overview</span></a>
		<a class="<?= $activePage === 'vehicles' ? 'is-active' : '' ?>" href="vehicles.php"><span class="admin-nav-mark" aria-hidden="true">02</span><span class="admin-nav-label-text">Vehicles</span></a>
		<a class="<?= $activePage === 'routes' ? 'is-active' : '' ?>" href="routes.php"><span class="admin-nav-mark" aria-hidden="true">03</span><span class="admin-nav-label-text">Routes</span></a>
		<a class="<?= $activePage === 'trips' ? 'is-active' : '' ?>" href="trips.php"><span class="admin-nav-mark" aria-hidden="true">04</span><span class="admin-nav-label-text">Trips</span></a>
		<a class="<?= $activePage === 'reports' ? 'is-active' : '' ?>" href="reports.php"><span class="admin-nav-mark" aria-hidden="true">05</span><span class="admin-nav-label-text">Reports</span></a>
		<a class="<?= $activePage === 'feedback' ? 'is-active' : '' ?>" href="feedback.php"><span class="admin-nav-mark" aria-hidden="true">06</span><span class="admin-nav-label-text">Feedback</span></a>
	</nav>
	<div class="admin-sidebar-foot"><span>TRANSPORT OPERATIONS</span><a href="../index.php">SmartMove home <span aria-hidden="true">↗</span></a></div>
</aside>