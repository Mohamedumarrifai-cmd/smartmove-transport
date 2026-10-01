<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database-oracle.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<pre style='font-family: monospace; font-size: 14px; padding: 20px; background:#f5f5f5;'>";

$conn = getOracleConnection();

// Test email and password
$testEmail = $_GET['email'] ?? 'mohamedumarrifai@gmail.com';
$testPassword = $_GET['password'] ?? '';

echo "=== DEBUG LOGIN ===\n";
echo "Testing email:    $testEmail\n";
echo "Testing password: " . ($testPassword ? $testPassword : '(not provided)') . "\n\n";

// Step 1: Find the user
$sql = "SELECT passenger_id, full_name, email, password_hash FROM PASSENGER WHERE LOWER(email) = LOWER(:email)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':email', $testEmail);
oci_execute($stmt);
$user = oci_fetch_assoc($stmt);

if (!$user) {
    echo "❌ User NOT FOUND in database!\n";
    echo "\nAll passengers in database:\n";
    $stmt2 = oci_parse($conn, "SELECT passenger_id, email FROM PASSENGER");
    oci_execute($stmt2);
    while ($r = oci_fetch_assoc($stmt2)) {
        echo "   - ID: {$r['PASSENGER_ID']} | Email: {$r['EMAIL']}\n";
    }
    oci_free_statement($stmt2);
    exit;
}

echo "✅ User FOUND:\n";
echo "   Passenger ID: " . $user['PASSENGER_ID'] . "\n";
echo "   Full Name:    " . $user['FULL_NAME'] . "\n";
echo "   Email:        " . $user['EMAIL'] . "\n";
echo "   Hash Length:  " . strlen($user['PASSWORD_HASH']) . " characters\n";
echo "   Hash (first 30): " . substr($user['PASSWORD_HASH'], 0, 30) . "...\n\n";

// Step 2: Check hash format
$hash = $user['PASSWORD_HASH'];
$hashInfo = password_get_info($hash);
echo "Hash algorithm: " . $hashInfo['algoName'] . "\n";
echo "Hash valid?     " . ($hashInfo['algo'] !== null && $hashInfo['algo'] !== 0 ? 'YES' : 'NO') . "\n\n";

// Step 3: Test password verification
if (!empty($testPassword)) {
    $result = password_verify($testPassword, $hash);
    echo "Password verify result: " . ($result ? "✅ MATCH!" : "❌ DOES NOT MATCH") . "\n";
    
    if (!$result) {
        echo "\n--- Testing common variations ---\n";
        $variations = [
            trim($testPassword),
            rtrim($testPassword),
            strtolower($testPassword),
        ];
        foreach ($variations as $i => $v) {
            $r = password_verify($v, $hash);
            echo "Variation $i ($v): " . ($r ? "✅ MATCH" : "❌") . "\n";
        }
    }
} else {
    echo "⚠️  Provide a password to test: ?email=...&password=...\n";
}

oci_free_statement($stmt);
echo "</pre>";