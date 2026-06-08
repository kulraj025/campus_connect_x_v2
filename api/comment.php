<?php
// api/comment.php
require_once '../includes/config.php';
if (!isLoggedIn()) jsonResponse(['error'=>'Unauthorized'], 401);
$data   = json_decode(file_get_contents('php://input'), true);
$postId = (int)($data['post_id'] ?? 0);
$body   = s($data['body'] ?? '');
if (!$postId || strlen($body) < 1) jsonResponse(['error'=>'Invalid'], 400);
if (!rateLimit('comment_'.auth()['id'], 10, 60)) jsonResponse(['error'=>'Too many comments'], 429);

db()->prepare("INSERT INTO comments(post_id,user_id,body)VALUES(?,?,?)")->execute([$postId, auth()['id'], $body]);
db()->prepare("UPDATE posts SET comments_count=comments_count+1 WHERE id=?")->execute([$postId]);

// Notify post author
$pa = db()->prepare("SELECT user_id FROM posts WHERE id=?"); $pa->execute([$postId]); $pa = $pa->fetch();
if ($pa) notify($pa['user_id'],'comment', auth()['name'].' commented on your post.', '/community.php', auth()['id']);

jsonResponse([
    'success'  => true,
    'name'     => clean(auth()['name']),
    'initials' => initials(auth()['name']),
    'gradient' => avatarGradient(auth()['id']),
    'body'     => clean($body),
]);

function jsonResponse(array $d,int $c=200):void{http_response_code($c);header('Content-Type:application/json');echo json_encode($d);exit;}
