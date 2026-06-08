<?php
require_once __DIR__ . '/../includes/config.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location:' . BASE_URL . '/dashboard.php'); exit;
}
verifyCsrf();

$toId    = (int)($_POST['to_id']    ?? 0);
$body    = s($_POST['body']         ?? '');
$refType = in_array($_POST['ref_type'] ?? '', ['service','job','general']) ? $_POST['ref_type'] : 'general';
$refId   = (int)($_POST['ref_id']   ?? 0);
$redirect = s($_POST['redirect']    ?? 'dashboard.php');

if (!$toId || strlen($body) < 5) {
    flash('error', 'Message too short. Please write at least 5 characters.');
    header('Location:' . BASE_URL . '/' . $redirect); exit;
}
if ($toId === auth()['id']) {
    flash('error', 'You cannot message yourself.');
    header('Location:' . BASE_URL . '/' . $redirect); exit;
}
if (!rateLimit('contact_' . auth()['id'], 5, 300)) {
    flash('error', 'Too many messages. Please wait a few minutes.');
    header('Location:' . BASE_URL . '/' . $redirect); exit;
}

// Build subject from reference
$subject = 'New message from ' . auth()['name'];
if ($refId && $refType === 'service') {
    $item = db()->prepare("SELECT title FROM services WHERE id=?");
    $item->execute([$refId]); $item = $item->fetch();
    if ($item) $subject = 'Service inquiry: ' . $item['title'];
}
if ($refId && $refType === 'job') {
    $item = db()->prepare("SELECT title, company FROM jobs WHERE id=?");
    $item->execute([$refId]); $item = $item->fetch();
    if ($item) $subject = 'Application: ' . $item['title'] . ' at ' . $item['company'];
}

sendContact(auth()['id'], $toId, $subject, $body, $refType, $refId);

flash('success', '✓ Message sent! They will be notified by email and in their inbox.');
header('Location:' . BASE_URL . '/' . $redirect);
exit;