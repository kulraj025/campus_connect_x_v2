<?php
// api/mark_read.php
require_once '../includes/config.php';
if (!isLoggedIn()) exit;
db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([auth()['id']]);
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;