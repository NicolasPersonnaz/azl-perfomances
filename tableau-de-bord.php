<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user"])) {
    header("Location: login.html"); // Redirige vers la page de connexion si non connecté
    exit();
}

$user = $_SESSION["user"];

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

// Assurez-vous d'avoir les mois en français
$conn->query("SET lc_time_names = 'fr_FR'");

$email = $user["email"];

// Sélectionner uniquement les 3 rendez-vous à venir
$stmt_rdv = $conn->prepare("
    SELECT id,
           DATE_FORMAT(date, '%d %M %Y') AS date_formatee,
           TIME_FORMAT(heure, '%H:%i') AS heure_formatee,
           service
    FROM rendezvous
    WHERE email = ? AND date >= CURDATE()
    ORDER BY date ASC, heure ASC
    LIMIT 3
");
$stmt_rdv->bind_param("s", $email);
$stmt_rdv->execute();
$rendezvous = $stmt_rdv->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_rdv->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Espace Client</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Font: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    /* Dégradé de fond moderne */
    .gradient-bg {
      background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    }
    /* Header avec dégradé et transparence */
    .modern-header {
      background: linear-gradient(135deg, #000000 0%, #111111 100%);
    }
  </style>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="logo_rond.png">
  
</head>
<body class="gradient-bg text-white min-h-screen flex flex-col">

  <!-- Header (fixe et moderne) avec titre centré -->
  <header class="relative w-full px-4 py-4 fixed top-0 left-0 right-0 z-20 modern-header shadow-lg flex items-center">
  <!-- Logo à gauche -->
  <a href="index.php" class="z-20">
    <img src="logo.jpg" alt="ALZ Performances" class="h-10">
  </a>
  
  <!-- Titre centré absolument par rapport au header -->
  <h1 
    class="absolute left-1/2 transform -translate-x-1/2
           text-xl sm:text-2xl md:text-3xl font-bold tracking-wider text-center"
  >
    ALZ PERFORMANCES
  </h1>
  
  <!-- Bouton burger à droite -->
  <button 
    class="text-white text-3xl focus:outline-none z-20 ml-auto" 
    onclick="toggleMenu()"
  >
    ☰
  </button>
</header>
  
  <!-- Contenu principal -->
  <!-- On met un padding-top plus important (pt-20) pour que le header fixe ne masque pas le contenu -->
  <div class="pt-20 flex-1 px-4 sm:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Zone de bienvenue -->
      <div class="text-center mb-8">
        <h2 class="text-3xl sm:text-4xl font-bold">
          Bienvenue, <?php echo htmlspecialchars($user["prenom"] . " " . $user["nom"]); ?> !
        </h2>
        <p class="mt-4 text-lg text-gray-400">
          Votre email : <?php echo htmlspecialchars($user["email"]); ?>
        </p>
        <p class="mt-2 text-gray-400">
          Gérez vos commandes, factures et paramètres ici.
        </p>
      </div>
      
      <!-- Tableau de bord moderne -->
      <div class="space-y-8">
        <!-- Section Voiture -->
        <a href="voiture.php" class="block transform hover:scale-105 transition duration-300">
          <div class="relative bg-gray-800 rounded-xl overflow-hidden shadow-2xl">
            <!-- Remplacez le chemin par votre image réelle -->
            <img src="chemin/vers/photo_voiture.jpg" alt="Photo de votre voiture" class="w-full h-64 object-cover opacity-90">
            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
              <h3 class="text-2xl sm:text-3xl font-bold tracking-wide">Modèle de Voiture</h3>
            </div>
          </div>
        </a>
        
        <!-- Deux cartes (Rendez-vous & Historique) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Carte Rendez-vous -->
          <a href="planning.php" class="block transform hover:scale-105 transition duration-300">
            <div class="bg-gray-800 rounded-xl p-6 shadow-2xl hover:bg-gray-700">
              <h3 class="text-xl sm:text-2xl font-bold mb-4">Mes Rendez-vous</h3>
              <?php if (!empty($rendezvous)): ?>
                <div class="overflow-hidden rounded-lg shadow-lg">
                  <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-900">
                      <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Heure</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Service</th>
                      </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                      <?php foreach ($rendezvous as $rdv): ?>
                        <tr class="hover:bg-gray-700">
                          <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <?php echo htmlspecialchars($rdv["date_formatee"]); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <?php echo htmlspecialchars($rdv["heure_formatee"]); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <?php echo htmlspecialchars($rdv["service"]); ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-gray-400">Aucun rendez-vous trouvé.</p>
              <?php endif; ?>
            </div>
          </a>
          
          <!-- Carte Historique & Factures -->
          <a href="historique.php" class="block transform hover:scale-105 transition duration-300">
            <div class="bg-gray-800 rounded-xl p-6 shadow-2xl hover:bg-gray-700">
              <h3 class="text-xl sm:text-2xl font-bold mb-4">Historique & Factures</h3>
              <p class="text-gray-400">Consultez votre historique et vos anciennes factures.</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Sidebar moderne avec bouton Déconnexion positionné en bas -->
  <!-- On met z-30 pour être sûr que la sidebar passe au-dessus du header (z-20) -->
  <aside id="sidebar" class="fixed right-0 top-0 h-full w-64 bg-gray-900 shadow-lg p-6 transform translate-x-full transition-transform duration-300 z-30 flex flex-col justify-between">
    <div>
      <!-- Bouton de fermeture dans la sidebar -->
      <button class="absolute top-4 left-4 text-white text-2xl focus:outline-none" onclick="toggleMenu()">✖</button>
      <h2 class="text-2xl font-bold mb-8 text-center">Espace Client</h2>
      <ul class="space-y-6">
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='tableau-de-bord.php'">Tableau de bord</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='historique.php'">Historique</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='planning.php'">Prendre Rendez-vous</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='my-profile.php'">Mon compte</li>
      </ul>
    </div>
    <div>
      <button class="w-full text-red-500 hover:text-white" onclick="window.location.href='logout.php'">
        Déconnexion
      </button>
    </div>
  </aside>
  
  <script>
    function toggleMenu() {
      const menu = document.getElementById("sidebar");
      menu.classList.toggle("translate-x-full");
    }
  </script>
</body>
</html>
