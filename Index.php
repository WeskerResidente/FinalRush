<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("essentiel.php");
include("security.php");
include("nav.php");
?>
<?php 
$requestSelect = $bdd->prepare('SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 3');
$requestSelect->execute();
var_dump($requestSelect->errorInfo());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>
    <section id="tournament-index">
        <div class="tournament-heading-index">
            <h2>Listes des dernier tournois ayant été ajouté :</h2>
        </div>
        <div class="tournament-list-index">
            <?php while ($tournament = $requestSelect->fetch()): ?>
                <div class="tournament-item-index">
                    <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                    <p><?= htmlspecialchars($tournament['description']) ?></p>
                    <p>Date de début : <?= htmlspecialchars($tournament['start_date']) ?></p>
                    <p>Créé le : <?= htmlspecialchars($tournament['created_at']) ?></p>
                    <a href="tournaments.php#tournamentID-<?= $tournament['id'] ?>">Voir les détails</a>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
</body>
</html>