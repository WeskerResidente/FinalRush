<?php
include("essentiel.php");
include("security.php");
include("nav.php");

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$userId = $_SESSION['user_id'];
$messageUsername = '';
$messageColor = '';
// gestion de l'avatar
$messageAvatar = '';
if (isset($_POST['upload_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['avatar']['tmp_name'];
    $fileName = basename($_FILES['avatar']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $newName = 'user_' . $userId . '_' . time() . '.' . $ext;
        if (!is_dir('uploads/avatars')) mkdir('uploads/avatars', 0777, true);
        if (move_uploaded_file($fileTmp, "uploads/avatars/$newName")) {
            // Met à jour la BDD
            $bdd->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$newName, $userId]);
            $messageAvatar = "<span style='color:green;'>Avatar mis à jour !</span>";
        } else {
            $messageAvatar = "<span style='color:red;'>Erreur lors de l'upload.</span>";
        }
    } else {
        $messageAvatar = "<span style='color:red;'>Format de fichier non autorisé.</span>";
    }
}
// Traitement du formulaire de modification
if (!empty($_POST['username'])) {
    $newUsername = trim($_POST['username']);
    if ($newUsername && $newUsername !== $_SESSION['username']) {
        // Vérifie si le pseudo est déjà pris
        $check = $bdd->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$newUsername]);
        if ($check->fetch()) {
            $message = "<span style='color:red;'>Ce pseudo est déjà utilisé.</span>";
        } else {
            $update = $bdd->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update->execute([$newUsername, $userId]);
            $_SESSION['username'] = $newUsername;
            $messageUsername = "<span style='color:green;'>Pseudo mis à jour !</span>";
        }
    }
}

// Récupère les infos utilisateur
$stmt = $bdd->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// gestion de la couleur du pseudo
if (isset($_POST['color'])) {
    $color = $_POST['color'];
    $updateColor = $bdd->prepare("UPDATE users SET color = ? WHERE id = ?");
    $updateColor->execute([$color, $userId]); 
    $messageColor .= "<span style='color:green;'>Couleur de pseudo mise à jour !</span>";
}

// suppression du compte utilisateur
if (isset($_POST['delete_account'])) {
    // supression de l'utilisateur de la base (et ses participations dnas les tournois)
    $bdd->prepare("DELETE FROM participants WHERE user_id = ?")->execute([$userId]);
    $bdd->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    // détruire la session et rediriger
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil</title>
</head>
<body>
    <h1>Mon profil</h1>

    <!-- Affichage de l'avatar -->
    <div style="margin-bottom:1em;">
        <?php
        // Affiche l'avatar s'il existe, sinon une image par défaut
        $stmtAvatar = $bdd->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmtAvatar->execute([$userId]);
        $avatar = $stmtAvatar->fetchColumn();
        $avatarUrl = ($avatar && file_exists("uploads/avatars/$avatar")) ? "uploads/avatars/$avatar" : "assets/img/default-avatar.png";
        ?>
        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
    </div>

    <!-- Formulaire d'upload d'avatar -->
    
    <!-- gestion du pseudo -->
    <h2>Modifier votre profil</h2>
    <p>Bienvenue, <?= $user['username'] ?> !</p>
    <p>Rôle : <?= $user['role'] ?></p>
    <form method="post">
        <label>Pseudo :</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        <button type="submit" class="btn-profile">Mettre à jour</button>
        <?= $messageUsername ?>
    </form>
    <!-- gestion de la couleur du pseudo -->
    <form method="post">
        <label for="color">Changer la couleur de votre pseudo</label>
        <input type="color" id="color" name="color" required>
        <button type="submit" class="btn-profile">Changer la couleur</button>
        <?= $messageColor ?>
    </form>
    <!-- gestion de l'avatar -->
    <form method="post" enctype="multipart/form-data">
        <label for="avatar">Changer d'avatar :</label>
        <input type="file" name="avatar" id="avatar" accept="image/*">
        <button type="submit" name="upload_avatar" class="btn-profile">Mettre à jour l'avatar</button>
    </form>
    <?php if (!empty($messageAvatar)) echo $messageAvatar; ?>
    <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
    <button type="submit" name="delete_account" class="btn-profile" style="background:#c00;color:#fff;margin-top:2em;">
        Supprimer mon compte
    </button>
</form>
    <p>Si vous supprimez votre compte, toutes vos participations aux tournois seront également supprimées.</p>
</body>
</html>