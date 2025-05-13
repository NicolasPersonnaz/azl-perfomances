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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifiez si les champs email et password sont définis
    if (isset($_POST["email"]) && isset($_POST["password"])) {
        $email = $_POST["email"];
        $password = $_POST["password"];

        // Hacher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insérer le nouvel utilisateur dans la base de données
        $insertSql = "INSERT INTO users (email, password) VALUES (?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ss", $email, $hashedPassword);

        if ($stmt->execute()) {
            echo "L'utilisateur a été créé avec succès.";
        } else {
            echo "Erreur lors de la création de l'utilisateur : " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Les champs email et mot de passe sont requis.";
    }
} else {
    echo "Méthode de requête non valide.";
}

$conn->close();
?>
