<?php
$db = new PDO('sqlite:tournament.sqlite');

$res = $db->query("
    SELECT p.player_id, p.firstname, p.lastname, p.birthyear,
           MAX(pa.month) AS last_month
    FROM players p
    LEFT JOIN participation pa ON p.player_id = pa.player_id
    GROUP BY p.player_id
    ORDER BY p.lastname
");

echo '<h1>Lista graczy</h1><table border="1" cellspacing="0" cellpadding="5">';
echo '<tr><th>ID</th><th>Imię</th><th>Nazwisko</th><th>Rok urodzenia</th><th>Ostatni miesiąc grania</th></tr>';

foreach ($res as $row) {
    $lastMonth = $row['last_month'] ?? '-';
    echo "<tr>
        <td>{$row['player_id']}</td>
        <td>{$row['firstname']}</td>
        <td>{$row['lastname']}</td>
        <td>{$row['birthyear']}</td>
        <td>$lastMonth</td>
    </tr>";
}

echo '</table>';
?>
