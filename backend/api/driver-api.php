<?php

require_once __DIR__ . '/../helpers/oracle-crud.php';

handleOracleCrud(
	'DRIVER',
	'driver_id',
	['full_name', 'license_number', 'phone', 'email', 'hire_date', 'status'],
	['full_name', 'license_number', 'phone', 'email']
);
