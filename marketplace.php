<?php
// marketplace.php
require_once 'includes/config.php';requireAuth();
$cIcons=['design'=>'🎨','coding'=>'💻','tutoring'=>'📚','photography'=>'📷','translation'=>'🌐','editing'=>'✏️','other'=>'🔧'];
$cBg=['design'=>'#F5F3FF','coding'=>'#EFF6FF','tutoring'=>'#ECFDF5','photography'=>'#FFF7ED','translation'=>'#EFF6FF','editing'=>'#FFF1F2','other'=>'#F8FAFC'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $t=s($_POST['title']??'');$d=s($_POST['description']??'');$c=isset($cIcons[$_POST['category']??''])?$_POST['category']:'';
    $p=(float)($_POST['price']??0);$del=in_array((int)($_POST['delivery_days']??0),[1,3,7,14,30])?(int)$_POST['delivery_days']:7;
    if(strlen($t)>=5&&$c&&$p>=1&&strlen($d)>=20){
        db()->prepare("INSERT INTO services(user_id,title,description,category,price,delivery_days)VALUES(?,?,?,?,?,?)")->execute([auth()['id'],$t,$d,$c,$p,$del]);
        flash('success','Service listed!');header('Location:'.BASE_URL.'/marketplace.php');exit;
    }else{flash('error','Please fill all fields correctly.');}
}
$page=max(1,(int)($_GET['page']??1));$fc=isset($cIcons[$_GET['cat']??''])?$_GET['cat']??'':'';
$r=paginate("SELECT s.*,u.name,u.university FROM services s JOIN users u ON s.user_id=u.id WHERE s.is_active=1".($fc?" AND s.category='$fc'":'')." ORDER BY s.created_at DESC",[],  $page,12);
$svcs=$r['items'];
$pageTitle='Marketplace';include 'includes/header.php';
?>
<div class="sec-hdr"><div><div class="sec-title">Skill Marketplace 💼</div><div class="sec-sub">Hire student talent or list your own services</div></div>
  <button class="btn btn-primary" data-modal="svc-modal">+ List Your Service</button></div>
<div class="filter-bar">
  <a href="marketplace.php" class="btn btn-sm <?=!$fc?'btn-primary':'btn-ghost'?>">All</a>
  <?php foreach($cIcons as $c=>$i):?><a href="?cat=<?=$c?>" class="btn btn-sm <?=$fc===$c?'btn-primary':'btn-ghost'?>"><?=$i?> <?=ucfirst($c)?></a><?php endforeach;?>
</div>
<div class="service-grid">
  <?php if(empty($svcs)):?>
    <div style="grid-column:1/-1"><div class="card"><div class="empty-state"><div class="icon">💼</div><h3>No services yet</h3><p>Be the first to list a skill!</p><button class="btn btn-primary" data-modal="svc-modal">List Your Service →</button></div></div></div>
  <?php else: foreach($svcs as $sv):?>
  <div class="service-card">
    <div class="service-thumb" style="background:<?=$cBg[$sv['category']]??'#F8FAFC'?>"><?=$cIcons[$sv['category']]??'🔧'?></div>
    <div class="service-body">
      <div class="service-title"><?=clean($sv['title'])?></div>
      <div class="service-seller">by <?=clean($sv['name'])?> · <?=clean($sv['university']??'Student')?></div>
      <p style="font-size:12px;color:var(--text3);line-height:1.5;"><?=clean(substr($sv['description'],0,80))?>...</p>
    </div>
    <div class="service-footer">
      <div><div class="service-price">$<?=number_format($sv['price'],2)?></div><div class="service-delivery"><?=$sv['delivery_days']?>-day delivery</div></div>
      <button class="btn btn-primary btn-sm" onclick="toast('Contact sent to <?=clean($sv['name'])?>!')">Contact</button>
    </div>
  </div>
  <?php endforeach; endif;?>
</div>
<?php if($r['pages']>1):?><div class="pagination"><?php for($i=1;$i<=$r['pages'];$i++):?><<?=$i==$page?'span class="active"':'a href="?page='.$i.($fc?"&cat=$fc":'').'"'?>><?=$i?></<?=$i==$page?'span':'a'?>><?php endfor;?></div><?php endif;?>

<div class="modal-overlay" id="svc-modal"><div class="modal">
  <div class="modal-header"><h2 class="modal-title">List Your Service</h2><button class="modal-close">×</button></div>
  <form method="POST" novalidate><input type="hidden" name="csrf" value="<?=csrf()?>">
    <div class="form-group"><label class="form-label">Service Title</label><input type="text" name="title" class="form-control" placeholder="e.g. I will design your logo professionally" required></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Category</label>
        <select name="category" class="form-control" required><?php foreach($cIcons as $c=>$i):?><option value="<?=$c?>"><?=$i?> <?=ucfirst($c)?></option><?php endforeach;?></select>
      </div>
      <div class="form-group"><label class="form-label">Price (USD)</label><input type="number" name="price" class="form-control" placeholder="25" min="1" max="10000" required></div>
    </div>
    <div class="form-group"><label class="form-label">Delivery Time</label>
      <select name="delivery_days" class="form-control"><option value="1">1 day</option><option value="3">3 days</option><option value="7" selected>7 days</option><option value="14">14 days</option><option value="30">30 days</option></select>
    </div>
    <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" data-max="1000" placeholder="Describe what you offer..." required></textarea></div>
    <button type="submit" class="btn btn-primary btn-full">Publish Service →</button>
  </form>
</div></div>
<?php include 'includes/footer.php';?>
