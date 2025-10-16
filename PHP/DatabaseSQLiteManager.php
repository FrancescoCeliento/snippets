<?php
/**
 * Una classe PHP per gestire un database SQLite utilizzando PDO.
 *
 */
class DatabaseSQLiteManager {
    /** @var string La stringa di connessione (DSN) per SQLite. */
    private $dsn;

    /** @var PDO|null La connessione attiva al database. */
    private $pdo;

    /**
     * Costruttore.
     *
     * @param string $dbPath Il percorso al file del database SQLite (es. 'database.db').
     * Se il file non esiste, verrà creato.
     */
    public function __construct(string $dbPath) {
        // Il DSN per SQLite è 'sqlite:percorso/al/file.db'
        $this->dsn = "sqlite:" . $dbPath;
    }

    // --- Metodi di Connessione e Transazione ---

    /**
     * Stabilisce e restituisce la connessione PDO.
     * Gestisce la creazione del file del database se non esiste.
     *
     * @return PDO La connessione attiva.
     * @throws PDOException Se la connessione fallisce.
     */
    private function connect(): PDO {
        // Se la connessione non è ancora stata stabilita o è stata chiusa (in PHP si controlla null)
        if ($this->pdo === null) {
            try {
                // PDO lancia un'eccezione in caso di errore
                $this->pdo = new PDO($this->dsn);

                // Abilita la modalità per lanciare eccezioni per gli errori (importante!)
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Disabilita il commit automatico, come nell'originale Java
                $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

                // Avvia una transazione per emulare l'uso iniziale di setAutoCommit(false)
                $this->pdo->beginTransaction();

            } catch (PDOException $e) {
                // Equivalente a e.printStackTrace() in Java
                echo "Errore di connessione al database: " . $e->getMessage() . "\n";
                // Rilancia l'eccezione o restituisce null, a seconda della gestione degli errori desiderata
                throw $e;
            }
        }
        return $this->pdo;
    }

    /**
     * Chiude la connessione al database.
     * La chiusura esplicita in PHP non è sempre strettamente necessaria (garbage collector),
     * ma è una buona pratica per rilasciare le risorse.
     */
    public function closeConnection(): void {
        // Impostare a null chiude la connessione PDO
        $this->pdo = null;
    }

    /**
     * Esegue il commit delle modifiche (commit transaction).
     */
    public function commitTransaction(): void {
        if ($this->pdo !== null) {
            try {
                $this->pdo->commit();
                // Riavvia la transazione per continuare a disabilitare l'autocommit
                $this->pdo->beginTransaction();
            } catch (PDOException $e) {
                echo "Errore durante il commit: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Esegue il rollback delle modifiche (rollback transaction).
     */
    public function rollbackTransaction(): void {
        if ($this->pdo !== null) {
            try {
                $this->pdo->rollBack();
                // Riavvia la transazione
                $this->pdo->beginTransaction();
            } catch (PDOException $e) {
                echo "Errore durante il rollback: " . $e->getMessage() . "\n";
            }
        }
    }

    // --- Metodi di Esecuzione Query ---

    /**
     * Metodo per eseguire una query di selezione con parametri.
     *
     * @param string $sql La query SQL.
     * @param array $params I parametri da associare (bind).
     * @return array|null Un array di righe risultanti (ogni riga è un array associativo) o null in caso di errore.
     */
    public function selectData(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->connect()->prepare($sql);

            // PDO gestisce l'associazione dei tipi in base al valore
            foreach ($params as $key => $value) {
                // PDO binding è 1-based, come in Java, ma usiamo l'indice del parametro nell'array (key + 1)
                $stmt->bindValue($key + 1, $value);
            }

            $stmt->execute();
            // In PHP, si restituisce un array completo di risultati, non un oggetto "cursore" come ResultSet
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Errore Select: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Metodo per scrivere (inserire, aggiornare o cancellare) dati nel database.
     *
     * @param string $query La query SQL (INSERT, UPDATE, DELETE).
     * @param array $params I parametri da associare (bind).
     * @return int Il numero di righe modificate (0 in caso di errore, o il numero di righe).
     */
    public function updateData(string $query, array $params = []): int {
        try {
            $stmt = $this->connect()->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }

            $stmt->execute();
            return $stmt->rowCount(); // Restituisce il numero di righe interessate
        } catch (PDOException $e) {
            echo "Errore Update/Delete: " . $e->getMessage() . "\n";
            return 0; // 0 righe modificate
        }
    }

    /**
     * Metodo per eseguire query DDL (CREATE, ALTER, DROP, ecc...) senza parametri.
     *
     * @param string $sql La query DDL SQL.
     */
    public function executeQuery(string $sql): void {
        try {
            // Per query DDL semplici, si può usare il metodo exec di PDO
            $this->connect()->exec($sql);
        } catch (PDOException $e) {
            echo "Errore DDL: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Metodo per eliminare una tabella.
     *
     * @param string $tableName Il nome della tabella da eliminare.
     */
    public function dropTable(string $tableName): void {
        $dropTableSQL = "DROP TABLE IF EXISTS " . $tableName;
        $this->executeQuery($dropTableSQL); // Riutilizza il metodo executeQuery
    }

    /**
     * Metodo per ottimizzare il database.
     */
    public function optimizeDatabase(): void {
        $this->executeQuery("VACUUM"); // Riutilizza il metodo executeQuery
    }

    // --- Metodi di utilità (Adattamento di tableExists/columnExists) ---

    /**
     * Metodo per controllare se esiste una tabella.
     *
     * @param string $tableName Il nome della tabella.
     * @return bool True se la tabella esiste, altrimenti false.
     */
    public function tableExists(string $tableName): bool {
        // Uso del metodo selectData, come il selectData in Java
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?";
        $result = $this->selectData($query, [$tableName]);

        // Se l'array di risultati non è vuoto, la tabella esiste
        return !empty($result);
    }

    /**
     * Metodo per controllare se esiste una colonna in una tabella.
     * Nota: in PDO/PHP, questo richiede la connessione per il PRAGMA.
     *
     * @param string $tableName Il nome della tabella.
     * @param string $columnName Il nome della colonna.
     * @return bool True se la colonna esiste, altrimenti false.
     */
    public function columnExists(string $tableName, string $columnName): bool {
        try {
            // PRAGMA table_info('tableName') è specifico di SQLite
            $query = "PRAGMA table_info(" . $tableName . ")";
            $stmt = $this->connect()->query($query); // Usiamo query() per una select semplice senza parametri

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Il nome della colonna in PRAGMA table_info è nel campo 'name'
                if (strcasecmp($row['name'], $columnName) === 0) {
                    return true;
                }
            }
        } catch (PDOException $e) {
            echo "Errore nella verifica della colonna: " . $e->getMessage() . "\n";
        }

        return false;
    }
}

?>
