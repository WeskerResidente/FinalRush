<?php 
// vérification de si l'utilisateur est connecté 
    if (!isset($_SESSION['user'])) {
        die("Vous n'êtes pas connecté."
        . " <a href='connexion.php'>Se connecter</a>");
    }
?>