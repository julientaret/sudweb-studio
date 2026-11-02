<?php

declare(strict_types=1);

/**
 * Flow — installateur de la base de données.
 *
 * À placer à la RACINE du projet (flow/). Lit les identifiants de connexion dans
 * .env.local (DB_HOST, DB_NAME, DB_USER, DB_PASS), crée la base si elle n'existe
 * pas encore, puis applique toutes les migrations (Core + modules activés) via le
 * Migrator déjà utilisé par bin/flow. Permet ensuite de créer un premier compte
 * administrateur, et de se supprimer lui-même d'un clic.
 *
 * Utilisable des deux façons :
 *   - en ligne de commande :  php install.php [email] [mot-de-passe] [nom]
 *   - dans le navigateur    :  http://localhost/flow/install.php
 *
 * Idempotent : relancer ne recrée pas la base et n'applique que les migrations
 * en attente. SUPPRIMEZ ce fichier une fois l'installation terminée (le bouton
 * « Supprimer install.php » le fait pour vous côté navigateur).
 */

use App\Core\Auth;
use App\Core\Config;
use App\Core\Kernel;
use App\Core\Migrator;
use App\Modules\Users\UserRepository;

$rootPath = __DIR__;
$cmsPath  = $rootPath . '/flow-cms';
$selfPath = __FILE__;

$cli    = PHP_SAPI === 'cli';
$action = $cli ? null : ($_POST['action'] ?? null);

$steps      = [];   // [niveau => 'ok'|'info'|'error', message] — installation DB
$fatal      = null; // message d'erreur bloquant
$userResult = null; // [niveau, message] — résultat de la création de compte
$userCount  = null; // nombre de comptes existants (après migrations)

/**
 * Empile une étape d'installation pour le rendu final.
 */
$report = static function (string $level, string $message) use (&$steps): void {
    $steps[] = [$level, $message];
};

