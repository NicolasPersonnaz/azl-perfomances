<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
/* -------------------------------------------------------------
 * Débogage : Vérifier l'email stocké en session
 * (Ouvrez le code source dans votre navigateur pour le voir)
 * ------------------------------------------------------------- */
echo "<!-- DEBUG: Email de session = " 
   . (isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : 'Pas défini') 
   . " -->";

// Vérification de la connexion utilisateur
if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

// Traitement AJAX pour sauvegarder les données du véhicule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field']) && isset($_POST['value'])) {
    // Paramètres de connexion à la base de données
    // Utiliser les variables d'environnement
    $host = $_ENV["DB_HOST"] ;
    $user = $_ENV["DB_USER"] ;
    $pass = $_ENV["DB_PASSWORD"] ;
    $dbname = $_ENV["DB_NAME"] ;

    $conn = new mysqli($host, $user_db, $pass_db, $dbname);
    if ($conn->connect_error) {
        echo json_encode(['error' => 'Erreur de connexion à la base de données']);
        exit();
    }

    // Vérification de l'existence des colonnes "email" et "km"
    $checkEmail = $conn->query("SHOW COLUMNS FROM vehicules LIKE 'email'");
    if ($checkEmail->num_rows == 0) {
        $conn->query("ALTER TABLE vehicules ADD COLUMN email VARCHAR(255) NOT NULL");
    }
    $checkKm = $conn->query("SHOW COLUMNS FROM vehicules LIKE 'km'");
    if ($checkKm->num_rows == 0) {
        $conn->query("ALTER TABLE vehicules ADD COLUMN km INT DEFAULT NULL");
    }

    // Récupération des données envoyées par POST
    $field = $_POST['field'];
    $value = $_POST['value'];

    // Liste des champs autorisés
    $allowed_fields = ['marque', 'modele', 'annee', 'puissance', 'carrosserie', 'km', 'immatriculation'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['error' => 'Champ non autorisé']);
        exit();
    }

    // Récupération de l'email de l'utilisateur connecté
    // (Assurez-vous que $_SESSION["user"]["email"] est correctement défini à la connexion)
    $email = $_SESSION["user"]["email"] ?? '';

    // Vérifier si l'email est bien défini
    if (empty($email)) {
        echo json_encode(['error' => 'Email utilisateur introuvable en session']);
        exit();
    }

    // Vérifier si un enregistrement existe déjà pour cet utilisateur
    $stmt = $conn->prepare("SELECT id FROM vehicules WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Mise à jour de l'enregistrement existant
        $row = $result->fetch_assoc();
        $vehicule_id = $row['id'];
        $stmt->close();

        $query = "UPDATE vehicules SET $field = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $value, $vehicule_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insertion d'une nouvelle ligne avec toutes les colonnes initialisées à NULL sauf la renseignée
        $marque = $modele = $annee = $puissance = $carrosserie = $km = $immatriculation = null;
        if ($field == 'marque')          $marque = $value;
        if ($field == 'modele')          $modele = $value;
        if ($field == 'annee')           $annee = $value;
        if ($field == 'puissance')       $puissance = $value;
        if ($field == 'carrosserie')     $carrosserie = $value;
        if ($field == 'km')              $km = $value;
        if ($field == 'immatriculation') $immatriculation = $value;

        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO vehicules (email, marque, modele, annee, puissance, carrosserie, km, immatriculation) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiisss", $email, $marque, $modele, $annee, $puissance, $carrosserie, $km, $immatriculation);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
    echo json_encode(['success' => true]);
    exit();
}

// Récupération des informations du véhicule pour l'affichage
$vehicule_info = null;
$host    = "wtfepdpwtfepdp.mysql.db";
$user_db = "wtfepdpwtfepdp";
$pass_db = "FcWOYJp0UBPQ0M0O4cl6keHjbJRlg2";
$dbname  = "wtfepdpwtfepdp";

