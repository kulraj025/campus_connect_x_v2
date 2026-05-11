<?php
// abroad.php
require_once 'includes/config.php';requireAuth();
$cats=['visa'=>'🛂','housing'=>'🏠','jobs'=>'💼','scams'=>'⚠️','language'=>'🗣','costs'=>'💰','general'=>'📝'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $co=s($_POST['country']??'');$ca=isset($cats[$_POST['category']??''])?$_POST['category']:'';
    $ti=s($_POST['title']??'');$bo=s($_POST['body']??'');
    if(strlen($co)>=2&&$ca&&strlen($ti)>=5&&strlen($bo)>=10){
        db()->prepare("INSERT INTO abroad_tips(user_id,country,category,title,body)VALUES(?,?,?,?,?)")->execute([auth()['id'],$co,$ca,$ti,$bo]);
        flash('success','Tip shared!');header('Location:'.BASE_URL.'/abroad.php');exit;
    }else{flash('error','Please fill all fields correctly.');}
}
$page=max(1,(int)($_GET['page']??1));$fc=s($_GET['country']??'');$fca=$_GET['category']??'';
$where=[];$params=[];
if($fc){$where[]="t.country LIKE ?";$params[]="%$fc%";}
if(isset($cats[$fca])&&$fca){$where[]="t.category=?";$params[]=$fca;}
$wstr=$where?"WHERE ".implode(" AND ",$where):"";
$r=paginate("SELECT t.*,u.name,u.university FROM abroad_tips t JOIN users u ON t.user_id=u.id $wstr ORDER BY t.created_at DESC",$params,$page,8);
$tips=$r['items'];
$pageTitle='Abroad Hub';include 'includes/header.php';
?>
<div class="feed-layout">
<div>
  <div class="card" style="margin-bottom:18px;">
    <div class="card-header"><h3>🌍 Share an Abroad Tip</h3>
      <button class="btn btn-ghost btn-sm" onclick="var f=document.getElementById('tf');f.style.display=f.style.display==='none'?'block':'none'">Toggle Form</button>
    </div>
    <div class="card-body" id="tf">
      <form method="POST" novalidate><input type="hidden" name="csrf" value="<?=csrf()?>">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control" placeholder="e.g. Germany, UK, Japan" required></div>
          <div class="form-group"><label class="form-label">Category</label>
            <select name="category" class="form-control" required><option value="">Select...</option>
              <?php foreach($cats as $v=>$e):?><option value="<?=$v?>"><?=$e?> <?=ucfirst($v)?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-control" placeholder="e.g. How I got my student visa in 2 weeks" required></div>
        <div class="form-group"><label class="form-label">Your Tip</label><textarea name="body" class="form-control" rows="4" data-max="2000" placeholder="Share your experience in detail..." required></textarea></div>
        <button type="submit" class="btn btn-primary">Share Tip →</button>
      </form>
    </div>
  </div>
  <form method="GET" class="filter-bar">
    <input type="text" name="country" class="form-control" placeholder="Filter by country..." value="<?=clean($fc)?>" style="max-width:160px;">
    <select name="category" class="form-control" style="width:auto;">
      <option value="">All Categories</option>
      <?php foreach($cats as $v=>$e):?><option value="<?=$v?>" <?=$fca===$v?'selected':''?>><?=$e?> <?=ucfirst($v)?></option><?php endforeach;?>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <?php if($fc||$fca):?><a href="abroad.php" class="btn btn-ghost">Clear</a><?php endif;?>
  </form>
  <div class="post-feed">
    <?php if(empty($tips)):?>
      <div class="card"><div class="empty-state"><div class="icon">🌍</div><h3>No tips yet</h3><p>Be the first to share an abroad survival tip!</p></div></div>
    <?php else: foreach($tips as $tip):?>
    <div class="post-card">
      <div class="post-header">
        <div class="post-av" style="background:linear-gradient(135deg,#10B981,#059669);"><?=initials($tip['name'])?></div>
        <div><div class="post-author"><?=clean($tip['name'])?></div><div class="post-meta"><?=clean($tip['university']??'Student')?> · <?=ago($tip['created_at'])?></div></div>
        <span class="badge bg-g"><?=($cats[$tip['category']]??'').' '.ucfirst($tip['category'])?></span>
      </div>
      <div class="post-body">
        <span class="badge bg-b" style="margin-bottom:8px;display:inline-flex;">📍 <?=clean($tip['country'])?></span>
        <strong style="display:block;font-size:15px;margin-bottom:8px;color:var(--text);"><?=clean($tip['title'])?></strong>
        <?=nl2br(clean($tip['body']))?>
      </div>
      <div class="post-footer">
        <button class="post-action" onclick="var s=this.querySelector('span');s.textContent=parseInt(s.textContent)+1">
          👍 <span><?=$tip['helpful_count']?></span> Helpful
        </button>
        <?php if($tip['user_id']==auth()['id']):?>
        <form method="POST" action="api/del.php" style="margin-left:auto;" onsubmit="return confirm('Delete?')">
          <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="type" value="tip"><input type="hidden" name="id" value="<?=$tip['id']?>">
          <button type="submit" class="post-action" style="color:var(--danger);">🗑 Delete</button>
        </form>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
  <?php if($r['pages']>1):?><div class="pagination">
    <?php for($i=1;$i<=$r['pages'];$i++):?><<?=$i==$page?'span class="active"':'a href="?page='.$i.($fc?"&country=$fc":'').($fca?"&category=$fca":'').'"'?>><?=$i?></<?=$i==$page?'span':'a'?>><?php endfor;?>
  </div><?php endif;?>
</div>
<div>
  <div class="card widget" style="margin-bottom:16px;"><div class="widget-title">🌐 Top Countries</div>
    <?php foreach(['Germany 🇩🇪','United Kingdom 🇬🇧','Canada 🇨🇦','Australia 🇦🇺','Japan 🇯🇵','UAE 🇦🇪','Netherlands 🇳🇱','South Korea 🇰🇷'] as $c):$cn=explode(' ',$c)[0];?>
    <a href="?country=<?=urlencode($cn)?>" class="trend-item"><div><div class="trend-text"><?=$c?></div><div class="trend-count">View tips</div></div></a>
    <?php endforeach;?>
  </div>
  <div class="card widget"><div class="widget-title">📋 Categories</div>
    <?php foreach($cats as $v=>$e):?><a href="?category=<?=$v?>" class="trend-item"><div class="trend-text"><?=$e?> <?=ucfirst($v)?></div></a><?php endforeach;?>
  </div>
</div>
</div>
<?php include 'includes/footer.php';?>
