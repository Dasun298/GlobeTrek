<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        srequire_post();$id=sid(spost('id'));$action=spost('action');
        if(!$id||!in_array($action,['confirm','cancel'],true)) throw new RuntimeException('Invalid hotel booking action.');
        $new=$action==='confirm'?'Confirmed':'Cancelled';
        if(sexec("UPDATE hotel_bookings SET status=? WHERE id=? AND LOWER(TRIM(status))='pending'",'si',[$new,$id])!==1) throw new RuntimeException('Only pending hotel bookings can be changed.');
        sflash('Hotel booking #'.$id.' marked '.$new.'.');sredirect('hotel_bookings.php');
    } catch(Throwable $e){ sfail($e,'hotel_bookings.php'); }
}
$items=srows('SELECT hb.id,hb.checkin_date,hb.checkout_date,hb.payment_method,hb.status,u.username,h.hotel_name,h.location,t.vehicle_name FROM hotel_bookings hb LEFT JOIN users u ON u.id=hb.user_id LEFT JOIN hotels h ON h.id=hb.hotel_id LEFT JOIN transport t ON t.id=hb.transport_id ORDER BY hb.id DESC LIMIT 200');
$pending=(int)snumber("SELECT COUNT(*) FROM hotel_bookings WHERE LOWER(TRIM(status))='pending'");
sstart('Hotel Bookings','hotel_bookings','Coordinate hotel stays and pending reservation requests.');
?>
<div class="metrics two"><article class="metric-card"><p>Total Hotel Bookings</p><strong><?= (int)snumber('SELECT COUNT(*) FROM hotel_bookings') ?></strong></article><article class="metric-card"><p>Pending Hotel Requests</p><strong><?= $pending ?></strong></article></div>
<section class="panel"><div class="panel-head"><h2>Hotel Reservations</h2><span class="count-chip">Latest 200</span></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Hotel / Location</th><th>Transport</th><th>Check-in / Check-out</th><th>Payment method</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$items): ?><tr><td colspan="8" class="empty-cell">No hotel bookings found.</td></tr><?php endif; ?>
<?php foreach($items as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><?= sh($r['username'] ?: 'Unknown') ?></td><td><strong><?= sh($r['hotel_name'] ?: 'Unavailable hotel') ?></strong><small><?= sh($r['location'] ?: '') ?></small></td><td><?= sh($r['vehicle_name'] ?: 'Not selected') ?></td><td><?= sh($r['checkin_date']) ?><small>to <?= sh($r['checkout_date']) ?></small></td><td><?= sh($r['payment_method'] ?: 'Not selected') ?></td><td><?= sbadge($r['status']) ?></td><td class="actions-cell">
<?php if(strtolower(trim((string)$r['status']))==='pending'): sconfirm_action((int)$r['id'],'confirm','Confirm','good','Confirm this pending hotel booking?');sconfirm_action((int)$r['id'],'cancel','Cancel','danger','Cancel this pending hotel booking?'); else: ?><span class="muted">Read-only</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">This page changes reservation status only. Hotel payments and refunds are not processed through GlobeTrek.</p></section>
<?php send(); ?>
