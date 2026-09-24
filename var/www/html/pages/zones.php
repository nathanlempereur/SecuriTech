<?php
declare(strict_types=1);

$csvPath = __DIR__ . '/../csv/zone.csv';
$zones = [];
$serverIp = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$serverHost = filter_var($serverIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $serverIp . ']' : $serverIp;
$handle = @fopen($csvPath, 'rb');

if ($handle !== false) {
	$headers = fgetcsv($handle, 0, ',', '"', chr(92));
	$headerMap = [];

	if ($headers !== false) {
		foreach ($headers as $index => $header) {
			$headerMap[strtolower(trim((string) $header, " \t\r\n\xEF\xBB\xBF"))] = $index;
		}
	}

	$requiredHeaders = ['zones', 'ip-zone', 'status-zone', 'user-error', 'ssh-error', 'tmp-error', 'authlogs-error'];
	$hasExpectedHeaders = $headers !== false && count(array_intersect($requiredHeaders, array_keys($headerMap))) === count($requiredHeaders);

	if ($hasExpectedHeaders) {
	while (($row = fgetcsv($handle, 0, ',', '"', chr(92))) !== false) {
		if (count($row) < count($headers)) {
			continue;
		}

		$name = trim((string) $row[$headerMap['zones']]);
		$address = trim((string) $row[$headerMap['ip-zone']]);
		$status = trim((string) $row[$headerMap['status-zone']]);
		$userError = trim((string) $row[$headerMap['user-error']]);
		$sshError = trim((string) $row[$headerMap['ssh-error']]);
		$tmpError = trim((string) $row[$headerMap['tmp-error']]);
		$authLogsError = trim((string) $row[$headerMap['authlogs-error']]);

		if ($name === '' || $address === '') {
			continue;
		}

		$zones[] = [
			'nom' => $name,
			'adresse_ip' => $address,
			'statut' => $status,
			'user_error' => $userError,
			'ssh_error' => $sshError,
			'tmp_error' => $tmpError,
			'authlogs_error' => $authLogsError,
		];
	}
	}
	fclose($handle);
}
?>

<main class="page-content access-page">
	<section class="access-heading" aria-labelledby="zones-title">
		<div>
			<p class="eyebrow">Supervision réseau</p>
			<h1 id="zones-title">Zones</h1>
			<p class="intro">État actuel des zones déclarées dans le fichier de supervision.</p>
		</div>
		<div class="demo-status"><span></span> Source CSV</div>
	</section>

	<section class="dashboard-section zones-section" aria-labelledby="zones-list-title">
		<div class="section-heading">
			<div>
				<p class="eyebrow">ZONES_ACTIVES</p>
				<h2 id="zones-list-title">Liste des zones</h2>
			</div>
			<span class="section-count"><?= count($zones) ?> zone<?= count($zones) > 1 ? 's' : '' ?></span>
		</div>

		<div class="zone-list">
			<?php if ($zones === []): ?>
				<p class="empty-table">Aucune zone disponible dans le fichier CSV.</p>
			<?php else: ?>
				<?php foreach ($zones as $zoneIndex => $zone): ?>
					<?php
					$zoneNumber = sprintf('%02d', $zoneIndex + 1);
					$zoneUrl = 'http://' . $serverHost . ':80' . $zoneNumber;
					?>
					<article class="zone-item has-zone-link" data-zone-url="<?= htmlspecialchars($zoneUrl, ENT_QUOTES, 'UTF-8') ?>" role="link" tabindex="0" title="Ouvrir la zone">
						<div class="zone-item-content">
							<h3><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
							<p><?= htmlspecialchars($zone['adresse_ip'], ENT_QUOTES, 'UTF-8') ?></p>
						</div>
						<span class="tag <?= strtolower($zone['statut']) === 'running' ? 'tag-normal' : (strtoupper($zone['statut']) === 'N/A' ? 'tag-unknown' : 'tag-critical') ?>">
							<?= htmlspecialchars($zone['statut'], ENT_QUOTES, 'UTF-8') ?>
						</span>
						<div class="zone-alerts" aria-label="État des erreurs de la zone">
							<?php foreach (['User-Error' => $zone['user_error'], 'SSH-Error' => $zone['ssh_error'], 'tmp-Error' => $zone['tmp_error'], 'AuthLogs-Error' => $zone['authlogs_error']] as $errorLabel => $errorValue): ?>
								<?php $errorState = strtoupper(trim($errorValue)); ?>
								<div class="zone-alert <?= $errorState === 'NO' ? 'has-no-error' : ($errorState === 'N/A' ? 'has-unknown' : 'has-error') ?>">
									<span><?= $errorLabel ?></span>
									<strong><?= htmlspecialchars($errorValue, ENT_QUOTES, 'UTF-8') ?></strong>
								</div>
							<?php endforeach; ?>
						</div>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</section>
</main>
