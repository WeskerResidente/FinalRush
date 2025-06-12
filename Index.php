<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("essentiel.php");
include("nav.php");

// Récupérer les 3 derniers tournois
$requestSelect = $bdd->prepare('SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 3');
$requestSelect->execute();

// Statistiques rapides
$totalTournois = $bdd->query('SELECT COUNT(*) FROM tournaments')->fetchColumn();
$totalJoueurs  = $bdd->query('SELECT COUNT(*) FROM users')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil — FinalRush</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/acceuil.css">
</head>
<body>


  <section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>Bienvenue sur FinalRush</h1>
      <p>Le hub ultime pour vos tournois e-sport en ligne</p>
      <a href="tournaments.php" class="btn btn-primary">Voir tous les tournois</a>
    </div>
  </section>

  <!-- Statistiques -->
  <section class="stats">
    <div class="stat-card">
      <h3><?= htmlspecialchars($totalTournois) ?></h3>
      <p>Tournois créés</p>
    </div>
    <div class="stat-card">
      <h3><?= htmlspecialchars($totalJoueurs) ?></h3>
      <p>Joueurs inscrits</p>
    </div>
  </section>

  <!-- Derniers tournois -->
  <section class="tournament-index">
    <div class="section-header">
      <h2>Derniers tournois ajoutés</h2>
      <a href="tournaments.php" class="btn btn-secondary">Voir plus</a>
    </div>
    <div class="tournament-list">
      <?php while ($t = $requestSelect->fetch()): ?>
        <div class="tournament-card">
          <h3><?= htmlspecialchars($t['name']) ?></h3>
          <p class="desc"><?= htmlspecialchars($t['description']) ?></p>
          <p class="date"><strong>Début :</strong> <?= htmlspecialchars($t['start_date']) ?></p>
          <a href="tournaments.php#tournamentID-<?= $t['id'] ?>" class="btn btn-tertiary">Détails</a>
        </div>
      <?php endwhile; ?>
    </div>
  </section>

  <?php include("footer.php"); ?>
</body>
</html>
