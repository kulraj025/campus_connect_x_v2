<?php
// api/track_ad.php
require_once '../includes/config.php';
$id   = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'view';
if (!$id) exit;
if ($type === 'click') {
    db()->prepare("UPDATE ads SET click_count=click_count+1 WHERE id=?")->execute([$id]);
} else {
    db()->prepare("UPDATE ads SET view_count=view_count+1 WHERE id=?")->execute([$id]);
}
http_response_code(204);
