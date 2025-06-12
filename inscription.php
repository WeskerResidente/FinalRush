<?php
include("essentiel.php");
include("nav.php");

// Initialisation des messages
$messageSuccess = '';
$messageError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération et nettoyage des champs
    $username        = trim($_POST['username'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Vérification des champs obligatoires
    if ($username === '' || $password === '') {
        $messageError = "Les champs « Pseudo » et « Mot de passe » sont obligatoires.";
    }
    // Vérification de la confirmation
    elseif ($password !== $passwordConfirm) {
        $messageError = "Les mots de passe ne correspondent pas.";
    }
    else {
        // Hashage sécurisé du mot de passe
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);

        $sql = "
            INSERT INTO users (username, password, role)
            VALUES (:username, :password, 'player')
        ";
        $stmt = $bdd->prepare($sql);

        try {
            $stmt->execute([
                ':username' => htmlspecialchars($username, ENT_QUOTES),
                ':password' => $hashedPwd,
            ]);
            $messageSuccess = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } catch (\PDOException $e) {
            // Gestion d’erreur (doublon pseudo)
            if ($e->getCode() === '23000') {
                $messageError = "Ce pseudo est déjà utilisé.";
            } else {
                $messageError = "Erreur lors de l’inscription : " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription Joueur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="page-inscription">
    <h2>Inscription Joueur</h2>

    <?php if ($messageError): ?>
      <div class="message erreur"><?= htmlspecialchars($messageError, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($messageSuccess): ?>
      <div class="message succes"><?= htmlspecialchars($messageSuccess, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form action="inscription.php" method="post">
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
          placeholder="Entrez un mot de passe"
          required
        >
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirmez le mot de passe :</label><br>
        <input
          type="password"
          id="password_confirm"
          name="password_confirm"
          placeholder="Confirmez votre mot de passe"
          required
        >
      </div>

      <div class="form-group">
        <button type="submit" class="btn-enregistrer">S’inscrire</button>
      </div>
    </form>
  </div>
</body>
</html>
