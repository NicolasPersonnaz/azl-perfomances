<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

$user_email = $_SESSION["user"]["email"]; // Récupérer l'email de l'utilisateur connecté

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST["nom"] ?? "";
    $prenom = $_POST["prenom"] ?? "";
    $email = $_POST["email"] ?? "";
    $numero_telephone = $_POST["numero_telephone"] ?? "";
    $adresse = $_POST["adresse"] ?? "";
    $voiture = $_POST["voiture"] ?? "";
    $code_postal = $_POST["code_postal"] ?? "";

    if (!$nom || !$prenom || !$email || !$numero_telephone || !$adresse || !$voiture || !$code_postal) {
        die("Tous les champs sont requis.");
    }

    // Mettre à jour les informations de l'utilisateur
    $sql = "UPDATE users SET nom = ?, prenom = ?, email = ?, numero_telephone = ?, adresse = ?, voiture = ?, code_postal = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param("ssssssss", $nom, $prenom, $email, $numero_telephone, $adresse, $voiture, $code_postal, $user_email);

    if ($stmt->execute()) {
        // Mettre à jour les informations de la session
        $_SESSION["user"] = [
            "email" => $email,
            "nom" => $nom,
            "prenom" => $prenom
        ];
        header("Location: tableau-de-bord.php");
    } else {
        die("Erreur lors de la mise à jour des informations : " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    die("Méthode non autorisée.");
}
?>
