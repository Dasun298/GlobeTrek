<?php
require_once __DIR__ . '/_common.php';
$id=sid(sget('id'));if(!$id)sredirect('hotels.php');
$hotel=sone('SELECT * FROM hotels WHERE id=?','i',[$id]);if(!$hotel){sflash('Hotel not found.','error');sredirect('hotels.php');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();
        $name=svalid_text(spost('hotel_name'),'Hotel name',100);
        $location=svalid_text(spost('location'),'Location',100);
        $room=svalid_text(spost('room_type'),'Room type',100,true);
        $price=svalid_price(spost('price'));$status=spost('status');
        if(!in_array($status,['Available','Unavailable'],true))throw new RuntimeException('Invalid hotel status.');
        $image=supload_image('image',true)??(string)$hotel['image'];
        sexec('UPDATE hotels SET hotel_name=?,location=?,room_type=?,price=?,status=?,image=? WHERE id=?','ssssssi',[$name,$location,$room,$price,$status,$image,$id]);
        sflash('Hotel #'.$id.' updated.');sredirect('hotels.php');
    }catch(Throwable $e){sfail($e,'edit_hotel.php?id='.$id);}
}
$pic=simage_url($hotel['image'],true);
sstart('Edit Hotel','hotels','Update property details, availability and hotel photo.');
?>
<section class="panel"><div class="panel-head"><h2>Editing Hotel #<?= $id ?></h2><a href="hotels.php" class="btn btn-muted">← Back</a></div><form method="post" enctype="multipart/form-data" class="admin-form admin-form-grid"><?php stoken_field(); ?>
<div class="form-field"><label>Hotel Name *</label><input name="hotel_name" maxlength="100" value="<?= sh($hotel['hotel_name']) ?>" required></div>
<div class="form-field"><label>Location *</label><input name="location" maxlength="100" value="<?= sh($hotel['location']) ?>" required></div>
<div class="form-field"><label>Room Type</label><input name="room_type" maxlength="100" value="<?= sh($hotel['room_type']) ?>"></div>
<div class="form-field"><label>Price (Rs.) *</label><input type="number" name="price" step="0.01" min="0.01" max="99999999.99" value="<?= sh($hotel['price']) ?>" required></div>
<div class="form-field"><label>Availability *</label><select name="status"><?php foreach(['Available','Unavailable'] as $s): ?><option value="<?= sh($s) ?>" <?= $hotel['status']===$s?'selected':'' ?>><?= sh($s) ?></option><?php endforeach; ?></select></div>
<div class="form-field"><label>Replace Hotel Photo (optional)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" data-image-preview="newHotelPreview"><?php if($pic): ?><img src="<?= sh($pic) ?>" class="form-image-preview" alt="Current hotel photo"><?php endif; ?><img id="newHotelPreview" alt="New photo preview" class="form-image-preview" hidden></div>
<div class="form-actions"><button class="btn btn-primary">Save Changes</button><a class="btn btn-muted" href="hotels.php">Cancel</a></div></form></section><?php send(); ?>
