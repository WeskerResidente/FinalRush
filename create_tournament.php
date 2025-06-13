<?php
include("essentiel.php");
include("security.php");
include("nav.php");

date_default_timezone_set('Europe/Paris');
// 1) Seul l’admin peut créer un tournoi
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Refresh:2; url=index.php");
    die("Accès interdit. Redirection vers la page d'accueil dans 2 secondes.");
}

// 2) Récupérer la liste des jeux pour le <select>
$games = $bdd
  ->query("SELECT id, name FROM games ORDER BY name ASC")
  ->fetchAll(PDO::FETCH_ASSOC);

// Messages à afficher
$error   = "";
$success = "";

// 3) Soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? "");
    $desc        = trim($_POST['description'] ?? "");
    $rawDate     = trim($_POST['date']        ?? "");
    $maxPlayers  = (int)($_POST['max_players'] ?? 0);
    $gameId      = (int)($_POST['game_id']     ?? 0);

    // Validation simple
    if ($name === "" || $desc === "" || $rawDate === "" || $maxPlayers < 2 || $gameId <= 0) {
        $error = "Tous les champs sont obligatoires et le nombre de joueurs ≥ 2.";
    } else {
        // formater la date en MySQL DATETIME
        $startDate = date('Y-m-d H:i:s', strtotime($rawDate));
        $createdAt = date('Y-m-d H:i:s');

        // Préparer l’INSERT
        $sql = "
          INSERT INTO tournaments
            (name, description, start_date, created_at, max_players, game_id)
          VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $bdd->prepare($sql);
        try {
            $stmt->execute([
                $name,
                $desc,
                $startDate,
                $createdAt,
                $maxPlayers,
                $gameId
            ]);
            $success = "Tournoi « {$name} » ajouté avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur à l’insertion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer un tournoi</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="form-create-tournament">
    <h2>Créer un tournoi</h2>

    <?php if ($error): ?>
      <div class="message erreur"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="message succes"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form action="create_tournament.php" method="post">
      <div class="form-item-create-tournament">
        <label for="game_id">Jeu :</label>
        <select id="game_id" name="game_id" required>
          <option value="">— Sélectionnez un jeu —</option>
          <?php foreach ($games as $g): ?>
            <option value="<?= $g['id'] ?>"
              <?= isset($_POST['game_id']) && (int)$_POST['game_id'] === (int)$g['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($g['name'], ENT_QUOTES) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-item-create-tournament">
        <label for="max_players">Nombre max de joueurs :</label>
        <input
          type="number"
          id="max_players"
          name="max_players"
          min="2"
          max="128"
          required
          value="<?= htmlspecialchars($_POST['max_players'] ?? 8, ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-item-create-tournament">
        <label for="name">Nom du tournoi :</label>
        <input
          type="text"
          id="name"
          name="name"
          required
          value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-item-create-tournament">
        <label for="description">Description :</label>
        <textarea
          id="description"
          name="description"
          rows="4"
          required
        ><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES) ?></textarea>
      </div>

      <div class="form-item-create-tournament">
        <label for="date">Date et heure de début :</label>
        <input
          type="datetime-local"
          id="date"
          name="date"
          required
          value="<?= htmlspecialchars($_POST['date'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-submit-create-tournament">
        <button type="submit">Créer</button>
      </div>
    </form>
  </section>
</body>
</html>
