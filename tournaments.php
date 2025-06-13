<?php
include("essentiel.php");
include("nav.php");

// recherche de tournois par leurs nom
// on met dnas l'url le nom entré dnas l'input.
// - si le nom n'est pas vide on récupère via requete sql que les tournois avec le même nom que la reqûete get.
// - prepare et execute puis on récupère les résultats
$search = htmlspecialchars(trim($_GET['search'] ?? ''));

$searchSelect = "SELECT t.*, g.name AS game_name
        FROM tournaments t
        JOIN games g ON g.id = t.game_id
";
$params = [];
if ($search !== '') {
    $searchSelect .= " WHERE t.name LIKE :search ";
    $params[':search'] = '%' . $search . '%';
}
$searchSelect .= " ORDER BY t.created_at DESC ";

$stmt = $bdd->prepare($searchSelect);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des tournois</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <form method="get" action="tournaments.php" class="search-tournament">
    <input
      type="text"
      name="search"
      placeholder="Rechercher un tournoi par nom…"
      value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="button">Rechercher</button>
  </form>
  <section id="tournament-index">
    <div class="tournament-heading-index">
      <h2>Liste des tournois :</h2>
    </div>
    <div class="tournament-list-index">
      <?php foreach ($tournaments as $t): ?>
        <?php if ($t['is_closed'] == 0): ?>
        <div class="tournament-item-index" id="tournamentID-<?= $t['id'] ?>">
          <h3>
            <?= htmlspecialchars($t['name']) ?>
            <small>(<?= htmlspecialchars($t['game_name']) ?>)</small>
          </h3>
          <p><?= htmlspecialchars($t['description']) ?></p>
          <p>Date de début : <?= htmlspecialchars($t['start_date']) ?></p>
          <p>Créé le : <?= htmlspecialchars($t['created_at']) ?></p>
          <a href="registerTournament.php?id=<?= $t['id'] ?>" class="consult">Participer</a>
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
            <?= htmlspecialchars($t['name']) ?>
            <small>(<?= htmlspecialchars($t['game_name']) ?>)</small>
          </h3>
          <p><?= htmlspecialchars($t['description']) ?></p>
          <p>Date de début : <?= htmlspecialchars($t['start_date']) ?></p>
          <p>Créé le : <?= htmlspecialchars($t['created_at']) ?></p>
          <p class="tournament-closed">Ce tournoi est terminé.</p>
          <a href="participant.php?tournament_id=<?= $t['id']?>" class="consult">Consulter</a>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    </div>
  </section>
</body>
</html>