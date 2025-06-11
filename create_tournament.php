<?php
include("essentiel.php");
include("nav.php");
// on définit l'heure par défaut pour la création du tournoi a l'heure locale de paris 
date_default_timezone_set('Europe/Paris');
if (!empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['date'])) {
    $name = htmlspecialchars($_POST['name']);
    $desc = htmlspecialchars($_POST['description']);
    $date = htmlspecialchars($_POST['date']);
    $createdAt = date('Y-m-d H:i:s');

    $requestCreate = $bdd->prepare('INSERT INTO tournaments(name,description,start_date,created_at)
                                                VALUES(?,?,?,?)');
    $dataCreate = $requestCreate->execute(array($name,$desc,$date,$createdAt));


    // header('location:Index.php.php');
    echo "tournois ajouté avec succès";
  }

// récupération des utilisateurs afin de pouvoir les afficher et les rajouter dans la table des tournois
$requestSelect = $bdd->prepare('SELECT * FROM users');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
  <section class="form-create-tournament">
    <h2>Créer un tournoi</h2>
    <form action="create_tournament.php" method="post">
        <div class="form-item-create-tournament">
            <label for="name">Nom du tournoi :</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-item-create-tournament">
            <label for="description">Ajouter la description :</label>
            <input type="textarea" name="description" required>
        </div>
        <div class="form-item-create-tournament">
            <label for="date">Date du tournoi :</label>
            <input type="date" id="date" name="date" required>
        </div>
        <div class="form-submit-create-tournament">
          <button>Créer</button>
        </div>
  </section>
</body>
</html>
