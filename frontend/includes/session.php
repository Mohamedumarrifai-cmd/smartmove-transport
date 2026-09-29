<?php

require_once __DIR__ . '/../../backend/config/constants.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_name(SESSION_NAME);
	session_set_cookie_params(SESSION_COOKIE_PARAMS);
	session_start();
}