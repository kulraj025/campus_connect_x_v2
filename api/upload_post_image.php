<?php
require_once '../includes/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid'], 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No file uploaded'], 400);
}

$file    = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'Max 5MB allowed.'], 400);
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

if (!isset($allowed[$mimeType])) {
    jsonResponse(['error' => 'Only JPG, PNG, WEBP, GIF allowed.'], 400);
}

if (!getimagesize($file['tmp_name'])) {
    jsonResponse(['error' => 'Invalid image.'], 400);
}

$ext      = $allowed[$mimeType];
$filename = 'post_' . auth()['id'] . '_' . time() . '.' . $ext;
$dir      = __DIR__ . '/../uploads/posts/';

if (!is_dir($dir)) mkdir($dir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
    jsonResponse(['error' => 'Save failed.'], 500);
}

$path = 'uploads/posts/' . $filename;
jsonResponse(['success' => true, 'path' => $path, 'url' => BASE_URL . '/' . $path]);

function jsonResponse(array $d, int $c = 200): void {
    http_response_code($c);
    header('Content-Type: application/json');
    echo json_encode($d);
    exit;
}