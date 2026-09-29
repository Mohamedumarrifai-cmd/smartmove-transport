(() => {
	const viewport = document.querySelector('[data-reel-viewport]');
	const slides = [...document.querySelectorAll('[data-slide]')];
	const dots = [...document.querySelectorAll('[data-slide-to]')];
	const menuButton = document.querySelector('[data-menu-toggle]');
	const navigation = document.querySelector('[data-navigation]');
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

	if (!viewport || slides.length === 0) return;

	const setActiveSlide = (activeSlide) => {
		const activeIndex = slides.indexOf(activeSlide);
		if (activeIndex < 0) return;
		slides.forEach((slide, index) => slide.classList.toggle('is-active', index === activeIndex));
		dots.forEach((dot, index) => {
			if (index === activeIndex) dot.setAttribute('aria-current', 'true');
			else dot.removeAttribute('aria-current');
		});
	};

	const revealElements = [...document.querySelectorAll('[data-reveal]')];
	if (reduceMotion.matches || !('IntersectionObserver' in window)) {
		revealElements.forEach((element) => element.classList.add('is-visible'));
	} else {
		const revealObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		}, { root: viewport, threshold: .2 });
		revealElements.forEach((element) => revealObserver.observe(element));
	}

	let countersStarted = false;
	const animateCounters = () => {
		if (countersStarted) return;
		countersStarted = true;
		document.querySelectorAll('[data-count]').forEach((counter) => {
			const target = Number(counter.dataset.count);
			const suffix = counter.dataset.suffix || '';
			if (!Number.isFinite(target)) return;
			if (reduceMotion.matches) {
				counter.textContent = `${target.toLocaleString('en-US')}${suffix}`;
				return;
			}
			const startedAt = performance.now();
			const duration = 1250;
			const tick = (now) => {
				const progress = Math.min((now - startedAt) / duration, 1);
				const eased = 1 - Math.pow(1 - progress, 4);
				const value = Math.round(target * eased);
				counter.textContent = `${value.toLocaleString('en-US')}${progress === 1 ? suffix : ''}`;
				if (progress < 1) requestAnimationFrame(tick);
			};
			requestAnimationFrame(tick);
		});
	};

	if ('IntersectionObserver' in window) {
		const slideObserver = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;
				setActiveSlide(entry.target);
				if (entry.target.id === 'slide-impact') animateCounters();
			});
		}, { root: viewport, threshold: .62 });
		slides.forEach((slide) => slideObserver.observe(slide));
	} else {
		slides.forEach(setActiveSlide);
		animateCounters();
	}

	dots.forEach((dot) => dot.addEventListener('click', () => {
		const target = slides[Number(dot.dataset.slideTo)];
		if (!target) return;
		target.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
	}));

	const closeMenu = () => {
		if (!menuButton || !navigation) return;
		menuButton.setAttribute('aria-expanded', 'false');
		menuButton.setAttribute('aria-label', 'Open navigation');
		navigation.classList.remove('is-open');
	};

	if (menuButton && navigation) {
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
			if (event.key === 'Escape') closeMenu();
		});
		window.addEventListener('resize', () => {
			if (window.innerWidth > 760) closeMenu();
		});
	}

	const internalLinks = [...document.querySelectorAll('a[href^="#"]')];
	internalLinks.forEach((link) => link.addEventListener('click', (event) => {
		const target = document.querySelector(link.getAttribute('href'));
		if (!target || !viewport.contains(target)) return;
		event.preventDefault();
		target.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
		closeMenu();
	}));
})();
