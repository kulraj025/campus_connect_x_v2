<?php
require_once 'includes/config.php';
requireAuth();

$tIcons  = ['internship'=>'🎓','part-time'=>'⏰','full-time'=>'💼','remote'=>'🌐','gig'=>'⚡'];
$tColors = ['internship'=>'bg-b','part-time'=>'bg-p','full-time'=>'bg-g','remote'=>'bg-a','gig'=>'bg-r'];

// Post job
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='save') {
    verifyCsrf();
    $ti=s($_POST['title']??''); $co=s($_POST['company']??''); $lo=s($_POST['location']??'');
    $ty=isset($tIcons[$_POST['type']??''])?$_POST['type']:'';
    $de=s($_POST['description']??'');
    $li=filter_var(trim($_POST['apply_link']??''),FILTER_SANITIZE_URL);
    $sa=is_numeric($_POST['salary']??'')?(float)$_POST['salary']:null;
    $dl=!empty($_POST['deadline'])?$_POST['deadline']:null;
    if (strlen($ti)>=3&&strlen($co)>=2&&strlen($lo)>=2&&$ty&&strlen($de)>=20) {
        db()->prepare("INSERT INTO jobs(user_id,title,company,location,type,description,apply_link,salary,deadline,is_active)VALUES(?,?,?,?,?,?,?,?,?,1)")
           ->execute([auth()['id'],$ti,$co,$lo,$ty,$de,$li?:null,$sa,$dl]);
        flash('success','Job posted!');
    } else flash('error','Please fill all required fields.');
    header('Location:'.BASE_URL.'/career.php'); exit;
}

// Mark hired
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='mark_hired') {
    verifyCsrf(); $jid=(int)($_POST['job_id']??0);
    $c=db()->prepare("SELECT id FROM jobs WHERE id=? AND user_id=?"); $c->execute([$jid,auth()['id']]);
    if ($c->fetch()) { db()->prepare("UPDATE jobs SET is_hired=1 WHERE id=?")->execute([$jid]); flash('success','Position marked as filled!'); }
    header('Location:'.BASE_URL.'/career.php'); exit;
}

// Reopen
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='reopen') {
    verifyCsrf(); $jid=(int)($_POST['job_id']??0);
    $c=db()->prepare("SELECT id FROM jobs WHERE id=? AND user_id=?"); $c->execute([$jid,auth()['id']]);
    if ($c->fetch()) { db()->prepare("UPDATE jobs SET is_hired=0 WHERE id=?")->execute([$jid]); flash('success','Job reopened!'); }
    header('Location:'.BASE_URL.'/career.php'); exit;
}

// Delete (owner or admin)
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    verifyCsrf(); $jid=(int)($_POST['job_id']??0);
    $isAdmin = !empty(auth()['is_admin']);
    $c=db()->prepare("SELECT id FROM jobs WHERE id=?".($isAdmin?'':' AND user_id=?'));
    $isAdmin ? $c->execute([$jid]) : $c->execute([$jid,auth()['id']]);
    if ($c->fetch()) { db()->prepare("DELETE FROM jobs WHERE id=?")->execute([$jid]); flash('success','Job removed.'); }
    header('Location:'.BASE_URL.'/career.php'); exit;
}

$page=max(1,(int)($_GET['page']??1));
$ft=isset($tIcons[$_GET['type']??''])?$_GET['type']:'';
$isAdmin=!empty(auth()['is_admin']);

$sql = "SELECT j.*,u.name,u.avatar,u.id as uid FROM jobs j JOIN users u ON j.user_id=u.id WHERE j.is_active=1".($ft?" AND j.type='$ft'":'')." ORDER BY j.is_hired ASC,j.created_at DESC";
$r=paginate($sql,[],$page,10); $jobs=$r['items'];

// Stats
$totalJobs   = (int)db()->query("SELECT COUNT(*) FROM jobs WHERE is_active=1")->fetchColumn();
$myJobs      = (int)db()->prepare("SELECT COUNT(*) FROM jobs WHERE user_id=?")->execute([auth()['id']]) ? (function(){ $s=db()->prepare("SELECT COUNT(*) FROM jobs WHERE user_id=?"); $s->execute([auth()['id']]); return $s->fetchColumn(); })() : 0;
$stmtMJ=db()->prepare("SELECT COUNT(*) FROM jobs WHERE user_id=?"); $stmtMJ->execute([auth()['id']]); $myJobs=$stmtMJ->fetchColumn();
$filledJobs  = (int)db()->query("SELECT COUNT(*) FROM jobs WHERE is_hired=1")->fetchColumn();

