<?php
require_once 'includes/config.php'; requireAuth();
$q    = s($_GET['q'] ?? '');
$type = in_array($_GET['type'] ?? '', ['all','students','posts','jobs','services','tips']) ? ($_GET['type'] ?? 'all') : 'all';
$results = ['students'=>[],'posts'=>[],'jobs'=>[],'services'=>[],'tips'=>[]];

if (strlen($q) >= 2) {
    $like = "%$q%";

    if ($type === 'all' || $type === 'students') {
        $s = db()->prepare("SELECT id,name,username,university,department,bio FROM users WHERE (name LIKE ? OR username LIKE ? OR university LIKE ? OR department LIKE ?) AND id != ? LIMIT 8");
        $s->execute([$like,$like,$like,$like, auth()['id']]);
        $results['students'] = $s->fetchAll();
    }
    if ($type === 'all' || $type === 'posts') {
        $s = db()->prepare("SELECT p.*,u.name,u.university FROM posts p JOIN users u ON p.user_id=u.id WHERE p.body LIKE ? ORDER BY p.created_at DESC LIMIT 8");
        $s->execute([$like]);
        $results['posts'] = $s->fetchAll();
    }
    if ($type === 'all' || $type === 'jobs') {
        $s = db()->prepare("SELECT j.*,u.name FROM jobs j JOIN users u ON j.user_id=u.id WHERE j.is_active=1 AND (j.title LIKE ? OR j.company LIKE ? OR j.description LIKE ?) ORDER BY j.created_at DESC LIMIT 8");
        $s->execute([$like,$like,$like]);
        $results['jobs'] = $s->fetchAll();
    }
    if ($type === 'all' || $type === 'services') {
        $s = db()->prepare("SELECT s.*,u.name FROM services s JOIN users u ON s.user_id=u.id WHERE s.is_active=1 AND (s.title LIKE ? OR s.description LIKE ?) ORDER BY s.created_at DESC LIMIT 8");
        $s->execute([$like,$like]);
        $results['services'] = $s->fetchAll();
    }
    if ($type === 'all' || $type === 'tips') {
        $s = db()->prepare("SELECT t.*,u.name FROM abroad_tips t JOIN users u ON t.user_id=u.id WHERE (t.title LIKE ? OR t.body LIKE ? OR t.country LIKE ?) ORDER BY t.created_at DESC LIMIT 8");
        $s->execute([$like,$like,$like]);
        $results['tips'] = $s->fetchAll();
    }
}

$total = array_sum(array_map('count', $results));
$catIcons = ['design'=>'🎨','coding'=>'💻','tutoring'=>'📚','photography'=>'📷','translation'=>'🌐','editing'=>'✏️','other'=>'🔧'];
$typeIcons = ['internship'=>'🎓','part-time'=>'⏰','full-time'=>'💼','remote'=>'🌐','gig'=>'⚡'];

$pageTitle = 'Search'; include 'includes/header.php';
?>

<div class="sec-hdr">
  <div>
    <div class="sec-title">Search Results</div>
    <?php if ($q): ?><div class="sec-sub"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= clean($q) ?>"</div><?php endif; ?>
  </div>
</div>

<!-- Search bar (large) -->
<form method="GET" style="margin-bottom:24px;">
  <div style="display:flex;gap:10px;max-width:600px;">
    <div style="flex:1;position:relative;">
      <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text3);" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" class="form-control" style="padding-left:40px;font-size:15px;height:46px;" placeholder="Search students, posts, jobs, services, abroad tips..." value="<?= clean($q) ?>" autofocus>
      <input type="hidden" name="type" value="<?= clean($type) ?>">
    </div>
    <button type="submit" class="btn btn-primary" style="height:46px;padding:0 24px;">Search</button>
  </div>
</form>

<?php if ($q): ?>
<!-- Filter Tabs -->
<div class="search-tab" style="margin-bottom:20px;">
  <?php
  $tabs = ['all'=>'All','students'=>'👤 Students','posts'=>'📢 Posts','jobs'=>'🚀 Jobs','services'=>'💼 Services','tips'=>'🌍 Abroad Tips'];
  foreach ($tabs as $k => $label):
    $cnt = $k === 'all' ? $total : count($results[$k]);
  ?>
  <a href="?q=<?= urlencode($q) ?>&type=<?= $k ?>" class="<?= $type === $k ? 'active' : '' ?>">
    <?= $label ?><?php if ($k !== 'all' && $cnt): ?> <span style="opacity:.7;font-size:11px;">(<?= $cnt ?>)</span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($total === 0): ?>
  <div class="card"><div class="empty-state">
    <span class="icon">🔍</span>
    <h3>No results found</h3>
    <p>Try different keywords or check your spelling.</p>
  </div></div>
