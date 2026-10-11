<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'bookings';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>My bookings | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="bookings">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / YOUR TRIPS</p><h1>Every mile, remembered.</h1></div>
				<a class="panel-button panel-button-coral" href="search-trip.php">Book another trip <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<section class="booking-page-intro"><div><p class="panel-eyebrow">TRAVEL HISTORY</p><h2>Your journeys</h2><p>Everything booked, in one place.</p></div><div class="booking-count" data-history-count>— <span>TRIPS</span></div></section>
				<div class="booking-controls">
					<div class="filter-tabs" role="group" aria-label="Filter bookings">
						<button class="filter-tab is-active" type="button" data-booking-filter="all" aria-pressed="true">All trips</button>
						<button class="filter-tab" type="button" data-booking-filter="upcoming" aria-pressed="false">Upcoming</button>
						<button class="filter-tab" type="button" data-booking-filter="past" aria-pressed="false">Past</button>
					</div>
					<span class="sort-label">Newest booking first</span>
				</div>
				<p class="panel-feedback" data-history-feedback role="status" aria-live="polite"></p>
				<div class="booking-list" data-bookings-list aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
			</div>
		</main>
	</div>
	<div class="modal fade" id="trip-feedback-modal" tabindex="-1" aria-labelledby="trip-feedback-title" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<form data-trip-feedback-form>
					<div class="modal-header">
						<div><p class="panel-eyebrow mb-1">YOUR EXPERIENCE MATTERS</p><h2 class="modal-title fs-5" id="trip-feedback-title">Trip feedback</h2></div>
						<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p class="small text-secondary mb-3" data-feedback-trip-label></p>
						<p class="small fw-semibold text-uppercase" data-feedback-status-label></p>
						<div data-feedback-completed-fields hidden>
							<div class="mb-3">
								<label class="form-label" for="trip-feedback-rating">Your rating</label>
								<select class="form-select" id="trip-feedback-rating" name="rating">
									<option value="">Choose a rating</option>
									<option value="5">5 - Excellent</option><option value="4">4 - Good</option>
									<option value="3">3 - Fair</option><option value="2">2 - Needs work</option>
									<option value="1">1 - Poor</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label" for="trip-feedback-recommend">Would you recommend this trip?</label>
								<select class="form-select" id="trip-feedback-recommend" name="would_recommend">
									<option value="">Choose yes or no</option><option value="yes">Yes</option><option value="no">No</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label" for="trip-feedback-highlights">Highlights / best moments (optional)</label>
								<textarea class="form-control" id="trip-feedback-highlights" name="highlights" rows="2" maxlength="1000"></textarea>
							</div>
						</div>
						<div data-feedback-incomplete-fields hidden>
							<div class="mb-3">
								<label class="form-label" for="trip-feedback-reason">Why was the trip not completed?</label>
								<select class="form-select" id="trip-feedback-reason" name="reason">
									<option value="">Choose a reason</option>
									<option value="change_of_plans">Change of plans</option><option value="weather">Weather</option>
									<option value="health">Health</option><option value="budget">Budget</option><option value="other">Other</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label" for="trip-feedback-optional-rating">Rating (optional)</label>
								<select class="form-select" id="trip-feedback-optional-rating" name="optional_rating">
									<option value="">No rating</option>
									<option value="5">5 - Excellent</option><option value="4">4 - Good</option>
									<option value="3">3 - Fair</option><option value="2">2 - Needs work</option>
									<option value="1">1 - Poor</option>
								</select>
							</div>
						</div>
						<div class="mb-2">
							<label class="form-label" for="trip-feedback-comment">Comment</label>
							<textarea class="form-control" id="trip-feedback-comment" name="comment" rows="4" maxlength="1000"></textarea>
						</div>
						<p class="panel-feedback mb-0" data-trip-feedback-message role="status" aria-live="polite"></p>
					</div>
					<div class="modal-footer">
						<button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
						<button class="btn btn-primary" type="submit" data-trip-feedback-submit>Save feedback</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</body>
</html>
