<?php 
if (isset($_SESSION['user_id'])) {
  include_once("essentiel.php");
  $requestSelect = $bdd->prepare('
    SELECT avatar, color 
      FROM users 
     WHERE id = ?
  ');
  $requestSelect->execute([$_SESSION['user_id']]);
  $request = $requestSelect->fetch(PDO::FETCH_ASSOC) ?: [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tournois Esport</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="navbar">
    <div class="logo">
      <a href="index.php">
        <img src="assets/img/logo.png" alt="FinalRush" class="logo-img">
        <p class="logo-text">FinalRush</p>
      </a>
    </div>

    <button class="navbar-burger" id="navbar-burger" aria-label="Menu" aria-expanded="false" aria-controls="nav-links">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <ul class="nav-links" id="nav-links">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="tournaments.php">Tournois</a></li>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <li><a href="create_tournament.php">Créer un tournoi</a></li>
          <li><a href="Admin-Panel.php">Panel Admin</a></li>
        <?php endif; ?>
        <li><a href="myTournament.php">Mes tournois</a></li>
        <li><a href="profile.php">Mon profil</a></li>
        <li>
          <form action="deconexion.php" method="post" style="display:inline">
            <button type="submit" class="button">Déconnexion</button>
          </form>
        </li>
      <?php else: ?>
        <li><a href="inscription.php">Inscription</a></li>
        <li><a href="connexion.php" class="button">Connexion</a></li>
      <?php endif; ?>
    </ul>
    
    <div class="navbar-user">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="nav-avatars-container">
          <a href="profile.php">
            <img 
              src="uploads/avatars/<?= htmlspecialchars($request['avatar']   ?? 'default.png', ENT_QUOTES) ?>" 
              alt="Avatar" 
              class="nav-avatar"
            >
          </a>
        </div>
        <?php 
          $usernameColor = $request['color'] ?? '#d9d9d9';
        ?>
        <span class="username-nav" style="color:<?= htmlspecialchars($usernameColor, ENT_QUOTES) ?>;">
          Bonjour <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES) ?>
        </span>
      <?php else: ?>
        <span>Bonjour Invité</span>
      <?php endif; ?>
    </div>
  </header>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const burger = document.getElementById('navbar-burger');
      const nav = document.getElementById('nav-links');
      if (burger && nav) {
        burger.addEventListener('click', function() {
          nav.classList.toggle('open');
          burger.classList.toggle('open');
          burger.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
        });
      }
    });
  </script>
</body>
</html>