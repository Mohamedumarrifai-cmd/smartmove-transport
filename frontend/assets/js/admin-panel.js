const apiRoot = '../../backend/api/';

async function apiRequest(endpoint, options = {}) {
	const response = await fetch(apiRoot + endpoint, {
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
	if (!response.ok || !result.success) throw new Error(result.error || 'The request could not be completed.');
	return result.data;
}

function setNotice(element, message, isError = true) {
	if (!element) return;
	element.textContent = message;
	element.classList.toggle('is-error', isError);
}

function addCell(row, value, className = '') {
	const cell = document.createElement('td');
	cell.textContent = value ?? '—';
	if (className) cell.className = className;
	row.append(cell);
	return cell;
}

function parseDate(value) {
	if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;
	if (typeof value !== 'string') return null;
	const direct = new Date(value);
	if (!Number.isNaN(direct.getTime())) return direct;
	const oracle = value.trim().match(/^(\d{1,2})-([A-Z]{3})-(\d{2}|\d{4})\s+(\d{1,2})[.:](\d{2})[.:](\d{2})(?:[.,]\d+)?\s*(AM|PM)?$/i);
	if (!oracle) return null;
	const month = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'].indexOf(oracle[2].toUpperCase());
	if (month < 0) return null;
	let year = Number(oracle[3]);
	if (year < 100) year += year < 50 ? 2000 : 1900;
	let hour = Number(oracle[4]);
	if (oracle[7]?.toUpperCase() === 'PM' && hour < 12) hour += 12;
	if (oracle[7]?.toUpperCase() === 'AM' && hour === 12) hour = 0;
	const parsed = new Date(year, month, Number(oracle[1]), hour, Number(oracle[5]), Number(oracle[6]));
	return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatDate(value, options = { dateStyle: 'medium', timeStyle: 'short' }) {
	const date = parseDate(value);
	if (!date) return value ? String(value) : '—';
	return new Intl.DateTimeFormat('en', options).format(date);
}

function toOracleTimestamp(value) {
	const date = parseDate(value);
	if (!date) throw new Error('Enter a valid departure and arrival time.');
	const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
	const hour = date.getHours() % 12 || 12;
	const pad = (number) => String(number).padStart(2, '0');
	return `${pad(date.getDate())}-${months[date.getMonth()]}-${date.getFullYear()} ${pad(hour)}.${pad(date.getMinutes())}.${pad(date.getSeconds())}.000000 ${date.getHours() >= 12 ? 'PM' : 'AM'}`;
}

function formatMoney(value) {
	const amount = Number(value);
	return Number.isFinite(amount) ? new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS' }).format(amount) : '—';
}

const entities = {
	vehicles: {
		endpoint: 'vehicle-api.php', id: 'vehicle_id', label: 'vehicle',
		fields: ['registration_number', 'make', 'model', 'vehicle_type', 'manufacture_year', 'capacity', 'status'],
		render(record) {
			const row = document.createElement('tr');
			addCell(row, record.registration_number);
			addCell(row, `${record.make || ''} ${record.model || ''}`.trim());
			addCell(row, record.vehicle_type);
			addCell(row, record.capacity);
			addCell(row, record.status, 'status-cell');
			return row;
		},
	},
	routes: {
		endpoint: 'route-api.php', id: 'route_id', label: 'route',
		fields: ['route_name', 'origin_city', 'destination_city', 'distance_km', 'estimated_duration_minutes', 'is_active'],
		render(record) {
			const row = document.createElement('tr');
			addCell(row, record.route_name);
			addCell(row, record.origin_city);
			addCell(row, record.destination_city);
			addCell(row, `${record.distance_km} km`);
			addCell(row, `${record.estimated_duration_minutes} min`);
			addCell(row, record.is_active === 'N' ? 'Inactive' : 'Active', 'status-cell');
			return row;
		},
	},
	trips: {
		endpoint: 'trip-api.php', id: 'trip_id', label: 'trip',
		fields: ['route_id', 'vehicle_id', 'driver_id', 'departure_time', 'arrival_time', 'fare', 'status'],
		render(record, context) {
			const row = document.createElement('tr');
			addCell(row, `#${record.trip_id}`);
			addCell(row, context.routes.get(String(record.route_id)) || `Route ${record.route_id}`);
			addCell(row, `${context.vehicles.get(String(record.vehicle_id)) || `Vehicle ${record.vehicle_id}`} / ${context.drivers.get(String(record.driver_id)) || `Driver ${record.driver_id}`}`);
			addCell(row, formatDate(record.departure_time));
			addCell(row, formatMoney(record.fare));
			addCell(row, record.status, 'status-cell');
			return row;
		},
	},
};

function actionButton(label, action, id) {
	const button = document.createElement('button');
	button.type = 'button';
	button.className = `table-action ${action === 'delete' ? 'table-action-danger' : ''}`;
	button.dataset.action = action;
	button.dataset.recordId = id;
	button.textContent = label;
	return button;
}

async function loadEntity(entityName, recordsRef, lookups = {}) {
	const entity = entities[entityName];
	const table = document.querySelector('[data-entity-table]');
	const count = document.querySelector('[data-record-count]');
	const data = await apiRequest(entity.endpoint);
	recordsRef.records = data;
	count.textContent = `${data.length} ${entityName.toUpperCase()}`;
	table.replaceChildren();
	if (!data.length) {
		const empty = document.createElement('tr');
		addCell(empty, `No ${entityName} recorded yet.`, 'admin-empty').colSpan = entityName === 'vehicles' ? 6 : 7;
		table.append(empty);
		return;
	}
	data.forEach((record) => {
		const row = entity.render(record, lookups);
		const actions = document.createElement('td');
		actions.className = 'table-actions';
		actions.append(actionButton('Edit', 'edit', record[entity.id]), actionButton('Delete', 'delete', record[entity.id]));
		row.append(actions);
		table.append(row);
	});
}

function resetEntityForm(form, entityName) {
	form.reset();
	form.elements[entities[entityName].id].value = '';
	document.querySelector('[data-form-title]').textContent = entityName === 'trips' ? 'Schedule a departure' : entityName === 'routes' ? 'Create a route' : 'Add to the fleet';
	document.querySelector('[data-form-eyebrow]').textContent = `NEW ${entityName.slice(0, -1).toUpperCase()}`;
	document.querySelector('[data-submit-label]').textContent = `Save ${entityName.slice(0, -1)}`;
	document.querySelector('[data-form-reset]').hidden = true;
	setNotice(document.querySelector('[data-form-message]'), '');
}

function initEntityPage(entityName) {
	const entity = entities[entityName];
	const form = document.querySelector('[data-entity-form]');
	const table = document.querySelector('[data-entity-table]');
	const message = document.querySelector('[data-form-message]');
	const state = { records: [], lookups: { routes: new Map(), vehicles: new Map(), drivers: new Map() } };
	const refresh = () => loadEntity(entityName, state, state.lookups).catch((error) => {
		const row = document.createElement('tr');
		addCell(row, 'Could not load records.', 'admin-empty').colSpan = entityName === 'vehicles' ? 6 : 7;
		table.replaceChildren(row);
		setNotice(message, error.message);
	});

	if (entityName === 'trips') {
		Promise.all([
			apiRequest('route-api.php'), apiRequest('vehicle-api.php'), apiRequest('driver-api.php'),
		]).then(([routes, vehicles, drivers]) => {
			const optionSets = [
				[form.elements.route_id, routes, 'route_id', (item) => `${item.route_name} · ${item.origin_city} to ${item.destination_city}`],
				[form.elements.vehicle_id, vehicles, 'vehicle_id', (item) => `${item.registration_number} · ${item.make} ${item.model} (${item.status})`],
				[form.elements.driver_id, drivers, 'driver_id', (item) => `${item.full_name} (${item.status})`],
			];
			optionSets.forEach(([select, items, idField, labelFor]) => {
				select.replaceChildren(new Option('Choose…', ''));
				items.forEach((item) => select.add(new Option(labelFor(item), item[idField])));
			});
			state.lookups.routes = new Map(routes.map((item) => [String(item.route_id), item.route_name]));
			state.lookups.vehicles = new Map(vehicles.map((item) => [String(item.vehicle_id), item.registration_number]));
			state.lookups.drivers = new Map(drivers.map((item) => [String(item.driver_id), item.full_name]));
			return refresh();
		}).catch((error) => {
			[form.elements.route_id, form.elements.vehicle_id, form.elements.driver_id].forEach((select) => {
				select.replaceChildren(new Option('Unavailable', ''));
				select.disabled = true;
			});
			const row = document.createElement('tr');
			addCell(row, 'Trip data is unavailable.', 'admin-empty').colSpan = 7;
			table.replaceChildren(row);
			setNotice(message, error.message);
		});
	} else {
		refresh();
	}

	document.querySelector('[data-refresh]').addEventListener('click', refresh);
	document.querySelector('[data-form-reset]').addEventListener('click', () => resetEntityForm(form, entityName));
	table.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-action]');
		if (!button) return;
		const record = state.records.find((item) => String(item[entity.id]) === button.dataset.recordId);
		if (!record) return;
		if (button.dataset.action === 'edit') {
			entity.fields.forEach((field) => {
				const input = form.elements[field];
				if (!input) return;
				let value = record[field] ?? '';
				if (input.type === 'datetime-local' && value) {
					const date = parseDate(String(value));
					value = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}T${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}` : '';
				}
				input.value = value;
			});
			form.elements[entity.id].value = record[entity.id];
			document.querySelector('[data-form-title]').textContent = `Edit ${entityName.slice(0, -1)} #${record[entity.id]}`;
			document.querySelector('[data-form-eyebrow]').textContent = `UPDATE ${entityName.slice(0, -1).toUpperCase()}`;
			document.querySelector('[data-submit-label]').textContent = 'Save changes';
			document.querySelector('[data-form-reset]').hidden = false;
			form.scrollIntoView({ behavior: 'smooth', block: 'start' });
			return;
		}
		if (!window.confirm(`Delete ${entityName.slice(0, -1)} #${record[entity.id]}?`)) return;
		try {
			await apiRequest(`${entity.endpoint}?id=${encodeURIComponent(record[entity.id])}`, { method: 'DELETE' });
			setNotice(message, `${entityName.slice(0, -1)} deleted.`, false);
			await refresh();
		} catch (error) {
			setNotice(message, error.message);
		}
	});

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!form.reportValidity()) return;
		const formData = new FormData(form);
		const payload = {};
		entity.fields.forEach((field) => {
			let value = formData.get(field);
			if (value === null || value === '') return;
			if (['route_id', 'vehicle_id', 'driver_id', 'capacity', 'manufacture_year', 'estimated_duration_minutes'].includes(field)) value = Number(value);
			if (field === 'distance_km' || field === 'fare') value = Number(value);
			if (field === 'departure_time' || field === 'arrival_time') value = toOracleTimestamp(value);
			payload[field] = value;
		});
		const recordId = form.elements[entity.id].value;
		if (recordId) payload[entity.id] = Number(recordId);
		const submit = document.querySelector('[data-submit-label]');
		submit.disabled = true;
		try {
			await apiRequest(entity.endpoint, { method: recordId ? 'PUT' : 'POST', body: JSON.stringify(payload) });
			resetEntityForm(form, entityName);
			setNotice(message, `${entityName.slice(0, -1)} saved.`, false);
			await refresh();
		} catch (error) {
			setNotice(message, error.message);
		} finally {
			submit.disabled = false;
		}
	});
}

