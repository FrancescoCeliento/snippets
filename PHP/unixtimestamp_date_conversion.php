<?php
function unixTimestampToDate($timestamp, $timezone) {
    // Crea un oggetto DateTime con il timestamp e il fuso orario specificato
    $date = new DateTime("@$timestamp");
    $date->setTimezone(new DateTimeZone($timezone));
    return $date->format("Y-m-d H:i:s");
}

function dateToUnixTimestamp($dateString, $timezone) {
    // Crea un oggetto DateTime con la data e il fuso orario specificato
    $date = new DateTime($dateString, new DateTimeZone($timezone));
    return $date->getTimestamp();
}

// Esempio di utilizzo
$timestamp = 1616516880;
$timezone = 'Europe/Rome'; // Puoi usare anche i formati come '+2', '-1', ecc.

$data = unixTimestampToDate($timestamp, $timezone);
echo "Timestamp a data: " . $data . "\n";

$dateString = "2021-03-23 10:01:20";
$unixTimestamp = dateToUnixTimestamp($dateString, $timezone);
echo "Data a timestamp: " . $unixTimestamp . "\n";
?>
