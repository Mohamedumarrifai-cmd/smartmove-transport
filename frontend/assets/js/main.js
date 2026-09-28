(() => {
	const menuButton = document.querySelector('[data-menu-toggle]');
	const navigation = document.querySelector('[data-navigation]');

	if (menuButton && navigation) {
		const closeMenu = () => {
			menuButton.setAttribute('aria-expanded', 'false');
			menuButton.setAttribute('aria-label', 'Open navigation');
			navigation.classList.remove('is-open');
		};

		menuButton.addEventListener('click', () => {
			const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
			menuButton.setAttribute('aria-expanded', String(!isOpen));
			menuButton.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
			navigation.classList.toggle('is-open', !isOpen);
		});

		navigation.addEventListener('click', (event) => {
			if (event.target.closest('a')) closeMenu();
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				closeMenu();
				menuButton.focus();
			}
		});

		window.addEventListener('resize', () => {
			if (window.innerWidth > 900) closeMenu();
		});
	}

	const revealElements = document.querySelectorAll('[data-reveal]');
	if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		revealElements.forEach((element) => element.classList.add('is-visible'));
		return;
	}

	const revealObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-visible');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.14, rootMargin: '0px 0px -24px 0px' });

	revealElements.forEach((element) => revealObserver.observe(element));
})();