$pageTitle='Career Hub';
include 'includes/header.php';
?>

<style>
.career-layout{ display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
.career-main{}
.career-side{ position:sticky; top:20px; display:flex; flex-direction:column; gap:16px; }
.stat-strip{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.stat-box{ background:var(--surface); border:1px solid var(--border); border-radius:var(--r); padding:14px; text-align:center; }
.stat-box-val{ font-size:22px; font-weight:900; color:var(--brand); font-family:var(--fd); }
.stat-box-lbl{ font-size:10px; color:var(--text3); font-weight:600; text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }
.hired-banner{ display:inline-flex; align-items:center; gap:4px; background:#ECFDF5; color:#065F46; border:1.5px solid #6EE7B7; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.job-card-new{ background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); padding:18px 20px; margin-bottom:14px; transition:var(--t); }
.job-card-new:hover{ box-shadow:var(--shm); border-color:var(--border2); }
.job-card-new.filled{ border-left:4px solid #10B981; opacity:.8; }
.job-card-top{ display:flex; gap:14px; align-items:flex-start; margin-bottom:12px; }
.job-icon{ width:46px; height:46px; border-radius:var(--r); background:var(--surface2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.job-name{ font-size:15px; font-weight:700; color:var(--text); margin-bottom:2px; font-family:var(--fd); }
.job-co{ font-size:13px; color:var(--text2); margin-bottom:5px; }
.job-tags{ display:flex; gap:6px; flex-wrap:wrap; }
.job-desc{ font-size:13px; color:var(--text2); line-height:1.7; margin-bottom:14px; }
.job-footer{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding-top:12px; border-top:1px solid var(--border); }
.job-meta-item{ font-size:12px; color:var(--text3); display:flex; align-items:center; gap:4px; }
.admin-badge{ display:inline-flex; align-items:center; gap:4px; background:#FFF7ED; color:#92400E; border:1px solid #FDE68A; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
@media(max-width:900px){ .career-layout{ grid-template-columns:1fr; } .career-side{ display:none; } }
</style>

<div class="sec-hdr">
  <div>
    <div class="sec-title">Career Hub 🚀</div>
    <div class="sec-sub">Internships, jobs &amp; student opportunities</div>
  </div>
  <button class="btn btn-primary" data-modal="job-modal">+ Post a Job</button>
</div>

<!-- Stats -->
<div class="stat-strip">
  <div class="stat-box"><div class="stat-box-val"><?=$totalJobs?></div><div class="stat-box-lbl">Open Jobs</div></div>
  <div class="stat-box"><div class="stat-box-val"><?=$myJobs?></div><div class="stat-box-lbl">My Postings</div></div>
  <div class="stat-box"><div class="stat-box-val"><?=$filledJobs?></div><div class="stat-box-lbl">Filled Roles</div></div>
</div>

<div class="career-layout">
<div class="career-main">

  <!-- Filter tabs -->
  <div class="filter-bar" style="margin-bottom:16px;">
    <a href="career.php" class="btn btn-sm <?=!$ft?'btn-primary':'btn-ghost'?>">All</a>
    <?php foreach($tIcons as $t=>$i):?>
    <a href="?type=<?=$t?>" class="btn btn-sm <?=$ft===$t?'btn-primary':'btn-ghost'?>"><?=$i?> <?=ucfirst($t)?></a>
    <?php endforeach;?>
  </div>

  <?php if(empty($jobs)):?>
  <div class="card"><div class="empty-state">
    <span class="icon">🚀</span><h3>No jobs yet</h3><p>Post the first opportunity for students!</p>
    <button class="btn btn-primary" data-modal="job-modal">Post a Job →</button>
  </div></div>
  <?php else: foreach($jobs as $j):
    $isHired = !empty($j['is_hired']);
    $isOwner = ((int)$j['user_id']===auth()['id']);
    $canAdmin= $isAdmin && !$isOwner;
  ?>

  <div class="job-card-new <?=$isHired?'filled':''?>">
    <div class="job-card-top">
      <div class="job-icon"><?=$tIcons[$j['type']]??'🏢'?></div>
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
          <div>
            <div class="job-name">
              <?=clean($j['title'])?>
              <?php if($isHired):?><span class="hired-banner" style="font-size:10px;margin-left:8px;vertical-align:middle;">✅ Filled</span><?php endif;?>
            </div>
            <div class="job-co"><?=clean($j['company'])?></div>
          </div>
          <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
            <span class="badge <?=$tColors[$j['type']]??'bg-b'?>"><?=ucfirst($j['type'])?></span>
            <?php if($isOwner):?><span class="admin-badge">⭐ Your Post</span><?php endif;?>
            <?php if($canAdmin):?><span class="admin-badge" style="background:#FEF2F2;color:#991B1B;border-color:#FECACA;">🛡 Admin</span><?php endif;?>
          </div>
        </div>
        <div class="job-tags" style="margin-top:6px;">
          <span class="job-meta-item">📍 <?=clean($j['location'])?></span>
          <?php if($j['salary']):?><span class="job-meta-item">💰 $<?=number_format($j['salary'])?>/mo</span><?php endif;?>
          <?php if($j['deadline']):?><span class="job-meta-item">⏰ <?=date('M d',strtotime($j['deadline']))?></span><?php endif;?>
          <span class="job-meta-item" style="margin-left:auto;"><?=ago($j['created_at'])?></span>
        </div>
      </div>
    </div>

    <p class="job-desc"><?=nl2br(clean(substr($j['description'],0,280)))?><?=strlen($j['description'])>280?'…':''?></p>

    <div class="job-footer">
      <?php if($isOwner || $isAdmin):?>
        <?php if(!$isOwner && $isAdmin):?><span style="font-size:11px;font-weight:700;color:#EF4444;">🛡 Admin view</span><?php endif;?>
        <?php if($isOwner):?><span style="font-size:11px;color:var(--brand);font-weight:600;">⭐ Your posting</span><?php endif;?>

        <?php if(!$isHired):?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="csrf"    value="<?=csrf()?>">
          <input type="hidden" name="action"  value="mark_hired">
          <input type="hidden" name="job_id"  value="<?=$j['id']?>">
          <button type="submit" class="btn btn-sm" style="background:#059669;color:#fff;border:none;" onclick="return confirm('Mark as filled?')">✓ Mark Filled</button>
        </form>
        <?php else:?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="csrf"    value="<?=csrf()?>">
          <input type="hidden" name="action"  value="reopen">
          <input type="hidden" name="job_id"  value="<?=$j['id']?>">
          <button type="submit" class="btn btn-ghost btn-sm">↺ Reopen</button>
        </form>
        <?php endif;?>

        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this job permanently?')">
          <input type="hidden" name="csrf"    value="<?=csrf()?>">
          <input type="hidden" name="action"  value="delete">
          <input type="hidden" name="job_id"  value="<?=$j['id']?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">🗑 Remove</button>
        </form>

        <?php if(!empty($j['apply_link'])):?>
        <a href="<?=clean($j['apply_link'])?>" target="_blank" class="btn btn-ghost btn-sm">🔗 View Link</a>
        <?php endif;?>

      <?php elseif($isHired):?>
        <span class="hired-banner">✅ This position has been filled</span>

      <?php else:?>
        <?php if($j['apply_link']):?>
        <a href="<?=clean($j['apply_link'])?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Apply Now →</a>
        <?php endif;?>
        <button class="btn btn-outline btn-sm" data-modal="apply-<?=$j['id']?>">✉ Send Application</button>
      <?php endif;?>
    </div>
  </div>

  <!-- Apply modal -->
  <?php if(!$isOwner && !$isHired && !$isAdmin):?>
  <div class="modal-overlay" id="apply-<?=$j['id']?>">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title">Apply: <?=clean($j['title'])?></h2>
        <button class="modal-close">×</button>
      </div>
      <div style="background:var(--surface2);border-radius:var(--r);padding:12px;margin-bottom:16px;">
        <div style="font-size:13px;font-weight:600;"><?=clean($j['company'])?></div>
        <div style="font-size:12px;color:var(--text3);">📍 <?=clean($j['location'])?> · <?=ucfirst($j['type'])?></div>
      </div>
      <form method="POST" action="api/contact.php">
        <input type="hidden" name="csrf"     value="<?=csrf()?>">
        <input type="hidden" name="to_id"    value="<?=$j['user_id']?>">
        <input type="hidden" name="ref_type" value="job">
        <input type="hidden" name="ref_id"   value="<?=$j['id']?>">
        <input type="hidden" name="redirect" value="career.php">
        <div class="form-group">
          <label class="form-label">Cover Letter / Message</label>
          <textarea name="body" class="form-control" rows="5" placeholder="Hi, I'm interested in this role because..." required data-max="1000"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Send Application →</button>
      </form>
    </div>
  </div>
  <?php endif;?>

  <?php endforeach; endif;?>

  <?php if($r['pages']>1):?>
  <div class="pagination">
    <?php for($i=1;$i<=$r['pages'];$i++):?>
    <<?=$i==$page?'span class="active"':'a href="?page='.$i.($ft?"&type=$ft":'').'"'?>><?=$i?></<?=$i==$page?'span':'a'?>>
    <?php endfor;?>
  </div>
  <?php endif;?>

</div><!-- /career-main -->

<!-- Right sidebar -->
<div class="career-side">
  <div class="card">
    <div class="widget-title">📊 By Type</div>
    <div style="padding:0 14px 14px;">
      <?php foreach($tIcons as $t=>$i):
        $cnt=db()->prepare("SELECT COUNT(*) FROM jobs WHERE type=? AND is_active=1"); $cnt->execute([$t]); $cnt=$cnt->fetchColumn();
      ?>
      <a href="?type=<?=$t?>" style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text2);font-size:13px;transition:var(--t);" onmouseover="this.style.color='var(--brand)'" onmouseout="this.style.color='var(--text2)'">
        <span><?=$i?> <?=ucfirst($t)?></span>
        <span style="font-weight:700;color:var(--brand);"><?=$cnt?></span>
      </a>
      <?php endforeach;?>
    </div>
  </div>

  <?php if($isAdmin):?>
  <div class="card" style="border:1.5px solid #FDE68A;">
    <div class="widget-title" style="color:#92400E;">🛡 Admin Controls</div>
    <div style="padding:0 14px 14px;font-size:12px;color:var(--text2);line-height:1.7;">
      You have full admin access. You can remove any job posting and mark any position as filled.<br><br>
      <a href="<?=BASE_URL?>/admin_ads.php" class="btn btn-sm" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A;width:100%;justify-content:center;">Go to Admin Panel →</a>
    </div>
  </div>
  <?php endif;?>

  <div class="card">
    <div class="widget-title">💡 Posting Tips</div>
    <div style="padding:0 14px 14px;font-size:12px;color:var(--text2);line-height:1.8;">
      ✅ Be specific about requirements<br>
      ✅ Include a salary range<br>
      ✅ Set a clear deadline<br>
      ✅ Add an external apply link<br>
      ✅ Mark as filled once hired
    </div>
  </div>
</div>

</div><!-- /career-layout -->

<!-- Post Job Modal -->
<div class="modal-overlay" id="job-modal">
  <div class="modal">
    <div class="modal-header"><h2 class="modal-title">Post a Job / Internship</h2><button class="modal-close">×</button></div>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf"   value="<?=csrf()?>">
      <input type="hidden" name="action" value="save">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Job Title *</label><input type="text" name="title" class="form-control" placeholder="e.g. Frontend Intern" required></div>
        <div class="form-group"><label class="form-label">Company *</label><input type="text" name="company" class="form-control" placeholder="Company name" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Location *</label><input type="text" name="location" class="form-control" placeholder="Seoul or Remote" required></div>
        <div class="form-group"><label class="form-label">Type *</label>
          <select name="type" class="form-control" required>
            <?php foreach($tIcons as $t=>$i):?><option value="<?=$t?>"><?=$i?> <?=ucfirst($t)?></option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Description * <span style="font-weight:400;color:var(--text3);">(min 20 chars)</span></label>
        <textarea name="description" class="form-control" rows="4" data-max="2000" placeholder="Role, requirements, what you're looking for..." required></textarea>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Monthly Salary (optional)</label><input type="number" name="salary" class="form-control" placeholder="e.g. 500"></div>
        <div class="form-group"><label class="form-label">Deadline (optional)</label><input type="date" name="deadline" class="form-control" min="<?=date('Y-m-d')?>"></div>
      </div>
      <div class="form-group"><label class="form-label">External Apply Link (optional)</label><input type="url" name="apply_link" class="form-control" placeholder="https://..."></div>
      <button type="submit" class="btn btn-primary btn-full">Post Job →</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php';?>