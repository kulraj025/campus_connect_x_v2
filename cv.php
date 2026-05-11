<?php
require_once 'includes/config.php'; requireAuth();
$user = auth();
$ps = db()->prepare("SELECT * FROM profiles WHERE user_id=?"); $ps->execute([$user['id']]); $profile = $ps->fetch();
if (!$profile) { flash('error','Please complete your profile first to generate a CV.'); header('Location:'.BASE_URL.'/profile.php'); exit; }
$skills = json_decode($profile['skills']      ?? '[]', true) ?: [];
$exp    = json_decode($profile['experience']  ?? '[]', true) ?: [];
$edu    = json_decode($profile['education']   ?? '[]', true) ?: [];
$langs  = json_decode($profile['languages']   ?? '[]', true) ?: [];
$certs  = json_decode($profile['certifications'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= clean($user['name']) ?> — CV | Campus Connect X</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:#F0F4FF;color:#0F172A;-webkit-font-smoothing:antialiased}
a{color:#2563EB;text-decoration:none}

/* ACTION BAR */
.cv-actions{max-width:900px;margin:0 auto;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;transition:all .2s}
.btn-primary{background:#2563EB;color:#fff} .btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0} .btn-ghost:hover{background:#E2E8F0}
.btn-success{background:#10B981;color:#fff}

/* CV WRAPPER */
.cv-wrap{max-width:900px;margin:0 auto;padding:0 24px 40px}

/* CV PAPER */
.cv-paper{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.12)}

/* HEADER */
.cv-head{background:linear-gradient(130deg,#0F172A 0%,#1E3A5F 55%,#2D1B69 100%);padding:40px 48px;color:#fff;display:flex;justify-content:space-between;align-items:flex-start;gap:24px;position:relative;overflow:hidden}
.cv-head::after{content:'';position:absolute;top:-60px;right:-60px;width:280px;height:280px;background:radial-gradient(circle,rgba(139,92,246,.15) 0%,transparent 70%);border-radius:50%}
.cv-name{font-family:'Syne',sans-serif;font-size:34px;font-weight:800;margin-bottom:4px;line-height:1.1}
.cv-role{font-size:15px;color:rgba(255,255,255,.6);margin-bottom:16px}
.cv-contacts{display:flex;flex-wrap:wrap;gap:10px 18px}
.cv-contact{font-size:12px;color:rgba(255,255,255,.65);display:flex;align-items:center;gap:5px}
.cv-av{width:84px;height:84px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#8B5CF6);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:30px;font-weight:800;color:#fff;flex-shrink:0;border:3px solid rgba(255,255,255,.2);z-index:1}

/* BODY */
.cv-body{display:grid;grid-template-columns:1fr 270px}
.cv-main{padding:36px 40px;border-right:1px solid #E2E8F0}
.cv-side{padding:32px 26px;background:#F8FAFF}

/* SECTIONS */
.cv-sec{margin-bottom:28px}
.cv-sec:last-child{margin-bottom:0}
.cv-sec-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#2563EB;margin-bottom:14px;padding-bottom:6px;border-bottom:2px solid #EFF6FF}
.cv-summary{font-size:14px;color:#475569;line-height:1.75}

/* TIMELINE */
.cv-item{margin-bottom:18px;padding-left:14px;border-left:2px solid #2563EB}
.cv-item:last-child{margin-bottom:0}
.cv-item-hdr{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:2px}
.cv-item-title{font-size:14px;font-weight:600;color:#0F172A}
.cv-item-period{font-size:11px;color:#94A3B8;white-space:nowrap;flex-shrink:0}
.cv-item-org{font-size:12px;color:#2563EB;font-weight:500;margin-bottom:4px}
.cv-item-desc{font-size:12px;color:#64748B;line-height:1.65}

/* SIDE */
.cv-side-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#8B5CF6;margin-bottom:10px;padding-bottom:5px;border-bottom:2px solid #F5F3FF}
.cv-side-sec{margin-bottom:24px}
.cv-side-sec:last-child{margin-bottom:0}
.skill-pill{display:inline-block;background:#EFF6FF;color:#1D4ED8;font-size:11px;font-weight:600;padding:4px 10px;border-radius:99px;margin:3px}
.cv-lang{display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;color:#475569}
.cv-cert{font-size:12px;color:#475569;padding:5px 0;border-bottom:1px solid #F1F5F9}
.cv-link{font-size:12px;margin-bottom:6px;display:block}
.cv-meta-row{margin-bottom:10px}
.cv-meta-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94A3B8;margin-bottom:2px}
.cv-meta-val{font-size:13px;color:#475569}

/* PRINT */
@media print{
  body{background:#fff}
  .cv-actions{display:none!important}
  .cv-wrap{padding:0;max-width:100%}
  .cv-paper{border-radius:0;box-shadow:none}
  .cv-body{grid-template-columns:1fr 240px}
  .cv-head{padding:30px 36px}
  .cv-main{padding:28px 32px}
  .cv-side{padding:28px 20px}
}
@media(max-width:700px){
  .cv-body{grid-template-columns:1fr}
  .cv-side{border-top:1px solid #E2E8F0}
  .cv-head{flex-direction:column}
  .cv-main,.cv-head{padding:24px}
  .cv-side{padding:24px}
}
</style>
</head>
<body>

<!-- ACTION BAR -->
<div class="cv-actions">
  <a href="<?= BASE_URL ?>/profile.php" class="btn btn-ghost">← Back to Profile</a>
  <div style="display:flex;gap:10px;">
    <button onclick="window.print()" class="btn btn-success">⬇ Download / Print PDF</button>
    <a href="<?= BASE_URL ?>/profile.php" class="btn btn-ghost">✏️ Edit Profile</a>
  </div>
</div>

<!-- CV PAPER -->
<div class="cv-wrap">
<div class="cv-paper">

  <!-- HEADER -->
  <div class="cv-head">
    <div>
      <div class="cv-name"><?= clean($user['name']) ?></div>
      <div class="cv-role"><?= clean($user['department'] ?? 'Student') ?><?= $user['university'] ? ' · ' . clean($user['university']) : '' ?></div>
      <div class="cv-contacts">
        <span class="cv-contact">✉ <?= clean($user['email']) ?></span>
        <?php if(!empty($profile['phone'])):?><span class="cv-contact">📞 <?= clean($profile['phone']) ?></span><?php endif;?>
        <?php if(!empty($profile['city'])):?><span class="cv-contact">📍 <?= clean($profile['city']) ?><?= !empty($profile['country'])?', '.clean($profile['country']):'' ?></span><?php endif;?>
        <?php if(!empty($profile['linkedin'])):?><span class="cv-contact">🔗 <a href="<?= clean($profile['linkedin']) ?>" style="color:rgba(255,255,255,.65);">LinkedIn</a></span><?php endif;?>
        <?php if(!empty($profile['github'])):?><span class="cv-contact">💻 <a href="<?= clean($profile['github']) ?>" style="color:rgba(255,255,255,.65);">GitHub</a></span><?php endif;?>
        <?php if(!empty($profile['website'])):?><span class="cv-contact">🌐 <a href="<?= clean($profile['website']) ?>" style="color:rgba(255,255,255,.65);">Portfolio</a></span><?php endif;?>
      </div>
    </div>
    <div class="cv-av"><?= initials($user['name']) ?></div>
  </div>

  <!-- BODY -->
  <div class="cv-body">

    <!-- MAIN COLUMN -->
    <div class="cv-main">

      <?php if(!empty($profile['summary'])):?>
      <div class="cv-sec">
        <div class="cv-sec-title">Professional Summary</div>
        <p class="cv-summary"><?= clean($profile['summary']) ?></p>
      </div>
      <?php endif;?>

      <?php if(!empty($exp)):?>
      <div class="cv-sec">
        <div class="cv-sec-title">Work Experience</div>
        <?php foreach($exp as $e):?>
        <div class="cv-item">
          <div class="cv-item-hdr">
            <div class="cv-item-title"><?= clean($e['title']) ?></div>
            <span class="cv-item-period"><?= clean($e['period']) ?></span>
          </div>
          <div class="cv-item-org"><?= clean($e['org']) ?></div>
          <?php if(!empty($e['desc'])):?><div class="cv-item-desc"><?= nl2br(clean($e['desc'])) ?></div><?php endif;?>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>

      <?php if(!empty($edu)):?>
      <div class="cv-sec">
        <div class="cv-sec-title">Education</div>
        <?php foreach($edu as $e):?>
        <div class="cv-item" style="border-left-color:#8B5CF6;">
          <div class="cv-item-hdr">
            <div class="cv-item-title"><?= clean($e['title']) ?></div>
            <span class="cv-item-period"><?= clean($e['period']) ?></span>
          </div>
          <div class="cv-item-org" style="color:#8B5CF6;"><?= clean($e['org']) ?></div>
          <?php if(!empty($e['desc'])):?><div class="cv-item-desc"><?= nl2br(clean($e['desc'])) ?></div><?php endif;?>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>

    </div>

    <!-- SIDE COLUMN -->
    <div class="cv-side">

      <?php if(!empty($skills)):?>
      <div class="cv-side-sec">
        <div class="cv-side-title">Skills</div>
        <?php foreach($skills as $sk):?><span class="skill-pill"><?= clean($sk) ?></span><?php endforeach;?>
      </div>
      <?php endif;?>

      <?php if(!empty($langs)):?>
      <div class="cv-side-sec">
        <div class="cv-side-title">Languages</div>
        <?php foreach($langs as $l):?><div class="cv-lang">🗣 <?= clean($l) ?></div><?php endforeach;?>
      </div>
      <?php endif;?>

      <?php if(!empty($certs)):?>
      <div class="cv-side-sec">
        <div class="cv-side-title">Certifications</div>
        <?php foreach($certs as $c):?><div class="cv-cert">🏆 <?= clean($c) ?></div><?php endforeach;?>
      </div>
      <?php endif;?>

      <div class="cv-side-sec">
        <div class="cv-side-title">Academic Info</div>
        <?php if($user['university']):?><div class="cv-meta-row"><div class="cv-meta-lbl">University</div><div class="cv-meta-val"><?= clean($user['university']) ?></div></div><?php endif;?>
        <?php if($user['department']):?><div class="cv-meta-row"><div class="cv-meta-lbl">Department</div><div class="cv-meta-val"><?= clean($user['department']) ?></div></div><?php endif;?>
        <?php if($user['graduation_year']):?><div class="cv-meta-row"><div class="cv-meta-lbl">Graduation</div><div class="cv-meta-val"><?= clean($user['graduation_year']) ?></div></div><?php endif;?>
      </div>

      <?php if(!empty($profile['linkedin'])||!empty($profile['github'])||!empty($profile['website'])):?>
      <div class="cv-side-sec">
        <div class="cv-side-title">Links</div>
        <?php if(!empty($profile['linkedin'])):?><a class="cv-link" href="<?= clean($profile['linkedin']) ?>">🔗 LinkedIn Profile</a><?php endif;?>
        <?php if(!empty($profile['github'])):?><a class="cv-link" href="<?= clean($profile['github']) ?>">💻 GitHub Profile</a><?php endif;?>
        <?php if(!empty($profile['website'])):?><a class="cv-link" href="<?= clean($profile['website']) ?>">🌐 Portfolio Website</a><?php endif;?>
      </div>
      <?php endif;?>

      <div class="cv-side-sec" style="padding-top:16px;border-top:1px solid #E2E8F0;">
        <div style="font-size:10px;color:#94A3B8;text-align:center;">Generated by<br><strong style="color:#2563EB;">Campus Connect X</strong></div>
      </div>

    </div>
  </div>
</div>
</div>

</body>
</html>
