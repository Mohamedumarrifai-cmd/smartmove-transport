document.querySelectorAll('[data-auth-form]').forEach((form) => {
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const message = form.querySelector('[data-form-message]');
		const button = form.querySelector('button[type="submit"]');
		const buttonLabel = form.querySelector('[data-button-label]');
		const originalLabel = buttonLabel.textContent;
		const formData = Object.fromEntries(new FormData(form).entries());
		formData.action = form.dataset.mode;

		message.textContent = '';
		message.classList.remove('is-error', 'is-success');
		button.disabled = true;
		button.classList.add('is-loading');
		buttonLabel.textContent = form.dataset.mode === 'register' ? 'Creating account…' : 'Signing in…';
		form.setAttribute('aria-busy', 'true');

		try {
			const response = await fetch('../backend/api/auth.php', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
				body: JSON.stringify(formData),
			});
			let result;
			if (!response.ok) {
				const errorResult = await response.json().catch(() => null);
				throw new Error(errorResult?.error || 'We could not complete authentication. Please try again.');
			}
			result = await response.json().catch(() => null);
			if (result?.success !== true) {
				throw new Error(result?.error || 'We could not complete authentication. Please try again.');
			}

			if (form.dataset.mode === 'register') {
				message.textContent = 'Your account is ready. Taking you to sign in…';
				message.classList.add('is-success');
				button.classList.remove('is-loading');
				button.classList.add('is-success');
				buttonLabel.textContent = 'Account created';
				button.querySelector('i').className = 'fa-solid fa-circle-check';
				window.setTimeout(() => { window.location.assign('login.php'); }, 1500);
				return;
			}

			message.textContent = `You’re in, ${result.user.full_name.split(' ')[0]}. Taking you home…`;
			message.classList.add('is-success');
			buttonLabel.textContent = 'Welcome aboard';
			const destination = result.user.role === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
			window.setTimeout(() => { window.location.assign(destination); }, 650);
		} catch (error) {
			message.textContent = error instanceof TypeError
				? 'The sign-in service could not be reached. Please try again shortly.'
				: error.message;
			message.classList.add('is-error');
			button.disabled = false;
			button.classList.remove('is-loading');
			buttonLabel.textContent = originalLabel;
			form.removeAttribute('aria-busy');
		}
	});
});