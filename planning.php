<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION["user"];
$email = $user["email"];

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

$conn->query("SET lc_time_names = 'fr_FR'");

// Vérifier et ajouter la colonne 'status' si nécessaire
$sql_check_column = "SHOW COLUMNS FROM disponibilites LIKE 'status'";
$result_check = $conn->query($sql_check_column);
if ($result_check->num_rows == 0) {
    $conn->query("ALTER TABLE disponibilites ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'disponible'");
}

// Traitement de la prise de RDV
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["disponibilite"], $_POST["service"])) {
    $dispo_id = $_POST["disponibilite"];
    $service = $_POST["service"];
    $nom = $user["nom"];
    $prenom = $user["prenom"];
    $numero_telephone = $user["numero_telephone"];

    $stmt_check = $conn->prepare("SELECT date, heure FROM disponibilites WHERE id = ? AND status = 'disponible'");
    $stmt_check->bind_param("i", $dispo_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($row = $result_check->fetch_assoc()) {
        $date = $row["date"];
        $heure = $row["heure"];

        $stmt_insert = $conn->prepare("
            INSERT INTO rendezvous (email, nom, prenom, numero_telephone, date, heure, service)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert->bind_param("sssssss", $email, $nom, $prenom, $numero_telephone, $date, $heure, $service);

        if ($stmt_insert->execute()) {
            $stmt_update = $conn->prepare("UPDATE disponibilites SET status = 'réservé' WHERE id = ?");
            $stmt_update->bind_param("i", $dispo_id);
            $stmt_update->execute();
            $stmt_update->close();
            $_SESSION["flash"] = "Votre rendez-vous a été pris avec succès.";
        } else {
            $_SESSION["flash"] = "Erreur lors de la prise du rendez-vous. Veuillez réessayer.";
        }
        $stmt_insert->close();
    } else {
        $_SESSION["flash"] = "Ce créneau n'est plus disponible.";
    }
    $stmt_check->close();
    header("Location: planning.php");
    exit();
}

// Traitement de l'annulation
if (isset($_GET['cancel_rdv_id'])) {
    $rdv_id = $_GET['cancel_rdv_id'];
    $stmt_rdv = $conn->prepare("SELECT date, heure FROM rendezvous WHERE id = ?");
    $stmt_rdv->bind_param("i", $rdv_id);
    $stmt_rdv->execute();
    $result_rdv = $stmt_rdv->get_result();

    if ($rdv = $result_rdv->fetch_assoc()) {
        $stmt_update_dispo = $conn->prepare("
            UPDATE disponibilites SET status = 'disponible'
            WHERE date = ? AND heure = ?
        ");
        $stmt_update_dispo->bind_param("ss", $rdv['date'], $rdv['heure']);
        $stmt_update_dispo->execute();
        $stmt_update_dispo->close();

        $stmt_delete = $conn->prepare("DELETE FROM rendezvous WHERE id = ?");
        $stmt_delete->bind_param("i", $rdv_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        $_SESSION["flash"] = "Votre rendez-vous a été annulé.";
    } else {
        $_SESSION["flash"] = "Rendez-vous introuvable.";
    }
    $stmt_rdv->close();
    header("Location: planning.php");
    exit();
}

// Récupérer créneaux disponibles
$dispo_result = $conn->query("
    SELECT id, DATE_FORMAT(date, '%d %M %Y') AS date_formatee, TIME_FORMAT(heure, '%H:%i') AS heure_formatee
    FROM disponibilites
    WHERE status = 'disponible'
    ORDER BY date, heure
");
$disponibilites = $dispo_result->fetch_all(MYSQLI_ASSOC);

// Récupérer les rendez-vous de l'utilisateur
$stmt_rdv = $conn->prepare("
    SELECT id, DATE_FORMAT(date, '%d %M %Y') AS date_formatee,
           TIME_FORMAT(heure, '%H:%i') AS heure_formatee, service
    FROM rendezvous
    WHERE email = ?
    ORDER BY date DESC
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
  <title>Prendre Rendez-vous</title>
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
      background: linear-gradient(135deg, #000000, #1a1a1a);
    }
    /* Header moderne */
    .modern-header {
      background: linear-gradient(135deg, #000000, #111111);
    }
  </style>
  <script>
    // --- Fonctions pour la réservation ---
    function showConfirmationModal() {
      const dispoSelect = document.querySelector('select[name="disponibilite"]');
      const serviceSelect = document.querySelector('select[name="service"]');
      const selectedDispoText = dispoSelect.options[dispoSelect.selectedIndex].text;
      const selectedServiceText = serviceSelect.options[serviceSelect.selectedIndex].text;
      
      if (!dispoSelect.value || !serviceSelect.value) {
        alert("Veuillez sélectionner un créneau et un service.");
        return;
      }
      
      document.getElementById('modal-dispo').innerText = selectedDispoText;
      document.getElementById('modal-service').innerText = selectedServiceText;
      
      document.getElementById('confirmationModal').classList.remove('hidden');
    }
    
    function confirmReservation() {
      document.getElementById('reservationForm').submit();
    }
    
    function closeModal() {
      document.getElementById('confirmationModal').classList.add('hidden');
    }
    
    // --- Fonctions pour l'annulation ---
    function showCancelModal(rdvId, dispoText, serviceText) {
      document.getElementById('cancel-modal-dispo').innerText = dispoText;
      document.getElementById('cancel-modal-service').innerText = serviceText;
      document.getElementById('cancelConfirmBtn').onclick = function() {
        window.location.href = "?cancel_rdv_id=" + rdvId;
      };
      document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    function closeCancelModal() {
      document.getElementById('cancelModal').classList.add('hidden');
    }
    
    function toggleMenu() {
      const menu = document.getElementById("sidebar");
      menu.classList.toggle("translate-x-full");
    }
  </script>

  <!-- Favicon -->
    <link rel="icon" type="image/png" href="logo_rond.png">

</head>
<body class="gradient-bg text-white min-h-screen flex flex-col">
  <!-- Header -->
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
  
  <!-- Contenu principal (espacement réduit sur mobile) -->
  <main class="pt-16 md:pt-24 flex-1 px-4 md:px-8 flex flex-col items-center">
    <!-- Formulaire de réservation -->
    <div class="max-w-xl w-full mb-12">
      <div class="bg-gray-800 rounded-xl shadow-2xl p-6">
        <h2 class="text-2xl md:text-3xl font-bold mb-4 text-center">Prendre un rendez-vous</h2>
        <p class="text-gray-400 mb-6 text-center">Sélectionnez une date, une heure et un service.</p>
        <form id="reservationForm" action="planning.php" method="POST" class="space-y-4">
          <div>
            <label for="disponibilite" class="block mb-2">Choisissez un créneau :</label>
            <select name="disponibilite" required class="w-full p-2 rounded-lg bg-gray-700 text-white">
              <option value="">Sélectionner</option>
              <?php foreach ($disponibilites as $dispo): ?>
                <option value="<?php echo htmlspecialchars($dispo['id']); ?>">
                  <?php echo htmlspecialchars($dispo['date_formatee'] . ' à ' . $dispo['heure_formatee']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="service" class="block mb-2">Service :</label>
            <select name="service" required class="w-full p-2 rounded-lg bg-gray-700 text-white">
              <option value="Révision">Révision</option>
              <option value="Diagnostic">Diagnostic</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          <div class="text-center">
            <button type="button" onclick="showConfirmationModal()" class="mt-4 w-full bg-red-500 text-white py-2 rounded-lg hover:scale-105 transition-transform">
              Prendre RDV
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Liste des rendez-vous -->
    <div class="max-w-3xl w-full mb-12">
      <div class="bg-gray-800 rounded-xl shadow-2xl p-6">
        <h2 class="text-xl md:text-2xl font-bold mb-4">Mes rendez-vous</h2>
        
        <!-- Pour éviter les débordements sur mobile -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-900">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Heure</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Service</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
              <?php if (!empty($rendezvous)): ?>
                <?php foreach ($rendezvous as $rdv): ?>
                  <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($rdv["date_formatee"]); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($rdv["heure_formatee"]); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($rdv["service"]); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <button 
                        onclick="showCancelModal(
                          <?php echo $rdv['id']; ?>, 
                          '<?php echo addslashes($rdv["date_formatee"] . " à " . $rdv["heure_formatee"]); ?>', 
                          '<?php echo addslashes($rdv["service"]); ?>'
                        )" 
                        class="text-red-500 hover:underline"
                      >
                        Annuler
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center p-4 text-gray-400">Aucun rendez-vous trouvé.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
  
  <!-- Modale de confirmation pour la réservation -->
  <div id="confirmationModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-gray-800 rounded-xl p-6 w-80 text-center shadow-2xl">
      <h3 class="text-xl font-bold mb-4">Confirmez votre réservation</h3>
      <p class="mb-2">Créneau :</p>
      <p id="modal-dispo" class="mb-4 font-semibold"></p>
      <p class="mb-2">Service :</p>
      <p id="modal-service" class="mb-6 font-semibold"></p>
      <div class="flex justify-around">
        <button 
          onclick="confirmReservation()" 
          class="bg-green-500 text-white py-2 px-4 rounded hover:scale-105 transition-transform"
        >
          Confirmer
        </button>
        <button 
          onclick="closeModal()" 
          class="bg-red-500 text-white py-2 px-4 rounded hover:scale-105 transition-transform"
        >
          Annuler
        </button>
      </div>
    </div>
  </div>
  
  <!-- Modale de confirmation pour l'annulation -->
  <div id="cancelModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-gray-800 rounded-xl p-6 w-80 text-center shadow-2xl">
      <h3 class="text-xl font-bold mb-4">Confirmez l'annulation</h3>
      <p class="mb-2">Créneau :</p>
      <p id="cancel-modal-dispo" class="mb-4 font-semibold"></p>
      <p class="mb-2">Service :</p>
      <p id="cancel-modal-service" class="mb-6 font-semibold"></p>
      <div class="flex justify-around">
        <button 
          id="cancelConfirmBtn" 
          class="bg-green-500 text-white py-2 px-4 rounded hover:scale-105 transition-transform"
        >
          Confirmer
        </button>
        <button 
          onclick="closeCancelModal()" 
          class="bg-red-500 text-white py-2 px-4 rounded hover:scale-105 transition-transform"
        >
          Annuler
        </button>
      </div>
    </div>
  </div>
  
  <!-- Sidebar moderne avec bouton Déconnexion en bas -->
  <aside 
    id="sidebar" 
    class="fixed right-0 top-0 h-full w-64 bg-gray-900 shadow-lg p-6 transform translate-x-full transition-transform duration-300 z-20 flex flex-col justify-between"
  >
    <div>
      <button 
        class="absolute top-4 left-4 text-white text-2xl focus:outline-none" 
        onclick="toggleMenu()"
      >
        ✖
      </button>
      <h2 class="text-2xl font-bold mb-8 text-center">Espace Client</h2>
      <ul class="space-y-6">
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='tableau-de-bord.php'">Tableau de bord</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='historique.php'">Historique</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='planning.php'">Prendre Rendez-vous</li>
        <li class="hover:text-red-500 cursor-pointer" onclick="window.location.href='my-profile.php'">Mon compte</li>
      </ul>
    </div>
    <div>
      <button 
        class="w-full text-red-500 hover:text-white" 
        onclick="window.location.href='logout.php'"
      >
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
