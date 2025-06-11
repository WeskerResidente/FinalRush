<?php
include("essentiel.php");
include("security.php");
include("nav.php");

$requestSelect = $bdd->prepare('SELECT * FROM tournaments ORDER BY created_at DESC');
$requestSelect->execute();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lites des tournois</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <section id="tournament-index">
        <div class="tournament-heading-index">
            <h2>Listes des dernier tournois ayant été ajouté :</h2>
        </div>
        <a href="#tournamentID-6">lien</a>
        <div class="tournament-list-index">
            <?php while ($tournament = $requestSelect->fetch()): ?>
                <div class="tournament-item-index" id="tournamentID-<?= $tournament['id']?>">
                    <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                    <p><?= htmlspecialchars($tournament['description']) ?></p>
                    <p>Date de début : <?= htmlspecialchars($tournament['start_date']) ?></p>
                    <p>Créé le : <?= htmlspecialchars($tournament['created_at']) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
</body>
</html>
