<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION["user"]; // Récupérer les infos de l'utilisateur connecté

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

// Vérifier si la table "commandes" existe et la créer si nécessaire
$checkTableQuery = "SHOW TABLES LIKE 'commandes'";
$result = $conn->query($checkTableQuery);

if ($result->num_rows == 0) {
    $createTableQuery = "
        CREATE TABLE commandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            produit VARCHAR(255) NOT NULL,
            prix DECIMAL(10,2) NOT NULL,
            date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
            statut VARCHAR(50) DEFAULT 'En attente'
        );
    ";
    $conn->query($createTableQuery);
}

// Récupérer l'historique des commandes de l'utilisateur
$email = $_SESSION["user"]["email"];
$sql = "SELECT id, produit, prix, date_commande, statut FROM commandes WHERE email = ? ORDER BY date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$commandes = [];
while ($row = $result->fetch_assoc()) {
    $commandes[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des commandes</title>
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
        </a>
        <h1 class="text-2xl font-bold text-white">ALZ PERFORMANCES</h1>
        <button class="text-white text-2xl" onclick="toggleMenu()">☰</button>
    </header>
    
    <!-- Contenu principal -->
    <div class="flex-1 p-6 flex flex-col items-center">
        <h2 class="text-3xl font-bold">Historique de vos commandes</h2>
        <p class="mt-2 text-gray-400">Consultez vos commandes passées.</p>

        <div class="w-full max-w-4xl mt-6">
            <table class="w-full border-collapse border border-gray-700">
                <thead>
                    <tr class="background-header text-white">
                        <th class="border border-gray-700 p-2">ID</th>
                        <th class="border border-gray-700 p-2">Produit</th>
                        <th class="border border-gray-700 p-2">Prix</th>
                        <th class="border border-gray-700 p-2">Date</th>
                        <th class="border border-gray-700 p-2">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($commandes) > 0): ?>
                        <?php foreach ($commandes as $commande): ?>
                            <tr class="text-center bg-gray-800">
                                <td class="border border-gray-700 p-2"><?php echo htmlspecialchars($commande["id"]); ?></td>
                                <td class="border border-gray-700 p-2"><?php echo htmlspecialchars($commande["produit"]); ?></td>
                                <td class="border border-gray-700 p-2"><?php echo htmlspecialchars($commande["prix"]); ?> €</td>
                                <td class="border border-gray-700 p-2"><?php echo htmlspecialchars($commande["date_commande"]); ?></td>
                                <td class="border border-gray-700 p-2"><?php echo htmlspecialchars($commande["statut"]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center p-4 text-gray-400">Aucune commande trouvée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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