const passengerApi = '../../backend/api/';

async function requestJson(endpoint, options = {}) {
	const response = await fetch(passengerApi + endpoint, {
		credentials: 'same-origin',
		headers: { Accept: 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) },
		...options,
	});
	let result;
	try {
		result = await response.json();
	} catch {
		throw new Error('The server returned an unreadable response.');
	}
	if (response.status === 401) {
		window.location.assign('../login.php');
		throw new Error('Sign in to continue.');
	}
	if (!response.ok || !result.success) {
		throw new Error(result.error || 'This request could not be completed.');
	}
	return result.data;
}

function parseOracleDate(value) {
	if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;
	if (typeof value !== 'string') return null;
	const direct = new Date(value);
	if (!Number.isNaN(direct.getTime())) return direct;
	const oracleDate = value.trim().match(/^(\d{1,2})-([A-Z]{3})-(\d{2}|\d{4})\s+(\d{1,2})[.:](\d{2})[.:](\d{2})(?:[.,]\d+)?\s*(AM|PM)?$/i);
	if (!oracleDate) return null;
	const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
	const month = months.indexOf(oracleDate[2].toUpperCase());
	if (month < 0) return null;
	let year = Number(oracleDate[3]);
	if (year < 100) year += year < 50 ? 2000 : 1900;
	let hour = Number(oracleDate[4]);
	const meridiem = oracleDate[7]?.toUpperCase();
	if (meridiem === 'PM' && hour < 12) hour += 12;
	if (meridiem === 'AM' && hour === 12) hour = 0;
	const parsed = new Date(year, month, Number(oracleDate[1]), hour, Number(oracleDate[5]), Number(oracleDate[6]));
	return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatDate(value, options = { weekday: 'short', month: 'short', day: 'numeric' }) {
	const date = parseOracleDate(value);
	return date ? new Intl.DateTimeFormat('en', options).format(date) : 'Time to be confirmed';
}

function formatTime(value) {
	const date = parseOracleDate(value);
	return date ? new Intl.DateTimeFormat('en', { hour: 'numeric', minute: '2-digit' }).format(date) : '—';
}

function formatFare(value) {
	const fare = Number(value);
	return Number.isFinite(fare)
		? new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS', maximumFractionDigits: 2 }).format(fare)
		: 'Fare unavailable';
}

function formatRupees(value) {
	const amount = Number(value);
	return Number.isFinite(amount)
		? `Rs. ${new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(amount)}`
		: 'Amount unavailable';
}

function departureIsUpcoming(trip) {
	const departure = parseOracleDate(trip.departure_time);
	return departure !== null && departure.getTime() > Date.now();
}

function setMessage(element, message, isError = true) {
	if (!element) return;
	element.textContent = message;
	element.classList.toggle('is-error', isError);
	element.classList.toggle('is-success', Boolean(message) && !isError);
	if (!message) element.classList.remove('is-error');
}

function createRouteMap(routes) {
	return new Map(routes.map((route) => [String(route.route_id), route]));
}

function appendOption(select, value, label) {
	const option = document.createElement('option');
	option.value = value;
	option.textContent = label;
	select.append(option);
}

function populateLocations(routes) {
	const originSelect = document.querySelector('#origin');
	const destinationSelect = document.querySelector('#destination');
	const origins = [...new Set(routes.map((route) => route.origin_city).filter(Boolean))].sort();
	const destinations = [...new Set(routes.map((route) => route.destination_city).filter(Boolean))].sort();
	origins.forEach((origin) => appendOption(originSelect, origin, origin));
	destinations.forEach((destination) => appendOption(destinationSelect, destination, destination));
	const query = new URLSearchParams(window.location.search);
	originSelect.value = query.get('origin') || '';
	destinationSelect.value = query.get('destination') || '';
	document.querySelector('#departure-date').value = query.get('date') || '';
}

function makeTripCard(trip, route) {
	const article = document.createElement('article');
	article.className = 'trip-result-row';
	const time = document.createElement('div');
	time.className = 'trip-result-time';
	const departure = document.createElement('strong');
	departure.textContent = formatTime(trip.departure_time);
	const date = document.createElement('span');
	date.textContent = formatDate(trip.departure_time);
	time.append(departure, date);

	const routeDetails = document.createElement('div');
	routeDetails.className = 'trip-result-route';
	const meta = document.createElement('p');
	meta.className = 'route-meta';
	meta.textContent = route.route_name || `ROUTE ${trip.route_id}`;
	const routeName = document.createElement('h3');
	const from = document.createElement('span');
	from.textContent = route.origin_city || 'Origin';
	const arrow = document.createElement('span');
	arrow.textContent = '→';
	const to = document.createElement('span');
	to.textContent = route.destination_city || 'Destination';
	routeName.append(from, arrow, to);
	const details = document.createElement('small');
	details.textContent = `${formatTime(trip.departure_time)} departure · ${formatTime(trip.arrival_time)} arrival`;
	routeDetails.append(meta, routeName, details);

	const fare = document.createElement('div');
	fare.className = 'trip-result-fare';
	const fareLabel = document.createElement('span');
	fareLabel.textContent = 'PER SEAT';
	const fareValue = document.createElement('strong');
	fareValue.textContent = formatFare(trip.fare);
	fare.append(fareLabel, fareValue);

	const book = document.createElement('a');
	book.className = 'panel-button panel-button-coral';
	book.href = `book-ticket.php?trip_id=${encodeURIComponent(trip.trip_id)}`;
	book.append(document.createTextNode('Choose seat'));
	const bookArrow = document.createElement('span');
	bookArrow.setAttribute('aria-hidden', 'true');
	bookArrow.textContent = '↗';
	book.append(bookArrow);
	article.append(time, routeDetails, fare, book);
	return article;
}

async function initTripSearch() {
	const form = document.querySelector('[data-trip-search]');
	const results = document.querySelector('[data-trip-results]');
	const count = document.querySelector('[data-results-count]');
	const title = document.querySelector('[data-results-title]');
	const feedback = document.querySelector('[data-search-feedback]');
	let trips = [];
	let routeMap = new Map();

	const renderTrips = () => {
		const origin = form.elements.origin.value.toLowerCase();
		const destination = form.elements.destination.value.toLowerCase();
		const date = form.elements.date.value;
		const matches = trips.filter((trip) => {
			const route = routeMap.get(String(trip.route_id));
			if (!route || !['SCHEDULED', 'BOARDING'].includes(String(trip.status).toUpperCase()) || !departureIsUpcoming(trip)) return false;
			if (origin && String(route.origin_city).toLowerCase() !== origin) return false;
			if (destination && String(route.destination_city).toLowerCase() !== destination) return false;
			if (date) {
				const departure = parseOracleDate(trip.departure_time);
				if (!departure || departure.toLocaleDateString('en-CA') !== date) return false;
			}
			return true;
		}).sort((first, second) => parseOracleDate(first.departure_time) - parseOracleDate(second.departure_time));

		results.replaceChildren();
		count.textContent = `${matches.length} ${matches.length === 1 ? 'departure' : 'departures'}`;
		title.textContent = origin || destination ? 'Your matching journeys' : 'Next departures';
		if (matches.length === 0) {
			const empty = document.createElement('div');
			empty.className = 'no-results';
			const heading = document.createElement('strong');
			heading.textContent = 'No departures match just yet.';
			const detail = document.createElement('p');
			detail.textContent = 'Try another route or date and we’ll check again.';
			empty.append(heading, detail);
			results.append(empty);
			return;
		}
		matches.forEach((trip) => results.append(makeTripCard(trip, routeMap.get(String(trip.route_id)))));
	};

	try {
		const [routeData, tripData] = await Promise.all([
			requestJson('route-api.php'),
			requestJson('trip-api.php'),
		]);
		const routes = routeData.filter((route) => route.is_active !== 'N');
		trips = tripData;
		routeMap = createRouteMap(routes);
		populateLocations(routes);
		form.addEventListener('submit', (event) => {
			event.preventDefault();
			setMessage(feedback, '');
			renderTrips();
		});
		form.elements.origin.addEventListener('change', renderTrips);
		form.elements.destination.addEventListener('change', renderTrips);
		form.elements.date.addEventListener('change', renderTrips);
		renderTrips();
	} catch (error) {
		results.replaceChildren();
		count.textContent = 'Unavailable';
		setMessage(feedback, error.message);
	}
}

function createNextTripCard(booking) {
	const card = document.createElement('article');
	card.className = 'next-trip-card';
	const heading = document.createElement('h3');
	heading.textContent = `${booking.origin_city} to ${booking.destination_city}`;
	const detail = document.createElement('p');
	detail.textContent = booking.route_name || `Route ${booking.route_id}`;
	const date = document.createElement('span');
	date.className = 'next-date';
	date.textContent = `${formatDate(booking.departure_time, { weekday: 'long', month: 'long', day: 'numeric' })} · ${formatTime(booking.departure_time)}`;
	const ticket = document.createElement('span');
	ticket.className = 'next-ticket';
	ticket.textContent = `Ticket #${booking.ticket_id} · Seat ${booking.seat_number}`;
	const link = document.createElement('a');
	link.href = 'my-bookings.php';
	link.setAttribute('aria-label', 'View your bookings');
	link.textContent = '↗';
	card.append(heading, detail, date, ticket, link);
	return card;
}

async function initDashboard() {
	const feedback = document.querySelector('[data-panel-feedback]');
	try {
		const bookings = await requestJson('booking-api.php');
		const upcoming = bookings
			.filter((booking) => departureIsUpcoming(booking) && !['CANCELLED', 'REFUNDED'].includes(String(booking.status).toUpperCase()))
			.sort((first, second) => parseOracleDate(first.departure_time) - parseOracleDate(second.departure_time));
		document.querySelector('[data-stat-total]').textContent = String(bookings.length);
		document.querySelector('[data-stat-upcoming]').textContent = String(upcoming.length);
		document.querySelector('[data-stat-paid]').textContent = String(bookings.filter((booking) => String(booking.status).toUpperCase() === 'PAID').length);
		const nextTrip = document.querySelector('[data-next-trip]');
		nextTrip.replaceChildren();
		if (upcoming.length > 0) {
			nextTrip.append(createNextTripCard(upcoming[0]));
		} else {
			const empty = document.createElement('div');
			empty.className = 'empty-state';
			const index = document.createElement('span');
			index.className = 'empty-index';
			index.textContent = '01';
			const message = document.createElement('p');
			message.textContent = bookings.length ? 'No upcoming trips. Ready for another?' : 'Your next departure will show up here.';
			empty.append(index, message);
			nextTrip.append(empty);
		}
	} catch (error) {
		setMessage(feedback, error.message);
	}
}

function createBookingRow(booking) {
	const row = document.createElement('article');
	row.className = 'booking-row';
	const route = document.createElement('div');
	route.className = 'booking-route';
	const routeName = document.createElement('strong');
	routeName.textContent = `${booking.origin_city} to ${booking.destination_city}`;
	const routeDetail = document.createElement('small');
	routeDetail.textContent = `${booking.route_name || `Route ${booking.route_id}`} · ${formatFare(booking.fare)}`;
	route.append(routeName, routeDetail);

	const date = document.createElement('div');
	date.className = 'booking-date';
	const dateStrong = document.createElement('strong');
	dateStrong.textContent = formatDate(booking.departure_time, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
	date.append(dateStrong, document.createTextNode(`${formatTime(booking.departure_time)} departure`));

	const seat = document.createElement('div');
	seat.className = 'booking-seat';
	seat.textContent = `BOOKING #${booking.ticket_id}`;
	const seatNumber = document.createElement('strong');
	seatNumber.textContent = `Seat ${booking.seat_number}`;
	seat.append(seatNumber);

	const status = document.createElement('span');
	const statusName = String(booking.status || 'BOOKED').toLowerCase();
	status.className = `booking-status status-${statusName}`;
	status.textContent = String(booking.status || 'BOOKED').replaceAll('_', ' ');
	const feedbackButton = document.createElement('button');
	feedbackButton.className = 'panel-button panel-button-quiet booking-feedback-button';
	feedbackButton.type = 'button';
	feedbackButton.dataset.feedbackTripId = booking.trip_id;
	feedbackButton.dataset.feedbackTripLabel = `${booking.origin_city} to ${booking.destination_city}`;
	feedbackButton.textContent = 'Give feedback';
	row.append(route, date, seat, status, feedbackButton);
	return row;
}

function initBookingHistory() {
	const list = document.querySelector('[data-bookings-list]');
	const feedback = document.querySelector('[data-history-feedback]');
	const count = document.querySelector('[data-history-count]');
	const filters = [...document.querySelectorAll('[data-booking-filter]')];
	const feedbackModalElement = document.querySelector('#trip-feedback-modal');
	const feedbackForm = document.querySelector('[data-trip-feedback-form]');
	const feedbackModal = window.bootstrap && feedbackModalElement
		? window.bootstrap.Modal.getOrCreateInstance(feedbackModalElement)
		: null;
	let activeFeedbackTripId = null;
	let activeFeedbackStatus = null;
	let activeFeedbackId = null;
	let bookings = [];
	let activeFilter = 'all';

	const render = () => {
		const now = Date.now();
		const filtered = bookings.filter((booking) => {
			const upcoming = departureIsUpcoming(booking) && !['CANCELLED', 'REFUNDED', 'USED', 'COMPLETED'].includes(String(booking.status).toUpperCase());
			if (activeFilter === 'upcoming') return upcoming;
			if (activeFilter === 'past') return !upcoming;
			return true;
		});
		list.replaceChildren();
		if (filtered.length === 0) {
			const empty = document.createElement('div');
			empty.className = 'booking-row-empty';
			empty.textContent = bookings.length === 0 ? 'No bookings yet. Your next trip is waiting.' : 'No bookings in this view.';
			list.append(empty);
			return;
		}
		filtered.forEach((booking) => list.append(createBookingRow(booking)));
	};

	filters.forEach((button) => button.addEventListener('click', () => {
		activeFilter = button.dataset.bookingFilter;
		filters.forEach((filter) => {
			const isActive = filter === button;
			filter.classList.toggle('is-active', isActive);
			filter.setAttribute('aria-pressed', String(isActive));
		});
		render();
	}));

	list.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-feedback-trip-id]');
		if (!button) return;
		if (!feedbackModal) {
			setMessage(feedback, 'Feedback form could not be opened. Reload the page and try again.');
			return;
		}

		activeFeedbackTripId = Number(button.dataset.feedbackTripId);
		activeFeedbackId = null;
		activeFeedbackStatus = null;
		const submitButton = feedbackForm.querySelector('[data-trip-feedback-submit]');
		const modalMessage = document.querySelector('[data-trip-feedback-message]');
		feedbackForm.reset();
		submitButton.disabled = true;
		submitButton.textContent = 'Loading…';
		document.querySelector('[data-feedback-trip-label]').textContent = button.dataset.feedbackTripLabel;
		setMessage(modalMessage, '');
		feedbackModal.show();

		try {
			const details = await requestJson(`feedback-api.php?action=get&trip_id=${encodeURIComponent(activeFeedbackTripId)}`);
			activeFeedbackStatus = details.trip_status_at_feedback;
			const completed = activeFeedbackStatus === 'completed';
			document.querySelector('[data-feedback-status-label]').textContent = `Trip status: ${activeFeedbackStatus.replaceAll('_', ' ')}`;
			document.querySelector('[data-feedback-completed-fields]').hidden = !completed;
			document.querySelector('[data-feedback-incomplete-fields]').hidden = completed;
			feedbackForm.elements.rating.required = completed;
			feedbackForm.elements.would_recommend.required = completed;
			feedbackForm.elements.reason.required = !completed;
			feedbackForm.elements.comment.required = !completed;

			const savedFeedback = details.feedback;
			if (savedFeedback) {
				activeFeedbackId = savedFeedback._id;
				button.textContent = 'Edit feedback';
				if (completed) {
					feedbackForm.elements.rating.value = savedFeedback.rating || '';
					feedbackForm.elements.would_recommend.value = savedFeedback.would_recommend === true
						? 'yes'
						: savedFeedback.would_recommend === false ? 'no' : '';
					feedbackForm.elements.highlights.value = savedFeedback.highlights || '';
				} else {
					feedbackForm.elements.optional_rating.value = savedFeedback.rating || '';
					feedbackForm.elements.reason.value = savedFeedback.reason || '';
				}
				feedbackForm.elements.comment.value = savedFeedback.comment || '';
			}
			submitButton.textContent = savedFeedback ? 'Update feedback' : 'Save feedback';
		} catch (error) {
			setMessage(modalMessage, error.message);
			submitButton.textContent = 'Save feedback';
			submitButton.disabled = true;
		} finally {
			if (activeFeedbackStatus !== null) submitButton.disabled = false;
		}
	});

	feedbackForm.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!feedbackForm.reportValidity() || !activeFeedbackTripId || !activeFeedbackStatus) return;
		const submitButton = feedbackForm.querySelector('[data-trip-feedback-submit]');
		const completed = activeFeedbackStatus === 'completed';
		const ratingValue = completed ? feedbackForm.elements.rating.value : feedbackForm.elements.optional_rating.value;
		const payload = {
			trip_id: activeFeedbackTripId,
			rating: ratingValue ? Number(ratingValue) : null,
			comment: feedbackForm.elements.comment.value.trim(),
			reason: completed ? null : feedbackForm.elements.reason.value,
			would_recommend: completed ? feedbackForm.elements.would_recommend.value === 'yes' : null,
			highlights: completed ? feedbackForm.elements.highlights.value.trim() : '',
		};
		submitButton.disabled = true;
		setMessage(document.querySelector('[data-trip-feedback-message]'), 'Saving feedback…', false);
		try {
			await requestJson(
				activeFeedbackId
					? `feedback-api.php?action=update&id=${encodeURIComponent(activeFeedbackId)}`
					: 'feedback-api.php?action=create',
				{
					method: activeFeedbackId ? 'PUT' : 'POST',
					body: JSON.stringify(payload),
				}
			);
			list.querySelectorAll('[data-feedback-trip-id]').forEach((trigger) => {
				if (Number(trigger.dataset.feedbackTripId) === activeFeedbackTripId) {
					trigger.textContent = 'Edit feedback';
				}
			});
			feedbackModal.hide();
			setMessage(feedback, 'Trip feedback saved.', false);
		} catch (error) {
			setMessage(document.querySelector('[data-trip-feedback-message]'), error.message);
		} finally {
			submitButton.disabled = false;
		}
	});

	requestJson('booking-api.php')
		.then((data) => {
			bookings = data.sort((first, second) => (parseOracleDate(second.booking_date)?.getTime() || 0) - (parseOracleDate(first.booking_date)?.getTime() || 0));
			count.textContent = `${bookings.length} TRIPS`;
			render();
		})
		.catch((error) => {
			list.replaceChildren();
			setMessage(feedback, error.message);
		});
}

