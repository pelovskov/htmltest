<?php
/**
 * p.php — tælle-endpoint til singlefile HTML-filer
 *
 * Modtager et ping som et helt almindeligt billed-kald og svarer altid
 * med en 1x1 transparent GIF. Ingen CORS, ingen cookies, ingen IP-log.
 *
 * Kald:  p.php?id=<fast-id>&e=<open|play|image|link>&t=<titel>&r=<tilfældigt>
 *
 * Gemmer to steder i data/:
 *   stats.json  — samlede tal pr. fil (dashboardet læser denne)
 *   log.jsonl   — én linje pr. hændelse, til senere tidsfiltrering
 */

declare(strict_types=1);
date_default_timezone_set('Europe/Copenhagen');
error_reporting(0);

// ---------------------------------------------------------------------
// Indstillinger
// ---------------------------------------------------------------------

const DATA_DIR   = __DIR__ . '/data';
const STATS_FILE = DATA_DIR . '/stats.json';
const LOG_FILE   = DATA_DIR . '/log.jsonl';

const MAX_TITEL  = 120;
const MAX_ID     = 64;

// Kun disse hændelsestyper accepteres. Alt andet ignoreres.
const HAENDELSER = ['open', 'play', 'image', 'link'];

// User-Agents der aldrig skal tælle med (Facebooks link-forhåndsvisning,
// søgemaskiner, overvågningstjenester osv.)
const BOT_MOENSTRE = [
    'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'facebot',
    'preview', 'fetcher', 'monitor', 'headless', 'python-requests',
    'curl/', 'wget', 'scrapy', 'lighthouse', 'pingdom', 'uptime',
    'whatsapp', 'telegrambot', 'discordbot', 'linkedinbot', 'twitterbot',
    'embedly', 'quora link preview', 'skypeuripreview', 'applebot',
];

// ---------------------------------------------------------------------
// 1. Svar først — klienten skal ikke vente på diskskrivning
// ---------------------------------------------------------------------

header('Content-Type: image/gif');
header('Content-Length: 43');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Luk forbindelsen hvis serveren kan (php-fpm). Ellers fortsætter vi bare.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

// ---------------------------------------------------------------------
// 2. Frasortering
// ---------------------------------------------------------------------

$ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
if ($ua === '') {
    exit; // Ingen User-Agent = ikke en rigtig browser
}
foreach (BOT_MOENSTRE as $m) {
    if (strpos($ua, $m) !== false) {
        exit;
    }
}

// ---------------------------------------------------------------------
// 3. Rens input
// ---------------------------------------------------------------------

$id = (string)($_GET['id'] ?? '');
if (!preg_match('/^[A-Za-z0-9._-]{1,' . MAX_ID . '}$/', $id)) {
    exit; // Ugyldigt eller manglende ID — vi gemmer ikke noget
}

$e = strtolower((string)($_GET['e'] ?? 'open'));
if (!in_array($e, HAENDELSER, true)) {
    $e = 'open';
}

$titel = (string)($_GET['t'] ?? '');

// Kasser titlen helt hvis den ikke er gyldig UTF-8 — ellers ville
// preg_replace med /u-flaget returnere null længere nede.
if ($titel !== '' && !preg_match('//u', $titel)) {
    $titel = '';
}

$titel = strip_tags($titel);
$titel = (string)preg_replace('/[\x00-\x1F\x7F]/u', ' ', $titel);
$titel = trim((string)preg_replace('/\s+/u', ' ', $titel));
if (function_exists('mb_substr')) {
    $titel = mb_substr($titel, 0, MAX_TITEL, 'UTF-8');
} else {
    $titel = substr($titel, 0, MAX_TITEL);
}

$naa = date('c');

// ---------------------------------------------------------------------
// 4. Sørg for at data-mappen findes og er lukket for direkte adgang
// ---------------------------------------------------------------------

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}
if (!is_dir(DATA_DIR)) {
    exit;
}

$htaccess = DATA_DIR . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents(
        $htaccess,
        "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n"
    );
}

// ---------------------------------------------------------------------
// 5. Log-linje (append, billig og sikker)
// ---------------------------------------------------------------------

$linje = json_encode(
    ['ts' => $naa, 'id' => $id, 'e' => $e],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
@file_put_contents(LOG_FILE, $linje . "\n", FILE_APPEND | LOCK_EX);

// ---------------------------------------------------------------------
// 6. Opdatér stats.json under lås (læs → ret → skriv)
// ---------------------------------------------------------------------

$fh = @fopen(STATS_FILE, 'c+');
if ($fh === false) {
    exit;
}

if (!flock($fh, LOCK_EX)) {
    fclose($fh);
    exit;
}

$raa   = stream_get_contents($fh);
$stats = json_decode($raa !== '' ? $raa : '{}', true);
if (!is_array($stats)) {
    $stats = [];
}
if (!isset($stats['filer']) || !is_array($stats['filer'])) {
    $stats['filer'] = [];
}

if (!isset($stats['filer'][$id])) {
    $stats['filer'][$id] = [
        'titel'  => $titel !== '' ? $titel : $id,
        'open'   => 0,
        'play'   => 0,
        'image'  => 0,
        'link'   => 0,
        'foerst' => $naa,
        'sidst'  => $naa,
    ];
}

$post = &$stats['filer'][$id];
$post[$e]    = (int)($post[$e] ?? 0) + 1;
$post['sidst'] = $naa;

// Titlen holdes opdateret, så en rettet overskrift slår igennem på dashboardet.
if ($titel !== '') {
    $post['titel'] = $titel;
}
unset($post);

$stats['opdateret'] = $naa;

$ud = json_encode(
    $stats,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($ud !== false) {
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $ud);
    fflush($fh);
}

flock($fh, LOCK_UN);
fclose($fh);
