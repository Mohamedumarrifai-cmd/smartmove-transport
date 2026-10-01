<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database-oracle.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<pre style='font-family: monospace; font-size: 14px; padding: 20px;'>";

$conn = getOracleConnection();

// 1. Which user are we connected as?
$stmt = oci_parse($conn, "SELECT USER AS CURRENT_USER FROM DUAL");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
echo "Current Oracle User:  " . $row['CURRENT_USER'] . "\n";
oci_free_statement($stmt);

// 2. Which container (PDB) are we in?
$stmt = oci_parse($conn, "SELECT SYS_CONTEXT('USERENV', 'CON_NAME') AS CONTAINER FROM DUAL");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
echo "Current Container:     " . $row['CONTAINER'] . "\n";
oci_free_statement($stmt);

// 3. Does PASSENGER table exist in this schema?
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM USER_TABLES WHERE TABLE_NAME = 'PASSENGER'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
echo "PASSENGER (USER_TABLES): " . $row['CNT'] . "\n";
oci_free_statement($stmt);

// 4. Check ALL_TABLES (any schema)
$stmt = oci_parse($conn, "SELECT owner, table_name FROM ALL_TABLES WHERE TABLE_NAME = 'PASSENGER'");
oci_execute($stmt);
echo "\nPASSENGER found in ALL_TABLES:\n";
while ($row = oci_fetch_assoc($stmt)) {
    echo "   - Owner: " . $row['OWNER'] . " | Table: " . $row['TABLE_NAME'] . "\n";
}
oci_free_statement($stmt);

// 5. List all tables in current schema
$stmt = oci_parse($conn, "SELECT table_name FROM USER_TABLES ORDER BY table_name");
oci_execute($stmt);
echo "\nAll tables in current schema:\n";
while ($row = oci_fetch_assoc($stmt)) {
    echo "   - " . $row['TABLE_NAME'] . "\n";
}
oci_free_statement($stmt);

echo "</pre>";