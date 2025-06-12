<?php
include("essentiel.php");
include("security.php");
$isAdmin=isset($_SESSION['role'])&&$_SESSION['role']==='admin';
$t=(int)($_GET['tournament_id']??0);
$stmtT=$bdd->prepare("SELECT name,is_closed FROM tournaments WHERE id=?");$stmtT->execute([$t]);
$tourney=$stmtT->fetch()?:die("Tournoi introuvable.");$locked=(bool)$tourney['is_closed'];
$pStmt=$bdd->prepare("SELECT p.id,p.seed,u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.tournament_id=? ORDER BY p.seed");
$aStmt=$bdd->prepare("SELECT id,username FROM users WHERE id NOT IN(SELECT user_id FROM participants WHERE tournament_id=?) ORDER BY username");
$pStmt->execute([$t]);$participants=$pStmt->fetchAll();
$aStmt->execute([$t]);$available=$aStmt->fetchAll();
$numP=count($participants);
$slots=pow(2,ceil(log(max($numP,2),2)));
$maxR=(int)log($slots,2);
if($_SERVER['REQUEST_METHOD']==='POST'&&$isAdmin&&!$locked){
 $act=$_POST['action']??'';
 if($act==='add'){
  $uid=(int)$_POST['user_id'];
  $chk=$bdd->prepare("SELECT 1 FROM participants WHERE tournament_id=? AND user_id=?");$chk->execute([$t,$uid]);
  if(!$chk->fetch()){
   $s=$bdd->prepare("SELECT COALESCE(MAX(seed),0)+1 FROM participants WHERE tournament_id=?");$s->execute([$t]);
   $seed=$s->fetchColumn();
   $bdd->prepare("INSERT INTO participants(tournament_id,user_id,seed)VALUES(?,?,?)")->execute([$t,$uid,$seed]);
  }
 }
 if($act==='del'){
  $pid=(int)$_POST['participant_id'];
  $bdd->prepare("DELETE FROM participants WHERE id=? AND tournament_id=?")->execute([$pid,$t]);
  $bdd->exec("SET @s=0");
  $bdd->prepare("UPDATE participants SET seed=(@s:=@s+1) WHERE tournament_id=? ORDER BY seed")->execute([$t]);
 }
 if($act==='generate'){
  $bdd->prepare("DELETE FROM matches WHERE tournament_id=?")->execute([$t]);
  $pStmt->execute([$t]);$ids=array_column($pStmt->fetchAll(),'id');
  while(count($ids)<$slots)$ids[]=null;
  $ins=$bdd->prepare("INSERT INTO matches (tournament_id,player_a_id,player_b_id,round,score_a,score_b,winner_id) VALUES(?,?,?,?,0,0,NULL)");
  foreach(array_chunk($ids,2)as$pair){
   list($a,$b)=$pair;
   $ins->execute([$t,$a,$b,1]);$mid=$bdd->lastInsertId();
   if(is_null($b)&&$a)$bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")->execute([$a,$mid]);
   if(is_null($a)&&$b)$bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")->execute([$b,$mid]);
  }
 }
 if($act==='win'){
  $mid=(int)$_POST['match_id'];$w=(int)$_POST['winner_id'];
  $bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")->execute([$w,$mid]);
  for($r=1;$r<$maxR;$r++){
   $tot=$bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=?");$tot->execute([$t,$r]);$total=$tot->fetchColumn();
   $don=$bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=? AND winner_id IS NOT NULL");$don->execute([$t,$r]);$fin=$don->fetchColumn();
   if(!$total||$fin<$total)break;
   $nx=$bdd->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id=? AND round=?");$nx->execute([$t,$r+1]);
   if($nx->fetchColumn())continue;
   $wq=$bdd->prepare("SELECT winner_id FROM matches WHERE tournament_id=? AND round=? ORDER BY id");$wq->execute([$t,$r]);
   $wins=$wq->fetchAll(PDO::FETCH_COLUMN);while(count($wins)%2)$wins[]=null;
   $ins2=$bdd->prepare("INSERT INTO matches (tournament_id,player_a_id,player_b_id,round,score_a,score_b,winner_id) VALUES(?,?,?,?,0,0,NULL)");
   foreach(array_chunk($wins,2)as$pair){
    list($a2,$b2)=$pair;
    $ins2->execute([$t,$a2,$b2,$r+1]);$m2=$bdd->lastInsertId();
    if(is_null($b2)&&$a2)$bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")->execute([$a2,$m2]);
    if(is_null($a2)&&$b2)$bdd->prepare("UPDATE matches SET winner_id=? WHERE id=?")->execute([$b2,$m2]);
   }
  }
 }
 if($act==='close'){
  $bdd->prepare("UPDATE tournaments SET is_closed=1 WHERE id=?")->execute([$t]);$locked=true;
 }
 $pStmt->execute([$t]);$participants=$pStmt->fetchAll();
 $aStmt->execute([$t]);$available=$aStmt->fetchAll();
}
$mt=$bdd->prepare("SELECT id,round,player_a_id,player_b_id,winner_id FROM matches WHERE tournament_id=? ORDER BY round,id");
$mt->execute([$t]);$matches=$mt->fetchAll();
$champ='';
$finals=array_filter($matches,fn($m)=>$m['round']===$maxR);
if(count($finals)===1&&$finals[array_key_first($finals)]['winner_id']){
 $wid=$finals[array_key_first($finals)]['winner_id'];
 $u=$bdd->prepare("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id=?");
 $u->execute([$wid]);$champ=$u->fetchColumn();
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title><?=htmlspecialchars($tourney['name'])?></title><style>body{background:#2b2e3b;color:#fff;font-family:sans-serif;padding:20px}.bracket{display:flex;gap:40px}.round{flex:1}.round h2{text-align:center;text-transform:uppercase;margin-bottom:10px}.match{display:flex;align-items:center;width:360px;margin:20px auto}.connector{flex:1;height:2px;background:#fff;pointer-events:none;margin:0 8px}.btn-win{flex:1;background:#444963;color:#fff;border:none;padding:12px;position:relative}.btn-win.winner{background:#3b9d3a}.btn-win:disabled{opacity:.5}.btn-win::after{content:'';position:absolute;right:0;top:50%;transform:translateY(-50%);width:8px;height:20px;background:#eb6f92;pointer-events:none}</style></head><body>
<h1><?=htmlspecialchars($tourney['name'])?></h1>
<?php if($champ):?><h2 style="color:#eb6f92">🏆 Champion : <?=htmlspecialchars($champ)?></h2><?php endif;?>
<h2>Participants <?=$locked?'(verrouillé)':''?></h2><ul>
<?php foreach($participants as$p):?><li><?=$p['seed']?>–<?=htmlspecialchars($p['username'])?><?php if($isAdmin&&!$locked):?><form method="post" action="?tournament_id=<?=$t?>" style="display:inline"><input type="hidden" name="action" value="del"><input type="hidden" name="participant_id" value="<?=$p['id']?>"><button>❌</button></form><?php endif;?></li><?php endforeach;?></ul>
<?php if($isAdmin&&!$locked):?><form method="post" action="?tournament_id=<?=$t?>"><input type="hidden" name="action" value="add"><select name="user_id"><?php foreach($available as$u):?><option value="<?=$u['id']?>"><?=htmlspecialchars($u['username'])?></option><?php endforeach;?></select><button>➕Ajouter</button></form><form method="post" action="?tournament_id=<?=$t?>"><input type="hidden" name="action" value="generate"><button>GénérerTour1</button></form><form method="post" action="?tournament_id=<?=$t?>"><input type="hidden" name="action" value="close"><button>CloreTournoi</button></form><?php endif;?>
<div class="bracket">
<?php $cur=null;foreach($matches as$m):if($m['round']!==$cur){$cur=$m['round'];echo($cur>1?'</div>':'')."<div class=\"round\"><h2>Tour{$cur}</h2>";} $a=$m['player_a_id']?$bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_a_id']}")->fetchColumn():'–';$b=$m['player_b_id']?$bdd->query("SELECT u.username FROM participants p JOIN users u ON u.id=p.user_id WHERE p.id={$m['player_b_id']}")->fetchColumn():'–';$winA=$m['winner_id']===$m['player_a_id'];$winB=$m['winner_id']===$m['player_b_id'];?>
<div class="match">
<?php if($isAdmin):?><form method="post" action="?tournament_id=<?=$t?>"><input type="hidden"name="action"value="win"><input type="hidden"name="match_id"value="<?=$m['id']?>"><input type="hidden"name="winner_id"value="<?=$m['player_a_id']?>"><button class="btn-win<?=$winA?' winner':''?>"<?=$winA?'disabled':''?>><?=htmlspecialchars($a)?></button></form><?php else:?><div class="btn-win<?=$winA?' winner':''?>"><?=htmlspecialchars($a)?></div><?php endif;?><div class="connector"></div><?php if($isAdmin):?><form method="post" action="?tournament_id=<?=$t?>"><input type="hidden"name="action"value="win"><input type="hidden"name="match_id"value="<?=$m['id']?>"><input type="hidden"name="winner_id"value="<?=$m['player_b_id']?>"><button class="btn-win<?=$winB?' winner':''?>"<?=$winB?'disabled':''?>><?=htmlspecialchars($b)?></button></form><?php else:?><div class="btn-win<?=$winB?' winner':''?>"><?=htmlspecialchars($b)?></div><?php endif;?>
</div>
<?php endforeach;?></div>
</body></html>
