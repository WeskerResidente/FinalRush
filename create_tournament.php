<?php
include("essentiel.php");
include("nav.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$messageSucces = '';
$messageErreur = '';

if (isset($_POST['creerTournoi'])) {
    // Récupération et nettoyage des champs
    $nomTournoi   = trim($_POST['nom_tournoi']   ?? '');
    $dateDebut    = trim($_POST['date_debut']    ?? '');
    $dateFin      = trim($_POST['date_fin']      ?? '');
    $idUser       = $_SESSION['user_id']         ?? null;

    // Vérification des champs obligatoires
    if ($nomTournoi === '') {
        $messageErreur = "Le champ « Nom du tournoi » est obligatoire.";
    } elseif ($dateDebut === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateDebut)) {
        $messageErreur = "La date de début est obligatoire et doit être au format AAAA-MM-JJ.";
    } elseif ($dateFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFin)) {
        $messageErreur = "La date de fin doit être au format AAAA-MM-JJ.";
    } elseif (is_null($idUser)) {
        $messageErreur = "Vous devez être connecté pour créer un tournoi.";
    } else {
        // Tout est ok : insertion en base
        $sql = "
            INSERT INTO tournaments
              (name, description, start_date, end_date, created_by, created_at)
            VALUES
              (:name, :description, :start_date, :end_date, :created_by, NOW())
        ";
        $stmt = $bdd->prepare($sql);
        $stmt->bindValue(':name',        htmlspecialchars($nomTournoi, ENT_QUOTES), PDO::PARAM_STR);
        $stmt->bindValue(':description', htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES), PDO::PARAM_STR);
        $stmt->bindValue(':start_date',  $dateDebut,   PDO::PARAM_STR);
        // Si date_fin vide, on envoie NULL
        if ($dateFin === '') {
            $stmt->bindValue(':end_date', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':end_date', $dateFin, PDO::PARAM_STR);
        }
        $stmt->bindValue(':created_by',  $idUser, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $messageSucces = "Le tournoi « " . e($nomTournoi) . " » a bien été créé.";
        } else {
            $messageErreur = "Erreur lors de l’insertion du tournoi en base. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<form action="create_tournament.php" method="post">
  <input type="hidden" name="creerTournoi" value="1">

  <div class="form-group">
    <label for="nom_tournoi">Nom du tournoi :</label><br>
    <input
      type="text"
      id="nom_tournoi"
      name="nom_tournoi"
      required
      value="<?= htmlspecialchars($_POST['nom_tournoi'] ?? '', ENT_QUOTES) ?>"
    >
  </div>

  <div class="form-group">
    <label for="description">Description (optionnel) :</label><br>
    <textarea
      id="description"
      name="description"
    ><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES) ?></textarea>
  </div>

  <div class="form-group">
    <label for="date_debut">Date de début :</label><br>
    <input
      type="date"
      id="date_debut"
      name="date_debut"
      required
      value="<?= htmlspecialchars($_POST['date_debut'] ?? '', ENT_QUOTES) ?>"
    >
  </div>

  <button type="submit" class="btn-enregistrer">Créer le tournoi</button>
</form>

<?php if ($messageErreur): ?>
  <div class="message erreur"><?= htmlspecialchars($messageErreur, ENT_QUOTES) ?></div>
<?php endif; ?>

<?php if ($messageSucces): ?>
  <div class="message succes"><?= htmlspecialchars($messageSucces, ENT_QUOTES) ?></div>
<?php endif; ?>

</body>
</html>
