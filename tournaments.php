<?php
include("essentiel.php");
include("nav.php");

function e($str) {
    return htmlspecialchars($str);
}

$messageSucces = '';
$messageErreur = '';
$uniqueName = null;

if (isset($_POST['creerSort'])) {
    $sortName  = trim($_POST['nom'] ?? '');
    $elementId = isset($_POST['element_id']) ? (int)$_POST['element_id'] : 0;
    $idUser    = $_SESSION['user_id'] ?? null;

    if ($sortName === '') {
        $messageErreur = "Le champ « Nom du sort » est obligatoire.";
    } elseif ($elementId <= 0) {
        $messageErreur = "Vous devez choisir un élément.";
    } elseif (is_null($idUser)) {
        $messageErreur = "Vous devez être connecté pour ajouter un sort.";
    } else {
        $stmtCheckElem = $bdd->prepare("SELECT COUNT(*) FROM elements WHERE id = ?");
        $stmtCheckElem->execute([$elementId]);
        if ($stmtCheckElem->fetchColumn() == 0) {
            $messageErreur = "L’élément sélectionné n’existe pas.";
        } else {
            if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
                $imageInfo = pathinfo($_FILES['upload']['name']);
                $imageExt  = strtolower($imageInfo['extension']);
                $authorizedExt = ['png','jpeg','jpg','webp','bmp','svg'];

                if (in_array($imageExt, $authorizedExt, true)) {
                    $uniqueName  = time() . '_' . rand(1000, 9999) . '.' . $imageExt;
                    $destination = __DIR__ . "/assets/img/" . $uniqueName;
                    if (!move_uploaded_file($_FILES['upload']['tmp_name'], $destination)) {
                        $messageErreur = "Impossible de déplacer le fichier image.";
                    }
                } else {
                    $messageErreur = "Format de fichier non autorisé (png, jpg, webp, bmp, svg).";
                }
            }

            if ($messageErreur === '') {
                $sql = "
                    INSERT INTO codex (nom, image, created_id, element_id)
                    VALUES (:nom, :image, :created_id, :element_id)
                ";
                $stmt = $bdd->prepare($sql);
                $stmt->bindValue(':nom',           $sortName);
                $stmt->bindValue(':image',         $uniqueName);
                $stmt->bindValue(':created_id',    $idUser);
                $stmt->bindValue(':element_id',    $elementId);

                if ($stmt->execute()) {
                    $messageSucces = "Le sort « " . e($sortName) . " » a bien été ajouté au codex.";
                } else {
                    $messageErreur = "Erreur lors de l’insertion en base. Veuillez réessayer.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un sort</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="page-ajout-monstre">
    <h3>Ajouter un sort au codex</h3>

    <?php if ($messageErreur): ?>
      <div class="message erreur"><?= e($messageErreur) ?></div>
    <?php endif; ?>

    <?php if ($messageSucces): ?>
      <div class="message succes"><?= e($messageSucces) ?></div>
    <?php endif; ?>

    <form action="ajoutcodex.php" method="post" enctype="multipart/form-data">
      <div>
        <label for="nom">Nom du sort :</label><br>
        <input type="text" id="nom" name="nom" required
               value="<?= e($_POST['nom'] ?? '') ?>">
      </div>

      <div>
        <label for="element">Élément :</label><br>
        <select id="element" name="element_id" required>
          <option value="">-- Sélectionner un élément --</option>
          <?php
          $req = $bdd->query("SELECT id, type FROM elements ORDER BY type");
          while ($el = $req->fetch(PDO::FETCH_ASSOC)):
            $selected = (isset($_POST['element_id']) && $_POST['element_id'] == $el['id'])
                        ? 'selected' : '';
          ?>
            <option value="<?= e($el['id']) ?>" <?= $selected ?>>
              <?= e($el['type']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label for="upload">Image du sort (optionnel) :</label><br>
        <input type="file" id="upload" name="upload"
               accept=".png,.jpg,.jpeg,.webp,.bmp,.svg">
      </div>

      <div style="margin-top: 10px;">
        <button type="submit" name="creerSort">Envoyer</button>
      </div>
    </form>
  </div>
</body>
</html>
