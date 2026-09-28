<?php

define('DB_ORACLE_USERNAME', getenv('DB_ORACLE_USERNAME') !== false ? getenv('DB_ORACLE_USERNAME') : 'smartmove');
define('DB_ORACLE_PASSWORD', getenv('DB_ORACLE_PASSWORD') !== false ? getenv('DB_ORACLE_PASSWORD') : '');
define('DB_ORACLE_CONNECTION_STRING', getenv('DB_ORACLE_CONNECTION_STRING') !== false ? getenv('DB_ORACLE_CONNECTION_STRING') : 'localhost/XEPDB1');
define('DB_ORACLE_CHARSET', getenv('DB_ORACLE_CHARSET') !== false ? getenv('DB_ORACLE_CHARSET') : 'AL32UTF8');

define('DB_MONGODB_URI', getenv('DB_MONGODB_URI') !== false ? getenv('DB_MONGODB_URI') : 'mongodb://127.0.0.1:27017');
define('DB_MONGODB_DATABASE', getenv('DB_MONGODB_DATABASE') !== false ? getenv('DB_MONGODB_DATABASE') : 'smartmove');
