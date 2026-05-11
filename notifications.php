<?php
// notifications.php
require_once 'includes/config.php'; requireAuth();
// Mark all read
db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([auth()['id']]);
$notifs = db()->prepare("SELECT n.*,u.name as from_name FROM notifications n LEFT JOIN users u ON n.from_user_id=u.id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 50");
$notifs->execute([auth()['id']]);
$notifs = $notifs->fetchAll();
$pageTitle = 'Notifications'; include 'includes/header.php';
?>
<div style="max-width:640px;">
  <div class="sec-hdr"><div class="sec-title">🔔 All Notifications</div></div>
  <div class="card">
    <?php if (empty($notifs)): ?>
      <div class="empty-state"><span class="icon">🔔</span><h3>No notifications yet</h3><p>When someone likes your post or connects with you, you'll see it here.</p></div>
    <?php else: ?>
      <?php
      $typeIcons = ['like'=>'❤️','follow'=>'👥','comment'=>'💬','job'=>'🚀','tip'=>'🌍','welcome'=>'🎉'];
      foreach ($notifs as $n):
      ?>
      <a href="<?= BASE_URL . clean($n['link'] ?? '/dashboard.php') ?>" style="display:flex;align-items:flex-start;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border);transition:var(--t);color:var(--text);text-decoration:none;" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background=''">
        <div style="width:38px;height:38px;border-radius:50%;background:var(--brand-l);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;"><?= $typeIcons[$n['type']] ?? '🔔' ?></div>
        <div style="flex:1;">
          <div style="font-size:14px;line-height:1.5;"><?= clean($n['message']) ?></div>
          <div style="font-size:12px;color:var(--text3);margin-top:4px;"><?= ago($n['created_at']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
