<?php
require_once __DIR__ . '/_common.php';
$statuses=['Available','Unavailable','Ready','Reserved','Booked'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();$action=spost('action');$id=sid(spost('id'));
        if($action==='delete'){
            if(!$id)throw new RuntimeException('Invalid hotel vehicle ID.');
            if(snumber('SELECT COUNT(*) FROM hotel_bookings WHERE transport_id=?','i',[$id])>0)throw new RuntimeException('Vehicle is linked to hotel bookings. Set it to Unavailable instead.');
            if(sexec('DELETE FROM transport WHERE id=?','i',[$id])!==1)throw new RuntimeException('Vehicle not found.');
            sflash('Unreferenced hotel transport record deleted.');sredirect('hotel_transport.php');
        }
        if($action!=='save')throw new RuntimeException('Invalid vehicle action.');
        $name=svalid_text(spost('vehicle_name'),'Vehicle name',100);
        $type=svalid_text(spost('vehicle_type'),'Vehicle type',100,true);
        $number=svalid_text(spost('vehicle_number'),'Vehicle number',100,true);
        $destination=svalid_text(spost('destination'),'Destination',100,true);
        $status=spost('status');if(!in_array($status,$statuses,true))throw new RuntimeException('Invalid status.');
        $price=svalid_price(spost('price'));
        if($id){
            sexec('UPDATE transport SET vehicle_name=?,vehicle_type=?,vehicle_number=?,destination=?,status=?,price=? WHERE id=?','ssssssi',[$name,$type,$number,$destination,$status,$price,$id]);sflash('Hotel transport updated.');
        } else {
            sexec('INSERT INTO transport (vehicle_name,vehicle_type,vehicle_number,destination,status,price) VALUES (?,?,?,?,?,?)','ssssss',[$name,$type,$number,$destination,$status,$price]);sflash('Hotel transport added.');
        }
        sredirect('hotel_transport.php');
    }catch(Throwable $e){sfail($e,'hotel_transport.php');}
}
$edit=sid(sget('edit'));
$current=$edit?sone('SELECT * FROM transport WHERE id=?','i',[$edit]):null;
$items=srows('SELECT t.*,(SELECT COUNT(*) FROM hotel_bookings hb WHERE hb.transport_id=t.id) AS booking_count FROM transport t ORDER BY id DESC LIMIT 200');
sstart('Hotel Transport','hotel_transport','Vehicles available when customers request hotel stays.');
?>
<section class="panel"><div class="panel-head"><h2><?= $current?'Edit Hotel Vehicle #'.(int)$current['id']:'Add Hotel Transport' ?></h2><span class="count-chip">Hotel booking vehicles</span></div>
<form class="admin-form admin-form-grid" method="post"><?php stoken_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $current?(int)$current['id']:0 ?>">
<div class="form-field"><label>Vehicle Name *</label><input name="vehicle_name" maxlength="100" value="<?= sh($current['vehicle_name']??'') ?>" required></div>
<div class="form-field"><label>Vehicle Type</label><input name="vehicle_type" maxlength="100" value="<?= sh($current['vehicle_type']??'') ?>" placeholder="Van, Bus or Car"></div>
<div class="form-field"><label>Vehicle Number</label><input name="vehicle_number" maxlength="100" value="<?= sh($current['vehicle_number']??'') ?>" placeholder="e.g. WP-7788"></div>
<div class="form-field"><label>Destination (optional)</label><input name="destination" maxlength="100" value="<?= sh($current['destination']??'') ?>"></div>
<div class="form-field"><label>Price (Rs.) *</label><input type="number" name="price" min="0.01" max="99999999.99" step="0.01" value="<?= sh(preg_replace('/[^\d.]/','',(string)($current['price']??''))) ?>" required></div>
<div class="form-field"><label>Availability *</label><select name="status"><?php foreach($statuses as $s): ?><option value="<?= sh($s) ?>" <?= ($current['status']??'Available')===$s?'selected':'' ?>><?= sh($s) ?></option><?php endforeach; ?></select></div>
<div class="form-actions"><button class="btn btn-primary"><?= $current?'Save Changes':'Add Hotel Vehicle' ?></button><a href="hotel_transport.php" class="btn btn-muted">Clear Form</a></div></form></section>
<section class="panel"><div class="panel-head"><h2>Hotel Vehicles</h2><span class="count-chip"><?= count($items) ?> entries</span></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Vehicle</th><th>Type / Number</th><th>Destination</th><th>Price</th><th>Status</th><th>Bookings</th><th>Action</th></tr></thead><tbody>
<?php if(!$items): ?><tr><td class="empty-cell" colspan="8">No transport found.</td></tr><?php endif; ?>
<?php foreach($items as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><strong><?= sh($r['vehicle_name']) ?></strong></td><td><?= sh($r['vehicle_type']) ?><small><?= sh($r['vehicle_number']) ?></small></td><td><?= sh($r['destination']) ?></td><td><?= smoney($r['price']) ?></td><td><?= sbadge($r['status']) ?></td><td><?= (int)$r['booking_count'] ?></td><td class="actions-cell"><a href="hotel_transport.php?edit=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm">Edit</a> <?php if(!(int)$r['booking_count']): sconfirm_action((int)$r['id'],'delete','Delete','danger','Delete this unbooked hotel vehicle?'); else: ?><span class="muted">Linked to bookings</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Hotel Transport uses the transport table. Only records without hotel booking references can be deleted.</p></section>
<?php send(); ?>
