const sceneMounts = [...document.querySelectorAll('[data-three-scene]')];

if (sceneMounts.length > 0) {
	import('https://cdn.jsdelivr.net/npm/three@0.166.1/build/three.module.js')
		.then((THREE) => sceneMounts.forEach((mount) => buildScene(THREE, mount)))
		.catch(() => sceneMounts.forEach((mount) => mount.classList.add('scene-unavailable')));
}

function buildScene(THREE, mount) {
	const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'low-power' });
	renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.8));
	renderer.setSize(mount.clientWidth, mount.clientHeight);
	renderer.shadowMap.enabled = true;
	renderer.shadowMap.type = THREE.PCFSoftShadowMap;
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = THREE.ACESFilmicToneMapping;
	renderer.toneMappingExposure = 1.12;
	renderer.domElement.setAttribute('aria-hidden', 'true');
	mount.prepend(renderer.domElement);

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera(34, mount.clientWidth / mount.clientHeight, 0.1, 100);
	camera.position.set(7.9, 5.7, 10.7);
	camera.lookAt(0, 1.05, 0);

	scene.add(new THREE.HemisphereLight(0xe8f2e7, 0x6c7770, 2.1));
	const keyLight = new THREE.DirectionalLight(0xfff0d2, 3.2);
	keyLight.position.set(-4, 9, 6);
	keyLight.castShadow = true;
	keyLight.shadow.mapSize.set(1024, 1024);
	keyLight.shadow.camera.left = -10;
	keyLight.shadow.camera.right = 10;
	keyLight.shadow.camera.top = 10;
	keyLight.shadow.camera.bottom = -10;
	scene.add(keyLight);
	const fillLight = new THREE.DirectionalLight(0x8ccbd1, 1.2);
	fillLight.position.set(4, 4, -7);
	scene.add(fillLight);

	const ground = new THREE.Mesh(
		new THREE.PlaneGeometry(100, 100),
		new THREE.MeshStandardMaterial({ color: 0xdfe8da, roughness: 0.94, metalness: 0 })
	);
	ground.rotation.x = -Math.PI / 2;
	ground.position.y = -0.08;
	ground.receiveShadow = true;
	scene.add(ground);

	const routePoints = [
		new THREE.Vector3(-8, 0.015, 2.8),
		new THREE.Vector3(-6, 0.018, 1.7),
		new THREE.Vector3(-3.7, 0.02, 2.2),
		new THREE.Vector3(-1.5, 0.022, 3.9),
		new THREE.Vector3(1.2, 0.02, 4.2),
		new THREE.Vector3(4.1, 0.018, 2.8),
		new THREE.Vector3(7.8, 0.015, 2.5),
	];
	const route = new THREE.Mesh(
		new THREE.TubeGeometry(new THREE.CatmullRomCurve3(routePoints), 100, 0.035, 8, false),
		new THREE.MeshStandardMaterial({ color: 0xef7659, emissive: 0x6b2114, emissiveIntensity: 0.26, roughness: 0.42 })
	);
	route.position.y = 0.015;
	scene.add(route);
	addStop(THREE, scene, -5.6, 1.82, 0xc9e977);
	addStop(THREE, scene, 5.9, 2.56, 0xef7659);

	const coach = buildCoach(THREE);
	coach.position.set(0, 0, 0.05);
	scene.add(coach);

	const shadow = new THREE.Mesh(
		new THREE.CircleGeometry(3.7, 48),
		new THREE.MeshBasicMaterial({ color: 0x25332e, transparent: true, opacity: 0.11, depthWrite: false })
	);
	shadow.rotation.x = -Math.PI / 2;
	shadow.position.set(0, -0.045, 0.02);
	shadow.scale.set(1, 0.42, 1);
	scene.add(shadow);

	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let frame = 0;
	let animationId = 0;
	const clock = new THREE.Clock();
	const render = () => {
		const delta = Math.min(clock.getDelta(), 0.05);
		const elapsed = clock.elapsedTime;
		if (!reducedMotion) {
			coach.position.y = Math.sin(elapsed * 1.35) * 0.035;
			coach.rotation.y = Math.sin(elapsed * 0.25) * 0.025;
			coach.userData.wheels.forEach((wheel) => { wheel.rotation.y += delta * 1.4; });
		}
		renderer.render(scene, camera);
		frame += 1;
		if (!reducedMotion || frame === 1) animationId = window.requestAnimationFrame(render);
	};
	render();

	const resizeObserver = new ResizeObserver(() => {
		const width = mount.clientWidth;
		const height = mount.clientHeight;
		if (!width || !height) return;
		camera.aspect = width / height;
		camera.position.z = width < 520 ? 13.2 : width < 760 ? 11.8 : 10.7;
		camera.lookAt(0, 1.05, 0);
		camera.updateProjectionMatrix();
		renderer.setSize(width, height);
	});
	resizeObserver.observe(mount);
	window.addEventListener('pagehide', () => {
		window.cancelAnimationFrame(animationId);
		resizeObserver.disconnect();
		renderer.dispose();
	});
}

