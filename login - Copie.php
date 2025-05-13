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
    die(json_encode(["success" => false, "message" => "Erreur MySQL : " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!$email || !$password) {
        die(json_encode(["success" => false, "message" => "Email ou mot de passe manquant."]));
    }

    // Récupérer l'utilisateur depuis la base
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(["success" => false, "message" => "Erreur SQL : " . $conn->error]));
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $userDb = $result->fetch_assoc(); // $userDb contiendra toutes les colonnes de la table users

        // Vérifier le mot de passe haché
        if (password_verify($password, $userDb["password"])) {
            // Stocker TOUTES les infos nécessaires dans la session
            // On utilise l'opérateur null coalescing (??) pour éviter d'avoir des valeurs NULL
            $_SESSION["user"] = [
                "id"               => $userDb["id"] ?? 0,
                "email"            => $userDb["email"] ?? "",
                "nom"              => $userDb["nom"] ?? "",
                "prenom"           => $userDb["prenom"] ?? "",
                "numero_telephone" => $userDb["numero_telephone"] ?? ""
            ];

            // Vérification de l'email spécial (admin)
            if ($email === "admin@alzperformances.fr") {
                $redirectPage = "admin.php";
            } else {
                $redirectPage = "tableau-de-bord.php";
            }

            // Gérer la réponse en AJAX ou via redirection
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode([
                    "success" => true, 
                    "message" => "Connexion réussie.", 
                    "redirect" => $redirectPage
                ]);
            } else {
                header("Location: " . $redirectPage);
            }
            exit();
        } else {
            // Mot de passe incorrect
            echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
            exit();
        }
    } else {
        // Aucune ligne trouvée => email inexistant
        echo json_encode(["success" => false, "message" => "Email non trouvé."]);
        exit();
    }
} else {
    // Méthode non POST
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
    exit();
}

$conn->close();
?>
