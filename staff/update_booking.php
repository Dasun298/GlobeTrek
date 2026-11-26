<?php
require_once __DIR__ . '/_common.php';
$id=sid(sget('id'));if(!$id)sredirect('bookings.php');
$booking=sone('SELECT b.id,b.status,b.travel_date,b.customer_name,p.package_name FROM bookings b LEFT JOIN packages p ON p.id=b.package_id WHERE b.id=?','i',[$id]);
if(!$booking){sflash('Booking not found.','error');sredirect('bookings.php');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();$action=spost('action');stour_action($id,$action);
        sflash('Tour booking #'.$id.' updated.');sredirect('bookings.php');
    }catch(Throwable $e){sfail($e,'update_booking.php?id='.$id);}
}
$status=strtolower(trim((string)$booking['status']));
sstart('Update Booking','bookings','Change a tour booking status using approved transitions.');
?>
<section class="panel"><div class="panel-head"><h2>Booking #<?= $id ?></h2><a href="bookings.php" class="btn btn-muted">← Back to Bookings</a></div>
<div class="detail-grid"><div><small>Customer</small><strong><?= sh($booking['customer_name']) ?></strong></div><div><small>Package</small><strong><?= sh($booking['package_name']) ?></strong></div><div><small>Travel date</small><strong><?= sh($booking['travel_date']) ?></strong></div></div>
<p>Current status: <?= sbadge($booking['status']) ?></p><div class="catalog-actions">
<?php if($status==='pending'): sconfirm_action($id,'confirm','Confirm Booking','good','Confirm this booking?');sconfirm_action($id,'cancel','Cancel Booking','danger','Cancel this booking?');elseif($status==='confirmed'):sconfirm_action($id,'complete','Mark Completed','primary','Mark this booking completed?');else:?><p class="panel-note">This booking has no available Staff status transitions.</p><?php endif; ?></div>
<p class="panel-note">Payment status is never updated to Paid by these controls.</p></section>
<?php send(); ?>
