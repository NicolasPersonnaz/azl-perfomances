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

// Traitement de la mise à jour du profil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    // Récupérer les données du formulaire
    $nom = isset($_POST["nom"]) ? $_POST["nom"] : '';
    $prenom = isset($_POST["prenom"]) ? $_POST["prenom"] : '';
    $numero_telephone = isset($_POST["numero_telephone"]) ? $_POST["numero_telephone"] : '';
    $adresse = isset($_POST["adresse"]) ? $_POST["adresse"] : '';
    $voiture = isset($_POST["voiture"]) ? $_POST["voiture"] : '';
    $code_postal = isset($_POST["code_postal"]) ? $_POST["code_postal"] : '';
    $new_password = isset($_POST["new_password"]) ? $_POST["new_password"] : '';
    $confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : '';

    // Vérifier si le nouveau mot de passe et la confirmation correspondent
    if (!empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            die("Les mots de passe ne correspondent pas.");
        }
        // Hacher le nouveau mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Mettre à jour les informations de l'utilisateur
    $sql = "UPDATE users SET nom = ?, prenom = ?, numero_telephone = ?, adresse = ?, voiture = ?, code_postal = ? ";
    $params = [$nom, $prenom, $numero_telephone, $adresse, $voiture, $code_postal];
    $types = "ssssss";

    if (isset($hashed_password)) {
        $sql .= ", password = ? ";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $sql .= "WHERE email = ?";
    $params[] = $user_email;
    $types .= "s";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }

    // Utiliser call_user_func_array pour passer les paramètres à bind_param
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        // Récupérer les informations mises à jour
        $sql = "SELECT nom, prenom, email, numero_telephone, adresse, voiture, code_postal FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Erreur de préparation de la requête : " . $conn->error);
        }

        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            die("Aucun utilisateur trouvé avec cet email.");
        }

        $profil = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        // Rediriger vers la même page pour afficher les informations mises à jour
        header("Location: edit-profile.php");
        exit();
    } else {
        die("Erreur lors de la mise à jour du profil.");
    }

    $stmt->close();
}

// Traitement de la suppression du compte
if (isset($_POST["delete_account"])) {
    $sql = "DELETE FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param("s", $user_email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Déconnecter l'utilisateur et rediriger vers la page de connexion
        session_destroy();
        header("Location: login.html");
        exit();
    } else {
        die("Erreur lors de la suppression du compte.");
    }

    $stmt->close();
}

// Récupérer les informations de l'utilisateur
$sql = "SELECT nom, prenom, email, numero_telephone, adresse, voiture, code_postal FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erreur de préparation de la requête : " . $conn->error);
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Aucun utilisateur trouvé avec cet email.");
}

$profil = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Aquatico', sans-serif;
            background-color: #000000; /* Couleur de fond principale */
            color: #FFFFFF; /* Texte en blanc */
        }
        .background-header { background-color: #000000; } /* Couleur de fond du header */
        .background-sidebar { background-color: #252525; } /* Couleur de fond du menu */
        .input-field {
            background-color: #121212; /* Couleur de fond des champs */
            color: #FFFFFF; /* Texte en blanc */
            border: 1px solid #FFFFFF; /* Bordure blanche */
        }
        .input-field::placeholder {
            color: #AAAAAA; /* Placeholder en gris clair */
        }
        .btn-primary {
            background-color: #1E90FF; /* Bouton bleu */
        }
        .btn-danger {
            background-color: #FF0000; /* Bouton rouge */
        }
    </style>
    <script>
        function toggleMenu() {
            const menu = document.getElementById("sidebar");
            menu.classList.toggle("translate-x-full");
        }

        function confirmDelete() {
            return confirm("Êtes-vous sûr de vouloir supprimer votre compte ?");
        }

        function togglePasswordFields() {
            const passwordFields = document.getElementById("password-fields");
            passwordFields.classList.toggle("hidden");
        }
    </script>
</head>
<body class="text-white h-screen flex flex-col">
    <!-- Header -->
    <header class="w-full flex items-center justify-between p-4 background-header shadow-md">
        <a href="index.html">
            <img src="logo.jpg" alt="ALZ Performances" class="h-10">
        </a> <!-- Logo en haut à gauche -->
        <h1 class="text-2xl font-bold text-white">ALZ PERFORMANCES</h1> <!-- Titre centré -->
        <button class="text-white text-2xl" onclick="toggleMenu()">☰</button> <!-- Bouton pour ouvrir le menu à droite -->
    </header>

    <!-- Contenu principal -->
    <div class="flex-1 p-6 flex flex-col items-center">
        <h2 class="text-3xl font-bold">Modifier mon Profil</h2>
        <form action="edit-profile.php" method="POST" class="w-full max-w-lg mt-6 p-4 bg-gray-800 rounded-lg shadow">
            <input type="text" name="nom" placeholder="Nom" value="<?php echo htmlspecialchars($profil["nom"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <input type="text" name="prenom" placeholder="Prénom" value="<?php echo htmlspecialchars($profil["prenom"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($profil["email"] ?? ''); ?>" readonly class="w-full p-2 mb-4 input-field">
            <input type="text" name="numero_telephone" placeholder="Téléphone" value="<?php echo htmlspecialchars($profil["numero_telephone"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <input type="text" name="adresse" placeholder="Adresse" value="<?php echo htmlspecialchars($profil["adresse"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <input type="text" name="voiture" placeholder="Voiture" value="<?php echo htmlspecialchars($profil["voiture"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <input type="text" name="code_postal" placeholder="Code Postal" value="<?php echo htmlspecialchars($profil["code_postal"] ?? ''); ?>" required class="w-full p-2 mb-4 input-field">
            <button type="button" class="w-full p-2 bg-blue-500 text-white rounded-lg btn-primary mb-4" onclick="togglePasswordFields()">Changer de mot de passe</button>
            <div id="password-fields" class="hidden">
                <input type="password" name="new_password" placeholder="Nouveau mot de passe" class="w-full p-2 mb-4 input-field">
                <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" class="w-full p-2 mb-4 input-field">
            </div>
            <button type="submit" name="update_profile" class="w-full p-2 bg-blue-500 text-white rounded-lg btn-primary">Mettre à jour</button>
        </form>
        <form action="edit-profile.php" method="POST" onsubmit="return confirmDelete()" class="w-full max-w-lg mt-6 p-4 bg-gray-800 rounded-lg shadow">
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="w-full p-2 text-white rounded-lg btn-danger">Supprimer mon compte</button>
        </form>
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
