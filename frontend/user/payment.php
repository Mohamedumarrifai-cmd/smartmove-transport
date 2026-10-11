<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'payments';
$ticketIds = [];
$checkoutError = null;
if (isset($_GET['tickets'])) {
	$ticketList = $_GET['tickets'];
	if (is_string($ticketList) && trim($ticketList) !== '') {
		foreach (explode(',', $ticketList) as $ticketId) {
			$validatedId = filter_var(trim($ticketId), FILTER_VALIDATE_INT);
			if ($validatedId === false || $validatedId < 1) {
				$checkoutError = 'The ticket list is invalid. Please return to your bookings and try again.';
				$ticketIds = [];
				break;
			}
			$ticketIds[] = $validatedId;
		}
		if (count($ticketIds) > 6 || count(array_unique($ticketIds)) !== count($ticketIds)) {
			$checkoutError = 'The ticket list is invalid. Please return to your bookings and try again.';
			$ticketIds = [];
		}
	} else {
		$checkoutError = 'The ticket list is invalid. Please return to your bookings and try again.';
	}
}
$isCheckout = isset($_GET['tickets']);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Payments | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="<?= $isCheckout ? 'payment-checkout' : 'payments' ?>" data-ticket-ids="<?= htmlspecialchars(implode(',', $ticketIds), ENT_QUOTES, 'UTF-8') ?>" data-checkout-error="<?= htmlspecialchars((string) $checkoutError, ENT_QUOTES, 'UTF-8') ?>">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / PAYMENTS</p><h1><?= $isCheckout ? 'Review your payment.' : 'Your payment records.' ?></h1></div>
				<a class="panel-button panel-button-coral" href="my-bookings.php">View bookings <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<?php if ($isCheckout): ?>
					<section class="page-intro-line"><div><p class="panel-eyebrow">SECURE CHECKOUT</p><h2>Booking total</h2><p>Review your tickets and confirm the amount due.</p></div></section>
					<p class="panel-feedback" data-checkout-feedback role="status" aria-live="polite"><?= htmlspecialchars((string) $checkoutError, ENT_QUOTES, 'UTF-8') ?></p>
					<div class="checkout-card" data-checkout-card <?= $checkoutError !== null || $ticketIds === [] ? 'hidden' : '' ?>>
						<div class="checkout-ticket-list" data-checkout-tickets><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
						<form data-checkout-form>
							<label class="floating-field"><span>Total amount</span><input name="amount" type="text" value="" readonly required><span class="floating-suffix" aria-hidden="true">INR</span></label>
							<label class="floating-field"><span>Payment method</span><select name="payment_method" required><option value="CARD">Card</option><option value="CASH">Cash</option><option value="BANK_TRANSFER">Bank transfer</option><option value="MOBILE_MONEY">Mobile money</option></select></label>
							<button class="panel-button panel-button-coral" type="submit" data-checkout-submit>Confirm payment</button>
						</form>
					</div>
				<?php else: ?>
				<section class="page-intro-line"><div><p class="panel-eyebrow">TRAVEL SPENDING</p><h2>Payment history</h2><p>Payments recorded for your SmartMove tickets.</p></div><span class="history-count" data-payment-count>— <small>RECORDS</small></span></section>
				<p class="panel-feedback" data-payment-feedback role="status" aria-live="polite"></p>
				<div class="payment-list" data-payment-list aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
				<p class="payment-note"><span class="material-symbols-rounded" aria-hidden="true">info</span> Online payment processing is not enabled yet. This page shows payment records already registered to your tickets.</p>
				<?php endif; ?>
			</div>
		</main>
	</div>
</body>
</html>
