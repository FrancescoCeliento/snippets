<?php
function generaNumeroCasuale($lunghezza) {
    // Inizializza una stringa vuota per il numero casuale
    $numeroCasuale = '';
    
    // Genera un numero casuale di lunghezza specificata
    for ($i = 0; $i < $lunghezza; $i++) {
        // Aggiungi una cifra casuale da 0 a 9
        $numeroCasuale .= rand(0, 9);
    }
    
    // Ritorna il numero casuale come stringa
    return $numeroCasuale;
}

// Esempio di utilizzo
$lunghezzaInput = 10;
$numeroCasuale = generaNumeroCasuale($lunghezzaInput);
echo "Numero casuale generato: " . $numeroCasuale;
?>
