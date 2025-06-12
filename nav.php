

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tournois Esport</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header class="container">
    <nav>
      <ul class="navbar-main">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="tournaments.php">Tournois</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li><a href="create_tournament.php">Créer un tournoi</a></li>
            <li><a href="Admin-Panel.php">Panel Admin</a></li>
          <?php endif; ?>
          <li><a href="my_tournaments.php">Mes tournois</a></li>
        <?php else: ?>
          <li><a href="inscription.php">Inscription</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="navbar-user">
      <ul>
        <li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <form action="deconexion.php" method="post" style="display:inline">
              <button type="submit" class="btn-magique">Déconnexion</button>
            </form>
            <span>Bonjour <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES) ?></span>
          <?php else: ?>
            <a href="connexion.php" class="btn-magique">Connexion</a>
            <span>Bonjour Invité</span>
          <?php endif; ?>
        </li>
      </ul>
    </div>
  </header>
</body>
</html>
