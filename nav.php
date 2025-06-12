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

    <ul class="nav-links">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="tournaments.php">Tournois</a></li>

      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <li><a href="create_tournament.php">Créer un tournoi</a></li>
          <li><a href="Admin-Panel.php">Panel Admin</a></li>
        <?php endif; ?>
        <li><a href="myTournament.php">Mes tournois</a></li>
      <?php else: ?>
        <li><a href="inscription.php">Inscription</a></li>
      <?php endif; ?>
    </ul>

    <div class="navbar-user">
      <?php if (isset($_SESSION['user_id'])): ?>
        <form action="deconexion.php" method="post" style="display:inline">
          <button type="submit" class="button">Déconnexion</button>
        </form>
        <span>Bonjour <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES) ?></span>
      <?php else: ?>
        <a href="connexion.php" class="button">Connexion</a>
        <span>Bonjour Invité</span>
      <?php endif; ?>
    </div>
  </header>
</body>
</html>
