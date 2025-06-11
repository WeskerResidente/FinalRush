<?php

include("essentiel.php");
include("security.php");
include("nav.php");

// Seul l’admin peut accéder ici
if (!isset($_SESSION['role'], $_GET['tournament_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès refusé.");
}
$tournamentId = (int)$_GET['tournament_id'];

// Charger les infos du tournoi
$stmt = $bdd->prepare("SELECT name, is_closed FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tourney = $stmt->fetch();
if (!$tourney) {
    die("Tournoi introuvable.");
}

// Verrouillage si clos
$locked = (bool)$tourney['is_closed'];
$message = '';

// 1) Gestion participants (avant génération)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add' && !$locked) {
        $uid = (int)$_POST['user_id'];
        $c = $bdd->prepare("SELECT 1 FROM participants WHERE tournament_id=? AND user_id=?");
        $c->execute([$tournamentId, $uid]);
        if (!$c->fetch()) {
            // calcul seed
            $s = $bdd->prepare("SELECT COALESCE(MAX(seed),0)+1 FROM participants WHERE tournament_id=?");
            $s->execute([$tournamentId]);
            $seed = $s->fetchColumn();
            $bdd->prepare("INSERT INTO participants (tournament_id,user_id,seed) VALUES (?,?,?)")
                ->execute([$tournamentId, $uid, $seed]);
            $message = "Joueur ajouté (seed $seed).";
        }
    }
    if ($act === 'del' && !$locked) {
        $pid = (int)$_POST['participant_id'];
        $bdd->prepare("DELETE FROM participants WHERE id=? AND tournament_id=?")
            ->execute([$pid, $tournamentId]);
        $message = "Joueur supprimé.";
        // réordonner seeds
        $bdd->exec("SET @s=0");
        $bdd->prepare("
            UPDATE participants
               SET seed = (@s := @s + 1)
             WHERE tournament_id=?
             ORDER BY seed
        ")->execute([$tournamentId]);
    }

    // 2) Génération automatique du premier round si nécessaire
    if ($act === 'generate' && !$locked) {
        // purge anciens matchs
        $bdd->prepare("DELETE FROM matches WHERE tournament_id=?")->execute([$tournamentId]);
        // récupérer participants
        $p = $bdd->prepare("SELECT id FROM participants WHERE tournament_id=? ORDER BY seed");
        $p->execute([$tournamentId]);
        $pool = $p->fetchAll(PDO::FETCH_COLUMN);
        $n = count($pool);
        $slots = pow(2, ceil(log(max($n, 2), 2)));
        while (count($pool) < $slots) $pool[] = null;
        $im = $bdd->prepare("
            INSERT INTO matches
              (tournament_id,player_a_id,player_b_id,round,score_a,score_b,winner_id)
            VALUES(?,?,?,?,0,0,NULL)
        ");
        for ($i = 0; $i < $slots/2; $i++) {
            $im->execute([$tournamentId, $pool[$i], $pool[$slots-1-$i], 1]);
        }
        $message = "Bracket généré.";
    }

    // 3) Enregistrer le vainqueur (click sur bouton)
    if ($act === 'win' && !$locked) {
        $mid = (int)$_POST['match_id'];
        $win = (int)$_POST['winner_id'];
        $bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")
            ->execute([$win, $mid]);
        $message = "Match #$mid validé.";
        // générer round suivant si tous finish
        $r = 1;
        while (true) {
            $tcount = $bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=?");
            $tcount->execute([$tournamentId, $r]);
            $total = $tcount->fetchColumn();
            if ($total == 0) break;
            $fcount = $bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=? AND winner_id IS NOT NULL");
            $fcount->execute([$tournamentId, $r]);
            if ($fcount->fetchColumn() < $total) break;
            // créer next round si pas existant
            $nc = $bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=?");
            $nc->execute([$tournamentId, $r+1]);
            if ($nc->fetchColumn() == 0) {
                $w = $bdd->prepare("
                    SELECT winner_id FROM matches
                     WHERE tournament_id=? AND round=?
                     ORDER BY id
                ");
                $w->execute([$tournamentId, $r]);
                $wins = $w->fetchAll(PDO::FETCH_COLUMN);
                while (count($wins) % 2) $wins[] = null;
                $im2 = $bdd->prepare("
                    INSERT INTO matches
                      (tournament_id,player_a_id,player_b_id,round,score_a,score_b,winner_id)
                    VALUES(?,?,?,?,0,0,NULL)
                ");
                $half = count($wins)/2;
                for ($i = 0; $i < $half; $i++) {
                    $im2->execute([$tournamentId, $wins[$i], $wins[count($wins)-1-$i], $r+1]);
                }
            }
            $r++;
        }
    }

    // 4) Clore le tournoi
    if ($act === 'close' && !$locked) {
        $bdd->prepare("UPDATE tournaments SET is_closed=1 WHERE id=?")->execute([$tournamentId]);
        $locked = true;
        $message = "Tournoi clos.";
    }
}

// Charger affichage
$participants = $bdd->prepare("
    SELECT p.id,p.seed,u.username
      FROM participants p
      JOIN users u ON u.id=p.user_id
     WHERE p.tournament_id=?
     ORDER BY p.seed
");
$participants->execute([$tournamentId]);
$participants = $participants->fetchAll();

$available = $bdd->prepare("
    SELECT id,username
      FROM users
     WHERE id NOT IN (SELECT user_id FROM participants WHERE tournament_id=?)
     ORDER BY username
");
$available->execute([$tournamentId]);
$available = $available->fetchAll();

$matches = $bdd->prepare("
    SELECT id,round,player_a_id,player_b_id,winner_id
      FROM matches
     WHERE tournament_id=?
     ORDER BY round,id
");
$matches->execute([$tournamentId]);
$matches = $matches->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Admin <?=htmlspecialchars($tour['name'])?></title></head>
<body>
  <h1><?=htmlspecialchars($tour['name'])?></h1>
  <?php if($message):?><p><strong><?=$message?></strong></p><?php endif;?>

  <h2>Participants <?= $locked?'(lock)':'' ?></h2>
  <ul>
    <?php foreach($participants as $p){?>
      <li>
        <?=$p['seed']?> – <?=htmlspecialchars($p['username'])?>
        <?php if(!$locked){?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="del">
            <input type="hidden" name="participant_id" value="<?=$p['id']?>">
            <button>Suppr</button>
          </form>
        <?php } ?>
      </li>
    <?php } ?>
  </ul>
  <?php if(!$locked){?>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <select name="user_id">
        <?php foreach($available as $u){?>
          <option value="<?=$u['id']?>"><?=htmlspecialchars($u['username'])?></option>
        <?php } ?>
      </select>
      <button>Ajouter</button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="generate">
      <button>Générer tour 1</button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="close">
      <button>Clore tournoi</button>
    </form>
  <?php } ?>

  <?php
  $current = 0;
  foreach($matches as $m):
    if($m['round']!==$current):
      $current = $m['round'];
      echo "<h2>Tour {$current}</h2>";
    endif;
    // noms
    $a = $m['player_a_id']
         ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_a_id']}")->fetchColumn()
         : '–';
    $b = $m['player_b_id']
         ? $bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_b_id']}")->fetchColumn()
         : '–';
  ?>
    <form method="post">
      Match #<?=$m['id']?>:
      <button name="action" value="win">
        <input type="hidden" name="match_id" value="<?=$m['id']?>">
        <input type="hidden" name="winner_id" value="<?=$m['player_a_id']?>">
        <?=$a?>
      </button>
      vs
      <button name="action" value="win">
        <input type="hidden" name="match_id" value="<?=$m['id']?>">
        <input type="hidden" name="winner_id" value="<?=$m['player_b_id']?>">
        <?=$b?>
      </button>
      <?php if($m['winner_id']) echo "→ <strong>".htmlspecialchars($m['winner_id']==$m['player_a_id']?$a:$b)."</strong>"; ?>
    </form>
  <?php endforeach; ?>

</body>
</html>
