<?php
session_start();

$dbFile = 'tournament.sqlite';

function initializeDatabase() {
    global $dbFile;
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

        // Relacja gracz - miesiąc
        $db->exec("
            CREATE TABLE IF NOT EXISTS participation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id TEXT NOT NULL,
                month TEXT NOT NULL,
                UNIQUE(player_id, month),
                FOREIGN KEY(player_id) REFERENCES players(player_id)
            );
        ");

        // Tabela admina
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

// Automatyczna instalacja bazy i tabel jeśli brak
if (!file_exists($dbFile)) {
    list($success, $error) = initializeDatabase();
    if ($success) {
        // Po instalacji odśwież stronę, baza gotowa
        header("Location: admin.php");
        exit;
    } else {
        echo "❌ Błąd podczas tworzenia bazy danych: $error";
        exit;
    }
} else {
    // Baza istnieje, ale sprawdź czy tabela admin jest obecna
    try {
        $db = new PDO("sqlite:$dbFile");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin'")->fetch();
        if (!$result) {
            list($success, $error) = initializeDatabase();
            if ($success) {
                header("Location: admin.php");
                exit;
            } else {
                echo "❌ Błąd podczas tworzenia tabel w bazie danych: $error";
                exit;
            }
        }
    } catch (PDOException $e) {
        echo "❌ Błąd połączenia z bazą danych: " . $e->getMessage();
        exit;
    }
}

// Po tym fragmencie masz już $db i bazę gotową

$hasPassword = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn() > 0;

// Obsługa logowania / ustawienia hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $pass = trim($_POST['password']);
    if (!$hasPassword) {
        $db->prepare("INSERT INTO admin (password_sha1) VALUES (?)")->execute([sha1($pass)]);
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $row = $db->query("SELECT password_sha1 FROM admin LIMIT 1")->fetch();
        if (sha1($pass) === $row['password_sha1']) {
            $_SESSION['logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "❌ Błędne hasło.";
        }
    }
}

// Formularz logowania jeśli nie jest zalogowany
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true):
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel logowania</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="panel">
        <h2><?= $hasPassword ? 'Zaloguj się' : 'Ustaw hasło administratora' ?></h2>
        <form method="POST">
            <input type="password" name="password" placeholder="Hasło" required>
            <button type="submit"><?= $hasPassword ? 'Zaloguj' : 'Ustaw hasło' ?></button>
        </form>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php exit; endif; ?>

<!-- Panel administratora -->
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel administratora</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="panel">
        <h1>Panel administratora</h1>

        <!-- Wgrywanie -->
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <label>Wgraj plik XML z turniejem:</label>
            <input type="file" name="xmlfile" accept=".tdf" required>
            <button type="submit">Wgraj i wygeneruj</button>
        </form>

        <!-- Czyszczenie -->
        <form method="POST" action="upload.php" onsubmit="return confirm('Na pewno wyczyścić widok?');">
            <input type="hidden" name="clear_view" value="1">
            <button type="submit" class="danger">🧹 Wyczyść widok</button>
        </form>

        <hr>
        <a href="view.php" class="link">📋 Lista graczy</a><br>
        <a href="index.php" class="link" target="_blank">🌐 Publiczny widok</a>

        <form method="POST" action="logout.php" style="margin-top: 20px;">
            <button type="submit">Wyloguj</button>
        </form>
    </div>
    <?php if (!empty($_SESSION['message'])): ?>
      <p class="success"><?= $_SESSION['message'] ?></p>
      <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

</body>
</html>
