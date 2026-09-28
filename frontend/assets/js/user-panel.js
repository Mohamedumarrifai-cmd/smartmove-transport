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

function departureIsUpcoming(trip) {
	const departure = parseOracleDate(trip.departure_time);
	return departure !== null && departure.getTime() > Date.now();
}

function setMessage(element, message, isError = true) {
	if (!element) return;
	element.textContent = message;
	element.classList.toggle('is-error', isError);
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
	row.append(route, date, seat, status);
	return row;
}

function initBookingHistory() {
	const list = document.querySelector('[data-bookings-list]');
	const feedback = document.querySelector('[data-history-feedback]');
	const count = document.querySelector('[data-history-count]');
	const filters = [...document.querySelectorAll('[data-booking-filter]')];
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
		const seatInput = document.querySelector('#seat-number');
		seatInput.max = String(vehicle.capacity);
		document.querySelector('[data-seat-hint]').textContent = `Choose a seat from 1 to ${vehicle.capacity}. Availability is confirmed when you book.`;
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
		const seatInput = form.elements.seat_number;
		if (!seatInput.reportValidity()) return;
		document.querySelector('[data-review-seat]').textContent = `Seat ${seatInput.value}`;
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
				body: JSON.stringify({ trip_id: tripId, seat_number: Number(form.elements.seat_number.value) }),
			});
			setMessage(feedback, `Seat reserved. Ticket #${data.ticket_id}. Opening your bookings…`, false);
			window.setTimeout(() => { window.location.assign('my-bookings.php'); }, 750);
		} catch (error) {
			setMessage(feedback, error.message);
			button.disabled = false;
			button.textContent = originalText;
		}
	});
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
}