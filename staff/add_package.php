<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        srequire_post();
        $name=svalid_text(spost('package_name'),'Package name',200);
        $destination=svalid_text(spost('destination'),'Destination',200);
        $details=svalid_text(spost('details'),'Description',3000,true);
        $price=svalid_price(spost('price'));
        $image=supload_image('image') ?? '';
        sexec('INSERT INTO packages (package_name,destination,price,details,image) VALUES (?,?,?,?,?)','sssss',[$name,$destination,$price,$details,$image]);
        sflash('Tour package added successfully.');sredirect('packages.php');
    }catch(Throwable $e){sfail($e,'add_package.php');}
}
sstart('Add Tour Package','packages','Publish a new destination and package to the catalog.');
?>
<section class="panel"><div class="panel-head"><h2>New Package Details</h2><a class="btn btn-muted" href="packages.php">← Back to Packages</a></div>
<form class="admin-form admin-form-grid" method="post" enctype="multipart/form-data"><?php stoken_field(); ?>
<div class="form-field"><label for="name">Package Name *</label><input id="name" name="package_name" maxlength="200" placeholder="e.g. Ella Mountain Adventure" required></div>
<div class="form-field"><label for="destination">Destination *</label><input id="destination" name="destination" maxlength="200" placeholder="e.g. Ella" required></div>
<div class="form-field"><label for="price">Package Price (Rs.) *</label><input id="price" type="number" min="0.01" max="99999999.99" step="0.01" name="price" required></div>
<div class="form-field"><label for="image">Tour Image (optional)</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" data-image-preview="packageImagePreview"><p class="form-help">JPG, PNG, WEBP or AVIF; max 5 MB.</p><img id="packageImagePreview" class="form-image-preview" alt="Selected preview" hidden></div>
<div class="form-field wide-field"><label for="details">Package Description</label><textarea id="details" name="details" maxlength="3000" rows="5" placeholder="Itinerary, highlights and tour experience"></textarea></div>
<div class="form-actions"><button class="btn btn-primary" type="submit">Save New Package</button><a href="packages.php" class="btn btn-muted">Cancel</a></div>
</form></section><?php send(); ?>
