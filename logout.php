<?php
session_start();
session_destroy();
header("Location: login.html"); // Redirige vers la page de connexion
exit();
?>