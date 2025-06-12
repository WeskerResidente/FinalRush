<?php
include("essentiel.php");
include("security.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Refresh:2; url=index.php");
    die("Accès interdit. Redirection vers la page d'accueil dans 2 secondes.");
}

// Pagination utilisateurs
$usersPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $usersPerPage;

$totalUsers = $bdd->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($totalUsers / $usersPerPage);

$stmt = $bdd->prepare("SELECT * FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Pagination tournois
$tournoisPerPage = 6;
$pageTournoi = isset($_GET['page_tournoi']) ? max(1, intval($_GET['page_tournoi'])) : 1;
$offsetTournoi = ($pageTournoi - 1) * $tournoisPerPage;

$totalTournois = $bdd->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$totalPagesTournoi = ceil($totalTournois / $tournoisPerPage);

$stmtTournoi = $bdd->prepare("SELECT * FROM tournaments ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmtTournoi->bindValue(':limit', $tournoisPerPage, PDO::PARAM_INT);
$stmtTournoi->bindValue(':offset', $offsetTournoi, PDO::PARAM_INT);
$stmtTournoi->execute();
$tournaments = $stmtTournoi->fetchAll(PDO::FETCH_ASSOC);

// Suppression utilisateur
if (isset($_GET['delete'])) {
    if ($_GET['delete'] == $_SESSION['user_id']) {
        header("Refresh:2; url=Admin-panel.php");
        die("Vous ne pouvez pas supprimer votre propre compte.");
    } else{
        $id = (int) $_GET['delete'];
        $delete = $bdd->prepare("DELETE FROM users WHERE id = ?");
        $delete->execute([$id]);
        header("Location: Admin-panel.php");
        exit;
    }
}

// Supprimer un tournoi
if (isset($_GET['delete_tournament'])) {
    $tid = (int)$_GET['delete_tournament'];
    // Supprimer les participants liés
    $bdd->prepare("DELETE FROM participants WHERE tournament_id = ?")->execute([$tid]);
    // Supprimer le tournoi
    $bdd->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tid]);
    header("Location: Admin-panel.php");
    exit;
}

// Supprimer un participant d'un tournoi
if (isset($_GET['remove_participant']) && isset($_GET['tournament'])) {
    $uid = (int)$_GET['remove_participant'];
    $tid = (int)$_GET['tournament'];
    $bdd->prepare("DELETE FROM participants WHERE user_id = ? AND tournament_id = ?")->execute([$uid, $tid]);
    header("Location: Admin-panel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des utilisateurs</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        th, td {
            padding: 10px;
            border: 1px solid #aaa;
            text-align: center;
        }
        h1 {
            text-align: center;
        }
        a.delete {
            color: red;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>
  <div class="admin-panel">
    <h1>Liste des utilisateurs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <a href="Admin-panel.php?delete=<?= $user['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>" class="delete" onclick="return confirm('Supprimer cet utilisateur ?');">🗑 Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div style="text-align:center; margin:20px;">
            <?php if ($page > 1): ?>
                <a href="Admin-panel.php?page=<?= $page - 1 ?>&page_tournoi=<?= $pageTournoi ?>" style="margin-right:10px;">&laquo; Précédent</a>
            <?php endif; ?>
            Page <?= $page ?> / <?= $totalPages ?>
            <?php if ($page < $totalPages): ?>
                <a href="Admin-panel.php?page=<?= $page + 1 ?>&page_tournoi=<?= $pageTournoi ?>" style="margin-left:10px;">Suivant &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h1>Liste des tournois</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Date de début</th>
                <th>Créé le</th>
                <th>Participants</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($tournaments as $tournament):
            // Récupérer les participants de ce tournoi
            $stmtP = $bdd->prepare("SELECT users.id, users.username 
                                            FROM participants 
                                            JOIN users ON participants.user_id = users.id 
                                            WHERE participants.tournament_id = ?");
            $stmtP->execute([$tournament['id']]);
            $participants = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <tr>
                <td><?= htmlspecialchars($tournament['id']) ?></td>
                <td><?= htmlspecialchars($tournament['name']) ?></td>
                <td><?= htmlspecialchars($tournament['start_date']) ?></td>
                <td><?= htmlspecialchars($tournament['created_at']) ?></td>
                <td>
                    <?php if ($participants): ?>
                        <ul style="padding-left:15px;">
                        <?php foreach ($participants as $p): ?>
                            <li>
                                <?= htmlspecialchars($p['username']) ?>
                                <a href="Admin-panel.php?remove_participant=<?= $p['id'] ?>&tournament=<?= $tournament['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>" class="delete" onclick="return confirm('Retirer ce participant ?');" title="Supprimer ce participant">❌</a>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        Aucun
                    <?php endif; ?>
                </td>
                <td>
                    <a href="Admin-panel.php?delete_tournament=<?= $tournament['id'] ?>&page=<?= $page ?>&page_tournoi=<?= $pageTournoi ?>" class="delete" onclick="return confirm('Supprimer ce tournoi ?');">🗑 Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPagesTournoi > 1): ?>
        <div style="text-align:center; margin:20px;">
            <?php if ($pageTournoi > 1): ?>
                <a href="Admin-panel.php?page_tournoi=<?= $pageTournoi - 1 ?>&page=<?= $page ?>" style="margin-right:10px;">&laquo; Précédent</a>
            <?php endif; ?>
            Page <?= $pageTournoi ?> / <?= $totalPagesTournoi ?>
            <?php if ($pageTournoi < $totalPagesTournoi): ?>
                <a href="Admin-panel.php?page_tournoi=<?= $pageTournoi + 1 ?>&page=<?= $page ?>" style="margin-left:10px;">Suivant &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</body>
</html>