async function initBooking() {
	const page = document.body;
	const tripId = Number(page.dataset.tripId);
	const layout = document.querySelector('[data-booking-layout]');
	const empty = document.querySelector('[data-booking-empty]');
	const feedback = document.querySelector('[data-booking-feedback]');
	const form = document.querySelector('[data-booking-form]');
	if (!Number.isInteger(tripId) || tripId < 1) return;

	try {
		const trip = await requestJson(`trip-api.php?id=${encodeURIComponent(tripId)}`);
		const [route, vehicle] = await Promise.all([
			requestJson(`route-api.php?id=${encodeURIComponent(trip.route_id)}`),
			requestJson(`vehicle-api.php?id=${encodeURIComponent(trip.vehicle_id)}`),
		]);
		if (!['SCHEDULED', 'BOARDING'].includes(String(trip.status).toUpperCase()) || !departureIsUpcoming(trip)) {
			throw new Error('This trip is no longer open for booking. Please choose another departure.');
		}
		document.querySelector('[data-booking-route]').textContent = route.route_name || 'Your journey';
		document.querySelector('[data-booking-origin]').textContent = route.origin_city;
		document.querySelector('[data-booking-destination]').textContent = route.destination_city;
		document.querySelector('[data-booking-departure]').textContent = `${formatDate(trip.departure_time, { weekday: 'short', month: 'short', day: 'numeric' })} · ${formatTime(trip.departure_time)}`;
		document.querySelector('[data-booking-arrival]').textContent = `${formatDate(trip.arrival_time, { weekday: 'short', month: 'short', day: 'numeric' })} · ${formatTime(trip.arrival_time)}`;
		document.querySelector('[data-booking-fare]').textContent = formatFare(trip.fare);
		const seatCountInput = document.querySelector('#number-of-seats');
		[...seatCountInput.options].forEach((option) => {
			option.disabled = Number(option.value) > Number(vehicle.capacity);
		});
		document.querySelector('[data-seat-hint]').textContent = `Choose 1 to ${Math.min(6, Number(vehicle.capacity))} seats. Available seats are assigned when you book.`;
		document.querySelector('[data-review-fare]').textContent = formatFare(trip.fare);
		document.querySelector('[data-review-departure]').textContent = `${formatDate(trip.departure_time, { weekday: 'short', month: 'short', day: 'numeric' })} · ${formatTime(trip.departure_time)}`;
		layout.hidden = false;
		empty.hidden = true;
		setMessage(feedback, '');
	} catch (error) {
		setMessage(feedback, error.message);
		if (empty) empty.hidden = false;
	}

	const bookingSteps = [...form.querySelectorAll('[data-booking-step]')];
	const progressSteps = [...document.querySelectorAll('[data-booking-progress]')];
	const showBookingStep = (stepNumber) => {
		bookingSteps.forEach((step) => {
			const isCurrent = Number(step.dataset.bookingStep) === stepNumber;
			step.hidden = !isCurrent;
			step.classList.toggle('is-active', isCurrent);
		});
		progressSteps.forEach((step) => {
			const isCurrent = Number(step.dataset.bookingProgress) === stepNumber;
			step.classList.toggle('is-current', isCurrent);
			step.classList.toggle('is-complete', Number(step.dataset.bookingProgress) < stepNumber);
			if (isCurrent) step.setAttribute('aria-current', 'step');
			else step.removeAttribute('aria-current');
		});
	};
	form.querySelector('[data-booking-next]').addEventListener('click', () => {
		const seatCountInput = form.elements.number_of_seats;
		if (!seatCountInput.reportValidity()) return;
		const seatCount = Number(seatCountInput.value);
		document.querySelector('[data-review-seats]').textContent = `${seatCount} ${seatCount === 1 ? 'seat' : 'seats'}`;
		showBookingStep(2);
	});
	form.querySelector('[data-booking-back]').addEventListener('click', () => showBookingStep(1));

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!form.reportValidity()) return;
		const button = form.querySelector('[data-booking-confirm]');
		const originalText = button.textContent;
		button.disabled = true;
		button.textContent = 'Reserving…';
		try {
			const data = await requestJson('booking-api.php', {
				method: 'POST',
				body: JSON.stringify({
					action: 'create_multi',
					trip_id: tripId,
					number_of_seats: Number(form.elements.number_of_seats.value),
				}),
			});
			const ticketIds = data.ticket_ids.join(',');
			setMessage(feedback, `${data.seats.length} ${data.seats.length === 1 ? 'seat' : 'seats'} reserved. Opening payment…`, false);
			window.setTimeout(() => {
				window.location.assign(`payment.php?tickets=${encodeURIComponent(ticketIds)}`);
			}, 500);
		} catch (error) {
			setMessage(feedback, error.message);
			button.disabled = false;
			button.textContent = originalText;
		}
	});
}

