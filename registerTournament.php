<?php

include("essentiel.php");
include("nav.php");

// 1) Récupère l’ID du tournoi en GET
$tournamentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($tournamentId <= 0) {
    die("Tournoi invalide. (ID reçue: {$tournamentId})");
}

// 2) Charger les infos du tournoi
$stmtT = $bdd->prepare("
  SELECT 
    t.name, 
    t.start_date, 
    t.max_players, 
    g.name AS game_name
  FROM tournaments t
  JOIN games g ON g.id = t.game_id
  WHERE t.id = ?
");
$stmtT->execute([$tournamentId]);
$tourney = $stmtT->fetch(PDO::FETCH_ASSOC)
          ?: die("Tournoi introuvable.");

// 3) Compter les inscrits
$stmtCount = $bdd->prepare('
  SELECT COUNT(*) 
    FROM participants 
   WHERE tournament_id = ?
');
$stmtCount->execute([$tournamentId]);
$currentCount = (int) $stmtCount->fetchColumn();

// 4) Vérifier si l’utilisateur est déjà inscrit
$alreadyRegistered = false;
if (!empty($_SESSION['user_id'])) {
    $stmtChk = $bdd->prepare('
      SELECT 1 
        FROM participants 
       WHERE tournament_id = ? AND user_id = ?
      LIMIT 1
    ');
    $stmtChk->execute([$tournamentId, $_SESSION['user_id']]);
    $alreadyRegistered = (bool) $stmtChk->fetchColumn();
}

// 5) Traitement de l’inscription si POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (empty($_SESSION['user_id'])) {
        die("Vous devez être connecté pour vous inscrire.");
    }
    if ($alreadyRegistered) {
        $error = "Vous êtes déjà inscrit à ce tournoi.";
    }
    elseif ($currentCount >= $tourney['max_players']) {
        $error = "Désolé, ce tournoi est déjà complet ({$tourney['max_players']} joueurs).";
    } else {
        $stmtIns = $bdd->prepare('
          INSERT INTO participants (tournament_id, user_id)
          VALUES (?, ?)
        ');
        $stmtIns->execute([$tournamentId, $_SESSION['user_id']]);
        $success = "Vous êtes inscrit au tournoi !";
        $currentCount++;
        $alreadyRegistered = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>
    Inscription — <?= htmlspecialchars($tourney['name'], ENT_QUOTES) ?>
  </title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="page-register-tournament">
    <h2>
    Inscription au tournoi
    « <?= htmlspecialchars($tourney['name'], ENT_QUOTES) ?> »
    <small>(<?= htmlspecialchars($tourney['game_name'], ENT_QUOTES) ?>)</small>
    </h2>
    <p>Date de début : <?= htmlspecialchars($tourney['start_date'], ENT_QUOTES) ?></p>
    <p>
      Places : <?= $currentCount ?>/<?= $tourney['max_players'] ?>
    </p>

    <?php if (!empty($error)): ?>
      <div class="message erreur">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="message succes">
        <?= htmlspecialchars($success, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <?php if (!$alreadyRegistered): ?>
      <form 
        method="post" 
        action="registerTournament.php?id=<?= $tournamentId ?>"
      >
        <button 
          type="submit" 
          name="register" 
          class="button"
        >
          S’inscrire
        </button>
      </form>
    <?php else: ?>
      <p class="info">
        <?php if (empty($success)): ?>
          Vous êtes déjà inscrit à ce tournoi.
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <p><a href="tournaments.php">← Retour à la liste des tournois</a></p>
  </section>
</body>
</html>
