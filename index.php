<?php
$xmlFile = 'uploads/tournament.xml';

if (!file_exists($xmlFile) || filesize($xmlFile) < 10) {
    echo generateEmptyHTML();
    exit;
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlFile);

if (!$xml || !$xml->players || !$xml->pods) {
    echo generateEmptyHTML();
    exit;
}

function getPlayerMap($players): array {
    $map = [];
    foreach ($players as $p) {
        $id = (string)$p['userid'];
        $map[$id] = trim("{$p->firstname} {$p->lastname}");
    }
    return $map;
}

function getLatestRound($pod) {
    if (!isset($pod->rounds->round)) return null;
    $rounds = $pod->rounds->round;
    if (!is_array($rounds) && !$rounds instanceof Traversable) $rounds = [$rounds];
    $latest = null;
    $max = -1;
    foreach ($rounds as $r) {
        $nr = (int)$r['number'];
        if ($nr > $max) {
            $max = $nr;
            $latest = $r;
        }
    }
    return $latest;
}

function mapCategoryName($cat) {
    $map = [
        '1' => '1',
        '2' => '2',
        '8' => '3'
    ];
    return $map[$cat] ?? $cat;
}

function generateHTML($tournamentName, $pods, $playerMap): string {

    $url = 'https://turniej.kry008.top/';
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($url);

    $sections = '';

    foreach ($pods->pod as $pod) {
        $cat = (string)$pod['category'];
        $catName = mapCategoryName($cat);
        $latestRound = getLatestRound($pod);
        if (!$latestRound || !$latestRound->matches) continue;

        $rows = '';
        foreach ($latestRound->matches->match as $match) {
            $table = $match->tablenumber ?? "-";
            $p1id = $match->player1['userid'] ?? $match->player['userid'] ?? null;
            $p2id = $match->player2['userid'] ?? null;

            $p1 = $p1id ? ($playerMap[(string)$p1id] ?? "???") : "wolny los";
            $p2 = $p2id ? ($playerMap[(string)$p2id] ?? "???") : "";

            $rows .= "<tr>
                <td data-label='Stół'>{$table}</td>
                <td data-label='Gracz 1'>{$p1}</td>
                <td data-label='Gracz 2'>{$p2}</td>
            </tr>\n";
        }

        $sections .= "
        <section>
            <table>
                <thead><tr><th>Stół</th><th>Gracz 1</th><th>Gracz 2</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
        </section>\n";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15" />
    <title>{$tournamentName}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
            color: #333;
        }
        header {
            background: #004466;
            color: white;
            padding: 20px 30px;
            text-align: center;
            position: relative;
        }

        h1 {
            font-size: 2.2em;
            margin: 0;
        }

        .header-qr {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 9999;

            background: white;
            padding: 6px;
            border-radius: 10px;

            box-shadow: 0 4px 12px rgba(0,0,0,0.25);

            text-align: center;
        }

        .header-qr img {
            width: 110px;
            height: 110px;
            display: block;
            border-radius: 6px;
        }

        .header-qr .qr-label {
            font-size: 0.75em;
            margin-top: 4px;
            color: #004466;
            font-weight: bold;
        }
        main { padding: 20px; }
        section { margin-bottom: 40px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: left;
            font-size: 1.1em;
            font-weight: bold;
        }
        th { background: #eeeeee; }
        tr:nth-child(even) { background: #ddd; }
        h2 {
            text-align: center;
            font-size: 2em;
            margin-top: 30px;
            color: #004466;
        }
    @media (max-width: 950px) {

        .header-qr {
            display: none;
        }

        h1 {
            font-size: 1.8em;
        }

        table, thead, tbody, th, td, tr {
            display: block;
        }

        thead {
            display: none;
        }

        tr {
            margin-bottom: 15px;
            background: white;
            padding: 10px;
            border: 1px solid #000;
            border-radius: 8px;
        }

        td {
            padding: 8px 10px;
            text-align: right;
            position: relative;
        }

        td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            top: 8px;
            font-weight: bold;
            text-align: left;
        }
    }
    </style>
</head>
<body>
<header>
    <h1>{$tournamentName}</h1>

    <div class="header-qr">
        <img src="{$qr}" alt="QR">
        <div class="qr-label">Zeskanuj</div>
    </div>
</header>
<main>
    <h2 style="font-size:1.4em; color:#555;">https://turniej.kry008.top/</h2>
    {$sections}
</main>
</body>
</html>
HTML;
}

function generateEmptyHTML(): string {

    $url = 'https://turniej.kry008.top/';
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);

    return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15" />
    <title>Oczekiwanie na dane</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f2f2f2;
            color: #333;
            text-align: center;
            padding: 20px;
        }

        .message {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.12);
            max-width: 500px;
            width: 100%;
        }

        h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        p {
            font-size: 1.1em;
            color: #666;
        }

        .qr {
            margin-top: 30px;
        }

        .qr img {
            width: 300px;
            height: 300px;
            border: 8px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            border-radius: 10px;
        }

        .url {
            margin-top: 15px;
            font-size: 1.1em;
            font-weight: bold;
            color: #004466;
            word-break: break-word;
        }
    </style>
</head>
<body>

<div class="message">
      <img src="https://flamberg.com.pl/data/gfx/mask/pol/logo_1_big.png" />
    <h1>Oczekiwanie na dane turniejowe...</h1>

    <p>Plik XML jest pusty lub jeszcze nie został wygenerowany.</p>

    <div class="qr">
        <img src="{$qr}" alt="Kod QR">
    </div>

    <div class="url">
        {$url}
    </div>
</div>

</body>
</html>
HTML;
}

// Renderowanie
$tournamentName = (string)$xml->data->name;
$players = $xml->players->player;
$playerMap = getPlayerMap($players);

echo generateHTML($tournamentName, $xml->pods, $playerMap);