function makeAnnouncementRow(announcement) {
	const article = document.createElement('article');
	article.className = 'announcement-row';
	const meta = document.createElement('p');
	meta.className = 'panel-eyebrow';
	meta.textContent = announcement.publishedAt ? formatDate(announcement.publishedAt, { month: 'short', day: 'numeric', year: 'numeric' }) : 'SMARTMOVE UPDATE';
	const title = document.createElement('h3');
	title.textContent = announcement.title || 'Travel update';
	const message = document.createElement('p');
	message.className = 'announcement-copy';
	message.textContent = announcement.message || '';
	article.append(meta, title, message);
	if (announcement.routeId) {
		const route = document.createElement('span');
		route.className = 'announcement-route';
		route.textContent = `Route ${announcement.routeId}`;
		article.append(route);
	}
	return article;
}

async function initAnnouncements() {
	const list = document.querySelector('[data-announcement-list]');
	const feedback = document.querySelector('[data-announcement-feedback]');
	try {
		const announcements = await requestJson('announcement-api.php?active=true&limit=100');
		list.replaceChildren();
		if (announcements.length === 0) {
			const empty = document.createElement('p');
			empty.className = 'empty-state';
			empty.textContent = 'There are no active travel updates right now.';
			list.append(empty);
			return;
		}
		announcements.forEach((announcement) => list.append(makeAnnouncementRow(announcement)));
	} catch (error) {
		list.replaceChildren();
		setMessage(feedback, error.message);
	}
}

