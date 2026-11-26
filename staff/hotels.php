<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();$action=spost('action');$id=sid(spost('id'));
        if($action!=='delete'||!$id)throw new RuntimeException('Invalid hotel action.');
        if(snumber('SELECT COUNT(*) FROM hotel_bookings WHERE hotel_id=?','i',[$id])>0)throw new RuntimeException('This hotel is linked to reservations. Set it to Unavailable instead of deleting.');
        if(sexec('DELETE FROM hotels WHERE id=?','i',[$id])!==1)throw new RuntimeException('Hotel not found.');
        sflash('Hotel deleted; its image was retained for safety.');sredirect('hotels.php');
    }catch(Throwable $e){sfail($e,'hotels.php');}
}
$hotels=srows('SELECT h.*,(SELECT COUNT(*) FROM hotel_bookings hb WHERE hb.hotel_id=h.id) AS booking_count FROM hotels h ORDER BY h.id DESC LIMIT 150');
sstart('Manage Hotels','hotels','Manage hotel availability, pricing and property images.');
?>
<section class="panel"><div class="panel-head"><h2>Hotels & Resorts</h2><a href="add_hotel.php" class="btn btn-primary">+ Add Hotel</a></div>
<?php if(!$hotels): ?><div class="empty-state">No hotels found. <a href="add_hotel.php">Add a hotel</a>.</div><?php else: ?><div class="catalog-grid">
<?php foreach($hotels as $r): $photo=simage_url($r['image'],true); ?><article class="catalog-card"><div class="catalog-photo"><?php if($photo): ?><img loading="lazy" src="<?= sh($photo) ?>" alt="<?= sh($r['hotel_name']) ?>"><?php else: ?><div class="image-fallback">⌂</div><?php endif; ?></div>
<div class="catalog-body"><div class="card-topline"><span class="eyebrow">HOTEL #<?= (int)$r['id'] ?></span><?= sbadge($r['status']) ?></div><h3><?= sh($r['hotel_name']) ?></h3><p class="catalog-meta">⌖ <?= sh($r['location']) ?></p><p class="catalog-meta">Room: <?= sh($r['room_type'] ?: 'Not specified') ?></p><strong class="catalog-price"><?= smoney($r['price']) ?></strong><p class="catalog-description"><?= (int)$r['booking_count'] ?> booking reference(s)</p><div class="catalog-actions"><a class="btn btn-outline btn-sm" href="edit_hotel.php?id=<?= (int)$r['id'] ?>">Edit Hotel</a><?php if((int)$r['booking_count']===0):sconfirm_action((int)$r['id'],'delete','Delete','danger','Delete this unbooked hotel?');else:?><span class="muted">Linked to bookings</span><?php endif;?></div></div></article><?php endforeach; ?>
</div><?php endif; ?><p class="panel-note">Hotels linked to reservations cannot be deleted. Use status Unavailable instead. Showing up to 150 hotels.</p></section>
<?php send(); ?>
