<?php
include("essentiel.php");
include("nav.php");

// Si l'utilisateur est déjà connecté, on le redirige
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$messageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $messageError = "Veuillez renseigner votre pseudo et votre mot de passe.";
    } else {
        // Récupérer l'utilisateur par pseudo
        $sql = 'SELECT id, username, password, role FROM users WHERE username = :username';
        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            ':username' => htmlspecialchars($username, ENT_QUOTES)
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe
        if ($user && password_verify($password, $user['password'])) {
            // Régénérer l'ID de session pour éviter la fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['color'] = $user['color'];

            header('Location: index.php');
            exit;
        } else {
            $messageError = "Pseudo ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="page-connexion">
    <h2>Connexion</h2>

    <?php if ($messageError): ?>
      <div class="message erreur"><?= htmlspecialchars($messageError, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form action="connexion.php" method="post">
      <div class="form-group">
        <label for="username">Pseudo :</label><br>
        <input
          type="text"
          id="username"
          name="username"
          placeholder="Entrez votre pseudo"
          required
          value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-group">
        <label for="password">Mot de passe :</label><br>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Entrez votre mot de passe"
          required
        >
      </div>

      <div class="form-group">
        <button type="submit" class="btn-enregistrer">Se connecter</button>
      </div>
    </form>
  </div>
</body>
</html>
