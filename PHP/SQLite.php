<?php
// Configurazione del database
$dbFile = 'path/to/your/database.sqlite'; // Sostituisci con il percorso del tuo database SQLite

// Funzione per connettersi al database
function connectToDatabase($dbFile) {
    try {
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Errore di connessione: " . $e->getMessage();
        return null;
    }
}

// Funzione per eseguire una query di lettura
function readData($pdo, $query) {
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Errore nella query: " . $e->getMessage();
        return [];
    }
}

// Funzione per eseguire una query di aggiornamento
function updateData($pdo, $query) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->rowCount(); // Restituisce il numero di righe aggiornate
    } catch (PDOException $e) {
        echo "Errore nella query: " . $e->getMessage();
        return 0;
    }
}

// Esempio di utilizzo
$pdo = connectToDatabase($dbFile);

// Esegui una query di lettura
$readQuery = "SELECT * FROM your_table"; // Sostituisci con la tua query
$data = readData($pdo, $readQuery);
print_r($data);

// Esegui una query di aggiornamento
$updateQuery = "UPDATE your_table SET column_name = 'new_value' WHERE condition"; // Sostituisci con la tua query
$rowsUpdated = updateData($pdo, $updateQuery);
echo "Righe aggiornate: $rowsUpdated";

?>
