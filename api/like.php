<?php
// api/like.php
require_once '../includes/config.php';
if(!isLoggedIn()){jsonResponse(['error'=>'Unauthorized'],401);}
$data=json_decode(file_get_contents('php://input'),true);
$postId=(int)($data['post_id']??0);
if(!$postId) jsonResponse(['error'=>'Invalid'],400);
// Check if already liked
$chk=db()->prepare("SELECT id FROM post_likes WHERE post_id=? AND user_id=?");
$chk->execute([$postId,auth()['id']]);
if($chk->fetch()){
    db()->prepare("DELETE FROM post_likes WHERE post_id=? AND user_id=?")->execute([$postId,auth()['id']]);
    db()->prepare("UPDATE posts SET likes_count=GREATEST(0,likes_count-1) WHERE id=?")->execute([$postId]);
    $liked=false;
}else{
    db()->prepare("INSERT IGNORE INTO post_likes(post_id,user_id)VALUES(?,?)")->execute([$postId,auth()['id']]);
    db()->prepare("UPDATE posts SET likes_count=likes_count+1 WHERE id=?")->execute([$postId]);
    $liked=true;
}
$cnt=db()->prepare("SELECT likes_count FROM posts WHERE id=?");$cnt->execute([$postId]);
$count=$cnt->fetchColumn();
jsonResponse(['success'=>true,'liked'=>$liked,'count'=>(int)$count]);

function jsonResponse(array $d,int $c=200):void{http_response_code($c);header('Content-Type:application/json');echo json_encode($d);exit;}
