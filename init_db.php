<?php
$dbFile = 'tournament.sqlite';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabela graczy
    $db->exec("
        CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_id TEXT UNIQUE NOT NULL,
            firstname TEXT NOT NULL,
            lastname TEXT NOT NULL,
            birthyear TEXT
        );
    ");

    // Relacja: gracz-miesiąc
    $db->exec("
        CREATE TABLE IF NOT EXISTS participation (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_id TEXT NOT NULL,
            month TEXT NOT NULL,
            UNIQUE(player_id, month),
            FOREIGN KEY(player_id) REFERENCES players(player_id)
        );
    ");

    // Tabela z hasłem
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            password_sha1 TEXT NOT NULL
        );
    ");

    echo "✅ Baza danych została przygotowana.\n";

} catch (PDOException $e) {
    echo "❌ Błąd bazy danych: " . $e->getMessage();
    exit(1);
}
