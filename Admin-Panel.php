<?php
include("essentiel.php");
include("security.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Refresh:2; url=index.php");
    die("Accès interdit. Redirection vers la page d'accueil dans 2 secondes.");
}
// Récupération de tous les utilisateurs
$users = $bdd->query("SELECT * 
                                FROM users 
                                ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php

// Suppression si paramètre "delete" en GET
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
                    <a href="Admin-panel.php?delete=<?= $user['id'] ?>" class="delete" onclick="return confirm('Supprimer cet utilisateur ?');">🗑 Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>