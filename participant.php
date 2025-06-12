<?php
include("essentiel.php");
include("security.php");
include("nav.php");

$isAdmin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$tourneyId  = (int)($_GET['tournament_id'] ?? 0);
if (!$tourneyId) {
    die("Tournoi invalide.");
}

// 1) Charger le tournoi
$stmt = $bdd->prepare("SELECT name, is_closed FROM tournaments WHERE id = ?");
$stmt->execute([$tourneyId]);
$tourney = $stmt->fetch() ?: die("Tournoi introuvable.");
$locked  = (bool)$tourney['is_closed'];

// 2) Charger participants et utilisateurs disponibles
$pStmt = $bdd->prepare("
  SELECT p.id, u.username
    FROM participants p
    JOIN users u ON u.id = p.user_id
   WHERE p.tournament_id = ?
   ORDER BY p.id
");
$aStmt = $bdd->prepare("
  SELECT id, username
    FROM users
   WHERE id NOT IN (
     SELECT user_id FROM participants WHERE tournament_id = ?
   )
   ORDER BY username
");
$pStmt->execute([$tourneyId]);
$participants = $pStmt->fetchAll();
$aStmt->execute([$tourneyId]);
$available    = $aStmt->fetchAll();

// 3) Charger tous les matchs existants
$mt = $bdd->prepare("
  SELECT id, round, player_a_id, player_b_id, winner_id
    FROM matches
   WHERE tournament_id = ?
   ORDER BY round, id
");
$mt->execute([$tourneyId]);
$matches = $mt->fetchAll();

// 4) Déterminer le dernier round généré
$roundsPlayed = 0;
foreach ($matches as $m) {
    $roundsPlayed = max($roundsPlayed, $m['round']);
}

// 5) Détecter champion si le dernier round est complet
$champion = '';
if ($roundsPlayed > 0) {
    $lastMatches = array_filter($matches, fn($m) => $m['round'] === $roundsPlayed);
    if (count($lastMatches) === 1 && $lastMatches[array_key_first($lastMatches)]['winner_id']) {
        $wid = $lastMatches[array_key_first($lastMatches)]['winner_id'];
        $u = $bdd->prepare("
          SELECT u.username
            FROM participants p
            JOIN users u ON u.id = p.user_id
           WHERE p.id = ?
        ");
        $u->execute([$wid]);
        $champion = $u->fetchColumn();
    }
}

// 6) Traitement POST (admin & pas locked)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && !$locked) {
    $act = $_POST['action'] ?? '';

    // — Ajouter un joueur
    if ($act === 'add') {
        $uid = (int)$_POST['user_id'];
        $chk = $bdd->prepare("SELECT 1 FROM participants WHERE tournament_id = ? AND user_id = ?");
        $chk->execute([$tourneyId, $uid]);
        if (!$chk->fetch()) {
            $bdd->prepare("INSERT INTO participants (tournament_id, user_id) VALUES (?, ?)")
                ->execute([$tourneyId, $uid]);
        }
    }

    // — Supprimer un participant
    if ($act === 'del') {
        $pid = (int)$_POST['participant_id'];
        $bdd->prepare("DELETE FROM participants WHERE id = ? AND tournament_id = ?")
            ->execute([$pid, $tourneyId]);
    }

    // — Générer le premier tour
    if ($act === 'generate') {
        // Supprimer anciens matchs
        $bdd->prepare("DELETE FROM matches WHERE tournament_id = ?")
            ->execute([$tourneyId]);

        // Pool initial
        $ids = array_column($participants, 'id');
        if (count($ids) % 2 === 1) {
            $ids[] = null;
        }

        // Insérer round 1
        $ins = $bdd->prepare("
          INSERT INTO matches
            (tournament_id, player_a_id, player_b_id, round, score_a, score_b, winner_id)
          VALUES (?, ?, ?, 1, 0, 0, NULL)
        ");
        foreach (array_chunk($ids, 2) as [$a, $b]) {
            $ins->execute([$tourneyId, $a, $b]);
            $mid = $bdd->lastInsertId();
            // bye auto-win
            if (is_null($b) && $a) {
                $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
                    ->execute([$a, $mid]);
            }
            if (is_null($a) && $b) {
                $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
                    ->execute([$b, $mid]);
            }
        }
    }

    // — Valider un match et générer le tour suivant si terminé
    if ($act === 'win') {
        $mid    = (int)$_POST['match_id'];
        $round  = (int)$_POST['round'];
        $winner = (int)$_POST['winner_id'];

        // MàJ winner
        $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
            ->execute([$winner, $mid]);

        // Si tous matchs du round sont finis => tour suivant
        $tot = $bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND round = ?");
        $fin = $bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND round = ? AND winner_id IS NOT NULL");
        $tot->execute([$tourneyId,$round]);  $total    = $tot->fetchColumn();
        $fin->execute([$tourneyId,$round]);  $finished = $fin->fetchColumn();

        if ($total > 0 && $finished === $total) {
            // récupérer tous les winners
            $wq = $bdd->prepare("
              SELECT winner_id
                FROM matches
               WHERE tournament_id = ? AND round = ?
               ORDER BY id
            ");
            $wq->execute([$tourneyId, $round]);
            $wins = array_filter($wq->fetchAll(PDO::FETCH_COLUMN));
            if (count($wins) > 1) {
                if (count($wins) % 2 === 1) {
                    $wins[] = null;
                }
                // insérer round suivant
                $ins2 = $bdd->prepare("
                  INSERT INTO matches
                    (tournament_id, player_a_id, player_b_id, round, score_a, score_b, winner_id)
                  VALUES (?, ?, ?, ?, 0, 0, NULL)
                ");
                foreach (array_chunk($wins, 2) as [$a2, $b2]) {
                    $ins2->execute([$tourneyId, $a2, $b2, $round + 1]);
                    $mid2 = $bdd->lastInsertId();
                    if (is_null($b2) && $a2) {
                        $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
                            ->execute([$a2, $mid2]);
                    }
                    if (is_null($a2) && $b2) {
                        $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
                            ->execute([$b2, $mid2]);
                    }
                }
            }
        }
    }

    // — Clore le tournoi
    if ($act === 'close') {
        $bdd->prepare("UPDATE tournaments SET is_closed = 1 WHERE id = ?")
            ->execute([$tourneyId]);
        $locked = true;
    }

    // Recharger
    $pStmt->execute([$tourneyId]); $participants = $pStmt->fetchAll();
    $mt->execute([$tourneyId]);    $matches     = $mt->fetchAll();

    // PRG
    header('Location: participant.php?tournament_id=' . $tourneyId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?=htmlspecialchars($tourney['name'])?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="bracket-page">
    <main>
      <h1><?=htmlspecialchars($tourney['name'])?></h1>
      <?php if($champion): ?>
        <h2 class="champion">🏆 <?=htmlspecialchars($champion)?></h2>
      <?php endif; ?>

      <!-- Participants -->
      <section class="participants">
        <h2>Participants <?= $locked ? '(clos)' : '' ?></h2>
        <ul>
          <?php foreach($participants as $p): ?>
            <li>
              <?=htmlspecialchars($p['username'])?>
              <?php if($isAdmin && !$locked): ?>
                <form method="post" action="?tournament_id=<?=$tourneyId?>" class="inline">
                  <input type="hidden" name="action" value="del">
                  <input type="hidden" name="participant_id" value="<?=$p['id']?>">
                  <button class="button small">❌</button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if($isAdmin && !$locked): ?>
          <form method="post" action="?tournament_id=<?=$tourneyId?>" class="controls">
            <select name="user_id">
              <?php foreach($available as $u): ?>
                <option value="<?=$u['id']?>"><?=htmlspecialchars($u['username'])?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="action" value="add"      class="button">➕ Ajouter</button>
            <button type="submit" name="action" value="generate" class="button">Générer</button>
            <button type="submit" name="action" value="close"    class="button">Clore</button>
          </form>
        <?php endif; ?>
      </section>

      <!-- Bracket -->
      <section class="bracket">
        <?php
        $current = 0;
        foreach($matches as $m):
          if (is_null($m['player_a_id']) && is_null($m['player_b_id'])) {
            continue;
          }
          if ($m['round'] !== $current):
            $current = $m['round'];
            echo ($current>1?'</div>':'') . "<div class=\"round\"><h2>Tour {$current}</h2>";
          endif;
          $a = $m['player_a_id']
               ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_a_id']}")->fetchColumn()
               : '–';
          $b = $m['player_b_id']
               ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_b_id']}")->fetchColumn()
               : '–';
          $winA = $m['winner_id'] === $m['player_a_id'];
          $winB = $m['winner_id'] === $m['player_b_id'];
        ?>
          <div class="match">
            <?php if($isAdmin): ?>
              <form method="post" action="?tournament_id=<?=$tourneyId?>">
                <input type="hidden" name="action"    value="win">
                <input type="hidden" name="round"     value="<?=$m['round']?>">
                <input type="hidden" name="match_id"  value="<?=$m['id']?>">
                <input type="hidden" name="winner_id" value="<?=$m['player_a_id']?>">
                <button class="btn-win<?=$winA?' winner':''?>" <?=$winA?'disabled':''?>>
                  <?=htmlspecialchars($a)?>
                </button>
              </form>
            <?php else: ?>
              <div class="btn-win<?=$winA?' winner':''?>"><?=htmlspecialchars($a)?></div>
            <?php endif; ?>

            <div class="connector"></div>

            <?php if($isAdmin): ?>
              <form method="post" action="?tournament_id=<?=$tourneyId?>">
                <input type="hidden" name="action"    value="win">
                <input type="hidden" name="round"     value="<?=$m['round']?>">
                <input type="hidden" name="match_id"  value="<?=$m['id']?>">
                <input type="hidden" name="winner_id" value="<?=$m['player_b_id']?>">
                <button class="btn-win<?=$winB?' winner':''?>" <?=$winB?'disabled':''?>>
                  <?=htmlspecialchars($b)?>
                </button>
              </form>
            <?php else: ?>
              <div class="btn-win<?=$winB?' winner':''?>"><?=htmlspecialchars($b)?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    </main>
  </div>
</body>
</html>
