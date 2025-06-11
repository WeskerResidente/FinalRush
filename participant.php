<?php
include("essentiel.php");
include("security.php");
include("nav.php");

$tournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
if ($tournamentId <= 0) {
    die("Tournoi invalide.");
}

$stmtT = $bdd->prepare("SELECT name, description, start_date FROM tournaments WHERE id = ?");
$stmtT->execute([$tournamentId]);
$tournament = $stmtT->fetch();
if (!$tournament) {
    die("Ce tournoi n'existe pas.");
}

$stmtP = $bdd->prepare("
    SELECT p.seed, u.username
      FROM participants p
      JOIN users u ON u.id = p.user_id
     WHERE p.tournament_id = ?
     ORDER BY p.seed ASC
");
$stmtP->execute([$tournamentId]);
$participants = $stmtP->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Participants — <?= $tournament['name'] ?></title>
</head>
<body>
  <h2>Participants du « <?= $tournament['name'] ?> »</h2>
  <p><?= nl2br($tournament['description']) ?></p>
  <p>Date de début : <?= $tournament['start_date'] ?></p>

  <?php if (empty($participants)): ?>
    <p>Aucun joueur inscrit.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($participants as $p): ?>
        <li><?= $p['seed'] ?> – <?= $p['username'] ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <p><a href="generate_matches.php?tournament_id=<?= $tournamentId ?>">Générer les matchs</a></p>
  <p><a href="index.php">Retour</a></p>
</body>
</html>
