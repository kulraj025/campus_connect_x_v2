<?php
require_once 'includes/config.php';
requireAuth();

$me = auth()['id'];

// Mark all as read
db()->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=?")->execute([$me]);

// Load inbox (received) + sent so we can show full threads
$msgs = db()->prepare("
    SELECT m.*,
           u.name    AS from_name,
           u.avatar  AS from_avatar,
           u.university AS from_uni,
           u.department AS from_dept,
           u.id      AS from_uid,
           r.name    AS to_name,
           r.avatar  AS to_avatar,
           r.id      AS to_uid
    FROM messages m
    JOIN users u ON m.sender_id   = u.id
    JOIN users r ON m.receiver_id = r.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.created_at DESC
");
$msgs->execute([$me, $me]);
$msgs = $msgs->fetchAll();

$pageTitle = 'Messages';
include 'includes/header.php';
?>

<style>
.msg-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:12px;overflow:hidden;transition:var(--t);}
.msg-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.07);}
.msg-header{display:flex;align-items:flex-start;gap:14px;padding:16px 20px;cursor:pointer;user-select:none;}
.msg-header:hover{background:var(--surface2);}
.msg-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;}
.badge-sent{background:#E8F5E9;color:#2E7D32;}
.badge-unread{background:#EDE7F6;color:#5E35B1;}
.msg-body-wrap{display:none;padding:0 20px 18px 74px;border-top:1px solid var(--border);}
.msg-body-wrap.open{display:block;}
.msg-bubble{font-size:13px;color:var(--text2);line-height:1.8;background:var(--surface2);padding:12px 16px;border-radius:var(--r);border-left:3px solid var(--brand);margin-top:14px;}
.reply-box{margin-top:14px;}
.reply-box textarea{width:100%;box-sizing:border-box;padding:10px 14px;border:1px solid var(--border);border-radius:var(--r);font-size:13px;resize:vertical;min-height:80px;font-family:inherit;background:var(--surface);color:var(--text);transition:border-color .2s;}
.reply-box textarea:focus{outline:none;border-color:var(--brand);}
.reply-actions{display:flex;gap:8px;margin-top:8px;align-items:center;}
.btn-reply{background:var(--brand);color:#fff;border:none;padding:8px 20px;border-radius:var(--r);font-size:13px;font-weight:600;cursor:pointer;transition:opacity .2s;}
.btn-reply:hover{opacity:.88;}
.btn-delete{background:none;border:1px solid #f87171;color:#ef4444;padding:7px 14px;border-radius:var(--r);font-size:12px;cursor:pointer;transition:var(--t);}
.btn-delete:hover{background:#fef2f2;}
.char-count{font-size:11px;color:var(--text3);margin-left:auto;}
.dot-unread{width:8px;height:8px;border-radius:50%;background:var(--brand);flex-shrink:0;margin-top:6px;}
</style>

<div style="max-width:720px;">

  <div class="sec-hdr">
    <div>
      <div class="sec-title">✉ Messages</div>
      <div class="sec-sub"><?= count($msgs) ?> message<?= count($msgs) !== 1 ? 's' : '' ?></div>
    </div>
  </div>

  <?php if (empty($msgs)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">✉</span>
      <h3>No messages yet</h3>
      <p>Messages you send or receive will appear here.</p>
    </div>
  </div>

  <?php else: foreach ($msgs as $m):
    $isSent   = ((int)$m['sender_id'] === $me);
    $isUnread = (!$isSent && !$m['is_read']);
    $otherId   = $isSent ? $m['to_uid']   : $m['from_uid'];
    $otherName = $isSent ? $m['to_name']  : $m['from_name'];
    $otherAvatar = $isSent ? ($m['to_avatar'] ?? '') : ($m['from_avatar'] ?? '');
    $otherDept = $isSent ? '' : ($m['from_dept'] ?? $m['from_uni'] ?? 'Student');
  ?>

  <div class="msg-card" id="msg-<?= $m['id'] ?>">

    <!-- Clickable header to expand/collapse -->
    <div class="msg-header" onclick="toggleMsg(<?= $m['id'] ?>)">

      <?= avatarHtml(['id'=>$otherId,'name'=>$otherName,'avatar'=>$otherAvatar], 44) ?>

      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
          <strong style="font-size:14px;color:var(--text);"><?= clean($otherName) ?></strong>
          <?php if ($isSent): ?>
            <span class="msg-badge badge-sent">Sent</span>
          <?php elseif ($isUnread): ?>
            <span class="msg-badge badge-unread">New</span>
          <?php endif; ?>
          <?php if ($otherDept): ?>
            <span style="font-size:12px;color:var(--text3);margin-left:4px;"><?= clean($otherDept) ?></span>
          <?php endif; ?>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px;"><?= clean($m['subject'] ?? 'Message') ?></div>
        <div style="font-size:12px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:480px;"><?= clean(mb_substr($m['body'], 0, 100)) ?>…</div>
      </div>

      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
        <span style="font-size:11px;color:var(--text3);"><?= ago($m['created_at']) ?></span>
        <?php if ($isUnread): ?><div class="dot-unread"></div><?php endif; ?>
        <svg id="arrow-<?= $m['id'] ?>" style="width:16px;height:16px;color:var(--text3);transition:transform .25s;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
      </div>

    </div>

    <!-- Expandable body + reply -->
    <div class="msg-body-wrap" id="body-<?= $m['id'] ?>">

      <div class="msg-bubble"><?= nl2br(clean($m['body'])) ?></div>

      <?php if (!$isSent): ?>
      <!-- Reply form -->
      <div class="reply-box">
        <form method="POST" action="<?= BASE_URL ?>/api/contact.php">
          <input type="hidden" name="csrf"      value="<?= csrf() ?>">
          <input type="hidden" name="to_id"     value="<?= (int)$m['sender_id'] ?>">
          <input type="hidden" name="ref_type"  value="<?= clean($m['ref_type'] ?? 'general') ?>">
          <input type="hidden" name="ref_id"    value="<?= (int)($m['ref_id'] ?? 0) ?>">
          <input type="hidden" name="redirect"  value="messages.php">
          <textarea
            name="body"
            placeholder="Write a reply to <?= clean($otherName) ?>…"
            maxlength="2000"
            oninput="updateCount(this, 'cc-<?= $m['id'] ?>')"
          ></textarea>
          <div class="reply-actions">
            <button type="submit" class="btn-reply">↩ Reply</button>
            <span class="char-count" id="cc-<?= $m['id'] ?>">0 / 2000</span>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Delete -->
      <div style="margin-top:10px;">
        <form method="POST" action="<?= BASE_URL ?>/api/del.php" onsubmit="return confirm('Delete this message?')">
          <input type="hidden" name="csrf"  value="<?= csrf() ?>">
          <input type="hidden" name="type"  value="message">
          <input type="hidden" name="id"    value="<?= $m['id'] ?>">
          <input type="hidden" name="redirect" value="messages.php">
          <button type="submit" class="btn-delete">🗑 Delete</button>
        </form>
      </div>

    </div>
  </div>

  <?php endforeach; endif; ?>
</div>

<script>
function toggleMsg(id) {
  const body  = document.getElementById('body-' + id);
  const arrow = document.getElementById('arrow-' + id);
  const open  = body.classList.toggle('open');
  arrow.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}
function updateCount(el, counterId) {
  document.getElementById(counterId).textContent = el.value.length + ' / 2000';
}
</script>

<?php include 'includes/footer.php'; ?>