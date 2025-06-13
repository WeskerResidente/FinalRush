<?php

include("essentiel.php");
include("security.php");
include("nav.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Refresh:2; url=index.php");
    die("Accès interdit. Redirection vers la page d'accueil dans 2 secondes.");
}

// affiche max 10 user par page
$usersPerPage = 10;
$page         = max(1, (int)($_GET['page']         ?? 1));
$offset       = ($page - 1) * $usersPerPage;
$totalUsers   = (int)$bdd->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages   = ceil($totalUsers / $usersPerPage);

$stmtU = $bdd->prepare(
    "SELECT id, username, role
       FROM users
      ORDER BY id ASC
      LIMIT :l OFFSET :o"
);
$stmtU->bindValue(':l', $usersPerPage, PDO::PARAM_INT);
$stmtU->bindValue(':o', $offset,       PDO::PARAM_INT);
$stmtU->execute();
$users = $stmtU->fetchAll(PDO::FETCH_ASSOC);

// suppression utilisateur
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid !== $_SESSION['user_id']) {
        $bdd->prepare("DELETE FROM users WHERE id = ?")
            ->execute([$uid]);
    }
    header("Location: Admin-panel.php?page={$page}&page_tournoi={$pageTournoi}");
    exit;
}

// max 6 tournois par page
$tournoisPerPage  = 6;
$pageTournoi      = max(1, (int)($_GET['page_tournoi'] ?? 1));
$offsetTournoi    = ($pageTournoi - 1) * $tournoisPerPage;
$totalTournois    = (int)$bdd->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$totalPagesTournoi= ceil($totalTournois / $tournoisPerPage);

$stmtT = $bdd->prepare("
  SELECT t.id, t.name, t.start_date, t.created_at, g.name AS game_name
    FROM tournaments t
LEFT JOIN games g ON g.id = t.game_id
   ORDER BY t.id DESC
   LIMIT :l OFFSET :o
");
$stmtT->bindValue(':l', $tournoisPerPage, PDO::PARAM_INT);
$stmtT->bindValue(':o', $offsetTournoi,   PDO::PARAM_INT);
$stmtT->execute();
$tournaments = $stmtT->fetchAll(PDO::FETCH_ASSOC);

// suppression tournoi
if (isset($_GET['delete_tournament'])) {
    $tid = (int)$_GET['delete_tournament'];
    $bdd->prepare("DELETE FROM participants WHERE tournament_id = ?")->execute([$tid]);
    $bdd->prepare("DELETE FROM tournaments WHERE id = ?")            ->execute([$tid]);
    header("Location: Admin-panel.php?page={$page}&page_tournoi={$pageTournoi}");
    exit;
}

// gestion des jeux
$messageGame = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $gname = trim($_POST['game_name'] ?? '');
    if ($gname !== '') {
        try {
            $bdd->prepare("INSERT INTO games(name) VALUES(?)")
                ->execute([$gname]);
            $messageGame = "Jeu « {$gname} » ajouté.";
        } catch (PDOException $e) {
            $messageGame = "Erreur : ce jeu existe déjà.";
        }
    }
}
if (isset($_GET['delete_game'])) {
    $gid = (int)$_GET['delete_game'];
    $bdd->prepare("DELETE FROM games WHERE id = ?")->execute([$gid]);
    header("Location: Admin-panel.php?page={$page}&page_tournoi={$pageTournoi}");
    exit;
}
$games = $bdd->query("SELECT id, name FROM games ORDER BY name ASC")
             ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin-Panel — FinalRush</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>


  <div class="admin-panel">

    <!-- affichage user -->
    <section>
      <h2>Utilisateurs</h2>
      <table>
        <thead>
          <tr><th>ID</th><th>Pseudo</th><th>Rôle</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($u['role'],     ENT_QUOTES) ?></td>
            <td>
              <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <a href="?delete_user=<?= $u['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>"
                   class="delete"
                   onclick="return confirm('Supprimer cet utilisateur ?');"
                >🗑</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&page_tournoi=<?= $pageTournoi ?>">&laquo; Précédent</a>
          <?php endif; ?>
          Page <?= $page ?> / <?= $totalPages ?>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&page_tournoi=<?= $pageTournoi ?>">Suivant &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- affichage tournois -->
    <section>
      <h2>Tournois</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Nom</th><th>Jeu</th>
            <th>Début</th><th>Créé le</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tournaments as $t): ?>
          <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['name'],      ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($t['game_name'], ENT_QUOTES) ?></td>
            <td><?= $t['start_date'] ?></td>
            <td><?= $t['created_at'] ?></td>
            <td>
              <a href="?delete_tournament=<?= $t['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>"
                 class="delete"
                 onclick="return confirm('Supprimer ce tournoi ?');"
              >🗑</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($totalPagesTournoi > 1): ?>
        <div class="pagination">
          <?php if ($pageTournoi > 1): ?>
            <a href="?page_tournoi=<?= $pageTournoi-1 ?>&page=<?= $page ?>">&laquo; Précédent</a>
          <?php endif; ?>
          Page <?= $pageTournoi ?> / <?= $totalPagesTournoi ?>
          <?php if ($pageTournoi < $totalPagesTournoi): ?>
            <a href="?page_tournoi=<?= $pageTournoi+1 ?>&page=<?= $page ?>">Suivant &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- affichage jeux -->
    <section>
      <h2>Jeux</h2>
      <?php if ($messageGame): ?>
        <p class="message succes"><?= htmlspecialchars($messageGame, ENT_QUOTES) ?></p>
      <?php endif; ?>
      <form method="post" class="form-inline">
        <input type="text" name="game_name" placeholder="Nom du jeu…" required>
        <button type="submit" name="add_game" class="button">➕ Ajouter</button>
      </form>
      <ul class="game-list">
        <?php foreach ($games as $g): ?>
          <li>
            <?= htmlspecialchars($g['name'], ENT_QUOTES) ?>
            <a href="?delete_game=<?= $g['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>"
               class="delete"
               onclick="return confirm('Supprimer ce jeu ?');"
            >🗑</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

  </div>
</body>
</html>
