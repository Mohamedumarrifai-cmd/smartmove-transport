document.querySelectorAll('[data-auth-form]').forEach((form) => {
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const message = form.querySelector('[data-form-message]');
		const button = form.querySelector('button[type="submit"]');
		const buttonLabel = form.querySelector('[data-button-label]');
		const originalLabel = buttonLabel.textContent;
		const data = Object.fromEntries(new FormData(form).entries());
		data.action = form.dataset.mode;

		message.textContent = '';
		message.classList.remove('is-error', 'is-success');
		button.disabled = true;
		buttonLabel.textContent = form.dataset.mode === 'register' ? 'Creating account…' : 'Signing in…';
		form.setAttribute('aria-busy', 'true');

		try {
			const response = await fetch('../backend/api/auth.php', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
				body: JSON.stringify(data),
			});
			const result = await response.json();
			if (!response.ok || !result.success) {
				throw new Error(result.error || 'We could not sign you in. Please try again.');
			}

			message.textContent = `You’re in, ${result.user.full_name.split(' ')[0]}. Taking you home…`;
			message.classList.add('is-success');
			buttonLabel.textContent = 'Welcome aboard';
			window.setTimeout(() => { window.location.assign('index.php'); }, 650);
		} catch (error) {
			message.textContent = error instanceof TypeError
				? 'The sign-in service could not be reached. Please try again shortly.'
				: error.message;
			message.classList.add('is-error');
			button.disabled = false;
			buttonLabel.textContent = originalLabel;
			form.removeAttribute('aria-busy');
		}
	});
});