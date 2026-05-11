<?php
// dashboard.php
require_once 'includes/config.php'; requireAuth(); $user=auth();
$pc=db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?");$pc->execute([$user['id']]);$pc=$pc->fetchColumn();
$tc=db()->prepare("SELECT COUNT(*) FROM abroad_tips WHERE user_id=?");$tc->execute([$user['id']]);$tc=$tc->fetchColumn();
$sc=db()->prepare("SELECT COUNT(*) FROM services WHERE user_id=?");$sc->execute([$user['id']]);$sc=$sc->fetchColumn();
$jc=db()->prepare("SELECT COUNT(*) FROM jobs WHERE is_active=1");$jc->execute();$jc=$jc->fetchColumn();
$rp=db()->prepare("SELECT p.*,u.name,u.university FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 4");$rp->execute();$rp=$rp->fetchAll();
$pageTitle='Dashboard'; include 'includes/header.php';
?>
<div class="pb">
  <div class="pb-av" style="width:70px;height:70px;font-size:24px;"><?=initials($user['name'])?></div>
  <div style="flex:1;">
    <div class="pb-name">Welcome back, <?=clean(explode(' ',$user['name'])[0])?> 👋</div>
    <div class="pb-meta">
      <?php if($user['university']):?><span>🎓 <?=clean($user['university'])?></span><?php endif;?>
      <?php if($user['department']):?><span>📚 <?=clean($user['department'])?></span><?php endif;?>
      <span>📅 Joined <?=date('M Y',strtotime($user['created_at']))?></span>
      <?php if($user['is_verified']):?><span class="verified-badge">✓ Verified Student</span><?php endif;?>
    </div>
  </div>
  <div class="pb-actions">
    <a href="<?=BASE_URL?>/profile.php" class="pb-btn">✏️ Edit Profile</a>
    <a href="<?=BASE_URL?>/cv.php" class="pb-btn hi">📄 Generate CV</a>
  </div>
</div>
<div class="stats-grid">
  <div class="stat-card sc-b"><div class="stat-icon si-b">📢</div><div class="stat-val"><?=$pc?></div><div class="stat-lbl">Posts</div></div>
  <div class="stat-card sc-p"><div class="stat-icon si-p">🌍</div><div class="stat-val"><?=$tc?></div><div class="stat-lbl">Abroad Tips</div></div>
  <div class="stat-card sc-g"><div class="stat-icon si-g">💼</div><div class="stat-val"><?=$sc?></div><div class="stat-lbl">Services</div></div>
  <div class="stat-card sc-a"><div class="stat-icon si-a">🚀</div><div class="stat-val"><?=$jc?></div><div class="stat-lbl">Open Jobs</div></div>
</div>
<div class="sec-hdr" style="margin-bottom:16px;"><div><div class="sec-title">Platform Modules</div><div class="sec-sub">Everything you need as a student</div></div></div>
<div class="mods-grid" style="margin-bottom:28px;">
  <a href="<?=BASE_URL?>/community.php" class="mod-card"><div class="mod-icon" style="background:#EFF6FF;">👥</div><div class="mod-title">Community</div><div class="mod-desc">Share posts, skills, experiences and collaborate with peers.</div><span class="mod-badge mb-live">Live</span></a>
  <a href="<?=BASE_URL?>/abroad.php" class="mod-card"><div class="mod-icon" style="background:#ECFDF5;">🌍</div><div class="mod-title">Abroad Hub</div><div class="mod-desc">Visa tips, housing guides, scam alerts, survival tips.</div><span class="mod-badge mb-live">Live</span></a>
  <a href="<?=BASE_URL?>/marketplace.php" class="mod-card"><div class="mod-icon" style="background:#F5F3FF;">🛒</div><div class="mod-title">Marketplace</div><div class="mod-desc">Sell your skills: design, coding, tutoring &amp; more.</div><span class="mod-badge mb-live">Live</span></a>
  <a href="<?=BASE_URL?>/career.php" class="mod-card"><div class="mod-icon" style="background:#FFFBEB;">🚀</div><div class="mod-title">Career Hub</div><div class="mod-desc">Internships, part-time jobs and student opportunities.</div><span class="mod-badge mb-live">Live</span></a>
  <a href="<?=BASE_URL?>/profile.php" class="mod-card"><div class="mod-icon" style="background:#FFF1F2;">👤</div><div class="mod-title">Profile</div><div class="mod-desc">Build your student identity — experience, skills, contacts.</div><span class="mod-badge mb-live">Live</span></a>
  <a href="<?=BASE_URL?>/cv.php" class="mod-card"><div class="mod-icon" style="background:#F0FDF4;">📄</div><div class="mod-title">CV Builder</div><div class="mod-desc">Auto-generate a professional CV from your profile. Download PDF.</div><span class="mod-badge mb-live">Live</span></a>
</div>
<?php if(!empty($rp)):?>
<div class="sec-hdr"><div class="sec-title">Recent Community Posts</div><a href="<?=BASE_URL?>/community.php" class="btn btn-ghost btn-sm">View all →</a></div>
<div class="post-feed">
<?php foreach($rp as $post):?>
<div class="post-card">
  <div class="post-header">
    <div class="post-av" style="background:linear-gradient(135deg,#2563EB,#8B5CF6);"><?=initials($post['name'])?></div>
    <div><div class="post-author"><?=clean($post['name'])?></div><div class="post-meta"><?=clean($post['university']??'Student')?> · <?=ago($post['created_at'])?></div></div>
    <span class="post-tag pt-<?=$post['tag']?>"><?=ucfirst($post['tag'])?></span>
  </div>
  <div class="post-body"><?=nl2br(clean(substr($post['body'],0,200)))?><?=strlen($post['body'])>200?'...':''?></div>
</div>
<?php endforeach;?>
</div><?php endif;?>
<?php include 'includes/footer.php';?>
