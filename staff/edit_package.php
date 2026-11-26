<?php
require_once __DIR__ . '/_common.php';
$id=sid(sget('id'));if(!$id)sredirect('packages.php');
$package=sone('SELECT * FROM packages WHERE id=?','i',[$id]);if(!$package){sflash('Package not found.','error');sredirect('packages.php');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();
        $name=svalid_text(spost('package_name'),'Package name',200);
        $destination=svalid_text(spost('destination'),'Destination',200);
        $details=svalid_text(spost('details'),'Description',3000,true);
        $price=svalid_price(spost('price'));
        $image=supload_image('image') ?? (string)($package['image'] ?? '');
        sexec('UPDATE packages SET package_name=?,destination=?,price=?,details=?,image=? WHERE id=?','sssssi',[$name,$destination,$price,$details,$image,$id]);
        sflash('Package #'.$id.' updated successfully.');sredirect('packages.php');
    }catch(Throwable $e){sfail($e,'edit_package.php?id='.$id);}
}
$pic=simage_url($package['image']);
sstart('Edit Tour Package','packages','Update destination, price, details and package image.');
?>
<section class="panel"><div class="panel-head"><h2>Editing Package #<?= $id ?></h2><a class="btn btn-muted" href="packages.php">← Back</a></div>
<form class="admin-form admin-form-grid" method="post" enctype="multipart/form-data"><?php stoken_field(); ?>
<div class="form-field"><label for="name">Package Name *</label><input id="name" name="package_name" maxlength="200" value="<?= sh($package['package_name']) ?>" required></div>
<div class="form-field"><label for="destination">Destination *</label><input id="destination" name="destination" maxlength="200" value="<?= sh($package['destination']) ?>" required></div>
<div class="form-field"><label for="price">Price (Rs.) *</label><input id="price" type="number" min="0.01" max="99999999.99" step="0.01" name="price" value="<?= sh($package['price']) ?>" required></div>
<div class="form-field"><label for="image">Replace Package Image (optional)</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" data-image-preview="packagePreview"><p class="form-help">Leave empty to keep the existing image.</p><?php if($pic): ?><img class="form-image-preview" src="<?= sh($pic) ?>" alt="Current package image"><?php endif; ?><img id="packagePreview" class="form-image-preview" alt="New selected image" hidden></div>
<div class="form-field wide-field"><label for="details">Description</label><textarea id="details" name="details" maxlength="3000" rows="6"><?= sh($package['details']) ?></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">Save Changes</button><a href="packages.php" class="btn btn-muted">Cancel</a></div>
</form></section><?php send(); ?>