function buildCoach(THREE) {
	const coach = new THREE.Group();
	const wheels = [];
	const bodyMaterial = new THREE.MeshStandardMaterial({ color: 0xf7f4e9, roughness: 0.36, metalness: 0.04 });
	const lowerMaterial = new THREE.MeshStandardMaterial({ color: 0xe66e50, roughness: 0.45 });
	const glassMaterial = new THREE.MeshPhysicalMaterial({ color: 0x27534f, roughness: 0.18, metalness: 0.2, clearcoat: 0.8, clearcoatRoughness: 0.16 });
	const trimMaterial = new THREE.MeshStandardMaterial({ color: 0x25322e, roughness: 0.48, metalness: 0.1 });
	const hubMaterial = new THREE.MeshStandardMaterial({ color: 0xd7dfd4, roughness: 0.3, metalness: 0.58 });
	const headlightMaterial = new THREE.MeshStandardMaterial({ color: 0xffe8a1, emissive: 0xffbd49, emissiveIntensity: 1.3 });

	addBox(THREE, coach, [5.8, 1.2, 1.84], [0, 1.5, 0], bodyMaterial, true);
	addBox(THREE, coach, [5.78, 0.27, 1.88], [0, 0.79, 0], lowerMaterial, true);
	addBox(THREE, coach, [5.45, 0.12, 1.7], [-0.04, 2.15, 0], bodyMaterial, true);
	addBox(THREE, coach, [0.9, 0.1, 1.72], [-1.95, 2.23, 0], trimMaterial, false);

	for (const side of [-1, 1]) {
		for (let index = 0; index < 6; index += 1) {
			const x = -2.17 + index * 0.77;
			const windowWidth = index === 5 ? 0.56 : 0.61;
			addBox(THREE, coach, [windowWidth, 0.56, 0.035], [x, 1.65, side * 0.928], glassMaterial, false);
			addBox(THREE, coach, [0.035, 0.6, 0.042], [x + windowWidth / 2 + 0.025, 1.65, side * 0.93], trimMaterial, false);
		}
		addBox(THREE, coach, [0.48, 0.72, 0.05], [2.36, 1.66, side * 0.925], glassMaterial, false);
		addBox(THREE, coach, [0.04, 0.31, 0.06], [2.38, 1.42, side * 0.96], trimMaterial, false);
		addBox(THREE, coach, [0.38, 0.17, 0.065], [2.35, 0.97, side * 0.956], headlightMaterial, false);
		addBox(THREE, coach, [0.58, 0.055, 0.07], [-0.1, 0.94, side * 0.956], bodyMaterial, false);
	}
	addBox(THREE, coach, [0.055, 0.78, 1.44], [2.92, 1.67, 0], glassMaterial, false);
	addBox(THREE, coach, [0.1, 0.12, 0.64], [2.96, 1.03, 0], trimMaterial, false);
	addBox(THREE, coach, [0.13, 0.05, 0.12], [3, 1.43, 0.4], headlightMaterial, false);
	addBox(THREE, coach, [0.13, 0.05, 0.12], [3, 1.43, -0.4], headlightMaterial, false);
	addBox(THREE, coach, [0.12, 0.25, 0.13], [-2.93, 1.14, 0.67], trimMaterial, false);
	addBox(THREE, coach, [0.12, 0.25, 0.13], [-2.93, 1.14, -0.67], trimMaterial, false);

	for (const x of [-1.88, 1.9]) {
		for (const side of [-1, 1]) {
			const tire = new THREE.Mesh(new THREE.CylinderGeometry(0.49, 0.49, 0.23, 28), trimMaterial);
			tire.rotation.x = Math.PI / 2;
			tire.position.set(x, 0.55, side * 0.89);
			tire.castShadow = true;
			coach.add(tire);
			const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.23, 0.23, 0.25, 20), hubMaterial);
			hub.rotation.x = Math.PI / 2;
			hub.position.set(x, 0.55, side * 0.9);
			coach.add(hub);
			wheels.push(tire, hub);
		}
	}
	coach.userData.wheels = wheels;
	return coach;
}

function addBox(THREE, parent, size, position, material, castsShadow) {
	const mesh = new THREE.Mesh(new THREE.BoxGeometry(...size), material);
	mesh.position.set(...position);
	mesh.castShadow = castsShadow;
	mesh.receiveShadow = true;
	parent.add(mesh);
	return mesh;
}

function addStop(THREE, scene, x, z, color) {
	const pole = new THREE.Mesh(
		new THREE.CylinderGeometry(0.025, 0.025, 0.9, 10),
		new THREE.MeshStandardMaterial({ color: 0x34433d, roughness: 0.5 })
	);
	pole.position.set(x, 0.46, z);
	scene.add(pole);
	const marker = new THREE.Mesh(
		new THREE.SphereGeometry(0.15, 16, 12),
		new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: 0.3, roughness: 0.24 })
	);
	marker.position.set(x, 0.98, z);
	marker.castShadow = true;
	scene.add(marker);
}
