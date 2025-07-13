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

function getLatestRound($pods) {
    $rounds = $pods->pod->rounds->round;
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

function generateHTML($tournamentName, $matches, $playerMap): string {
    $rows = '';
    foreach ($matches as $match) {
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

    return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
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
            padding: 20px;
            text-align: center;
        }
        h1 { font-size: 1.8em; margin: 0; }
        main { padding: 20px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            font-size: 1em;
        }
        th { background: #eeeeee; }
        tr:nth-child(even) { background: #fafafa; }

        @media (max-width: 850px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr {
                margin-bottom: 15px;
                background: white;
                padding: 10px;
                border: 1px solid #ddd;
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
<header><h1>{$tournamentName}</h1></header>
<main>
    <table>
        <thead>
            <tr><th>Stół</th><th>Gracz 1</th><th>Gracz 2</th></tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
</main>
</body>
</html>
HTML;
}

function generateEmptyHTML(): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
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
            padding: 30px 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 { font-size: 1.6em; }
    </style>
</head>
<body>
<div class="message">
    <h1>Oczekiwanie na dane turniejowe...</h1>
    <p>Plik XML jest pusty lub jeszcze nie został wygenerowany.</p>
</div>
</body>
</html>
HTML;
}

// Parsuj i renderuj
$tournamentName = (string)$xml->data->name;
$players = $xml->players->player;
$playerMap = getPlayerMap($players);
$latestRound = getLatestRound($xml->pods);
$matches = [];

if ($latestRound && $latestRound->matches && isset($latestRound->matches->match)) {
    foreach ($latestRound->matches->match as $m) {
        $matches[] = $m;
    }
}

echo generateHTML($tournamentName, $matches, $playerMap);

