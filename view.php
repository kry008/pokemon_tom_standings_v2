<?php
session_start();

$dbFile = 'tournament.sqlite';

function initializeDatabase() {
    global $dbFile;
    try {
        $db = new PDO("sqlite:$dbFile");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec("
            CREATE TABLE IF NOT EXISTS players (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id TEXT UNIQUE NOT NULL,
                firstname TEXT NOT NULL,
                lastname TEXT NOT NULL,
                birthyear TEXT
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS participation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id TEXT NOT NULL,
                month TEXT NOT NULL,
                UNIQUE(player_id, month),
                FOREIGN KEY(player_id) REFERENCES players(player_id)
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS admin (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                password_sha1 TEXT NOT NULL
            );
        ");

        return [true, null];
    } catch (PDOException $e) {
        return [false, $e->getMessage()];
    }
}

if (!file_exists($dbFile)) {
    list($success, $error) = initializeDatabase();
    if (!$success) {
        echo "❌ Błąd podczas tworzenia bazy danych: $error";
        exit;
    }
} else {
    try {
        $db = new PDO("sqlite:$dbFile");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin'")->fetch();
        if (!$result) {
            list($success, $error) = initializeDatabase();
            if (!$success) {
                echo "❌ Błąd podczas inicjalizacji tabel: $error";
                exit;
            }
        }
    } catch (PDOException $e) {
        echo "❌ Błąd połączenia z bazą danych: " . $e->getMessage();
        exit;
    }
}

// Wymagaj logowania
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

$res = $db->query("
    SELECT p.player_id, p.firstname, p.lastname, p.birthyear,
           MAX(pa.month) AS last_month
    FROM players p
    LEFT JOIN participation pa ON p.player_id = pa.player_id
    GROUP BY p.player_id
    ORDER BY p.lastname
");

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Lista graczy</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="panel">
    <h1>📋 Lista graczy</h1>
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię</th>
                <th>Nazwisko</th>
                <th>Rok urodzenia</th>
                <th>Ostatni miesiąc grania</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($res as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['player_id']) ?></td>
                <td><?= htmlspecialchars($row['firstname']) ?></td>
                <td><?= htmlspecialchars($row['lastname']) ?></td>
                <td><?= htmlspecialchars($row['birthyear']) ?></td>
                <td><?= htmlspecialchars($row['last_month'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="admin.php" class="link">⬅️ Powrót do panelu</a></p>
</div>
</body>
</html>
