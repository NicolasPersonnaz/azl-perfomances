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

// Récupérer les informations de l'utilisateur
$email = $user["email"];
$sql = "SELECT nom, prenom, email, telephone, adresse FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$profil = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Aquatico', sans-serif;
        }
        .background-page { background-color: #030303; } /* Couleur de fond principale */
        .background-header { background-color: #000000; } /* Couleur de fond du header */
        .background-sidebar { background-color: #252525; } /* Couleur de fond du menu */
    </style>
    <script>
        function toggleMenu() {
            const menu = document.getElementById("sidebar");
            menu.classList.toggle("translate-x-full");
        }
    </script>

    <!-- Favicon -->
        <link rel="icon" type="image/png" href="logo_rond.png">

</head>
<body class="background-page text-white h-screen flex flex-col">
    <!-- Header -->
    <header class="w-full flex items-center justify-between p-4 background-header shadow-md">
        <a href="index.php">
            <img src="logo.jpg" alt="ALZ Performances" class="h-10">
        </a> <!-- Logo en haut à gauche -->
        <h1 class="text-2xl font-bold text-white">ALZ PERFORMANCES</h1> <!-- Titre centré -->
        <button class="text-white text-2xl" onclick="toggleMenu()">☰</button> <!-- Bouton pour ouvrir le menu à droite -->
    </header>
    
    <!-- Contenu principal -->
    <div class="flex-1 p-6 flex flex-col items-center">
        <h2 class="text-3xl font-bold">Mon Profil</h2>
        <p class="mt-2 text-gray-400">Consultez et mettez à jour vos informations personnelles.</p>

        <div class="w-full max-w-lg mt-6 p-4 bg-gray-800 rounded-lg shadow">
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($profil["nom"]); ?></p>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($profil["prenom"]); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($profil["email"]); ?></p>
            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($profil["telephone"]); ?></p> <!-- Affichage du téléphone -->
            <p><strong>Adresse :</strong> <?php echo htmlspecialchars($profil["adresse"]); ?></p>

            <a href="edit-profile.php" class="mt-4 block text-center text-red-500 hover:underline">Modifier mon profil</a>
        </div>
    </div>
    
    <!-- Menu latéral à droite -->
    <div id="sidebar" class="fixed right-0 top-0 h-full w-64 background-sidebar shadow-lg p-4 transform translate-x-full transition-transform duration-300">
        <button class="absolute top-4 left-4 text-white" onclick="toggleMenu()">✖</button>
        <h2 class="text-xl font-bold mb-6 text-center">Espace Client</h2>
        <ul class="space-y-4">
            <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='tableau-de-bord.php'">Tableau de bord</li>
            <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='historique.php'">Historique</li>
            <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='planning.php'">Prendre Rendez-vous</li>
            <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='my-profile.php'">Mon compte</li>
            <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='logout.php'">Déconnexion</li>
        </ul>
    </div>
</body>
</html>