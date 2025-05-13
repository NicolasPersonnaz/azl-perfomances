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

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erreur MySQL : " . $conn->connect_error);
}

// Récupérer les utilisateurs avec mot de passe non haché
$sql = "SELECT id, password FROM users";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id = $row["id"];
    $password = $row["password"];

    // Si le mot de passe n'est pas déjà haché, on le hash
    if (!password_get_info($password)["algo"]) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $hashedPassword, $id);
        $stmt->execute();
    }
}

echo "Tous les mots de passe non hachés ont été sécurisés.";
$conn->close();
?>