async function initDashboard() {
	const feedback = document.querySelector('[data-admin-feedback]');
	try {
		const [vehicles, routes, trips, drivers] = await Promise.all([
			apiRequest('vehicle-api.php'), apiRequest('route-api.php'), apiRequest('trip-api.php'), apiRequest('driver-api.php'),
		]);
		document.querySelector('[data-admin-stat="vehicles"]').textContent = vehicles.length;
		document.querySelector('[data-admin-stat="routes"]').textContent = routes.filter((route) => route.is_active !== 'N').length;
		document.querySelector('[data-admin-stat="trips"]').textContent = trips.filter((trip) => (parseDate(trip.departure_time)?.getTime() || 0) > Date.now() && ['SCHEDULED', 'BOARDING'].includes(String(trip.status).toUpperCase())).length;
		document.querySelector('[data-admin-stat="drivers"]').textContent = drivers.length;
		const routeNames = new Map(routes.map((route) => [String(route.route_id), `${route.origin_city} to ${route.destination_city}`]));
		const tbody = document.querySelector('[data-dashboard-trips]');
		const upcoming = trips.filter((trip) => (parseDate(trip.departure_time)?.getTime() || 0) > Date.now() && !['CANCELLED', 'COMPLETED'].includes(String(trip.status).toUpperCase())).sort((a, b) => (parseDate(a.departure_time)?.getTime() || 0) - (parseDate(b.departure_time)?.getTime() || 0)).slice(0, 8);
		tbody.replaceChildren();
		if (!upcoming.length) {
			const row = document.createElement('tr'); addCell(row, 'No upcoming departures.', 'admin-empty').colSpan = 5; tbody.append(row); return;
		}
		upcoming.forEach((trip) => {
			const row = document.createElement('tr');
			addCell(row, `#${trip.trip_id}`); addCell(row, routeNames.get(String(trip.route_id)) || `Route ${trip.route_id}`);
			addCell(row, formatDate(trip.departure_time)); addCell(row, formatMoney(trip.fare)); addCell(row, trip.status, 'status-cell'); tbody.append(row);
		});
	} catch (error) {
		const row = document.createElement('tr');
		addCell(row, 'Upcoming departures are unavailable.', 'admin-empty').colSpan = 5;
		document.querySelector('[data-dashboard-trips]').replaceChildren(row);
		setNotice(feedback, error.message);
	}
}

