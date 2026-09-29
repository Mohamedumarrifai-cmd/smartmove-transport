<?php
$passengerName = $passenger['full_name'] ?? 'Passenger';
$passengerInitial = strtoupper(substr(trim($passengerName), 0, 1));
?>
<aside class="app-sidebar" id="passenger-sidebar">
	<a class="panel-brand" href="../index.php" aria-label="SmartMove home"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span class="sidebar-brand-label">smartmove</span></a>
	<div class="sidebar-profile"><span class="profile-avatar"><?= htmlspecialchars($passengerInitial, ENT_QUOTES, 'UTF-8') ?></span><span><strong><?= htmlspecialchars($passengerName, ENT_QUOTES, 'UTF-8') ?></strong><small>Passenger account</small></span></div>
	<p class="sidebar-label">YOUR TRAVEL</p>
	<nav class="panel-nav" aria-label="Passenger navigation">
		<a class="<?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="dashboard.php"><span class="nav-index">01</span><span class="nav-label">Overview</span></a>
		<a class="<?= $activePage === 'search' ? 'is-active' : '' ?>" href="search-trip.php"><span class="nav-index">02</span><span class="nav-label">Search trips</span></a>
		<a class="<?= $activePage === 'bookings' ? 'is-active' : '' ?>" href="my-bookings.php"><span class="nav-index">03</span><span class="nav-label">My bookings</span></a>
		<a class="<?= $activePage === 'payments' ? 'is-active' : '' ?>" href="payment.php"><span class="nav-index">04</span><span class="nav-label">Payments</span></a>
		<a class="<?= $activePage === 'announcements' ? 'is-active' : '' ?>" href="announcements.php"><span class="nav-index">05</span><span class="nav-label">Travel updates</span></a>
		<a class="<?= $activePage === 'feedback' ? 'is-active' : '' ?>" href="feedback.php"><span class="nav-index">06</span><span class="nav-label">Feedback</span></a>
	</nav>
	<div class="sidebar-bottom"><span class="sidebar-status"><i></i> All aboard</span><a href="../index.php">SmartMove home <span aria-hidden="true">↗</span></a><a href="../logout.php">Log out <span aria-hidden="true">↗</span></a></div>
</aside>