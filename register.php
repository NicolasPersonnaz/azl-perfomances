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
    die("Erreur de connexion : " . $conn->connect_error);
}

// Vérifier si la requête est en POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST["nom"] ?? "";
    $prenom = $_POST["prenom"] ?? "";
    $voiture = $_POST["voiture"] ?? "";
    $adresse = $_POST["adresse"] ?? "";
    $code_postal = $_POST["code_postal"] ?? "";
    $numero_telephone = $_POST["numero_telephone"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!$email || !$password || !$nom || !$prenom || !$voiture || !$adresse || !$code_postal || !$numero_telephone) {
        die("Tous les champs sont requis.");
    }

    // Vérifier si l'utilisateur existe déjà
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Cet email est déjà utilisé.");
    }

    // Hachage du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insérer l'utilisateur
    $insertSql = "INSERT INTO users (nom, prenom, voiture, adresse, code_postal, numero_telephone, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssssssss", $nom, $prenom, $voiture, $adresse, $code_postal, $numero_telephone, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "Utilisateur créé avec succès. <a href='login.html'>Se connecter</a>";
    } else {
        echo "Erreur lors de l'inscription.";
    }
} else {
    echo "Méthode non autorisée.";
}

$conn->close();
?>
