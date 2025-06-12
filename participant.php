<?php
include("essentiel.php");
include("security.php");
include("nav.php");

$isAdmin   = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$tourneyId = isset($_GET['tournament_id']) 
           ? (int)$_GET['tournament_id'] 
           : 0;
if ($tourneyId <= 0) {
    die("Tournoi invalide.");
}

// 1) Charger le tournoi
$stmt = $bdd->prepare("
  SELECT name, is_closed, max_players
    FROM tournaments
   WHERE id = ?
");
$stmt->execute([$tourneyId]);
$tourney = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tourney) {
    die("Tournoi introuvable.");
}
$locked     = (bool)$tourney['is_closed'];
$maxPlayers = (int)$tourney['max_players'];

// 2) Compter les inscrits + places restantes
$countStmt = $bdd->prepare("
  SELECT COUNT(*) AS cnt
    FROM participants
   WHERE tournament_id = ?
");
$countStmt->execute([$tourneyId]);
$currentCount = (int)$countStmt->fetchColumn();
$remaining    = $maxPlayers - $currentCount;

// 3) Charger la liste des participants
$pStmt = $bdd->prepare("
  SELECT p.id AS part_id, u.username
    FROM participants p
    JOIN users u ON u.id = p.user_id
   WHERE p.tournament_id = ?
   ORDER BY p.id
");
$pStmt->execute([$tourneyId]);
$participants = $pStmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Charger les matches
$mt = $bdd->prepare("
  SELECT id, round, player_a_id, player_b_id, winner_id
    FROM matches
   WHERE tournament_id = ?
   ORDER BY round, id
");
$mt->execute([$tourneyId]);
$matches = $mt->fetchAll(PDO::FETCH_ASSOC);

// 5) Déterminer le dernier round et le champion éventuel
$roundsPlayed = 0;
foreach ($matches as $m) {
    $roundsPlayed = max($roundsPlayed, (int)$m['round']);
}

$champion = '';
if ($roundsPlayed > 0) {
    $last = array_filter(
      $matches,
      fn($m) => (int)$m['round'] === $roundsPlayed
    );
    if (count($last) === 1 && $last[array_key_first($last)]['winner_id']) {
        $wid = (int)$last[array_key_first($last)]['winner_id'];
        $u   = $bdd->prepare("
          SELECT u.username
            FROM participants p
            JOIN users u ON u.id = p.user_id
           WHERE p.id = ?
        ");
        $u->execute([$wid]);
        $champion = $u->fetchColumn() ?: '';
    }
}

// 6) Charger les utilisateurs non-inscrits (pour l’admin)
$aStmt = $bdd->prepare("
  SELECT id, username
    FROM users
   WHERE id NOT IN (
     SELECT user_id FROM participants WHERE tournament_id = ?
   )
   ORDER BY username
");
$aStmt->execute([$tourneyId]);
$available = $aStmt->fetchAll(PDO::FETCH_ASSOC);

// 7) Traitement du POST (PRG-safe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && ! $locked) {
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $uid = (int)$_POST['user_id'];
        $chk = $bdd->prepare("
          SELECT 1 
            FROM participants 
           WHERE tournament_id = ? AND user_id = ?
        ");
        $chk->execute([$tourneyId, $uid]);
        if (! $chk->fetch()) {
            $bdd->prepare("
              INSERT INTO participants (tournament_id, user_id)
              VALUES (?, ?)
            ")->execute([$tourneyId, $uid]);
        }
    }

    if ($act === 'del') {
        $pid = (int)$_POST['participant_id'];
        $bdd->prepare("
          DELETE FROM participants
           WHERE id = ? AND tournament_id = ?
        ")->execute([$pid, $tourneyId]);
    }

    if ($act === 'generate') {
        // supprime les anciens
        $bdd->prepare("DELETE FROM matches WHERE tournament_id = ?")
            ->execute([$tourneyId]);

        $ids = array_column($participants, 'part_id');
        if (count($ids) % 2 === 1) {
            $ids[] = null;
        }
        $ins = $bdd->prepare("
          INSERT INTO matches
            (tournament_id, player_a_id, player_b_id, round, score_a, score_b, winner_id)
          VALUES (?, ?, ?, 1, 0, 0, NULL)
        ");
        foreach (array_chunk($ids, 2) as [$a, $b]) {
            $ins->execute([$tourneyId, $a, $b]);
            $mid = $bdd->lastInsertId();
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

    if ($act === 'win') {
        $mid    = (int)$_POST['match_id'];
        $round  = (int)$_POST['round'];
        $winner = (int)$_POST['winner_id'];

        $bdd->prepare("UPDATE matches SET winner_id = ? WHERE id = ?")
            ->execute([$winner, $mid]);

        // si round complet => génère le suivant (similaire à plus haut)…
        $tot = $bdd->prepare("
          SELECT COUNT(*) FROM matches
           WHERE tournament_id = ? AND round = ?
        ");
        $fin = $bdd->prepare("
          SELECT COUNT(*) FROM matches
           WHERE tournament_id = ? AND round = ? AND winner_id IS NOT NULL
        ");
        $tot->execute([$tourneyId, $round]);  $total    = $tot->fetchColumn();
        $fin->execute([$tourneyId, $round]);  $finished = $fin->fetchColumn();
        if ($total>0 && $total===$finished) {
            $wq = $bdd->prepare("
              SELECT winner_id FROM matches
               WHERE tournament_id = ? AND round = ?
               ORDER BY id
            ");
            $wq->execute([$tourneyId,$round]);
            $wins = array_filter($wq->fetchAll(PDO::FETCH_COLUMN));
            if (count($wins)>1) {
                if (count($wins)%2===1) $wins[]=null;
                $ins2 = $bdd->prepare("
                  INSERT INTO matches
                  (tournament_id, player_a_id, player_b_id, round, score_a, score_b, winner_id)
                  VALUES (?, ?, ?, ?, 0, 0, NULL)
                ");
                foreach (array_chunk($wins,2) as [$a2,$b2]) {
                    $ins2->execute([$tourneyId,$a2,$b2,$round+1]);
                    $mid2 = $bdd->lastInsertId();
                    if (is_null($b2)&&$a2) {
                        $bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")
                            ->execute([$a2,$mid2]);
                    }
                    if (is_null($a2)&&$b2) {
                        $bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")
                            ->execute([$b2,$mid2]);
                    }
                }
            }
        }
    }

    if ($act === 'close') {
        $bdd->prepare("UPDATE tournaments SET is_closed = 1 WHERE id = ?")
            ->execute([$tourneyId]);
        $locked = true;
    }

    // PRG
    header("Location: participant.php?tournament_id={$tourneyId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($tourney['name'], ENT_QUOTES) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bracket-page">

  <main>
    <p>
      Places restantes :
      <?php if ($remaining>0): ?>
        <strong><?= $remaining ?></strong>
      <?php else: ?>
        <strong class="full">Complet</strong>
      <?php endif; ?>
    </p>

    <h1><?= htmlspecialchars($tourney['name'], ENT_QUOTES) ?></h1>
    <?php if ($champion): ?>
      <h2 class="champion">🏆 <?= htmlspecialchars($champion, ENT_QUOTES) ?></h2>
    <?php endif; ?>

    <section class="participants">
      <h2>Participants <?= $locked?'(clos)':'' ?></h2>
      <ul>
        <?php foreach($participants as $p): ?>
        <li>
          <?= htmlspecialchars($p['username'], ENT_QUOTES) ?>
          <?php if ($isAdmin && !$locked): ?>
          <form method="post" class="inline" action="?tournament_id=<?= $tourneyId ?>">
            <input type="hidden" name="action" value="del">
            <input type="hidden" name="participant_id" value="<?= $p['part_id'] ?>">
            <button class="button small">❌</button>
          </form>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
<?php if($isAdmin && !$locked): ?>
  <div class="participants-admin-controls">

    <!-- 1) Formulaire pour ajouter un joueur -->
    <form method="post" action="?tournament_id=<?= $tourneyId ?>" class="control-add">
      <input type="hidden" name="action" value="add">
      <select name="user_id" required>
        <option value="">— Sélectionnez un joueur —</option>
        <?php foreach($available as $u): ?>
          <option value="<?= $u['id'] ?>">
            <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="button">➕ Ajouter</button>
    </form>

    <!-- 2) Formulaire pour générer le premier tour -->
    <form method="post" action="?tournament_id=<?= $tourneyId ?>" class="control-generate">
      <input type="hidden" name="action" value="generate">
      <button class="button">Générer Tour 1</button>
    </form>

    <!-- 3) Formulaire pour clore le tournoi -->
    <form method="post" action="?tournament_id=<?= $tourneyId ?>" class="control-close">
      <input type="hidden" name="action" value="close">
      <button class="button danger">Clore Tournoi</button>
    </form>

  </div>
<?php endif; ?>

    </section>

    <section class="bracket">
      <?php 
      $cur = 0;
      foreach ($matches as $m):
        if (is_null($m['player_a_id']) && is_null($m['player_b_id'])) continue;
        if ($m['round'] !== $cur):
          $cur = $m['round'];
          echo ($cur>1?'</div>':'')."<div class=\"round\"><h2>Tour {$cur}</h2>";
        endif;
        $a = $m['player_a_id']
             ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_a_id']}")
                   ->fetchColumn()
             : '–';
        $b = $m['player_b_id']
             ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_b_id']}")
                   ->fetchColumn()
             : '–';
        $winA = $m['winner_id']=== $m['player_a_id'];
        $winB = $m['winner_id']=== $m['player_b_id'];
      ?>
        <div class="match">
          <?php if ($isAdmin): ?>
            <form method="post" action="?tournament_id=<?= $tourneyId ?>">
              <input type="hidden" name="action"    value="win">
              <input type="hidden" name="round"     value="<?= $m['round'] ?>">
              <input type="hidden" name="match_id"  value="<?= $m['id'] ?>">
              <input type="hidden" name="winner_id" value="<?= $m['player_a_id'] ?>">
              <button class="btn-win<?= $winA?' winner':''?>"<?= $winA?' disabled':''?>>
                <?= htmlspecialchars($a, ENT_QUOTES) ?>
              </button>
            </form>
          <?php else: ?>
            <div class="btn-win<?= $winA?' winner':''?>">
              <?= htmlspecialchars($a, ENT_QUOTES) ?>
            </div>
          <?php endif; ?>

          <div class="connector"></div>

          <?php if ($isAdmin): ?>
            <form method="post" action="?tournament_id=<?= $tourneyId ?>">
              <input type="hidden" name="action"    value="win">
              <input type="hidden" name="round"     value="<?= $m['round'] ?>">
              <input type="hidden" name="match_id"  value="<?= $m['id'] ?>">
              <input type="hidden" name="winner_id" value="<?= $m['player_b_id'] ?>">
              <button class="btn-win<?= $winB?' winner':''?>"<?= $winB?' disabled':''?>>
                <?= htmlspecialchars($b, ENT_QUOTES) ?>
              </button>
            </form>
          <?php else: ?>
            <div class="btn-win<?= $winB?' winner':''?>">
              <?= htmlspecialchars($b, ENT_QUOTES) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>
