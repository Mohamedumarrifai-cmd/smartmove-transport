(() => {
	const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
	const finePointer = window.matchMedia('(pointer: fine) and (min-width: 821px)');

	document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
		const field = toggle.closest('.field-control');
		const input = field?.querySelector('input[type="password"], input[type="text"]');
		const icon = toggle.querySelector('i');
		if (!input) return;

		toggle.addEventListener('click', () => {
			const shouldShow = input.type === 'password';
			input.type = shouldShow ? 'text' : 'password';
			toggle.setAttribute('aria-pressed', String(shouldShow));
			toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
			icon?.classList.toggle('fa-eye', !shouldShow);
			icon?.classList.toggle('fa-eye-slash', shouldShow);
			input.focus();
		});
	});

	const strengthInput = document.querySelector('[data-strength-input]');
	const strengthMeter = document.querySelector('[data-strength-meter]');
	const strengthLabel = document.querySelector('[data-strength-label]');
	if (strengthInput && strengthMeter && strengthLabel) {
		strengthInput.addEventListener('input', () => {
			const value = strengthInput.value;
			if (!value) {
				strengthMeter.removeAttribute('data-strength');
				strengthMeter.setAttribute('aria-valuenow', '0');
				strengthMeter.setAttribute('aria-valuetext', 'Use 8 to 72 characters.');
				strengthLabel.textContent = 'Use 8 to 72 characters.';
				return;
			}

			// This client-side score is guidance only; the API still validates password length.
			let score = 0;
			if (value.length >= 8) score += 1;
			if (value.length >= 12) score += 1;
			if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
			if (/\d/.test(value)) score += 1;
			if (/[^a-zA-Z0-9]/.test(value)) score += 1;
			const level = score <= 2 ? 'weak' : score <= 3 ? 'medium' : 'strong';
			const copy = { weak: 'Weak password', medium: 'Getting stronger', strong: 'Strong password' };
			strengthMeter.dataset.strength = level;
			strengthMeter.setAttribute('aria-valuenow', String(level === 'weak' ? 1 : level === 'medium' ? 2 : 3));
			strengthMeter.setAttribute('aria-valuetext', copy[level]);
			strengthLabel.textContent = copy[level];
		});
	}

	document.querySelectorAll('.field-control').forEach((field) => {
		const input = field.querySelector('input');
		if (!input) return;
		input.addEventListener('focus', () => field.classList.add('is-focused'));
		input.addEventListener('blur', () => field.classList.remove('is-focused'));
	});

	document.querySelectorAll('.submit-button').forEach((button) => {
		button.addEventListener('pointerdown', (event) => {
			if (motionPreference.matches || button.disabled) return;
			const bounds = button.getBoundingClientRect();
			const ripple = document.createElement('span');
			ripple.className = 'ripple';
			ripple.style.left = `${event.clientX - bounds.left}px`;
			ripple.style.top = `${event.clientY - bounds.top}px`;
			button.append(ripple);
			ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
		});
	});

	if (motionPreference.matches || !finePointer.matches) return;
	const illustration = document.querySelector('[data-parallax]');
	if (!illustration) return;

	let frame = 0;
	let pointerX = 0;
	let pointerY = 0;
	let currentX = 0;
	let currentY = 0;
	const animateParallax = () => {
		const targetX = pointerX * 12;
		const targetY = pointerY * 9;
		// Interpolate toward the pointer target so illustration movement stays subtle.
		currentX += (targetX - currentX) * .09;
		currentY += (targetY - currentY) * .09;
		illustration.style.transform = `translate3d(${currentX.toFixed(2)}px, ${currentY.toFixed(2)}px, 0)`;
		if (Math.abs(targetX - currentX) > .05 || Math.abs(targetY - currentY) > .05) {
			frame = requestAnimationFrame(animateParallax);
		} else {
			frame = 0;
		}
	};

	document.addEventListener('pointermove', (event) => {
		pointerX = (event.clientX / window.innerWidth - .5) * 2;
		pointerY = (event.clientY / window.innerHeight - .5) * 2;
		if (!frame) frame = requestAnimationFrame(animateParallax);
	}, { passive: true });
	document.addEventListener('pointerleave', () => {
		pointerX = 0;
		pointerY = 0;
		if (!frame) frame = requestAnimationFrame(animateParallax);
	});
})();