function createPaymentRow(payment) {
	const row = document.createElement('article');
	row.className = 'payment-row';
	const details = document.createElement('div');
	details.className = 'payment-details';
	const heading = document.createElement('strong');
	heading.textContent = `Ticket #${payment.ticket_id}`;
	const meta = document.createElement('span');
	meta.textContent = `${String(payment.payment_method || 'Payment').replaceAll('_', ' ')} · ${formatDate(payment.paid_at, { month: 'short', day: 'numeric', year: 'numeric' })}`;
	details.append(heading, meta);
	const amount = document.createElement('strong');
	amount.className = 'payment-amount';
	amount.textContent = formatRupees(payment.amount);
	const status = document.createElement('span');
	status.className = 'payment-status';
	status.textContent = String(payment.status || 'RECORDED').replaceAll('_', ' ');
	row.append(details, amount, status);
	return row;
}

async function initPayments() {
	const list = document.querySelector('[data-payment-list]');
	const count = document.querySelector('[data-payment-count]');
	const feedback = document.querySelector('[data-payment-feedback]');
	try {
		const payments = await requestJson('payment-api.php');
		payments.sort((first, second) => (parseOracleDate(second.paid_at)?.getTime() || 0) - (parseOracleDate(first.paid_at)?.getTime() || 0));
		count.textContent = `${payments.length} RECORDS`;
		list.replaceChildren();
		if (payments.length === 0) {
			const empty = document.createElement('div');
			empty.className = 'payment-empty';
			empty.textContent = 'No payment records yet. Your completed payments will appear here.';
			list.append(empty);
			return;
		}
		payments.forEach((payment) => list.append(createPaymentRow(payment)));
	} catch (error) {
		count.textContent = 'Unavailable';
		list.replaceChildren();
		setMessage(feedback, error.message);
	}
}

