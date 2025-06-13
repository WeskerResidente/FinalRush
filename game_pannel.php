<?php
include("essentiel.php");
include("security.php");
if ($_SESSION['role']!=='admin') die("Accès interdit.");

$message = '';
// Ajout
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])) {
  $n = trim($_POST['name']);
  if ($n!=='') {
    $stmt = $bdd->prepare("INSERT INTO games(name) VALUES(?)");
    try {
      $stmt->execute([$n]);
      $message = "Jeu ajouté.";
    } catch (\PDOException $e) {
      $message = "Erreur ou jeu déjà existant.";
    }
  }
}
// Récupérer la liste
$games = $bdd->query("SELECT * FROM games ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!— small UI —>
<h2>Gestion des jeux</h2>
<?= $message ? "<p>$message</p>" : "" ?>
<form method="post">
  <input name="name" placeholder="Nom du jeu">
  <button name="add">➕ Ajouter</button>
</form>
<ul>
<?php foreach($games as $g): ?>
  <li>
    <?= htmlspecialchars($g['name']) ?>
    <!-- Pour la suppression, un petit lien ou formulaire "del" -->
  </li>
<?php endforeach; ?>
</ul>
