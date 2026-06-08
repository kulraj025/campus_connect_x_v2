<?php
// api/like.php
require_once '../includes/config.php';
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
if (!rateLimit('like_'.auth()['id'], 30, 60)) { http_response_code(429); echo json_encode(['error'=>'Too fast']); exit; }
$data   = json_decode(file_get_contents('php://input'), true);
$postId = (int)($data['post_id'] ?? 0);
if (!$postId) { http_response_code(400); echo json_encode(['error'=>'Invalid']); exit; }

$chk = db()->prepare("SELECT id FROM post_likes WHERE post_id=? AND user_id=?");
$chk->execute([$postId, auth()['id']]);
if ($chk->fetch()) {
    db()->prepare("DELETE FROM post_likes WHERE post_id=? AND user_id=?")->execute([$postId, auth()['id']]);
    db()->prepare("UPDATE posts SET likes_count=GREATEST(0,likes_count-1) WHERE id=?")->execute([$postId]);
    $liked = false;
} else {
    db()->prepare("INSERT IGNORE INTO post_likes(post_id,user_id)VALUES(?,?)")->execute([$postId, auth()['id']]);
    db()->prepare("UPDATE posts SET likes_count=likes_count+1 WHERE id=?")->execute([$postId]);
    $pa = db()->prepare("SELECT user_id FROM posts WHERE id=?"); $pa->execute([$postId]); $pa = $pa->fetch();
    if ($pa) notify($pa['user_id'], 'like', auth()['name'] . ' liked your post.', '/community.php', auth()['id']);
    $liked = true;
}
$cnt = db()->prepare("SELECT likes_count FROM posts WHERE id=?"); $cnt->execute([$postId]);
header('Content-Type: application/json');
echo json_encode(['success'=>true,'liked'=>$liked,'count'=>(int)$cnt->fetchColumn()]);
exit;