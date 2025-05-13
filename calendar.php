<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Charger les variables d'environnement depuis le fichier .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Utiliser les variables d'environnement
$host = $_ENV["DB_HOST"] ;
$user = $_ENV["DB_USER"] ;
$pass = $_ENV["DB_PASSWORD"] ;
$dbname = $_ENV["DB_NAME"] ;

$conn = new mysqli($host, $user_db, $pass_db, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

function escapeString($string) {
    // Échapper virgules et points‑virgules
    $string = preg_replace('/([\,;])/', '\\\$1', $string);
    // Convertir vrai retour à la ligne en séquence ICS \n
    return preg_replace("/\r?\n/", "\\\\n", $string);
}

$ics  = "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//alzperformances.fr//Calendrier RDV//FR\r\n";
$ics .= "CALSCALE:GREGORIAN\r\n";
$ics .= "METHOD:PUBLISH\r\n";

$sql = "SELECT id, date, heure, nom, prenom, numero_telephone FROM rendezvous ORDER BY date ASC";
$result = $conn->query($sql);
if (!$result) {
    die("Erreur lors de la requête : " . $conn->error);
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datetimeStr = $row['date'] . ' ' . $row['heure'];
        $timestamp   = strtotime($datetimeStr);
        if ($timestamp === false) continue;

        $start = date('Ymd\THis', $timestamp);
        $end   = date('Ymd\THis', strtotime('+1 hour', $timestamp));
        $uid   = uniqid() . "@alzperformances.fr";

        $summary     = "Réservation";
        $description = "Réservation effectuée par {$row['nom']} {$row['prenom']}\nJoignable au : {$row['numero_telephone']}";

        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:{$start}\r\n";
        $ics .= "DTEND:{$end}\r\n";
        $ics .= "SUMMARY:" . escapeString($summary) . "\r\n";
        $ics .= "DESCRIPTION:" . escapeString($description) . "\r\n";
        $ics .= "X-APPLE-CALENDAR-COLOR:#ffcccc\r\n";
        $ics .= "END:VEVENT\r\n";
    }
} else {
    $uid = uniqid() . "@alzperformances.fr";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:{$uid}\r\n";
    $ics .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
    $ics .= "DTSTART:" . date('Ymd\THis') . "\r\n";
    $ics .= "DTEND:" . date('Ymd\THis', strtotime('+1 hour')) . "\r\n";
    $ics .= "SUMMARY:" . escapeString("Aucune réservation") . "\r\n";
    $ics .= "DESCRIPTION:" . escapeString("Aucune réservation n'a été effectuée.") . "\r\n";
    $ics .= "X-APPLE-CALENDAR-COLOR:#ffcccc\r\n";
    $ics .= "END:VEVENT\r\n";
}

$ics .= "END:VCALENDAR\r\n";
echo $ics;
$conn->close();
?>
