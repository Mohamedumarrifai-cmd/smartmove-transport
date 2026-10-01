<?php

function escapeSetupValue(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setupSequenceExists(mixed $connection, string $sequenceName): bool
{
	$sql = 'SELECT 1 FROM USER_SEQUENCES WHERE SEQUENCE_NAME = :sequence_name';
	$statement = @oci_parse($connection, $sql);
	if ($statement === false) {
		$error = oci_error($connection);
		throw new RuntimeException(is_array($error) ? (string) ($error['message'] ?? 'Could not check sequence existence.') : 'Could not check sequence existence.');
	}
	oci_bind_by_name($statement, ':sequence_name', $sequenceName, 128);
	if (!@oci_execute($statement)) {
		$error = oci_error($statement);
		oci_free_statement($statement);
		throw new RuntimeException(is_array($error) ? (string) ($error['message'] ?? 'Could not check sequence existence.') : 'Could not check sequence existence.');
	}
	$exists = oci_fetch_row($statement) !== false;
	oci_free_statement($statement);
	return $exists;
}

$requiredTables = ['PASSENGER', 'VEHICLE', 'DRIVER', 'ROUTE', 'TRIP', 'TICKET', 'PAYMENT', 'MAINTENANCE', 'FEEDBACK'];
$tableStatuses = [];
$statementResults = [];
$reportError = null;
$connection = null;
$runSetup = ($_GET['run'] ?? '') === '1';

try {
	require_once __DIR__ . '/database-oracle.php';
	require_once __DIR__ . '/../helpers/oracle-helper.php';
	if ($runSetup && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
		throw new RuntimeException('Table creation is permitted only from this XAMPP server on localhost.');
	}
	$connection = getOracleConnection();

	foreach ($requiredTables as $tableName) {
		$tableStatuses[$tableName] = tableExists($connection, $tableName);
	}

	if ($runSetup && in_array(false, $tableStatuses, true)) {
		$sqlFile = __DIR__ . '/../../database/oracle/01_tables.sql';
		$sqlContents = @file_get_contents($sqlFile);
		if (!is_string($sqlContents)) {
			throw new RuntimeException('Unable to read database/oracle/01_tables.sql.');
		}

		// Remove full-line SQL comments before splitting this plain CREATE TABLE script on semicolons.
		$sqlContents = preg_replace('/^\s*--.*(?:\R|$)/m', '', $sqlContents) ?? $sqlContents;
		foreach (explode(';', $sqlContents) as $statementSql) {
			$statementSql = trim($statementSql);
			if ($statementSql === '') {
				continue;
			}

			if (!preg_match('/\ACREATE\s+TABLE\s+([A-Z0-9_$#]+)/i', $statementSql, $matches)) {
				$statementResults[] = ['status' => 'skipped', 'label' => 'Unrecognized non-table statement', 'message' => 'Only CREATE TABLE statements are run by this page.'];
				continue;
			}

			$tableName = strtoupper($matches[1]);
			if (tableExists($connection, $tableName)) {
				$statementResults[] = ['status' => 'skipped', 'label' => 'CREATE TABLE ' . $tableName, 'message' => 'Table already exists.'];
				continue;
			}

			$GLOBALS['authSqlSnippet'] = $statementSql;
			$statement = @oci_parse($connection, $statementSql);
			if ($statement === false) {
				$error = oci_error($connection);
				$statementResults[] = [
					'status' => 'failed',
					'label' => 'CREATE TABLE ' . $tableName,
					'message' => is_array($error) ? (string) ($error['message'] ?? 'Oracle could not prepare the statement.') : 'Oracle could not prepare the statement.',
				];
				continue;
			}

			if (!@oci_execute($statement)) {
				$error = oci_error($statement);
				$statementResults[] = [
					'status' => 'failed',
					'label' => 'CREATE TABLE ' . $tableName,
					'message' => is_array($error) ? (string) ($error['message'] ?? 'Oracle could not execute the statement.') : 'Oracle could not execute the statement.',
				];
			} else {
				$statementResults[] = ['status' => 'success', 'label' => 'CREATE TABLE ' . $tableName, 'message' => 'Table created.'];
			}
			oci_free_statement($statement);
		}

		foreach ($requiredTables as $tableName) {
			$tableStatuses[$tableName] = tableExists($connection, $tableName);
		}
	}

	if ($runSetup) {
		$sequenceFile = __DIR__ . '/../../database/oracle/02_sequences.sql';
		$sequenceContents = @file_get_contents($sequenceFile);
		if (!is_string($sequenceContents)) {
			throw new RuntimeException('Unable to read database/oracle/02_sequences.sql.');
		}

		$sequenceContents = preg_replace('/^\s*--.*(?:\R|$)/m', '', $sequenceContents) ?? $sequenceContents;
		foreach (explode(';', $sequenceContents) as $statementSql) {
			$statementSql = trim($statementSql);
			if ($statementSql === '') {
				continue;
			}

			if (preg_match('/\ACREATE\s+SEQUENCE\s+([A-Z0-9_$#]+)/i', $statementSql, $matches) === 1) {
				$sequenceName = strtoupper($matches[1]);
				if (setupSequenceExists($connection, $sequenceName)) {
					$statementResults[] = ['status' => 'skipped', 'label' => 'CREATE SEQUENCE ' . $sequenceName, 'message' => 'Sequence already exists.'];
					continue;
				}
				$label = 'CREATE SEQUENCE ' . $sequenceName;
			} elseif (preg_match('/\AALTER\s+TABLE\s+([A-Z0-9_$#]+)/i', $statementSql, $matches) === 1) {
				$tableName = strtoupper($matches[1]);
				$label = 'ALTER TABLE ' . $tableName;
				if (!tableExists($connection, $tableName)) {
					$statementResults[] = ['status' => 'skipped', 'label' => $label, 'message' => 'Table does not exist.'];
					continue;
				}
			} else {
				$statementResults[] = ['status' => 'skipped', 'label' => 'Unrecognized sequence statement', 'message' => 'Only CREATE SEQUENCE and ALTER TABLE statements are run.'];
				continue;
			}

			$statement = @oci_parse($connection, $statementSql);
			if ($statement === false) {
				$error = oci_error($connection);
				$statementResults[] = [
					'status' => 'failed',
					'label' => $label,
					'message' => is_array($error) ? (string) ($error['message'] ?? 'Oracle could not prepare the statement.') : 'Oracle could not prepare the statement.',
				];
				continue;
			}

			if (!@oci_execute($statement)) {
				$error = oci_error($statement);
				$statementResults[] = [
					'status' => 'failed',
					'label' => $label,
					'message' => is_array($error) ? (string) ($error['message'] ?? 'Oracle could not execute the statement.') : 'Oracle could not execute the statement.',
				];
			} else {
				$statementResults[] = ['status' => 'success', 'label' => $label, 'message' => 'Statement applied.'];
			}
			oci_free_statement($statement);
		}
	}
} catch (Throwable $exception) {
	$reportError = $exception->getMessage();
}

$missingTables = array_keys(array_filter($tableStatuses, static fn(bool $exists): bool => !$exists));
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>SmartMove Oracle Tables</title>
	<style>
		:root { font-family: "Segoe UI", sans-serif; color: #1b2925; background: #f2f5f3; }
		* { box-sizing: border-box; }
		body { margin: 0; padding: 32px 16px; }
		main { max-width: 820px; margin: 0 auto; }
		h1 { margin: 0 0 8px; font-size: 27px; }
		.lead { margin: 0 0 22px; color: #596963; }
		.panel { margin: 14px 0; padding: 20px; border: 1px solid #dce5e0; border-radius: 6px; background: #fff; }
		h2 { margin: 0 0 14px; font-size: 17px; }
		.row, .result { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 11px 0; border-top: 1px solid #edf1ef; }
		.row:first-of-type, .result:first-of-type { border-top: 0; }
		.badge { flex: 0 0 auto; padding: 5px 9px; border-radius: 4px; font-size: 11px; font-weight: 700; }
		.success { color: #176b39; background: #e2f4e9; }
		.error { color: #a32920; background: #fde8e6; }
		.skipped { color: #795215; background: #fff1d4; }
		.detail { color: #596963; overflow-wrap: anywhere; }
		.error-box { padding: 12px 14px; border-left: 4px solid #b83229; background: #fde8e6; overflow-wrap: anywhere; }
		.warning { padding: 12px 14px; border-left: 4px solid #c58a28; background: #fff4dc; color: #674b1b; }
		.actions { display: flex; flex-wrap: wrap; gap: 10px; }
		a.button { display: inline-block; padding: 10px 14px; border-radius: 4px; color: #fff; background: #285d4d; text-decoration: none; font-weight: 700; }
		code { white-space: pre-wrap; overflow-wrap: anywhere; }
		@media (max-width: 520px) { body { padding: 20px 10px; } .panel { padding: 15px; } }
	</style>
</head>
<body>
<main>
	<h1>SmartMove Oracle Tables</h1>
	<p class="lead">Required application tables in the connected Oracle schema.</p>
	<p class="warning">This setup page can create database tables. Restrict access while using it, then remove or disable it.</p>
	<p class="detail">The local create action also configures the ID sequences required by SmartMove.</p>
	<?php if ($reportError !== null): ?><p class="error-box"><?= escapeSetupValue($reportError) ?></p><?php endif; ?>
	<section class="panel">
		<h2>Required Tables</h2>
		<?php if ($connection === null): ?>
			<p class="detail">Table status is unavailable until the Oracle connection succeeds.</p>
		<?php else: ?>
			<?php foreach ($requiredTables as $tableName): $exists = $tableStatuses[$tableName] ?? false; ?>
			<div class="row"><strong><?= escapeSetupValue($tableName) ?></strong><span class="badge <?= $exists ? 'success' : 'error' ?>"><?= $exists ? 'EXISTS' : 'MISSING' ?></span></div>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>
	<?php if ($statementResults !== []): ?>
	<section class="panel">
		<h2>Setup Statement Results</h2>
		<?php foreach ($statementResults as $result): ?>
		<div class="result"><div><strong><?= escapeSetupValue($result['label']) ?></strong><div class="detail"><?= escapeSetupValue($result['message']) ?></div></div><span class="badge <?= escapeSetupValue($result['status']) ?>"><?= strtoupper(escapeSetupValue($result['status'])) ?></span></div>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>
	<?php if ($connection !== null): ?>
	<div class="actions">
		<?php if ($missingTables !== []): ?><a class="button" href="?run=1" onclick="return confirm('Create the missing Oracle tables now?');">Create Missing Tables</a><?php else: ?><span class="badge success">All required tables exist</span><?php endif; ?>
	</div>
	<?php endif; ?>
</main>
</body>
</html>