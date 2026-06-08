<?php
require_once 'includes/config.php';
requireAuth();

$cIcons = ['design'=>'🎨','coding'=>'💻','tutoring'=>'📚','photography'=>'📷','translation'=>'🌐','editing'=>'✏️','other'=>'🔧'];
$cBg    = ['design'=>'#F5F3FF','coding'=>'#EFF6FF','tutoring'=>'#ECFDF5','photography'=>'#FFF7ED','translation'=>'#EFF6FF','editing'=>'#FFF1F2','other'=>'#F8FAFC'];

// Save service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'save') {
    verifyCsrf();
    $t   = s($_POST['title']??'');
    $d   = s($_POST['description']??'');
    $c   = isset($cIcons[$_POST['category']??'']) ? $_POST['category'] : '';
    $p   = (float)($_POST['price']??0);
    $del = in_array((int)($_POST['delivery_days']??0),[1,3,7,14,30]) ? (int)$_POST['delivery_days'] : 7;
    if (strlen($t)>=5 && $c && $p>=1 && strlen($d)>=20) {
        db()->prepare("INSERT INTO services(user_id,title,description,category,price,delivery_days)VALUES(?,?,?,?,?,?)")
           ->execute([auth()['id'],$t,$d,$c,$p,$del]);
        flash('success','Service listed!');
    } else { flash('error','Please fill all fields correctly.'); }
    header('Location:'.BASE_URL.'/marketplace.php'); exit;
}

// Mark service as hired
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'mark_hired') {
    verifyCsrf();
    $sid = (int)($_POST['service_id'] ?? 0);
    // Only the owner can mark hired
    $check = db()->prepare("SELECT id FROM services WHERE id=? AND user_id=?");
    $check->execute([$sid, auth()['id']]);
    if ($check->fetch()) {
        db()->prepare("UPDATE services SET is_hired=1 WHERE id=?")->execute([$sid]);
        flash('success', '🎉 Marked as hired! Applicants will see this.');
    }
    header('Location:'.BASE_URL.'/marketplace.php'); exit;
}

// Reopen service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'reopen') {
    verifyCsrf();
    $sid = (int)($_POST['service_id'] ?? 0);
    $check = db()->prepare("SELECT id FROM services WHERE id=? AND user_id=?");
    $check->execute([$sid, auth()['id']]);
    if ($check->fetch()) {
        db()->prepare("UPDATE services SET is_hired=0 WHERE id=?")->execute([$sid]);
        flash('success', 'Service reopened.');
    }
    header('Location:'.BASE_URL.'/marketplace.php'); exit;
}

$page = max(1,(int)($_GET['page']??1));
$fc   = isset($cIcons[$_GET['cat']??'']) ? $_GET['cat'] : '';
$r    = paginate("SELECT s.*,u.name,u.university,u.avatar,u.id as uid FROM services s JOIN users u ON s.user_id=u.id WHERE s.is_active=1".($fc?" AND s.category='$fc'":'')." ORDER BY s.is_hired ASC, s.created_at DESC",[], $page, 12);
$svcs = $r['items'];

$pageTitle = 'Marketplace';
include 'includes/header.php';
?>