$conn = new mysqli($host, $user_db, $pass_db, $dbname);
if (!$conn->connect_error) {
    // Vérification de l'existence des colonnes "email" et "km"
    $checkEmail = $conn->query("SHOW COLUMNS FROM vehicules LIKE 'email'");
    if ($checkEmail->num_rows == 0) {
        $conn->query("ALTER TABLE vehicules ADD COLUMN email VARCHAR(255) NOT NULL");
    }
    $checkKm = $conn->query("SHOW COLUMNS FROM vehicules LIKE 'km'");
    if ($checkKm->num_rows == 0) {
        $conn->query("ALTER TABLE vehicules ADD COLUMN km INT DEFAULT NULL");
    }

    $email = $_SESSION["user"]["email"] ?? '';
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT marque, modele, annee, puissance, carrosserie, km, immatriculation 
                                FROM vehicules 
                                WHERE email = ? 
                                LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $vehicule_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Paramétrage de votre véhicule</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .modal {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      display: none; align-items: center; justify-content: center;
      z-index: 50;
    }
    .modal.active { display: flex; }
    .modal-content {
      background: #fff; padding: 1.5rem; border-radius: 0.5rem;
      width: 90%; max-width: 500px;
    }
    .hidden { display: none; }
  </style>
  <link rel="icon" type="image/png" href="logo_rond.png">
</head>
<body class="bg-gray-900 text-white">
  <!-- HEADER / MENU -->
  <header class="relative w-full px-4 py-4 fixed top-0 left-0 right-0 z-20 bg-black shadow-lg flex items-center">
    <a href="index.php" class="z-20">
      <img src="logo.jpg" alt="ALZ Performances" class="h-10">
    </a>
    <h1 class="absolute left-1/2 transform -translate-x-1/2 text-xl sm:text-2xl md:text-3xl font-bold tracking-wider text-center">
      ALZ PERFORMANCES
    </h1>
    <button class="text-white text-3xl focus:outline-none z-20 ml-auto" onclick="toggleMenu()">☰</button>
  </header>

  <!-- CONTENU PRINCIPAL -->
  <main class="pt-20 p-6">
    <p>Bienvenue sur la page de paramétrage de votre véhicule. Vous pouvez accéder aux fonctionnalités du site en arrière-plan pendant que vous configurez votre véhicule.</p>
    <!-- Affichage des informations du véhicule -->
    <?php if($vehicule_info): ?>
      <div class="vehicle-info mt-6 p-4 bg-gray-800 rounded">
        <h2 class="text-xl font-bold mb-4">Informations de votre véhicule</h2>
        <p><strong>Marque :</strong> <?php echo htmlspecialchars($vehicule_info['marque']); ?></p>
        <p><strong>Modèle :</strong> <?php echo htmlspecialchars($vehicule_info['modele']); ?></p>
        <p><strong>Année :</strong> <?php echo htmlspecialchars($vehicule_info['annee']); ?></p>
        <p><strong>Puissance :</strong> <?php echo htmlspecialchars($vehicule_info['puissance']); ?> CV</p>
        <p><strong>Carrosserie :</strong> <?php echo htmlspecialchars($vehicule_info['carrosserie']); ?></p>
        <p><strong>Kilométrage :</strong> <?php echo htmlspecialchars($vehicule_info['km']); ?> km</p>
        <p><strong>Immatriculation :</strong> <?php echo htmlspecialchars($vehicule_info['immatriculation']); ?></p>
      </div>
    <?php else: ?>
      <div class="vehicle-info mt-6 p-4 bg-gray-800 rounded">
        <p>Aucune information sur votre véhicule n'a été enregistrée.</p>
      </div>
    <?php endif; ?>
  </main>

  <!-- MODALE DE CONFIGURATION -->
  <div id="initialModal" class="modal active">
    <div class="modal-content text-black">
      <h2 class="text-xl font-bold mb-4">Voulez-vous paramétrer votre véhicule ?</h2>
      <div class="flex justify-end space-x-4">
        <button id="btnRefuser" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Non</button>
        <button id="btnAccepter" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Oui</button>
      </div>
    </div>
  </div>

  <div id="vehicleModal" class="modal">
    <div class="modal-content text-black">
      <div id="formStep">
        <!-- Étape 1 : Marque -->
        <div id="step1">
          <h2 class="text-xl font-bold mb-4">Entrez la marque de votre véhicule :</h2>
          <input type="text" id="vehiculeMarque" class="border p-2 w-full" placeholder="Marque">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="next1" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 2 : Modèle -->
        <div id="step2" class="hidden">
          <h2 class="text-xl font-bold mb-4">Entrez le modèle de votre véhicule :</h2>
          <input type="text" id="vehiculeModele" class="border p-2 w-full" placeholder="Modèle">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev2" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next2" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 3 : Année -->
        <div id="step3" class="hidden">
          <h2 class="text-xl font-bold mb-4">Entrez l'année de votre véhicule :</h2>
          <input type="number" id="vehiculeAnnee" class="border p-2 w-full" placeholder="Année">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev3" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next3" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 4 : Puissance -->
        <div id="step4" class="hidden">
          <h2 class="text-xl font-bold mb-4">Entrez la puissance de votre véhicule (en CV) :</h2>
          <input type="number" id="vehiculePuissance" class="border p-2 w-full" placeholder="Puissance">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev4" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next4" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 5 : Carrosserie -->
        <div id="step5" class="hidden">
          <h2 class="text-xl font-bold mb-4">Choisissez le type de carrosserie :</h2>
          <div class="flex space-x-4">
            <label class="flex items-center space-x-2">
              <input type="radio" name="carrosserie" value="3 portes">
              <span>3 portes</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="radio" name="carrosserie" value="5 portes">
              <span>5 portes</span>
            </label>
          </div>
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev5" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next5" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 6 : Kilométrage -->
        <div id="step6" class="hidden">
          <h2 class="text-xl font-bold mb-4">Entrez le kilométrage de votre véhicule :</h2>
          <input type="number" id="vehiculeKm" class="border p-2 w-full" placeholder="Kilomètres">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev6" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next6" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape 7 : Immatriculation -->
        <div id="step7" class="hidden">
          <h2 class="text-xl font-bold mb-4">Entrez la plaque d'immatriculation :</h2>
          <input type="text" id="vehiculeImmat" class="border p-2 w-full" placeholder="Immatriculation">
          <div class="flex justify-end space-x-4 mt-4">
            <button id="prev7" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Précédent</button>
            <button id="next7" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Suivant</button>
          </div>
        </div>
        <!-- Étape finale : Récapitulatif -->
        <div id="finalStep" class="hidden">
          <h2 class="text-xl font-bold mb-4">Récapitulatif</h2>
          <div id="recap" class="mb-4"></div>
          <div class="flex justify-end space-x-4">
            <button id="restart" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Recommencer</button>
            <button id="confirm" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Confirmer</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Fonction pour sauvegarder les données via AJAX
    function saveData(field, value, callback) {
      var formData = new FormData();
      formData.append("field", field);
      formData.append("value", value);
      fetch("", { method: "POST", body: formData })
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            alert("Erreur: " + data.error);
          }
          if (callback) callback();
        })
        .catch(error => {
          console.error("Erreur:", error);
          if (callback) callback();
        });
    }

    // Gestion des modales
    document.getElementById("btnAccepter").addEventListener("click", function(){
      document.getElementById("initialModal").classList.remove("active");
      document.getElementById("vehicleModal").classList.add("active");
    });
    document.getElementById("btnRefuser").addEventListener("click", function(){
      document.getElementById("initialModal").classList.remove("active");
    });

    // Navigation entre les étapes
    function showStep(current, next) {
      document.getElementById(current).classList.add("hidden");
      document.getElementById(next).classList.remove("hidden");
    }

    // Étape 1 : Marque
    document.getElementById("next1").addEventListener("click", function(){
      var marque = document.getElementById("vehiculeMarque").value;
      saveData("marque", marque, function(){ showStep("step1", "step2"); });
    });
    // Étape 2 : Modèle
    document.getElementById("prev2").addEventListener("click", function(){
      showStep("step2", "step1");
    });
    document.getElementById("next2").addEventListener("click", function(){
      var modele = document.getElementById("vehiculeModele").value;
      saveData("modele", modele, function(){ showStep("step2", "step3"); });
    });
    // Étape 3 : Année
    document.getElementById("prev3").addEventListener("click", function(){
      showStep("step3", "step2");
    });
    document.getElementById("next3").addEventListener("click", function(){
      var annee = document.getElementById("vehiculeAnnee").value;
      saveData("annee", annee, function(){ showStep("step3", "step4"); });
    });
    // Étape 4 : Puissance
    document.getElementById("prev4").addEventListener("click", function(){
      showStep("step4", "step3");
    });
    document.getElementById("next4").addEventListener("click", function(){
      var puissance = document.getElementById("vehiculePuissance").value;
      saveData("puissance", puissance, function(){ showStep("step4", "step5"); });
    });
    // Étape 5 : Carrosserie
    document.getElementById("prev5").addEventListener("click", function(){
      showStep("step5", "step4");
    });
    document.getElementById("next5").addEventListener("click", function(){
      var radios = document.getElementsByName("carrosserie");
      var carrosserie = "";
      for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
          carrosserie = radios[i].value;
          break;
        }
      }
      saveData("carrosserie", carrosserie, function(){ showStep("step5", "step6"); });
    });
    // Étape 6 : Kilométrage
    document.getElementById("prev6").addEventListener("click", function(){
      showStep("step6", "step5");
    });
    document.getElementById("next6").addEventListener("click", function(){
      var km = document.getElementById("vehiculeKm").value;
      saveData("km", km, function(){ showStep("step6", "step7"); });
    });
    // Étape 7 : Immatriculation
    document.getElementById("prev7").addEventListener("click", function(){
      showStep("step7", "step6");
    });
    document.getElementById("next7").addEventListener("click", function(){
      var immatriculation = document.getElementById("vehiculeImmat").value;
      saveData("immatriculation", immatriculation, function(){
        // Génération du récapitulatif
        var recapContent = "";
        recapContent += "<p><strong>Marque :</strong> " + document.getElementById("vehiculeMarque").value + "</p>";
        recapContent += "<p><strong>Modèle :</strong> " + document.getElementById("vehiculeModele").value + "</p>";
        recapContent += "<p><strong>Année :</strong> " + document.getElementById("vehiculeAnnee").value + "</p>";
        recapContent += "<p><strong>Puissance :</strong> " + document.getElementById("vehiculePuissance").value + " CV</p>";
        var radios = document.getElementsByName("carrosserie");
        var carrosserie = "";
        for (var i = 0; i < radios.length; i++) {
          if (radios[i].checked) {
            carrosserie = radios[i].value;
            break;
          }
        }
        recapContent += "<p><strong>Carrosserie :</strong> " + carrosserie + "</p>";
        recapContent += "<p><strong>Kilomètres :</strong> " + document.getElementById("vehiculeKm").value + " km</p>";
        recapContent += "<p><strong>Immatriculation :</strong> " + document.getElementById("vehiculeImmat").value + "</p>";
        document.getElementById("recap").innerHTML = recapContent;
        showStep("step7", "finalStep");
      });
    });
    // Bouton "Recommencer"
    document.getElementById("restart").addEventListener("click", function(){
      document.getElementById("vehiculeMarque").value = "";
      document.getElementById("vehiculeModele").value = "";
      document.getElementById("vehiculeAnnee").value = "";
      document.getElementById("vehiculePuissance").value = "";
      var radios = document.getElementsByName("carrosserie");
      for (var i = 0; i < radios.length; i++) {
        radios[i].checked = false;
      }
      document.getElementById("vehiculeKm").value = "";
      document.getElementById("vehiculeImmat").value = "";
      var steps = ["step1","step2","step3","step4","step5","step6","step7","finalStep"];
      steps.forEach(function(step){ document.getElementById(step).classList.add("hidden"); });
      document.getElementById("step1").classList.remove("hidden");
    });
    // Bouton "Confirmer" : après confirmation, on recharge la page pour mettre à jour l'affichage
    document.getElementById("confirm").addEventListener("click", function(){
      alert("Véhicule paramétré avec succès !");
      // Recharge de la page pour afficher les informations mises à jour
      window.location.reload();
    });

    // Fonction pour le menu (bouton burger)
    function toggleMenu() {
      alert("Fonction de menu à implémenter !");
    }
  </script>
</body>
</html>
