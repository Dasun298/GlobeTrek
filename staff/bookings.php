<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        srequire_post();
        $action=spost('action');
        $id=sid(spost('id'));
        stour_action($id,$action);
        sflash('Tour booking #'.$id.' status updated successfully.');
        sredirect('bookings.php');
    } catch (Throwable $e) { sfail($e,'bookings.php'); }
}
$search=substr(sget('search'),0,80);$like='%'.$search.'%';
$filter=strtolower(sget('status'));$filters=['all','pending','confirmed','completed','cancelled'];
if(!in_array($filter,$filters,true))$filter='all';
$items=srows("SELECT b.id,b.customer_name,b.user_id,b.travel_date,b.payment_method,b.status,p.package_name,p.destination,u.username,(SELECT py.status FROM payments py WHERE py.booking_id=b.id ORDER BY py.id DESC LIMIT 1) AS payment_status,(SELECT COUNT(*) FROM payments py WHERE py.booking_id=b.id AND LOWER(TRIM(py.status)) IN ('paid','approved','completed','successful')) AS paid_count FROM bookings b LEFT JOIN packages p ON p.id=b.package_id LEFT JOIN users u ON u.id=b.user_id WHERE (?='' OR b.customer_name LIKE ? OR u.username LIKE ? OR p.package_name LIKE ? OR p.destination LIKE ? OR CAST(b.id AS CHAR)=?) AND (?='all' OR LOWER(TRIM(b.status))=?) ORDER BY b.id DESC LIMIT 200",'ssssssss',[$search,$like,$like,$like,$like,$search,$filter,$filter]);
$counts=['All'=>(int)snumber('SELECT COUNT(*) FROM bookings'),'Pending'=>(int)snumber("SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='pending'"),'Confirmed'=>(int)snumber("SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='confirmed'"),'Completed'=>(int)snumber("SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='completed'")];
sstart('Tour Bookings','bookings','Review trip requests and update their booking statuses.');
?>
<section class="metrics four"><?php foreach($counts as $label=>$value): ?><article class="metric-card"><p><?= sh($label) ?></p><strong><?= $value ?></strong><small>Tour booking records</small></article><?php endforeach; ?></section>
<section class="panel"><div class="panel-head"><h2>Customer Booking Requests</h2><span class="count-chip">Latest 200</span></div>
<form class="filter-bar" method="get" role="search"><label class="search-group">Search customer, package, destination or ID<input name="search" maxlength="80" placeholder="Search bookings" value="<?= sh($search) ?>"></label><label>Booking status<select name="status"><?php foreach($filters as $s): ?><option value="<?= sh($s) ?>" <?= $filter===$s?'selected':'' ?>><?= sh(ucfirst($s)) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" type="submit">Search</button><a href="bookings.php" class="btn btn-muted">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Package / Destination</th><th>Travel Date</th><th>Status</th><th>Payment record</th><th>Actions</th></tr></thead><tbody>
<?php if(!$items): ?><tr><td colspan="7" class="empty-cell">No matching bookings.</td></tr><?php endif; ?>
<?php foreach($items as $r): $status=strtolower(trim((string)$r['status'])); ?><tr>
<td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= sh($r['customer_name'] ?: ($r['username'] ?: 'Unknown')) ?><small>User #<?= (int)$r['user_id'] ?></small></td>
<td><strong><?= sh($r['package_name'] ?: 'Unavailable package') ?></strong><small><?= sh($r['destination'] ?: '-') ?></small></td>
<td><?= sh($r['travel_date'] ?: 'Not set') ?></td><td><?= sbadge($r['status']) ?></td><td><?= sbadge($r['payment_status'] ?: 'Not recorded') ?></td>
<td class="actions-cell">
<?php if($status==='pending'): ?>
<?php sconfirm_action((int)$r['id'],'confirm','Confirm','good','Confirm this pending tour booking? Payment is not marked as paid.'); ?>
<?php if(!(int)$r['paid_count']): sconfirm_action((int)$r['id'],'cancel','Cancel','danger','Cancel this pending tour booking?'); else: ?><small>Payment review required before cancellation</small><?php endif; ?>
<?php elseif($status==='confirmed'): sconfirm_action((int)$r['id'],'complete','Complete','primary','Mark this confirmed tour booking as Completed?'); else: ?><span class="muted">Read-only</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Actions use protected POST requests. Completing a booking does not collect money. To amend payment records, contact Admin.</p></section>
<?php send(); ?>
