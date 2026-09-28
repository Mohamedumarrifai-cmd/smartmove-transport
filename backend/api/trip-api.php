<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

handleOracleCrud(
	'TRIP',
	'trip_id',
	['route_id', 'vehicle_id', 'driver_id', 'departure_time', 'arrival_time', 'fare', 'status'],
	['route_id', 'vehicle_id', 'driver_id', 'departure_time', 'arrival_time', 'fare']
);