async function initPaymentCheckout() {
	const page = document.body;
	const ticketIds = page.dataset.ticketIds.split(',').filter(Boolean).map(Number);
	const card = document.querySelector('[data-checkout-card]');
	const feedback = document.querySelector('[data-checkout-feedback]');
	const ticketList = document.querySelector('[data-checkout-tickets]');
	const form = document.querySelector('[data-checkout-form]');
	if (page.dataset.checkoutError || ticketIds.length === 0) return;

	try {
		const booking = await requestJson(`booking-api.php?tickets=${encodeURIComponent(ticketIds.join(','))}`);
		const tickets = booking.tickets;
		ticketList.replaceChildren();
		tickets.forEach((ticket) => {
			const row = document.createElement('div');
			row.className = 'checkout-ticket-row';
			const details = document.createElement('span');
			details.textContent = `Ticket #${ticket.ticket_id} · Seat ${ticket.seat_number}`;
			const fare = document.createElement('strong');
			fare.textContent = formatRupees(ticket.fare);
			row.append(details, fare);
			ticketList.append(row);
		});

		const summary = document.createElement('p');
		summary.className = 'checkout-total';
		const farePerSeat = Number(tickets[0].fare);
		summary.textContent = `${tickets.length} ${tickets.length === 1 ? 'seat' : 'seats'} × ${formatRupees(farePerSeat)} = ${formatRupees(booking.total_amount)}`;
		ticketList.append(summary);
		form.elements.amount.value = Number(booking.total_amount).toFixed(2);
		card.hidden = false;
	} catch (error) {
		card.hidden = true;
		setMessage(feedback, error.message);
		return;
	}

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!form.reportValidity()) return;
		const button = form.querySelector('[data-checkout-submit]');
		button.disabled = true;
		button.textContent = 'Processing…';
		try {
			const payment = await requestJson('payment-api.php?action=create', {
				method: 'POST',
				body: JSON.stringify({
					ticket_ids: ticketIds,
					amount: Number(form.elements.amount.value),
					payment_method: form.elements.payment_method.value,
				}),
			});
			window.location.assign(`payment-success.php?payment_id=${encodeURIComponent(payment.payment_id)}`);
		} catch (error) {
			setMessage(feedback, error.message);
			button.disabled = false;
			button.textContent = 'Confirm payment';
		}
	});
}

