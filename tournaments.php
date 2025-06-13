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
        <?php if ( $t['is_closed'] == 0 ): ?>
        <div class="tournament-item-index" id="tournamentID-<?= $t['id'] ?>">
          <h3>
            <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>
            <small>(<?= htmlspecialchars($t['game_name'], ENT_QUOTES) ?>)</small>
          </h3>
          <p><?= htmlspecialchars($t['description'], ENT_QUOTES) ?></p>
          <p>Date de début : <?= htmlspecialchars($t['start_date'], ENT_QUOTES) ?></p>
          <p>Créé le : <?= htmlspecialchars($t['created_at'], ENT_QUOTES) ?></p>
          <a href="registerTournament.php?id=<?= $t['id'] ?>" class="consult-participer">Participer</a>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <section id="tournament-index">
    <div class="tournament-heading-index">
      <h2>Liste des tournois terminé :</h2>
    </div>
    <div class="tournament-list-index">
    <?php foreach ($tournaments as $t): ?>
      <?php if (!empty($t['is_closed']) && $t['is_closed']): ?>
        <div class="tournament-item-index" id="tournamentID-<?= $t['id'] ?>">
          <h3>
            <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>
            <small>(<?= htmlspecialchars($t['game_name'], ENT_QUOTES) ?>)</small>
          </h3>
          <p><?= htmlspecialchars($t['description'], ENT_QUOTES) ?></p>
          <p>Date de début : <?= htmlspecialchars($t['start_date'], ENT_QUOTES) ?></p>
          <p>Créé le : <?= htmlspecialchars($t['created_at'], ENT_QUOTES) ?></p>
          <p class="tournament-closed">Ce tournoi est terminé.</p>
          <a href="participant.php?tournament_id=<?= $t['id']?>" class="consult">Consulter</a>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    </div>
  </section>
</body>
</html>
