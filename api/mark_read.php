<?php
// api/mark_read.php
require_once '../includes/config.php';
if (!isLoggedIn()) { header('Location:'.BASE_URL.'/login.php'); exit; }
db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([auth()['id']]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type:application/json'); echo json_encode(['success'=>true]); exit;
}
header('Location:'.BASE_URL.'/notifications.php'); exit;
