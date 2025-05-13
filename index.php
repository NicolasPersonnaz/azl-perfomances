<?php
session_start();

// Vérifie si l'utilisateur est connecté
$userConnected = isset($_SESSION["user"]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALZ Performances</title>
    <link rel="stylesheet" href="style.css">
    <!-- Intégration des polices et icônes nécessaires -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="logo_rond.png">
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php">
                <img src="logo.jpg" alt="ALZ Performances">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="#">Accueil</a></li>
                <li>
                    <a href="#">Moto</a>
                    <ul class="submenu">
                        <li><a href="#">Réparation</a></li>
                        <li><a href="#">Révision pour CT</a></li>
                        <li><a href="#">Préparation</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Voiture</a>
                    <ul class="submenu">
                        <li><a href="#">Réparation</a></li>
                        <li><a href="#">Révision pour CT</a></li>
                        <li><a href="#">Carrosserie</a></li>
                        <li><a href="#">Peinture</a></li>
                        <li><a href="#">Optimisation</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div class="icons">
            <a href="#"><i class="fas fa-search"></i></a>

            <?php if ($userConnected): ?>
                <!-- Si l'utilisateur est connecté, affichage de l'espace personnel -->
                <a href="tableau-de-bord.php" class="login-button">
                    <img src="login.png" alt="Espace personnel" class="logo">
                </a>
            <?php else: ?>
                <!-- Si l'utilisateur n'est pas connecté, affichage du bouton connexion -->
                <a href="login.html" class="login-button">
                    <img src="login.png" alt="Connexion" class="logo">
                </a>
            <?php endif; ?>
        </div>
    </header>
    <script src="script.js"></script>
</body>
</html>