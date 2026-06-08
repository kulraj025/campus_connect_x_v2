<?php
require_once 'includes/config.php';
requireAuth();

$me = auth()['id'];

// Mark all as read on open
db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$me]);

$notifs = db()->prepare("
    SELECT n.*, u.name AS fn, u.avatar AS fa, u.id AS fid
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 80
");
$notifs->execute([$me]);
$notifs = $notifs->fetchAll();

// Icon + color map per type
$meta = [
    'message' => ['icon'=>'✉',  'color'=>'#6366F1','bg'=>'#EEF2FF'],
    'like'    => ['icon'=>'❤️', 'color'=>'#EF4444','bg'=>'#FEF2F2'],
    'follow'  => ['icon'=>'👥', 'color'=>'#10B981','bg'=>'#ECFDF5'],
    'comment' => ['icon'=>'💬', 'color'=>'#F59E0B','bg'=>'#FFFBEB'],
    'job'     => ['icon'=>'🚀', 'color'=>'#3B82F6','bg'=>'#EFF6FF'],
    'tip'     => ['icon'=>'🌍', 'color'=>'#8B5CF6','bg'=>'#F5F3FF'],
    'welcome' => ['icon'=>'🎉', 'color'=>'#EC4899','bg'=>'#FDF2F8'],
    'system'  => ['icon'=>'📢', 'color'=>'#64748B','bg'=>'#F8FAFC'],
];

$pageTitle = 'Notifications';
include 'includes/header.php';
?>

<style>
.notif-wrap{max-width:660px;}
.notif-tabs{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.notif-tab{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);transition:var(--t);}
.notif-tab.active,.notif-tab:hover{background:var(--brand);color:#fff;border-color:var(--brand);}
.notif-item{display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);transition:background .15s;position:relative;}
.notif-item:hover{background:var(--surface2);}
.notif-item.unread{background:#FAFBFF;}
.notif-icon{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.notif-msg{font-size:13px;line-height:1.55;color:var(--text);}
.notif-msg strong{color:var(--brand);}
.notif-time{font-size:11px;color:var(--text3);margin-top:4px;}
.notif-dot{width:8px;height:8px;border-radius:50%;background:var(--brand);flex-shrink:0;margin-top:7px;}
.notif-empty{text-align:center;padding:48px 20px;color:var(--text3);}
.notif-empty .icon{font-size:40px;display:block;margin-bottom:12px;}
.mark-read-btn{font-size:12px;color:var(--brand);background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;transition:var(--t);}
.mark-read-btn:hover{background:var(--surface2);}
</style>

<div class="notif-wrap">

  <div class="sec-hdr" style="margin-bottom:18px;">
    <div>
      <div class="sec-title">🔔 Notifications</div>
      <div class="sec-sub"><?= count($notifs) ?> total</div>
    </div>
    <?php if (!empty($notifs)): ?>
    <form method="POST" action="<?= BASE_URL ?>/api/mark_read.php">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="type" value="all">
      <button type="submit" class="mark-read-btn">✓ Mark all read</button>
    </form>
    <?php endif; ?>
  </div>

  <!-- Filter tabs -->
  <div class="notif-tabs">
    <button class="notif-tab active" onclick="filterNotifs('all', this)">All</button>
    <button class="notif-tab" onclick="filterNotifs('message', this)">✉ Messages</button>
    <button class="notif-tab" onclick="filterNotifs('like', this)">❤️ Likes</button>
    <button class="notif-tab" onclick="filterNotifs('follow', this)">👥 Follows</button>
    <button class="notif-tab" onclick="filterNotifs('comment', this)">💬 Comments</button>
    <button class="notif-tab" onclick="filterNotifs('job', this)">🚀 Jobs</button>
    <button class="notif-tab" onclick="filterNotifs('system', this)">📢 System</button>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($notifs)): ?>
    <div class="notif-empty">
      <span class="icon">🔔</span>
      <strong>You're all caught up!</strong>
      <p style="margin-top:6px;font-size:13px;">Activity from messages, likes and connections will appear here.</p>
    </div>

    <?php else: foreach ($notifs as $n):
      $type = $n['type'] ?? 'system';
      $m    = $meta[$type] ?? $meta['system'];
      $link = !empty($n['link']) ? BASE_URL . $n['link'] : BASE_URL . '/dashboard.php';
    ?>

    <a href="<?= $link ?>"
       class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>"
       data-type="<?= clean($type) ?>">

      <div class="notif-icon" style="background:<?= $m['bg'] ?>;color:<?= $m['color'] ?>;">
        <?= $m['icon'] ?>
      </div>

      <div style="flex:1;min-width:0;">
        <div class="notif-msg"><?= $n['message'] ?></div>
        <div class="notif-time"><?= ago($n['created_at']) ?></div>
      </div>

      <?php if (!$n['is_read']): ?>
        <div class="notif-dot"></div>
      <?php endif; ?>

    </a>

    <?php endforeach; endif; ?>
  </div>
</div>

<script>
function filterNotifs(type, btn) {
  // Update active tab
  document.querySelectorAll('.notif-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Show/hide items
  document.querySelectorAll('.notif-item').forEach(item => {
    item.style.display = (type === 'all' || item.dataset.type === type) ? 'flex' : 'none';
  });
}
</script>

<?php include 'includes/footer.php'; ?>