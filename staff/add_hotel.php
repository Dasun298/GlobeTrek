<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();
        $name=svalid_text(spost('hotel_name'),'Hotel name',100);
        $location=svalid_text(spost('location'),'Location',100);
        $room=svalid_text(spost('room_type'),'Room type',100,true);
        $price=svalid_price(spost('price'));
        $status=spost('status');if(!in_array($status,['Available','Unavailable'],true))throw new RuntimeException('Invalid hotel status.');
        $image=supload_image('image',true)??'';
        sexec('INSERT INTO hotels (hotel_name,location,room_type,price,status,image) VALUES (?,?,?,?,?,?)','ssssss',[$name,$location,$room,$price,$status,$image]);
        sflash('Hotel added successfully.');sredirect('hotels.php');
    }catch(Throwable $e){sfail($e,'add_hotel.php');}
}
sstart('Add Hotel','hotels','Create a new property in GlobeTrek hotel listings.');
?>
<section class="panel"><div class="panel-head"><h2>Hotel Details</h2><a href="hotels.php" class="btn btn-muted">← Back to Hotels</a></div><form method="post" enctype="multipart/form-data" class="admin-form admin-form-grid"><?php stoken_field(); ?>
<div class="form-field"><label>Hotel Name *</label><input name="hotel_name" maxlength="100" required placeholder="e.g. Kandy Lake Hotel"></div>
<div class="form-field"><label>Location *</label><input name="location" maxlength="100" required placeholder="e.g. Kandy"></div>
<div class="form-field"><label>Room Type</label><input name="room_type" maxlength="100" placeholder="Family Suite / Deluxe"></div>
<div class="form-field"><label>Price (Rs.) *</label><input name="price" type="number" min="0.01" max="99999999.99" step="0.01" required></div>
<div class="form-field"><label>Availability *</label><select name="status"><option value="Available">Available</option><option value="Unavailable">Unavailable</option></select></div>
<div class="form-field"><label>Hotel Photo (optional)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" data-image-preview="hotelPreview"><p class="form-help">JPG, PNG, WEBP or AVIF (max 5 MB).</p><img id="hotelPreview" alt="Image preview" class="form-image-preview" hidden></div>
<div class="form-actions"><button class="btn btn-primary">Add Hotel</button><a href="hotels.php" class="btn btn-muted">Cancel</a></div></form></section><?php send(); ?>
