<?php
function sendContact($fromId, $toId, $subject, $body, $refType = 'general', $refId = 0)
{
    $db = db();

    $stmt = $db->prepare("
        INSERT INTO messages 
        (sender_id, receiver_id, subject, body, ref_type, ref_id, created_at, is_read)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)
    ");

    $stmt->execute([
        $fromId,
        $toId,
        $subject,
        $body,
        $refType,
        $refId
    ]);

    $msgId = (int)$db->lastInsertId();

    // ── Notify the receiver ──────────────────────────────────────────
    $sender = $db->prepare("SELECT name FROM users WHERE id=?");
    $sender->execute([$fromId]);
    $senderName = $sender->fetchColumn() ?: 'Someone';

    $notifMsg = "✉ <strong>{$senderName}</strong> sent you a message: <em>" . mb_substr($body, 0, 60) . (mb_strlen($body) > 60 ? '…' : '') . "</em>";

    $n = $db->prepare("
        INSERT INTO notifications (user_id, from_user_id, type, ref_id, message, link, created_at, is_read)
        VALUES (?, ?, 'message', ?, ?, ?, NOW(), 0)
    ");
    $n->execute([
        $toId,
        $fromId,
        $msgId,
        $notifMsg,
        '/messages.php'
    ]);
    // ────────────────────────────────────────────────────────────────

    // Email notification
    $u = $db->prepare("SELECT email FROM users WHERE id=?");
    $u->execute([$toId]);
    $user = $u->fetch();

    if ($user && !empty($user['email'])) {
        @mail($user['email'], $subject, $body, "From: no-reply@campusconnectx.com");
    }

    return $msgId;
}