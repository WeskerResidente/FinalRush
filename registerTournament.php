<?php 
include("essentiel.php");
include("security.php");
include("nav.php");

// on récupère l'id depuis l'URL GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération du tournoi
$requestSelect = $bdd->prepare('SELECT * FROM tournaments WHERE id = :id');
$requestSelect->execute(['id' => $id]);
$tournament = $requestSelect->fetch();

// Récupération du pseudo de l'utilisateur connecté
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
    if ($formUsername !== $username) {
        echo "<p style='color:red;'>Vous ne pouvez vous inscrire qu'avec votre propre pseudo !</p>";
    } else {
        // Vérification si l'utilisateur existe
        $userCheck = $bdd->prepare('SELECT id FROM users WHERE username = :username');
        $userCheck->execute(['username' => $username]);
        $user = $userCheck->fetch();
        $tournament_id = $id;
        $user_id = $_SESSION['user_id'];

        if ($user) {
            // Vérifier si déjà inscrit
            $already = $bdd->prepare('SELECT id FROM participants WHERE tournament_id = ? AND user_id = ?');
            $already->execute([$tournament_id, $user_id]);
            if ($already->fetch()) {
                echo "<p style='color:orange;'>Vous êtes déjà inscrit à ce tournoi.</p>";
            } else {
                // Insertion de l'inscription
                $insertRegistration = $bdd->prepare('INSERT INTO participants (tournament_id, user_id) VALUES (?,?)');
                $insertRegistration->execute([$tournament_id, $user_id]);
                echo "<p style='color:green;'>Inscription réussie pour le tournoi : " . htmlspecialchars($tournament['name']) . "</p>";
            }
        } else {
            echo "<p style='color:red;'>Utilisateur non trouvé. Veuillez vérifier votre session.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S'inscrire au tournoi</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <section class="register-tournament">
        <div class="register-tournament-heading">
            <h2>Inscription au tournoi : <?= htmlspecialchars($tournament['name']) ?></h2>
            <p>Date de début du tournoi : <?= htmlspecialchars($tournament['start_date']) ?></p>
        </div>
        <form action="registerTournament.php?id=<?= $id ?>" method="post" class="register-tournament-form">
            <div class="form-item-register-tournament">
                <label for="username">Pseudo :</label>
                <input type="text" id="username" name="username" value="" required>
            </div>
            <div class="form-submit-register-tournament">
                <button type="submit">S'inscrire</button>
            </div>
        </form>
    </section>
</body>
</html>