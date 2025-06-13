<?php
include("essentiel.php");
include("nav.php");

// Requête unique pour récupérer tournois + nom du jeu
$stmt = $bdd->prepare("
  SELECT t.*, g.name AS game_name
    FROM tournaments t
    JOIN games g       ON g.id = t.game_id
   ORDER BY t.created_at DESC
");
$stmt->execute();
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des tournois</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <section id="tournament-index">
    <div class="tournament-heading-index">
      <h2>Liste des tournois :</h2>
    </div>
    <div class="tournament-list-index">
      <?php foreach ($tournaments as $t): ?>
        <div class="tournament-item-index" id="tournamentID-<?= $t['id'] ?>">
          <h3>
            <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>
            <small>(<?= htmlspecialchars($t['game_name'], ENT_QUOTES) ?>)</small>
          </h3>
          <p><?= htmlspecialchars($t['description'], ENT_QUOTES) ?></p>
          <p>Date de début : <?= htmlspecialchars($t['start_date'], ENT_QUOTES) ?></p>
          <p>Créé le : <?= htmlspecialchars($t['created_at'], ENT_QUOTES) ?></p>
          <a href="registerTournament.php?id=<?= $t['id'] ?>">Participer</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</body>
</html>
