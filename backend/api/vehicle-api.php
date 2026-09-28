<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

handleOracleCrud(
	'VEHICLE',
	'vehicle_id',
	['registration_number', 'make', 'model', 'vehicle_type', 'manufacture_year', 'capacity', 'status'],
	['registration_number', 'make', 'model', 'vehicle_type', 'capacity']
);
