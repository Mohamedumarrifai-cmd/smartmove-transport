<?php

// Render all diagnostic values as text so PHP and Oracle messages cannot inject HTML.
function escapeDiagnosticValue(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Capture the module section of phpinfo to verify OCI8 and the loaded Oracle client runtime.
ob_start();
phpinfo(INFO_MODULES);
$phpInfoOutput = (string) ob_get_clean();
$phpInfoText = strip_tags($phpInfoOutput);
$oci8PhpInfoFound = stripos($phpInfoText, 'OCI8 Support') !== false;
$instantClientVersion = null;
if (preg_match('/Oracle Run-time Client Library Version\s*([^\r\n]+)/i', $phpInfoText, $clientVersionMatch) === 1) {
	$instantClientVersion = trim($clientVersionMatch[1]);
}

// Determine extension availability independently from phpinfo's module text.
$oci8FunctionExists = function_exists('oci_connect');
$oci8ExtensionLoaded = extension_loaded('oci8');
$connection = null;
$connectionError = null;
$oracleVersion = null;

// Use the application's own configured connection so this test exercises the actual settings.
try {
	require_once __DIR__ . '/database-oracle.php';
	$connection = getOracleConnection();
	$oracleVersion = oci_server_version($connection);
	if ($oracleVersion === false) {
		$error = oci_error($connection);
		$connectionError = is_array($error) ? (string) ($error['message'] ?? 'Unable to read Oracle server version.') : 'Unable to read Oracle server version.';
	}
} catch (Throwable $exception) {
	$connectionError = $exception->getMessage();
}

// Assign clear status classes for the report's success and error badges.
$oci8Status = $oci8FunctionExists ? 'success' : 'error';
$clientStatus = $oci8PhpInfoFound && $instantClientVersion !== null ? 'success' : 'error';
$connectionStatus = $connection !== null && $connectionError === null ? 'success' : 'error';
$phpVersion = PHP_VERSION;
$extensions = get_loaded_extensions();
sort($extensions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>SmartMove Oracle Diagnostic</title>
	<style>
		:root { color-scheme: light; font-family: "Segoe UI", sans-serif; background: #f2f5f4; color: #192522; }
		* { box-sizing: border-box; }
		body { margin: 0; padding: 36px 18px; }
		main { max-width: 900px; margin: 0 auto; }
		header { margin-bottom: 24px; }
		h1 { margin: 0 0 8px; font-size: 28px; }
		.lead { margin: 0; color: #52635e; }
		section { margin: 14px 0; padding: 20px; background: #fff; border: 1px solid #dce5e1; border-radius: 7px; }
		h2 { margin: 0 0 14px; font-size: 17px; }
		.row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 11px 0; border-top: 1px solid #edf1ef; }
		.row:first-of-type { border-top: 0; }
		.label { font-weight: 600; }
		.detail { margin-top: 4px; color: #53645f; overflow-wrap: anywhere; }
		.badge { flex: 0 0 auto; padding: 5px 9px; border-radius: 4px; font-size: 12px; font-weight: 700; }
		.success { background: #e2f4e9; color: #176b39; }
		.error { background: #fde8e6; color: #a32920; }
		.extensions { margin: 0; color: #53645f; line-height: 1.7; overflow-wrap: anywhere; }
		.warning { padding: 13px 15px; border-left: 4px solid #d28b24; background: #fff5df; color: #674b1b; }
		@media (max-width: 560px) { body { padding: 22px 12px; } section { padding: 16px; } .row { align-items: flex-start; } }
	</style>
</head>
<body>
<main>
	<header>
		<h1>SmartMove Oracle Diagnostic</h1>
		<p class="lead">PHP runtime, OCI8, Oracle client, and database connection status.</p>
	</header>
	<p class="warning">This page exposes server diagnostic details. Delete it or restrict access after troubleshooting.</p>
	<section>
		<h2>PHP Runtime</h2>
		<div class="row"><div><div class="label">PHP version</div><div class="detail"><?= escapeDiagnosticValue($phpVersion) ?></div></div><span class="badge success">AVAILABLE</span></div>
		<div class="row"><div><div class="label">OCI8 extension loaded</div><div class="detail">extension_loaded('oci8'): <?= $oci8ExtensionLoaded ? 'yes' : 'no' ?>; oci_connect(): <?= $oci8FunctionExists ? 'available' : 'not available' ?></div></div><span class="badge <?= escapeDiagnosticValue($oci8Status) ?>"><?= $oci8FunctionExists ? 'READY' : 'MISSING' ?></span></div>
	</section>
	<section>
		<h2>Oracle Client</h2>
		<div class="row"><div><div class="label">OCI8 Support in phpinfo()</div><div class="detail"><?= $oci8PhpInfoFound ? 'OCI8 module information was found.' : 'OCI8 Support was not found in the PHP module information.' ?></div></div><span class="badge <?= escapeDiagnosticValue($oci8PhpInfoFound ? 'success' : 'error') ?>"><?= $oci8PhpInfoFound ? 'FOUND' : 'NOT FOUND' ?></span></div>
		<div class="row"><div><div class="label">Oracle client runtime DLLs</div><div class="detail"><?= $instantClientVersion !== null ? 'Oracle Run-time Client Library Version: ' . escapeDiagnosticValue($instantClientVersion) : 'No Oracle runtime client version was reported by phpinfo().' ?></div></div><span class="badge <?= escapeDiagnosticValue($clientStatus) ?>"><?= $instantClientVersion !== null ? 'DETECTED' : 'NOT DETECTED' ?></span></div>
	</section>
	<section>
		<h2>Oracle Database Connection</h2>
		<div class="row"><div><div class="label">Connection test</div><div class="detail"><?= $connectionError !== null ? nl2br(escapeDiagnosticValue($connectionError)) : 'Connected successfully using the configured database-oracle.php credentials.' ?></div></div><span class="badge <?= escapeDiagnosticValue($connectionStatus) ?>"><?= $connectionStatus === 'success' ? 'CONNECTED' : 'FAILED' ?></span></div>
		<?php if ($oracleVersion !== null): ?>
		<div class="row"><div><div class="label">Oracle server version</div><div class="detail"><?= escapeDiagnosticValue((string) $oracleVersion) ?></div></div><span class="badge success">AVAILABLE</span></div>
		<?php endif; ?>
	</section>
	<section>
		<h2>Loaded PHP Extensions</h2>
		<p class="extensions"><?= escapeDiagnosticValue(implode(', ', $extensions)) ?></p>
	</section>
</main>
</body>
</html>