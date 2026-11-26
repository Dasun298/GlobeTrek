<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        srequire_post();$action=spost('action');$id=sid(spost('id'));
        if($action!=='delete'||!$id)throw new RuntimeException('Invalid package action.');
        if(snumber('SELECT COUNT(*) FROM bookings WHERE package_id=?','i',[$id])>0) throw new RuntimeException('Package is linked to bookings. Edit the package instead of deleting it.');
        if(sexec('DELETE FROM packages WHERE id=?','i',[$id])!==1)throw new RuntimeException('Package not found.');
        sflash('Package deleted. Existing uploaded images were retained for safety.');sredirect('packages.php');
    }catch(Throwable $e){sfail($e,'packages.php');}
}
$search=substr(sget('search'),0,80);$like='%'.$search.'%';
$items=srows("SELECT p.id,p.package_name,p.destination,p.price,p.details,p.image,(SELECT COUNT(*) FROM bookings b WHERE b.package_id=p.id) AS booking_count FROM packages p WHERE (?='' OR p.package_name LIKE ? OR p.destination LIKE ?) ORDER BY p.id DESC LIMIT 150",'sss',[$search,$like,$like]);
sstart('Manage Packages','packages','Create and maintain the tour packages shown on the website.');
?>
<section class="panel"><div class="panel-head"><h2>Tour Package Catalog</h2><a class="btn btn-primary" href="add_package.php">+ Add New Package</a></div>
<form method="get" role="search" class="filter-bar"><label class="search-group">Search package or destination<input name="search" maxlength="80" value="<?= sh($search) ?>" placeholder="Ella, Yala, Galle..."></label><button class="btn btn-primary">Search</button><a class="btn btn-muted" href="packages.php">Reset</a></form>
<?php if(!$items): ?><div class="empty-state"><h3>No packages found</h3><p>Add your first package to get started.</p><a class="btn btn-primary" href="add_package.php">Add Package</a></div><?php else: ?>
<div class="catalog-grid">
<?php foreach($items as $r): $pic=simage_url($r['image']); ?>
<article class="catalog-card"><div class="catalog-photo"><?php if($pic): ?><img loading="lazy" src="<?= sh($pic) ?>" alt="<?= sh($r['destination']) ?>"><?php else: ?><div class="image-fallback" aria-label="No package photo">✈</div><?php endif; ?></div>
<div class="catalog-body"><div class="card-topline"><span class="eyebrow">PACKAGE #<?= (int)$r['id'] ?></span><span class="staff-pill-note"><?= (int)$r['booking_count'] ?> booking(s)</span></div><h3><?= sh($r['package_name']) ?></h3><p class="catalog-meta">⌖ <?= sh($r['destination']) ?></p><strong class="catalog-price"><?= smoney($r['price']) ?></strong><p class="catalog-description"><?= sh(substr((string)$r['details'],0,190)) ?></p>
<div class="catalog-actions"><a class="btn btn-outline btn-sm" href="edit_package.php?id=<?= (int)$r['id'] ?>">Edit Package</a>
<?php if((int)$r['booking_count']===0): sconfirm_action((int)$r['id'],'delete','Delete','danger','Delete this unbooked package?'); else: ?><span class="muted">Deletion blocked: booking history</span><?php endif; ?>
</div></div></article>
<?php endforeach; ?></div><?php endif; ?>
<p class="panel-note">Showing at most 150 packages. Packages linked to bookings cannot be deleted. Changes appear on the public Tour Packages page.</p></section>
<?php send(); ?>
