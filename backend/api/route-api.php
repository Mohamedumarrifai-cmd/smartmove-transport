<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

handleOracleCrud(
	'ROUTE',
	'route_id',
	['route_name', 'origin_city', 'destination_city', 'distance_km', 'estimated_duration_minutes', 'is_active'],
	['route_name', 'origin_city', 'destination_city', 'distance_km', 'estimated_duration_minutes']
);
