<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['id'])) {
    die("Aucun créneau sélectionné.");
}

$id_creaneau = $_GET['id'];

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
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM creaneaux WHERE id = $id_creaneau AND status = 'disponible'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Ce créneau n'est plus disponible.");
}

// Update the status to 'réservé' for this creaneau
$sql_update = "UPDATE creaneaux SET status = 'réservé', utilisateur_id = ".$_SESSION['user']['id']." WHERE id = $id_creaneau";
$conn->query($sql_update);

echo "Votre réservation a été confirmée !";
?>
