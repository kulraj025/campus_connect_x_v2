<?php
require_once '../includes/config.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:'.BASE_URL.'/dashboard.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid token');

$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }

$map = [
    'post'    => ['table'=>'posts',       'redirect'=>'/community.php',   'soft'=>false],
    'tip'     => ['table'=>'abroad_tips', 'redirect'=>'/abroad.php',      'soft'=>false],
    'job'     => ['table'=>'jobs',        'redirect'=>'/career.php',      'soft'=>true],
    'service' => ['table'=>'services',    'redirect'=>'/marketplace.php', 'soft'=>true],
    'message' => ['table'=>'messages',    'redirect'=>'/messages.php',    'soft'=>false],
];

if (!isset($map[$type])) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }

$m       = $map[$type];
$isAdmin = !empty(auth()['is_admin']);

// Admin can delete anything — regular user only their own
if ($isAdmin) {
    $chk = db()->prepare("SELECT id FROM {$m['table']} WHERE id=?");
    $chk->execute([$id]);
} else {
    $chk = db()->prepare("SELECT id FROM {$m['table']} WHERE id=? AND user_id=?");
    $chk->execute([$id, auth()['id']]);
}

if ($chk->fetch()) {
    if ($m['soft']) {
        db()->prepare("UPDATE {$m['table']} SET is_active=0 WHERE id=?")->execute([$id]);
    } else {
        db()->prepare("DELETE FROM {$m['table']} WHERE id=?")->execute([$id]);
    }
    flash('success', ucfirst($type) . ' deleted.');
} else {
    flash('error', 'Permission denied.');
}

$redirect = $_POST['redirect'] ?? $m['redirect'];
header('Location:' . BASE_URL . '/' . ltrim($redirect, '/')); exit;