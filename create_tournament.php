<?php
include("essentiel.php");
include("security.php");
include("nav.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Refresh:2; url=index.php");
    die("Accès interdit. Redirection vers la page d'accueil dans 2 secondes.");
}
// on définit l'heure par défaut pour la création du tournoi a l'heure locale de paris 
date_default_timezone_set('Europe/Paris');
if (!empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['date']) && !empty($_POST['max_players'])) {
    $name = htmlspecialchars($_POST['name']);
    $desc = htmlspecialchars($_POST['description']);
    $date = htmlspecialchars($_POST['date']);
    $gameId = (int) ($_POST['game_id'] ?? 0);
    $formatedDate = date('Y-m-d H:i:s', strtotime($date));
    $createdAt = date('Y-m-d H:i:s');
    $maxPlayers = (int) $_POST['max_players'];
    $requestCreate = $bdd->prepare('INSERT INTO tournaments(name,description,start_date,created_at, max_players)
                                                VALUES(?,?,?,?,?)');
    $dataCreate = $requestCreate->execute(array($name,$desc,$formatedDate,$createdAt, $maxPlayers));
    $requestCreate = $bdd->prepare(
  'INSERT INTO tournaments(name, description, start_date, created_at, max_players, game_id)
   VALUES(?,?,?,?,?,?)'
  );
  $requestCreate->execute([
  $name, $desc, $formatedDate, $createdAt, $maxPlayers, $gameId
  ]);

    // header('location:Index.php.php');
    if ($dataCreate) {
    $messageSuccess = "Tournoi ajouté avec succès !";
    } else {
        $messageError   = "Erreur lors de la création du tournoi.";
    }
  }
// récupération des utilisateurs afin de pouvoir les afficher et les rajouter dans la table des tournois
$requestSelect = $bdd->prepare('SELECT * FROM users');
$games = $bdd->query("SELECT id,name FROM games ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php if (!empty($messageError)): ?>
    <div class="message erreur"><?= htmlspecialchars($messageError) ?></div>
  <?php endif; ?>
  <?php if (!empty($messageSuccess)): ?>
    <div class="message succes"><?= htmlspecialchars($messageSuccess) ?></div>
  <?php endif; ?>
<section class="form-create-tournament">
  <h2>Créer un tournoi</h2>
  <form action="create_tournament.php" method="post">
    <div class="form-item-create-tournament">
      <label for="max_players">Nombre max de joueurs :</label>
      <input
        type="number"
        id="max_players"
        name="max_players"
        min="2"
        max="128"
        value="8"
        required>
    </div>
    <div class="form-item-create-tournament">
      <label for="name">Nom du tournoi :</label>
      <input type="text" id="name" name="name" required>
    </div>
    <div class="form-item-create-tournament">
      <label for="game_id">Jeu :</label>
      <select name="game_id" id="game_id" required>
        <?php foreach($games as $g): ?>
          <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-item-create-tournament">
      <label for="description">Ajouter la description :</label>
      <!-- on passe à un vrai textarea -->
      <textarea id="description" name="description" rows="4" required></textarea>
    </div>

    <div class="form-item-create-tournament">
      <label for="date">Date du tournoi :</label>
      <input type="datetime-local" id="date" name="date" required>
    </div>

    <div class="form-submit-create-tournament">
      <button type="submit">Créer</button>
    </div>

  </form>
</section>
</body>
</html>
