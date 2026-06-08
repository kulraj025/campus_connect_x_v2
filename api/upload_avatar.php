<?php
require_once '../includes/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request'], 405);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No file uploaded'], 400);
}

$file    = $_FILES['avatar'];
$maxSize = 3 * 1024 * 1024; // 3MB

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'File too large. Max 3MB.'], 400);
}

// Validate MIME type
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

if (!isset($allowed[$mimeType])) {
    jsonResponse(['error' => 'Only JPG, PNG, WEBP or GIF allowed.'], 400);
}

// Validate it is actually an image
if (!getimagesize($file['tmp_name'])) {
    jsonResponse(['error' => 'Invalid image file.'], 400);
}

$ext      = $allowed[$mimeType];
$filename = 'avatar_' . auth()['id'] . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/avatars/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Delete old avatar
if (!empty(auth()['avatar'])) {
    $old = __DIR__ . '/../' . auth()['avatar'];
    if (file_exists($old)) @unlink($old);
}

if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    jsonResponse(['error' => 'Failed to save. Check folder permissions.'], 500);
}

$path = 'uploads/avatars/' . $filename;
db()->prepare("UPDATE users SET avatar=?,updated_at=NOW() WHERE id=?")->execute([$path, auth()['id']]);
$_SESSION['user']['avatar'] = $path;

jsonResponse(['success' => true, 'avatar' => BASE_URL . '/' . $path]);

function jsonResponse(array $d, int $c = 200): void {
    http_response_code($c);
    header('Content-Type: application/json');
    echo json_encode($d);
    exit;
}