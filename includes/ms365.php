<?php
/**
 * includes/ms365.php — logowanie do panelu admina przez Microsoft 365 (Azure AD)
 * Autoryzacja: Authorization Code + PKCE (Microsoft identity platform v2.0).
 * Port sprawdzonego mechanizmu z feerSZO (includes/auth.php), dopasowany do
 * pojedynczej tabeli admins (bez ról/organizacji).
 *
 * Dopasowanie konta: po adresie e-mail (admins.email) lub microsoft_id.
 * Logowanie NIE tworzy automatycznie nowych kont — konto administratora
 * i jego e-mail trzeba najpierw założyć (instalator / cli/install.php / Ustawienia).
 */

function ms365_creds(): array {
    return [
        setting('ms_tenant_id') ?: 'common',
        setting('ms_client_id'),
        setting('ms_client_secret'),
    ];
}

function ms365_available(): bool {
    [$tid, $cid] = ms365_creds();
    return $cid !== '' && $tid !== 'common';
}

function ms365_redirect_uri(): string {
    return rtrim(APP_URL, '/') . '/admin/ms365_callback.php';
}

function ms365_auth_url(string $redirect_after = ''): string {
    auth_start();
    [$tenant_id, $client_id] = ms365_creds();

    $verifier  = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $state     = bin2hex(random_bytes(16));

    $_SESSION['ms365_state']    = $state;
    $_SESSION['ms365_verifier'] = $verifier;
    if ($redirect_after) $_SESSION['ms365_redirect'] = $redirect_after;

    try {
        db()->prepare(
            "INSERT OR REPLACE INTO oauth_states (state, verifier, redirect_to, created_at) VALUES (?,?,?,?)"
        )->execute([$state, $verifier, $redirect_after, time()]);
    } catch (\Throwable $e) {}

    $params = http_build_query([
        'client_id'             => $client_id,
        'response_type'         => 'code',
        'redirect_uri'          => ms365_redirect_uri(),
        'scope'                 => 'openid email profile User.Read',
        'response_mode'         => 'query',
        'state'                 => $state,
        'code_challenge'        => $challenge,
        'code_challenge_method' => 'S256',
    ]);
    return "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/authorize?" . $params;
}

function ms365_exchange_code(string $code): ?array {
    auth_start();
    [$tenant_id, $client_id, $client_secret] = ms365_creds();
    $verifier = $_SESSION['ms365_verifier'] ?? '';

    $data = [
        'client_id'    => $client_id,
        'code'         => $code,
        'redirect_uri' => ms365_redirect_uri(),
        'grant_type'   => 'authorization_code',
    ];
    if ($client_secret) $data['client_secret'] = $client_secret;
    if ($verifier)       $data['code_verifier'] = $verifier;

    $ch = curl_init("https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    return $response ? json_decode($response, true) : null;
}

function ms365_get_user(string $access_token): ?array {
    $ch = curl_init('https://graph.microsoft.com/v1.0/me');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    return $response ? json_decode($response, true) : null;
}
