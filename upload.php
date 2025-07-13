<?php
session_start();
$db = new PDO('sqlite:tournament.sqlite');
libxml_use_internal_errors(true);

// Czyszczenie widoku
if (isset($_POST['clear_view'])) {
    if (file_exists('uploads/tournament.xml')) unlink('uploads/tournament.xml');
    if (file_exists('output.html')) unlink('output.html');
    $_SESSION['message'] = "✅ Widok został wyczyszczony. Dane graczy pozostały w bazie.";
    header("Location: admin.php");
    exit;
}

// Wgrywanie XML
if (isset($_FILES['xmlfile']) && $_FILES['xmlfile']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $destination = "$uploadDir/tournament.xml";
    move_uploaded_file($_FILES['xmlfile']['tmp_name'], $destination);

    $xmlContent = file_get_contents($destination);
    $xml = simplexml_load_string($xmlContent);
    if (!$xml) {
        $_SESSION['message'] = "❌ Błąd: Nieprawidłowy plik XML.";
        header("Location: admin.php");
        exit;
    }

    $tournamentName = (string)$xml->data->name;
    $players = $xml->players->player;
    $month = date('Y-m');

    foreach ($players as $p) {
        $id = (string)$p['userid'];
        $fname = (string)$p->firstname;
        $lname = (string)$p->lastname;
        $birthdate = (string)$p->birthdate;
        $byear = null;
        if (preg_match('#\d{2}/\d{2}/(\d{4})#', $birthdate, $m)) {
            $byear = $m[1];
        }


        $stmt = $db->prepare("INSERT OR IGNORE INTO players (player_id, firstname, lastname, birthyear)
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $fname, $lname, $byear]);

        $stmt = $db->prepare("INSERT INTO participation (player_id, month)
                              SELECT ?, ? WHERE NOT EXISTS (
                                  SELECT 1 FROM participation WHERE player_id = ? AND month = ?
                              )");
        $stmt->execute([$id, $month, $id, $month]);
    }

    // Generowanie HTML
    $rounds = $xml->pods->pod->rounds->round;
    $latest = is_array($rounds) ? usort($rounds, fn($a, $b) => (int)$b['number'] - (int)$a['number']) ? $rounds[0] : null : $rounds;

    $html = '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><title>'.$tournamentName.'</title>';
    $html .= '<link rel="stylesheet" href="tournament.css"></head><body>';
    $html .= '<header><h1>'.$tournamentName.'</h1></header><main><table><thead><tr><th>Stół</th><th>Gracz 1</th><th>Gracz 2</th></tr></thead><tbody>';

    foreach ($latest->matches->match as $match) {
        $table = $match->tablenumber ?? '-';
        $p1 = $match->player1['userid'] ?? $match->player['userid'] ?? null;
        $p2 = $match->player2['userid'] ?? null;

        $stmt = $db->prepare("SELECT firstname, lastname FROM players WHERE player_id = ?");
        $stmt->execute([$p1]);
        $p1n = $stmt->fetch();
        $p1name = $p1n ? $p1n['firstname'].' '.$p1n['lastname'] : '???';

        $stmt->execute([$p2]);
        $p2n = $stmt->fetch();
        $p2name = $p2n ? $p2n['firstname'].' '.$p2n['lastname'] : '???';

        $html .= "<tr><td data-label='Stół'>{$table}</td><td data-label='Gracz 1'>{$p1name}</td><td data-label='Gracz 2'>{$p2name}</td></tr>";
    }

    $html .= '</tbody></table></main></body></html>';
    file_put_contents('output.html', $html);

    $_SESSION['message'] = "✅ Turniej „$tournamentName” został załadowany.";
    header("Location: admin.php");
    exit;
}
?>
