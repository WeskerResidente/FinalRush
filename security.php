<?php 
// vérif de si l'utilisateur est connecté 
    if (!isset($_SESSION['user_id'])) {
        die("Vous n'êtes pas connecté."
        . " <a href='connexion.php'>Se connecter</a>");
    }
?>