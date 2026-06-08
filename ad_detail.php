<?php
require_once 'includes/config.php';
requireAuth();

$id   = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM ads WHERE id=? AND is_active=1");
$stmt->execute([$id]);
$ad   = $stmt->fetch();

if (!$ad) {
    flash('error', 'Ad not found or no longer active.');
    header('Location:'.BASE_URL.'/dashboard.php'); exit;
}

$hasImg     = !empty($ad['image_path']) && file_exists(__DIR__.'/'.$ad['image_path']);
$typeLabels = ['info'=>'ℹ️ Info','news'=>'📰 News','event'=>'📅 Event','feature'=>'🎉 Feature','notice'=>'📢 Notice'];

$pageTitle = clean($ad['title']);
include 'includes/header.php';
?>

<style>
.ad-detail-wrap {
  max-width: 740px;
  margin: 0 auto;
}
.ad-hero {
  border-radius: var(--rl);
  overflow: hidden;
  margin-bottom: 24px;
  background: <?= clean($ad['bg_color']) ?>;
}
/* Full image — never cropped, any size */
.ad-hero-img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: contain;
  background: <?= clean($ad['bg_color']) ?>;
  max-height: 520px;
}
.ad-hero-emoji {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  font-size: 72px;
}
.ad-type-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: <?= clean($ad['bg_color']) ?>;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 14px;
  border-radius: 20px;
  margin-bottom: 14px;
}
</style>

<div class="ad-detail-wrap">

  <a href="<?= BASE_URL ?>/dashboard.php"
     style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text3);text-decoration:none;margin-bottom:18px;transition:var(--t);"
     onmouseover="this.style.color='var(--brand)'"
     onmouseout="this.style.color='var(--text3)'">
    ← Back to Dashboard
  </a>

  <!-- Hero: image or emoji -->
  <div class="ad-hero">
    <?php if ($hasImg): ?>
      <img src="<?= BASE_URL.'/'.$ad['image_path'] ?>" class="ad-hero-img" alt="<?= clean($ad['title']) ?>">
    <?php else: ?>
      <div class="ad-hero-emoji"><?= clean($ad['emoji']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Type pill + meta -->
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <span class="ad-type-chip"><?= $typeLabels[$ad['type']] ?? ('📢 '.ucfirst($ad['type'])) ?></span>
    <?php if ($ad['ends_at']): ?>
      <span style="font-size:12px;color:var(--text3);">⏰ Expires <?= date('M d, Y', strtotime($ad['ends_at'])) ?></span>
    <?php endif; ?>
    <span style="font-size:12px;color:var(--text3);">📅 <?= ago($ad['created_at']) ?></span>
  </div>

  <!-- Title + body -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body">
      <h1 style="font-size:26px;font-weight:900;color:var(--text);margin-bottom:14px;line-height:1.25;font-family:var(--fd);">
        <?= clean($ad['title']) ?>
      </h1>
      <div style="font-size:15px;color:var(--text2);line-height:1.85;">
        <?= nl2br(clean($ad['body'])) ?>
      </div>
    </div>
  </div>

  <!-- CTA button -->
  <?php if (!empty($ad['cta_text']) && !empty($ad['cta_link'])): ?>
  <a href="<?= clean($ad['cta_link']) ?>"
     target="_blank" rel="noopener"
     class="btn btn-primary btn-lg"
     style="display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;">
    <?= clean($ad['cta_text']) ?> →
  </a>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>