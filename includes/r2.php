<?php
/**
 * includes/r2.php — konfiguracja Cloudflare R2 (S3) dla przeglądarki plików
 * w panelu admina. Wykorzystuje generyczny klient z includes/s3_client.php.
 */

function r2_cfg(): array {
    $account  = setting('r2_account_id');
    $endpoint = setting('r2_endpoint');
    if ($endpoint === '' && $account !== '') {
        $endpoint = "https://{$account}.r2.cloudflarestorage.com";
    }
    return [
        'account_id'  => $account,
        'endpoint'    => $endpoint,
        'region'      => setting('r2_region') ?: 'auto',
        'bucket'      => setting('r2_bucket'),
        'access_key'  => setting('r2_access_key'),
        'secret_key'  => setting('r2_secret_key'),
        'prefix'      => setting('r2_prefix'),
        'public_base' => setting('r2_public_base'),
    ];
}

function r2_public_url(array $cfg, string $key): string {
    if ($cfg['public_base'] === '') return '';
    return rtrim($cfg['public_base'], '/') . '/' . $key;
}
