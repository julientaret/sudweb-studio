<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mode maintenance du site public — état piloté depuis l'admin (onglet
 * Maintenance), stocké dans storage/maintenance.json. Même approche que
 * storage/modules.json : un état d'admin, pas une constante serveur.
 *
 * Quand il est actif, le site public (frontend) ne répond plus : toute requête
 * est remplacée par une page de maintenance (HTTP 503). L'admin (flow-cms) reste
 * toujours joignable — le garde-fou ne vit que dans le front controller public.
 *
 * Deux façons de contourner (bypass) la maintenance :
 *  - une URL secrète portant un jeton (« hash ») : la visiter enregistre l'IP du
 *    visiteur dans la liste d'autorisation et pose un cookie, puis redirige vers
 *    le site. Le jeton est régénérable depuis l'admin (invalide l'ancienne URL) ;
 *  - une liste d'adresses IP autorisées : alimentée automatiquement à chaque
 *    bypass, et complétable/épurable à la main depuis l'admin.
 */
final class MaintenanceSettings
{
    /** Paramètre d'URL portant le jeton de bypass (ex. /?acces=<jeton>). */
    public const BYPASS_PARAM = 'acces';

    /** Cookie posé lors d'un bypass réussi (utile si l'IP du visiteur change). */
    private const COOKIE = 'flow_bypass';

    private string $path;

    public function __construct(string $basePath)
    {
        $this->path = rtrim($basePath, '/') . '/storage/maintenance.json';
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->read()['enabled'] ?? false);
    }

    public function setEnabled(bool $enabled): void
    {
        $this->write(['enabled' => $enabled] + $this->read());
    }

    /**
     * Jeton de bypass courant. Généré et persisté à la première lecture pour que
     * l'URL secrète existe dès l'ouverture de l'onglet (avant même l'activation).
     */
    public function bypassToken(): string
    {
        $data = $this->read();
        $token = $data['token'] ?? null;
        if (!is_string($token) || $token === '') {
            return $this->regenerateToken();
        }
        return $token;
    }

    /** Régénère le jeton (l'ancienne URL de bypass cesse de fonctionner). */
    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(16));
        $this->write(['token' => $token] + $this->read());
        return $token;
    }

    public function cookieName(): string
    {
        return self::COOKIE;
    }

    /** @return array<int, string> IP autorisées à contourner la maintenance. */
    public function allowedIps(): array
    {
        return array_values(array_filter(
            (array) ($this->read()['ips'] ?? []),
            'is_string'
        ));
    }

    /**
     * Ajoute une IP à la liste d'autorisation. Retourne false si l'IP est
     * invalide ; un doublon est ignoré silencieusement (retourne true).
     */
    public function addIp(string $ip): bool
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $ips = $this->allowedIps();
        if (!in_array($ip, $ips, true)) {
            $ips[] = $ip;
            $this->write(['ips' => $ips] + $this->read());
        }
        return true;
    }

    public function removeIp(string $ip): void
    {
        $ips = array_values(array_diff($this->allowedIps(), [$ip]));
        $this->write(['ips' => $ips] + $this->read());
    }

    /**
     * Le visiteur a-t-il le droit de contourner la maintenance ? Vrai si son IP
     * est listée, ou s'il présente le cookie de bypass au jeton courant.
     */
    public function isAllowed(string $ip, ?string $cookie): bool
    {
        if ($ip !== '' && in_array($ip, $this->allowedIps(), true)) {
            return true;
        }
        return is_string($cookie) && $cookie !== '' && hash_equals($this->bypassToken(), $cookie);
    }

    /**
     * Garde-fou appelé par le front controller public avant tout rendu.
     *
     * - maintenance inactive → ne fait rien ;
     * - URL de bypass valide → enregistre l'IP, pose le cookie, redirige vers
     *   l'URL propre (jeton retiré) puis stoppe la requête ;
     * - visiteur autorisé (IP ou cookie) → laisse passer ;
     * - sinon → envoie la page de maintenance (503) et stoppe la requête.
     */
    public function enforce(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $submitted = $_GET[self::BYPASS_PARAM] ?? null;

        if (is_string($submitted) && $submitted !== '' && hash_equals($this->bypassToken(), $submitted)) {
            if ($ip !== '') {
                $this->addIp($ip);
            }
            $this->setBypassCookie();
            header('Location: ' . $this->cleanUrl(), true, 302);
            exit;
        }

        if ($this->isAllowed($ip, $_COOKIE[self::COOKIE] ?? null)) {
            return;
        }

        $this->renderPage();
        exit;
    }

    private function setBypassCookie(): void
    {
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off');
        setcookie(self::COOKIE, $this->bypassToken(), [
            'expires'  => time() + 30 * 24 * 3600,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** URL courante débarrassée du paramètre de bypass (pour la redirection). */
    private function cleanUrl(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $query = (string) (parse_url($uri, PHP_URL_QUERY) ?: '');

        parse_str($query, $params);
        unset($params[self::BYPASS_PARAM]);
        $rebuilt = http_build_query($params);

        return $path . ($rebuilt !== '' ? '?' . $rebuilt : '');
    }

    /**
     * Envoie la page de maintenance (503). Le site peut la personnaliser via un
     * fichier frontend/maintenance.php ; sinon une page intégrée est affichée.
     */
    private function renderPage(): void
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600');
        header('Cache-Control: no-store');

        $publicDir = (string) Config::get('storage.public_dir', '');
        $override = $publicDir !== '' ? dirname($publicDir) . '/maintenance.php' : '';
        if ($override !== '' && is_file($override)) {
            require $override;
            return;
        }

        $site = e((string) Config::get('app.site_name', 'Le site'));
        echo <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Maintenance — {$site}</title>
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:grid;place-items:center;padding:1.5rem;
    font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif;
    color:#1b2030;background:radial-gradient(1200px 600px at 50% -10%,#eaecff 0%,#f3f4fb 55%)}
  .box{max-width:520px;text-align:center;background:#fff;border:1px solid #e9eaf3;
    border-radius:18px;padding:2.6rem 2.2rem;box-shadow:0 8px 24px rgba(16,24,40,.08)}
  .icon{width:60px;height:60px;margin:0 auto 1.2rem;border-radius:16px;display:grid;place-items:center;
    color:#fff;background:linear-gradient(135deg,#2b8bff 0%,#37c6f0 100%);
    box-shadow:0 10px 24px rgba(43,139,255,.4)}
  .icon svg{width:30px;height:30px}
  h1{font-size:1.5rem;letter-spacing:-.02em;margin:0 0 .6rem}
  p{margin:0;color:#71768a}
</style>
</head>
<body>
  <div class="box">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
      stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
    <h1>Site en maintenance</h1>
    <p>{$site} est momentanément indisponible pour cause de maintenance. Merci de revenir dans un instant.</p>
  </div>
</body>
</html>
HTML;
    }

    /** @return array<string, mixed> */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->path), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function write(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->path, $json . "\n", LOCK_EX);
    }
}