<style>
.hired-banner{background:#ECFDF5;border:1.5px solid #6EE7B7;color:#065F46;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:inline-flex;align-items:center;gap:5px;}
.service-card.is-hired{opacity:.75;position:relative;}
.service-card.is-hired::after{content:'HIRED';position:absolute;top:10px;right:10px;background:#059669;color:#fff;font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;letter-spacing:.5px;}
</style>

<div class="sec-hdr">
  <div><div class="sec-title">Skill Marketplace 💼</div><div class="sec-sub">Hire student talent or list your own services</div></div>
  <button class="btn btn-primary" data-modal="svc-modal">+ List Your Service</button>
</div>

<div class="filter-bar">
  <a href="marketplace.php" class="btn btn-sm <?= !$fc?'btn-primary':'btn-ghost' ?>">All</a>
  <?php foreach($cIcons as $c=>$i): ?>
    <a href="?cat=<?= $c ?>" class="btn btn-sm <?= $fc===$c?'btn-primary':'btn-ghost' ?>"><?= $i ?> <?= ucfirst($c) ?></a>
  <?php endforeach; ?>
</div>

<div class="service-grid">
  <?php if (empty($svcs)): ?>
    <div style="grid-column:1/-1"><div class="card"><div class="empty-state">
      <span class="icon">💼</span><h3>No services yet</h3><p>Be the first to list a skill!</p>
      <button class="btn btn-primary" data-modal="svc-modal">List Your Service →</button>
    </div></div></div>
  <?php else: foreach ($svcs as $sv): 
    $isHired = !empty($sv['is_hired']);
    $isOwner = ((int)$sv['user_id'] === auth()['id']);
  ?>
  <div class="service-card <?= $isHired ? 'is-hired' : '' ?>">
    <div class="service-thumb" style="background:<?= $cBg[$sv['category']]??'#F8FAFC' ?>;">
      <?= $cIcons[$sv['category']]??'🔧' ?>
    </div>
    <div class="service-body">
      <div class="service-title"><?= clean($sv['title']) ?></div>
      <div class="service-seller" style="display:flex;align-items:center;gap:6px;">
        <?= avatarHtml(['id'=>$sv['uid'],'name'=>$sv['name'],'avatar'=>$sv['avatar']??''], 20) ?>
        <?= clean($sv['name']) ?>
      </div>
      <?php if ($isHired): ?>
        <div style="margin:6px 0;"><span class="hired-banner">✅ Position Filled</span></div>
      <?php endif; ?>
      <p style="font-size:12px;color:var(--text3);line-height:1.5;"><?= clean(substr($sv['description'],0,80)) ?>...</p>
    </div>
    <div class="service-footer">
      <div>
        <div class="service-price">$<?= number_format($sv['price'],2) ?></div>
        <div class="service-delivery"><?= $sv['delivery_days'] ?>-day delivery</div>
      </div>

      <?php if ($isOwner): ?>
        <div style="display:flex;flex-direction:column;gap:5px;align-items:flex-end;">
          <span style="font-size:11px;color:var(--brand);font-weight:600;">Your listing</span>
          <?php if (!$isHired): ?>
          <form method="POST">
            <input type="hidden" name="csrf"       value="<?= csrf() ?>">
            <input type="hidden" name="action"     value="mark_hired">
            <input type="hidden" name="service_id" value="<?= $sv['id'] ?>">
            <button type="submit" class="btn btn-sm" style="background:#059669;color:#fff;border:none;font-size:11px;" onclick="return confirm('Mark this service as hired/filled?')">✓ Mark Hired</button>
          </form>
          <?php else: ?>
          <form method="POST">
            <input type="hidden" name="csrf"       value="<?= csrf() ?>">
            <input type="hidden" name="action"     value="reopen">
            <input type="hidden" name="service_id" value="<?= $sv['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px;">↺ Reopen</button>
          </form>
          <?php endif; ?>
        </div>

      <?php elseif ($isHired): ?>
        <span class="hired-banner" style="font-size:11px;">Position Filled</span>

      <?php else: ?>
        <button class="btn btn-primary btn-sm" data-modal="contact-<?= $sv['id'] ?>">Contact →</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Contact Modal -->
  <?php if (!$isOwner && !$isHired): ?>
  <div class="modal-overlay" id="contact-<?= $sv['id'] ?>">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title">Contact <?= clean($sv['name']) ?></h2>
        <button class="modal-close">×</button>
      </div>
      <div style="background:var(--surface2);border-radius:var(--r);padding:14px;margin-bottom:18px;">
        <div style="font-size:13px;font-weight:600;"><?= clean($sv['title']) ?></div>
        <div style="font-size:12px;color:var(--text3);margin-top:2px;">$<?= number_format($sv['price'],2) ?> · <?= $sv['delivery_days'] ?>-day delivery</div>
      </div>
      <form method="POST" action="api/contact.php">
        <input type="hidden" name="csrf"     value="<?= csrf() ?>">
        <input type="hidden" name="to_id"    value="<?= $sv['user_id'] ?>">
        <input type="hidden" name="ref_type" value="service">
        <input type="hidden" name="ref_id"   value="<?= $sv['id'] ?>">
        <input type="hidden" name="redirect" value="marketplace.php">
        <div class="form-group">
          <label class="form-label">Your Message</label>
          <textarea name="body" class="form-control" rows="4" placeholder="Hi! I'm interested in your service. I need..." required data-max="500"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Send Message →</button>
        <p style="font-size:11px;color:var(--text3);text-align:center;margin-top:8px;">They'll be notified by email and in-app.</p>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php endforeach; endif; ?>
</div>

<?php if ($r['pages']>1): ?>
<div class="pagination">
  <?php for($i=1;$i<=$r['pages'];$i++): ?>
    <<?= $i==$page?'span class="active"':'a href="?page='.$i.($fc?"&cat=$fc":'').'"' ?>><?= $i ?></<?= $i==$page?'span':'a' ?>>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- List Service Modal -->
<div class="modal-overlay" id="svc-modal">
  <div class="modal">
    <div class="modal-header"><h2 class="modal-title">List Your Service</h2><button class="modal-close">×</button></div>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="save">
      <div class="form-group">
        <label class="form-label">Service Title</label>
        <input type="text" name="title" class="form-control" placeholder="e.g. I will design your logo professionally" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category" class="form-control" required>
            <?php foreach($cIcons as $c=>$i): ?><option value="<?= $c ?>"><?= $i ?> <?= ucfirst($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Price (USD)</label>
          <input type="number" name="price" class="form-control" placeholder="25" min="1" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Delivery Time</label>
        <select name="delivery_days" class="form-control">
          <option value="1">1 day</option><option value="3">3 days</option>
          <option value="7" selected>7 days</option><option value="14">14 days</option><option value="30">30 days</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4" data-max="1000" placeholder="Describe what you offer, your experience, and what the client receives..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Publish Service →</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>