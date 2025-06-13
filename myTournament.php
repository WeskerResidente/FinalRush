<?php 
include("essentiel.php");
include("security.php");
include("nav.php");

if (isset($_POST['leave'], $_POST['tournament_id'])) {
    $userId = $_SESSION['user_id'];
    $tournamentId = intval($_POST['tournament_id']);
    $stmt = $bdd->prepare('DELETE FROM participants WHERE user_id = ? AND tournament_id = ?');
    $stmt->execute([$userId, $tournamentId]);
    header("Location: myTournament.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
button.delete {
    color: red;
    text-decoration: none;
    font-weight: bold;
    border: none;
    background: none;
    cursor: pointer;
}
    </style>
</head>
<body>
    <div class="my-tournaments">
    <h1>Mes tournois</h1>
    <ul>
    <?php
    $userId = $_SESSION['user_id'];
    $requestSelect = $bdd->prepare('SELECT t.id, t.name, t.start_date, t.is_closed
        FROM tournaments t
        INNER JOIN participants p ON t.id = p.tournament_id
        WHERE p.user_id = ?
        ORDER BY t.start_date DESC');
        
    $requestSelect->execute([$userId]);
    $tournaments = $requestSelect->fetchAll();

        if (empty($tournaments)) {
            echo "<li>Vous n'êtes inscrit à aucun tournoi.</li>";
        } else {?>
            <?php foreach ($tournaments as $t): ?>
                <li>
                    <strong><?= htmlspecialchars($t['name']) ?></strong> (début : <?= htmlspecialchars($t['start_date']) ?>)
                    <br>
                    <?php if ($t['is_closed'] == 1): ?>
                        <p class="tournament-closed">Ce tournois est terminé</p>
                    <?php endif; ?>
                    <form method="post" action="" style="display:inline">
                        <input type="hidden" name="tournament_id" value="<?= intval($t['id']) ?>">
                        <button type="submit" name="leave" class="delete" onclick="return confirm('Se désinscrire de ce tournoi ?');">❌ Se désinscrire</button>
                    </form>
                    <a href="participant.php?tournament_id=<?= $t['id'] ?>">Consulter</a>
                </li>
            <?php endforeach;} ?>
        
    </ul>
    </div>
</body>
</html>