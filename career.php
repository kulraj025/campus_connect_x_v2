<?php
require_once 'includes/config.php'; requireAuth();
$tIcons=['internship'=>'🎓','part-time'=>'⏰','full-time'=>'💼','remote'=>'🌐','gig'=>'⚡'];
$tColors=['internship'=>'bg-b','part-time'=>'bg-p','full-time'=>'bg-g','remote'=>'bg-a','gig'=>'bg-r'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $ti=s($_POST['title']??'');$co=s($_POST['company']??'');$lo=s($_POST['location']??'');
    $ty=isset($tIcons[$_POST['type']??''])?$_POST['type']:'';$de=s($_POST['description']??'');
    $li=filter_var(trim($_POST['apply_link']??''),FILTER_SANITIZE_URL);
    $sa=is_numeric($_POST['salary']??'')?(float)$_POST['salary']:null;
    $dl=!empty($_POST['deadline'])?$_POST['deadline']:null;
    if(strlen($ti)>=3&&strlen($co)>=2&&strlen($lo)>=2&&$ty&&strlen($de)>=20){
        db()->prepare("INSERT INTO jobs(user_id,title,company,location,type,description,apply_link,salary,deadline)VALUES(?,?,?,?,?,?,?,?,?)")->execute([auth()['id'],$ti,$co,$lo,$ty,$de,$li?:null,$sa,$dl]);
        flash('success','Job posted!');header('Location:'.BASE_URL.'/career.php');exit;
    }else{flash('error','Please fill all required fields.');}
}
$page=max(1,(int)($_GET['page']??1));$ft=isset($tIcons[$_GET['type']??''])?$_GET['type']??'':'';
$r=paginate("SELECT j.*,u.name FROM jobs j JOIN users u ON j.user_id=u.id WHERE j.is_active=1".($ft?" AND j.type='$ft'":'')." ORDER BY j.created_at DESC",[],  $page,10);
$jobs=$r['items'];
$pageTitle='Career Hub';include 'includes/header.php';
?>
<div class="sec-hdr">
  <div><div class="sec-title">Career Hub 🚀</div><div class="sec-sub">Internships, jobs &amp; student opportunities</div></div>
  <button class="btn btn-primary" data-modal="job-modal">+ Post a Job</button>
</div>
<div class="filter-bar">
  <a href="career.php" class="btn btn-sm <?=!$ft?'btn-primary':'btn-ghost'?>">All</a>
  <?php foreach($tIcons as $t=>$i):?><a href="?type=<?=$t?>" class="btn btn-sm <?=$ft===$t?'btn-primary':'btn-ghost'?>"><?=$i?> <?=ucfirst($t)?></a><?php endforeach;?>
</div>
<?php if(empty($jobs)):?>
  <div class="card"><div class="empty-state"><div class="icon">🚀</div><h3>No jobs posted yet</h3><p>Post the first opportunity!</p><button class="btn btn-primary" data-modal="job-modal">Post a Job →</button></div></div>
<?php else: foreach($jobs as $j):?>
<div class="job-card">
  <div class="job-logo"><?=$tIcons[$j['type']]??'🏢'?></div>
  <div style="flex:1;min-width:0;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
      <div><div class="job-title"><?=clean($j['title'])?></div><div class="job-company"><?=clean($j['company'])?></div></div>
      <span class="badge <?=$tColors[$j['type']]??'bg-b'?>"><?=ucfirst($j['type'])?></span>
    </div>
    <div class="job-meta" style="margin-bottom:10px;">
      <span>📍 <?=clean($j['location'])?></span>
      <?php if($j['salary']):?><span>💰 $<?=number_format($j['salary'])?>/mo</span><?php endif;?>
      <?php if($j['deadline']):?><span>⏰ By <?=date('M d, Y',strtotime($j['deadline']))?></span><?php endif;?>
      <span>Posted by <?=clean($j['name'])?> · <?=ago($j['created_at'])?></span>
    </div>
    <p style="font-size:13px;color:var(--text2);line-height:1.6;margin-bottom:12px;"><?=nl2br(clean(substr($j['description'],0,250)))?><?=strlen($j['description'])>250?'...':''?></p>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <?php if($j['apply_link']):?>
        <a href="<?=clean($j['apply_link'])?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Apply Now →</a>
      <?php else:?>
        <button class="btn btn-primary btn-sm" onclick="toast('Application sent! ✓')">Apply Now →</button>
      <?php endif;?>
      <?php if($j['user_id']==auth()['id']):?>
      <form method="POST" action="api/del.php" onsubmit="return confirm('Delete this job?')">
        <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="type" value="job"><input type="hidden" name="id" value="<?=$j['id']?>">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">🗑 Delete</button>
      </form>
      <?php endif;?>
    </div>
  </div>
</div>
<?php endforeach; endif;?>
<?php if($r['pages']>1):?><div class="pagination"><?php for($i=1;$i<=$r['pages'];$i++):?><<?=$i==$page?'span class="active"':'a href="?page='.$i.($ft?"&type=$ft":'').'"'?>><?=$i?></<?=$i==$page?'span':'a'?>><?php endfor;?></div><?php endif;?>

<div class="modal-overlay" id="job-modal"><div class="modal">
  <div class="modal-header"><h2 class="modal-title">Post a Job / Internship</h2><button class="modal-close">×</button></div>
  <form method="POST" novalidate><input type="hidden" name="csrf" value="<?=csrf()?>">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Job Title *</label><input type="text" name="title" class="form-control" placeholder="e.g. Frontend Intern" required></div>
      <div class="form-group"><label class="form-label">Company *</label><input type="text" name="company" class="form-control" placeholder="Company name" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Location *</label><input type="text" name="location" class="form-control" placeholder="City or Remote" required></div>
      <div class="form-group"><label class="form-label">Type *</label>
        <select name="type" class="form-control" required><?php foreach($tIcons as $t=>$i):?><option value="<?=$t?>"><?=$i?> <?=ucfirst($t)?></option><?php endforeach;?></select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Description *</label><textarea name="description" class="form-control" rows="4" data-max="2000" placeholder="Role, requirements, what you're looking for..." required></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Monthly Salary (optional)</label><input type="number" name="salary" class="form-control" placeholder="e.g. 500"></div>
      <div class="form-group"><label class="form-label">Deadline (optional)</label><input type="date" name="deadline" class="form-control" min="<?=date('Y-m-d')?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Apply Link (optional)</label><input type="url" name="apply_link" class="form-control" placeholder="https://..."></div>
    <button type="submit" class="btn btn-primary btn-full">Post Job →</button>
  </form>
</div></div>
<?php include 'includes/footer.php';?>