async function initPaymentReceipt() {
	const page = document.body;
	const paymentId = Number(page.dataset.paymentId);
	const feedback = document.querySelector('[data-receipt-feedback]');
	const receipt = document.querySelector('[data-payment-receipt]');
	if (!Number.isInteger(paymentId) || paymentId < 1) {
		setMessage(feedback, 'A valid payment reference is required.');
		return;
	}

	try {
		const payment = await requestJson(`payment-api.php?id=${encodeURIComponent(paymentId)}`);
		document.querySelector('[data-receipt-amount]').textContent = formatRupees(payment.amount);
		document.querySelector('[data-receipt-reference]').textContent = payment.transaction_reference || `SM-${payment.payment_id}`;
		document.querySelector('[data-receipt-method]').textContent = String(payment.payment_method || '—').replaceAll('_', ' ');
		document.querySelector('[data-receipt-time]').textContent = formatDate(payment.paid_at, { dateStyle: 'medium', timeStyle: 'short' });
		document.querySelector('[data-receipt-payment-id]').textContent = payment.payment_id;
		receipt.hidden = false;
	} catch (error) {
		setMessage(feedback, error.message);
	}
}

function initSidebarToggle() {
	const button = document.querySelector('[data-sidebar-toggle]');
	const shell = document.querySelector('.panel-shell');
	if (!button || !shell) return;
	button.addEventListener('click', () => {
		const isExpanded = button.getAttribute('aria-expanded') === 'true';
		button.setAttribute('aria-expanded', String(!isExpanded));
		button.setAttribute('aria-label', isExpanded ? 'Expand sidebar' : 'Collapse sidebar');
		shell.classList.toggle('is-sidebar-collapsed', isExpanded);
	});
}

initSidebarToggle();

switch (document.body.dataset.page) {
	case 'dashboard':
		initDashboard();
		break;
	case 'search':
		initTripSearch();
		break;
	case 'book':
		initBooking();
		break;
	case 'bookings':
		initBookingHistory();
		break;
	case 'announcements':
		initAnnouncements();
		break;
	case 'payments':
		initPayments();
		break;
	case 'payment-checkout':
		initPaymentCheckout();
		break;
	case 'payment-success':
		initPaymentReceipt();
		break;
}