async function initReports() {
	const feedback = document.querySelector('[data-admin-feedback]');
	try {
		const [vehicles, routes, trips, drivers, payments] = await Promise.all([
			apiRequest('vehicle-api.php'), apiRequest('route-api.php'), apiRequest('trip-api.php'), apiRequest('driver-api.php'), apiRequest('payment-api.php'),
		]);
		const totalPaid = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
		document.querySelector('[data-report-stat="trips"]').textContent = trips.length;
		document.querySelector('[data-report-stat="bookings"]').textContent = payments.length;
		document.querySelector('[data-report-stat="value"]').textContent = formatMoney(totalPaid);
		const assignedVehicles = new Set(trips.map((trip) => String(trip.vehicle_id))).size;
		document.querySelector('[data-report-stat="fleet"]').textContent = vehicles.length ? `${Math.round(assignedVehicles / vehicles.length * 100)}%` : '0%';
		const statusBody = document.querySelector('[data-report-status]');
		const statuses = ['SCHEDULED', 'BOARDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];
		statusBody.replaceChildren();
		statuses.forEach((status) => {
			const count = trips.filter((trip) => String(trip.status).toUpperCase() === status).length;
			const row = document.createElement('tr'); addCell(row, status); addCell(row, count); addCell(row, trips.length ? `${Math.round(count / trips.length * 100)}%` : '0%'); statusBody.append(row);
		});
		document.querySelector('[data-report-updated]').textContent = `UPDATED ${new Intl.DateTimeFormat('en', { hour: 'numeric', minute: '2-digit' }).format(new Date())}`;
	} catch (error) {
		const row = document.createElement('tr');
		addCell(row, 'Report data is unavailable.', 'admin-empty').colSpan = 3;
		document.querySelector('[data-report-status]').replaceChildren(row);
		setNotice(feedback, error.message);
	}
}

async function initFeedback() {
	const tbody = document.querySelector('[data-feedback-table]');
	const feedback = document.querySelector('[data-admin-feedback]');
	try {
		const reviews = await apiRequest('feedback-api.php?limit=100');
		document.querySelector('[data-feedback-count]').textContent = `${reviews.length} REVIEWS`;
		tbody.replaceChildren();
		if (!reviews.length) {
			const row = document.createElement('tr'); addCell(row, 'No passenger feedback has been submitted.', 'admin-empty').colSpan = 5; tbody.append(row); return;
		}
		reviews.forEach((review) => {
			const row = document.createElement('tr');
			addCell(row, `${'★'.repeat(Number(review.rating) || 0)}${'☆'.repeat(Math.max(0, 5 - (Number(review.rating) || 0)))}`, 'feedback-rating');
			addCell(row, `Passenger #${review.passengerId}`);
			addCell(row, `Trip #${review.tripId} · Route #${review.routeId}`);
			addCell(row, review.comments, 'feedback-comment');
			addCell(row, formatDate(review.createdAt));
			tbody.append(row);
		});
	} catch (error) {
		const row = document.createElement('tr');
		addCell(row, 'Feedback is unavailable.', 'admin-empty').colSpan = 5;
		tbody.replaceChildren(row);
		setNotice(feedback, error.message);
	}
}

const page = document.body.dataset.adminPage;
if (['vehicles', 'routes', 'trips'].includes(page)) initEntityPage(page);
if (page === 'dashboard') initDashboard();
if (page === 'reports') {
	initReports();
	document.querySelector('[data-refresh]').addEventListener('click', initReports);
}
if (page === 'feedback') {
	initFeedback();
	document.querySelector('[data-refresh]').addEventListener('click', initFeedback);
}