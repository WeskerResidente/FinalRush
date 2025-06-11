<?php session_start(); ?>
<?php ob_start(); ?>

<?php 
     $bdd = new PDO('mysql:host=mysql;dbname=FinalRush;charset=utf8','root','root');
     $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
?>