<?php else: ?>

  <!-- STUDENTS -->
  <?php if (!empty($results['students']) && ($type === 'all' || $type === 'students')): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>👤 Students (<?= count($results['students']) ?>)</h3></div>
    <?php foreach ($results['students'] as $u): ?>
    <a href="<?= BASE_URL ?>/profile.php?uid=<?= $u['id'] ?>" class="search-result">
      <div class="search-result-icon" style="background:<?= avatarGradient($u['id']) ?>;border-radius:50%;color:#fff;font-weight:700;font-size:14px;"><?= initials($u['name']) ?></div>
      <div style="flex:1;">
        <div class="search-result-title"><?= clean($u['name']) ?>
          <span style="font-size:12px;color:var(--text3);font-weight:400;"> @<?= clean($u['username']) ?></span>
        </div>
        <div class="search-result-meta"><?= clean($u['university'] ?? '') ?><?= $u['department'] ? ' · ' . clean($u['department']) : '' ?></div>
        <?php if ($u['bio']): ?><div style="font-size:12px;color:var(--text3);margin-top:2px;"><?= clean(substr($u['bio'], 0, 80)) ?></div><?php endif; ?>
      </div>
      <button class="follow-btn" onclick="event.preventDefault();toast('Connection request sent!')">Connect</button>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- POSTS -->
  <?php if (!empty($results['posts']) && ($type === 'all' || $type === 'posts')): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>📢 Posts (<?= count($results['posts']) ?>)</h3></div>
    <?php foreach ($results['posts'] as $post): ?>
    <a href="<?= BASE_URL ?>/community.php#post-<?= $post['id'] ?>" class="search-result">
      <div class="search-result-icon" style="background:<?= avatarGradient($post['user_id']) ?>;border-radius:50%;color:#fff;font-weight:700;font-size:13px;"><?= initials($post['name']) ?></div>
      <div>
        <div class="search-result-title"><?= clean($post['name']) ?> <span class="post-tag pt-<?= $post['tag'] ?>" style="margin-left:6px;"><?= ucfirst($post['tag']) ?></span></div>
        <div class="search-result-meta"><?= clean(substr($post['body'], 0, 120)) ?>...</div>
        <div style="font-size:11px;color:var(--text3);margin-top:3px;"><?= ago($post['created_at']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- JOBS -->
  <?php if (!empty($results['jobs']) && ($type === 'all' || $type === 'jobs')): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🚀 Jobs (<?= count($results['jobs']) ?>)</h3></div>
    <?php foreach ($results['jobs'] as $job): ?>
    <a href="<?= BASE_URL ?>/career.php" class="search-result">
      <div class="search-result-icon" style="background:var(--surface2);border:1px solid var(--border);"><?= $typeIcons[$job['type']] ?? '🏢' ?></div>
      <div>
        <div class="search-result-title"><?= clean($job['title']) ?> — <?= clean($job['company']) ?></div>
        <div class="search-result-meta">📍 <?= clean($job['location']) ?> · <?= ucfirst($job['type']) ?><?= $job['salary'] ? ' · $'.number_format($job['salary']).'/mo' : '' ?></div>
      </div>
      <span class="badge bg-b"><?= ucfirst($job['type']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- SERVICES -->
  <?php if (!empty($results['services']) && ($type === 'all' || $type === 'services')): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>💼 Services (<?= count($results['services']) ?>)</h3></div>
    <?php foreach ($results['services'] as $svc): ?>
    <a href="<?= BASE_URL ?>/marketplace.php" class="search-result">
      <div class="search-result-icon" style="background:var(--accent-l);"><?= $catIcons[$svc['category']] ?? '🔧' ?></div>
      <div>
        <div class="search-result-title"><?= clean($svc['title']) ?></div>
        <div class="search-result-meta">by <?= clean($svc['name']) ?> · $<?= number_format($svc['price'], 2) ?> · <?= $svc['delivery_days'] ?>-day delivery</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ABROAD TIPS -->
  <?php if (!empty($results['tips']) && ($type === 'all' || $type === 'tips')): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🌍 Abroad Tips (<?= count($results['tips']) ?>)</h3></div>
    <?php foreach ($results['tips'] as $tip): ?>
    <a href="<?= BASE_URL ?>/abroad.php?country=<?= urlencode($tip['country']) ?>" class="search-result">
      <div class="search-result-icon" style="background:#ECFDF5;">🌍</div>
      <div>
        <div class="search-result-title"><?= clean($tip['title']) ?></div>
        <div class="search-result-meta">📍 <?= clean($tip['country']) ?> · <?= ucfirst($tip['category']) ?> · by <?= clean($tip['name']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php endif; // total > 0 ?>
<?php else: ?>
<!-- No query yet -->
<div class="card"><div class="empty-state">
  <span class="icon">🔍</span>
  <h3>Search Campus Connect X</h3>
  <p>Find students, community posts, jobs, services and abroad tips all in one place.</p>
</div></div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
