<?php
require_once __DIR__ . '/_common.php';
$statuses=['Available','Ready','Reserved','Booked','Unavailable'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();$action=spost('action');$id=sid(spost('id'));
        if($action==='delete'){
            if(!$id)throw new RuntimeException('Invalid transportation record.');
            if(sexec('DELETE FROM transportation WHERE id=?','i',[$id])!==1)throw new RuntimeException('Transportation record not found.');
            sflash('Tour transportation record deleted.');sredirect('transport.php');
        }
        if($action!=='save')throw new RuntimeException('Invalid transportation action.');
        $vehicle=svalid_text(spost('vehicle'),'Vehicle name',100);
        $destination=svalid_text(spost('destination'),'Destination',100);
        $status=spost('status');if(!in_array($status,$statuses,true))throw new RuntimeException('Invalid vehicle status.');
        $price=svalid_price(spost('price'));
        if($id){
            if(sexec('UPDATE transportation SET vehicle=?,destination=?,price=?,status=? WHERE id=?','ssssi',[$vehicle,$destination,$price,$status,$id])<0)throw new RuntimeException('Could not update transportation.');
            sflash('Tour transportation updated.');
        } else {
            sexec('INSERT INTO transportation (vehicle,destination,price,status) VALUES (?,?,?,?)','ssss',[$vehicle,$destination,$price,$status]);
            sflash('Tour transportation added.');
        }
        sredirect('transport.php');
    }catch(Throwable $e){sfail($e,'transport.php');}
}
$edit=sid(sget('edit'));
$current=$edit?sone('SELECT * FROM transportation WHERE id=?','i',[$edit]):null;
$items=srows('SELECT id,vehicle,destination,status,price FROM transportation ORDER BY id DESC LIMIT 200');
sstart('Tour Transportation','transport','Manage tour buses, vans, cars, destinations and availability.');
?>
<section class="panel"><div class="panel-head"><h2><?= $current?'Edit Vehicle #'.(int)$current['id']:'Add Tour Transportation' ?></h2><span class="count-chip">Tour vehicles</span></div>
<form method="post" class="admin-form admin-form-grid"><?php stoken_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $current?(int)$current['id']:0 ?>">
<div class="form-field"><label>Vehicle Name *</label><input name="vehicle" maxlength="100" required value="<?= sh($current['vehicle'] ?? '') ?>" placeholder="e.g. Safari Jeep"></div>
<div class="form-field"><label>Destination *</label><input name="destination" maxlength="100" required value="<?= sh($current['destination'] ?? '') ?>" placeholder="e.g. Yala"></div>
<div class="form-field"><label>Price (Rs.) *</label><input type="number" name="price" min="0.01" max="99999999.99" step="0.01" value="<?= sh(preg_replace('/[^\d.]/','',(string)($current['price']??''))) ?>" required></div>
<div class="form-field"><label>Status *</label><select name="status"><?php foreach($statuses as $s): ?><option value="<?= sh($s) ?>" <?= ($current['status']??'Available')===$s?'selected':'' ?>><?= sh($s) ?></option><?php endforeach; ?></select></div>
<div class="form-actions"><button class="btn btn-primary" type="submit"><?= $current?'Save Changes':'Add Vehicle' ?></button><a class="btn btn-muted" href="transport.php">Clear Form</a></div></form></section>
<section class="panel"><div class="panel-head"><h2>Tour Transportation List</h2><span class="count-chip"><?= count($items) ?> entries</span></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Vehicle</th><th>Destination</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$items): ?><tr><td class="empty-cell" colspan="6">No vehicles found.</td></tr><?php endif; ?>
<?php foreach($items as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><strong><?= sh($r['vehicle']) ?></strong></td><td><?= sh($r['destination']) ?></td><td><?= smoney($r['price']) ?></td><td><?= sbadge($r['status']) ?></td><td class="actions-cell"><a class="btn btn-outline btn-sm" href="transport.php?edit=<?= (int)$r['id'] ?>">Edit</a> <?php sconfirm_action((int)$r['id'],'delete','Delete','danger','Delete this tour vehicle?'); ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Tour Transportation uses the transportation table. Hotel Transport uses a separate table, accessed through Hotel Transport in the menu.</p></section>
<?php send(); ?>