// ---------------------------------------------------------------------------
// Auto-suppression (navigateur) : on supprime le fichier et on rend une page
// de confirmation autonome, sans rien bootstrapper d'autre.
// ---------------------------------------------------------------------------
if ($action === 'self_destruct') {
    $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $deleted = @unlink($selfPath);
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Flow — Installation</title>
        <style>
            body { font: 15px/1.6 system-ui, sans-serif; max-width: 640px; margin: 3rem auto; padding: 0 1rem; }
            .box { padding: 1rem; border-radius: 6px; }
            .ok { background: #e7f6ec; color: #1c6b3a; }
            .error { background: #fdeaea; color: #a01818; }
            code { background: rgba(127,127,127,.18); padding: .1em .35em; border-radius: 4px; }
        </style>
    </head>
    <body>
        <?php if ($deleted): ?>
            <p class="box ok"><strong>install.php a été supprimé.</strong> L'installation est terminée.</p>
        <?php else: ?>
            <p class="box error">
                <strong>Suppression impossible.</strong> Supprimez le fichier
                <code><?= $esc($selfPath) ?></code> manuellement.
            </p>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------------------------------------
// Installation de la base : création + migrations. Idempotent, joué à chaque
// chargement (y compris avant une création de compte).
// ---------------------------------------------------------------------------
try {
    // 1. Autoloader + lecture de .env.local (Config lit la racine = parent de flow-cms).
    $autoload = $cmsPath . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException(
            "Autoloader introuvable ($autoload). Lancez d'abord « composer install » dans flow-cms."
        );
    }
    require $autoload;

    Config::boot($cmsPath);

    $host = (string) Config::env('DB_HOST', '127.0.0.1');
    $name = (string) Config::env('DB_NAME', '');
    $user = (string) Config::env('DB_USER', 'root');
    $pass = (string) Config::env('DB_PASS', '');

    if ($name === '') {
        throw new RuntimeException('DB_NAME est vide dans .env.local — renseignez le nom de la base à créer.');
    }

    $report('info', "Cible : base « {$name} » sur {$host} (utilisateur {$user}).");

    // 2. Connexion au serveur SANS nom de base, pour pouvoir la créer si besoin.
    $serverDsn = sprintf('mysql:host=%s;charset=utf8mb4', $host);
    try {
        $server = new PDO($serverDsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Connexion au serveur MySQL impossible : ' . $e->getMessage(), 0, $e);
    }

    // 3. Création de la base si elle n'existe pas (utf8mb4 / collation unicode).
    //    Le nom vient de la config (pas d'une entrée utilisateur) : on le protège
    //    tout de même par des backticks échappés, MySQL n'acceptant pas de bind ici.
    $quotedName = '`' . str_replace('`', '``', $name) . '`';

    // SHOW DATABASES compare en LIKE : _ et % y sont des jokers. Sans échappement,
    // « client_site » dirait « déjà présente » en voyant « clientXsite ». Les noms
    // des hébergements mutualisés (préfixe + underscore) sont justement de cette
    // forme.
    $existed = (bool) $server
        ->query('SHOW DATABASES LIKE ' . $server->quote(addcslashes($name, '%_')))
        ->fetchColumn();

    if (!$existed) {
        try {
            $server->exec(
                "CREATE DATABASE IF NOT EXISTS {$quotedName} "
                . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        } catch (PDOException $e) {
            /*
             * Création refusée (typiquement 1044 sur un hébergement mutualisé, où
             * seul le panneau de l'hébergeur crée les bases). La base existe
             * peut-être déjà sans être visible : SHOW DATABASES ne montre que
             * celles sur lesquelles le compte a des droits. On essaie donc de s'y
             * connecter directement avant de conclure.
             */
            try {
                new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $existed = true;
            } catch (PDOException $unreachable) {
                throw new RuntimeException(
                    "La base « {$name} » est injoignable et le compte « {$user} » n'a pas le droit "
                    . "de la créer. Sur un hébergement mutualisé, créez-la depuis le panneau de "
                    . "l'hébergeur, puis rattachez-lui cet utilisateur avec tous les privilèges — "
                    . "le nom et l'utilisateur doivent correspondre exactement à DB_NAME et DB_USER "
                    . "de .env.local. Détail : " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    $report(
        $existed ? 'info' : 'ok',
        $existed ? "Base « {$name} » déjà présente." : "Base « {$name} » créée."
    );

    // 4. Boot du noyau (qui réutilise la même config) puis application des migrations.
    //    bootAll() enregistre routes ET migrations des modules activés ; le Migrator
    //    se connecte ensuite via Database::pdo() à la base désormais existante.
    $kernel = Kernel::boot($cmsPath);
    $kernel->registry()->bootAll($kernel->router());

    $migrator = new Migrator($cmsPath);
    $applied  = $migrator->run($kernel->registry());

    if ($applied === []) {
        $report('info', 'Aucune migration en attente — le schéma est à jour.');
    } else {
        $report('ok', count($applied) . ' migration(s) appliquée(s) :');
        foreach ($applied as $file) {
            $report('info', '  • ' . $file);
        }
    }

    $report('ok', 'Base de données prête.');

    // 5. Création d'un compte administrateur.
    //    - CLI : si email + mot de passe passés en arguments ;
    //    - Web : via le formulaire (POST action=create_user).
    $users = new UserRepository();
    $userCount = $users->count();

    $email    = null;
    $password = null;
    $fullName = '';

    if ($cli) {
        $argv     = $_SERVER['argv'] ?? [];
        $email    = $argv[1] ?? null;
        $password = $argv[2] ?? null;
        $fullName = $argv[3] ?? '';
    } elseif ($action === 'create_user') {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $fullName = trim((string) ($_POST['name'] ?? ''));
    }

    // Sécurité : par le web, on n'autorise QUE la création du tout premier compte.
    // Dès qu'un compte existe, le formulaire est verrouillé — sans quoi un
    // install.php oublié sur un serveur laisserait n'importe qui se créer un
    // administrateur (prise de contrôle totale). La CLI reste ouverte : y accéder
    // suppose déjà un shell sur le serveur (utilisez plutôt bin/flow ensuite).
    $createLocked = !$cli && $userCount > 0;

    if ($email !== null && ($email !== '' || $password !== '')) {
        if ($createLocked) {
            $userResult = ['error', 'Un compte existe déjà : la création de compte est désactivée. Supprimez install.php et gérez les comptes depuis l’admin.'];
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $userResult = ['error', 'Adresse e-mail invalide.'];
        } elseif (strlen($password) < 8) {
            $userResult = ['error', 'Le mot de passe doit faire au moins 8 caractères.'];
        } elseif ($users->findByEmail($email) !== null) {
            $userResult = ['error', "Un compte existe déjà avec l'e-mail « {$email} »."];
        } else {
            $hash = password_hash($password, Auth::hashAlgo());
            $id   = $users->create($email, $hash, $fullName, 'admin');
            $userCount++;
            $userResult = ['ok', "Compte administrateur #{$id} créé : {$email}."];
        }
    }
} catch (\Throwable $e) {
    $fatal = $e->getMessage();
}

// ---------------------------------------------------------------------------
// Rendu : texte brut en CLI, page HTML dans le navigateur.
// ---------------------------------------------------------------------------

if ($cli) {
    foreach ($steps as [$level, $message]) {
        $prefix = $level === 'error' ? '✗ ' : ($level === 'ok' ? '✓ ' : '  ');
        fwrite($level === 'error' ? STDERR : STDOUT, $prefix . $message . "\n");
    }
    if ($userResult !== null) {
        [$level, $message] = $userResult;
        fwrite($level === 'error' ? STDERR : STDOUT, ($level === 'error' ? '✗ ' : '✓ ') . $message . "\n");
    }
    if ($fatal !== null) {
        fwrite(STDERR, "✗ Erreur : {$fatal}\n");
        exit(1);
    }
    fwrite(STDOUT, "\nPensez à supprimer install.php.\n");
    exit($userResult !== null && $userResult[0] === 'error' ? 1 : 0);
}

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
http_response_code($fatal === null ? 200 : 500);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flow — Installation</title>
    <style>
        :root { color-scheme: light dark; }
        body { font: 15px/1.6 system-ui, sans-serif; max-width: 640px; margin: 3rem auto; padding: 0 1rem; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; }
        ul { list-style: none; padding: 0; }
        li { padding: .35rem .75rem; border-radius: 6px; margin: .25rem 0; }
        li.ok, .msg.ok { background: #e7f6ec; color: #1c6b3a; }
        li.info { background: #eef1f5; color: #333; }
        li.error, .msg.error { background: #fdeaea; color: #a01818; }
        .msg { padding: .6rem .75rem; border-radius: 6px; margin: .5rem 0; }
        .fatal { background: #fdeaea; color: #a01818; padding: 1rem; border-radius: 6px; }
        form { margin: 1rem 0; }
        label { display: block; margin: .6rem 0 .15rem; font-weight: 600; }
        input { width: 100%; padding: .5rem; border: 1px solid #bbb; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .55rem 1.1rem; border: 0; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        button.primary { background: #2563eb; color: #fff; }
        button.danger { background: #dc2626; color: #fff; }
        .danger-zone { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(127,127,127,.3); }
        small { color: #777; }
    </style>
</head>
<body>
    <h1>Flow — Installation</h1>

    <?php if ($steps !== []): ?>
        <ul>
            <?php foreach ($steps as [$level, $message]): ?>
                <li class="<?= $esc($level) ?>"><?= $esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($fatal !== null): ?>
        <p class="fatal"><strong>Échec :</strong> <?= $esc($fatal) ?></p>
    <?php else: ?>

        <h2>Compte administrateur</h2>
        <?php if ($userResult !== null): ?>
            <p class="msg <?= $esc($userResult[0]) ?>"><?= $esc($userResult[1]) ?></p>
        <?php endif; ?>
        <?php if ($createLocked): ?>
            <p class="msg error">
                <strong>Création désactivée.</strong> <?= (int) $userCount ?> compte(s) existe(nt) déjà.
                Par sécurité, install.php ne crée que le tout premier compte.
                <strong>Supprimez ce fichier ci-dessous</strong> et gérez les comptes depuis l’admin.
            </p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="create_user">
                <label for="name">Nom</label>
                <input id="name" name="name" type="text" autocomplete="name">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required autocomplete="username">
                <label for="password">Mot de passe <small>(8 caractères min.)</small></label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
                <button type="submit" class="primary">Créer l'administrateur</button>
            </form>
        <?php endif; ?>

        <div class="danger-zone">
            <h2>Terminer l'installation</h2>
            <p>Pour des raisons de sécurité, supprimez ce fichier une fois la base installée
               et votre compte créé.</p>
            <form method="post" onsubmit="return confirm('Supprimer définitivement install.php ?');">
                <input type="hidden" name="action" value="self_destruct">
                <button type="submit" class="danger">Supprimer install.php</button>
            </form>
        </div>

    <?php endif; ?>
</body>
</html>
