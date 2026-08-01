<?php
/**
 * kig.php — midlertidig testvisning af tælleren
 *
 * Ikke det færdige dashboard. Formålet er kun at kunne se om pingene
 * kommer frem mens vi tester, og at kunne nulstille efter egne test.
 */

declare(strict_types=1);
date_default_timezone_set('Europe/Copenhagen');

const PIN        = '1234';                       // <<< RET til din egen kode
const DATA_DIR   = __DIR__ . '/data';
const STATS_FILE = DATA_DIR . '/stats.json';
const LOG_FILE   = DATA_DIR . '/log.jsonl';

session_start();

$fejl = '';

if (isset($_GET['luk'])) {
    session_destroy();
    header('Location: kig.php');
    exit;
}

if (!empty($_POST['pin'])) {
    if (hash_equals(PIN, (string)$_POST['pin'])) {
        $_SESSION['ok'] = true;
    } else {
        $fejl = 'Forkert kode.';
    }
}

$adgang = !empty($_SESSION['ok']);

if ($adgang && isset($_POST['nulstil'])) {
    @unlink(STATS_FILE);
    @unlink(LOG_FILE);
    header('Location: kig.php');
    exit;
}

$stats = [];
if ($adgang && is_readable(STATS_FILE)) {
    $stats = json_decode((string)file_get_contents(STATS_FILE), true) ?: [];
}
$filer = $stats['filer'] ?? [];
if (!is_array($filer)) { $filer = []; }

// Mest afspillet øverst, derefter flest åbninger
uasort($filer, static function ($a, $b) {
    return ((int)($b['play'] ?? 0) <=> (int)($a['play'] ?? 0))
        ?: ((int)($b['open'] ?? 0) <=> (int)($a['open'] ?? 0));
});

// Sidste hændelser
$seneste = [];
if ($adgang && is_readable(LOG_FILE)) {
    $alle = @file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach (array_slice($alle, -25) as $l) {
        $d = json_decode($l, true);
        if (is_array($d)) { $seneste[] = $d; }
    }
    $seneste = array_reverse($seneste);
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tæller — test</title>
<style>
  :root {
    --blaek:  #1c1f24;
    --daemp:  #6b7280;
    --linje:  #e3e5e9;
    --papir:  #fbfbf9;
    --maerke: #2f5d50;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; padding: 2rem 1.25rem 4rem;
    background: var(--papir); color: var(--blaek);
    font: 16px/1.55 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
  }
  main { max-width: 680px; margin: 0 auto; }
  h1 {
    font-size: 1.15rem; font-weight: 600; letter-spacing: .01em;
    margin: 0 0 .25rem;
  }
  .under { color: var(--daemp); font-size: .875rem; margin: 0 0 2rem; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 2.5rem; }
  th, td {
    text-align: left; padding: .6rem .5rem;
    border-bottom: 1px solid var(--linje); vertical-align: top;
  }
  th {
    font-size: .72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .07em; color: var(--daemp); border-bottom-width: 2px;
  }
  td.tal, th.tal { text-align: right; width: 4.5rem;
    font-variant-numeric: tabular-nums; font-feature-settings: "tnum"; }
  td.tal { font-size: 1.05rem; }
  .id { display: block; font-size: .74rem; color: var(--daemp);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
  tr.top td { background: rgba(47,93,80,.05); }
  tr.top .titel::before {
    content: "●"; color: var(--maerke); font-size: .6rem;
    vertical-align: .25em; margin-right: .45rem;
  }
  .log { font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
         font-size: .78rem; color: var(--daemp); }
  .log div { padding: .18rem 0; }
  .log b { color: var(--blaek); font-weight: 600; }
  .tom { color: var(--daemp); padding: 2rem 0; }
  form.pin { max-width: 260px; margin: 15vh auto 0; }
  input[type=password] {
    width: 100%; padding: .6rem .7rem; font-size: 1rem;
    border: 1px solid var(--linje); border-radius: 6px; background: #fff;
  }
  button {
    margin-top: .6rem; padding: .55rem 1rem; font-size: .9rem;
    background: var(--maerke); color: #fff; border: 0;
    border-radius: 6px; cursor: pointer;
  }
  button.let { background: none; color: var(--daemp);
               border: 1px solid var(--linje); }
  .fejl { color: #b3261e; font-size: .85rem; margin-top: .5rem; }
  .bund { display: flex; gap: .6rem; align-items: center;
          border-top: 1px solid var(--linje); padding-top: 1.25rem; }
  a { color: var(--maerke); font-size: .85rem; }
</style>
</head>
<body>
<main>

<?php if (!$adgang): ?>

  <form class="pin" method="post">
    <label for="pin" style="font-size:.85rem;color:var(--daemp)">Kode</label>
    <input type="password" name="pin" id="pin" autofocus>
    <button type="submit">Vis tal</button>
    <?php if (!empty($fejl)): ?><p class="fejl"><?= h($fejl) ?></p><?php endif; ?>
  </form>

<?php else: ?>

  <h1>Tæller — testvisning</h1>
  <p class="under">
    <?= count($filer) ?> fil<?= count($filer) === 1 ? '' : 'er' ?> registreret.
    <?php if (!empty($stats['opdateret'])): ?>
      Sidste ping <?= h(date('j/n H:i', strtotime($stats['opdateret']))) ?>.
    <?php endif; ?>
  </p>

  <?php if (!$filer): ?>
    <p class="tom">Ingen ping endnu. Åbn en fil med snippetten i og hent siden igen.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Fil</th>
          <th class="tal">Åbnet</th>
          <th class="tal">Afspil</th>
          <th class="tal">Billed</th>
        </tr>
      </thead>
      <tbody>
      <?php $n = 0; foreach ($filer as $id => $f): $n++; ?>
        <tr class="<?= $n <= 5 ? 'top' : '' ?>">
          <td>
            <span class="titel"><?= h($f['titel'] ?? $id) ?></span>
            <span class="id"><?= h((string)$id) ?></span>
          </td>
          <td class="tal"><?= (int)($f['open'] ?? 0) ?></td>
          <td class="tal"><?= (int)($f['play'] ?? 0) ?></td>
          <td class="tal"><?= (int)($f['image'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($seneste): ?>
    <h1>Seneste hændelser</h1>
    <p class="under">De 25 nyeste linjer fra loggen.</p>
    <div class="log">
      <?php foreach ($seneste as $s): ?>
        <div>
          <?= h(date('j/n H:i:s', strtotime($s['ts']))) ?>
          &nbsp; <b><?= h($s['e']) ?></b>
          &nbsp; <?= h($s['id']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form class="bund" method="post"
        onsubmit="return confirm('Slet alle tal og hele loggen?');">
    <button class="let" name="nulstil" value="1" type="submit">Nulstil alt</button>
    <a href="?luk=1">Log ud</a>
  </form>

<?php endif; ?>

</main>
</body>
</html>
