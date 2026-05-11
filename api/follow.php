<?php
// api/follow.php
require_once '../includes/config.php';
if (!isLoggedIn()) jsonResponse(['error'=>'Unauthorized'], 401);
$data = json_decode(file_get_contents('php://input'), true);
$uid  = (int)($data['user_id'] ?? 0);
if (!$uid || $uid === auth()['id']) jsonResponse(['error'=>'Invalid'], 400);

$chk = db()->prepare("SELECT id FROM follows WHERE follower_id=? AND following_id=?");
$chk->execute([auth()['id'], $uid]);

if ($chk->fetch()) {
    db()->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")->execute([auth()['id'], $uid]);
    jsonResponse(['success'=>true,'following'=>false]);
} else {
    db()->prepare("INSERT IGNORE INTO follows(follower_id,following_id)VALUES(?,?)")->execute([auth()['id'], $uid]);
    // Get followed user's name
    $u = db()->prepare("SELECT name FROM users WHERE id=?"); $u->execute([$uid]); $user = $u->fetch();
    notify($uid, 'follow', auth()['name'].' started following you.', '/profile.php', auth()['id']);
    jsonResponse(['success'=>true,'following'=>true,'name'=>clean($user['name']??'Student')]);
}

function jsonResponse(array $d, int $c=200):void{http_response_code($c);header('Content-Type:application/json');echo json_encode($d);exit;}
