<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_admin_post();
        $id = recid(postv('id')); $action = postv('action');
        if (!$id || !in_array($action,['confirm','cancel'],true)) throw new RuntimeException('Invalid hotel booking action.');
        $status = $action === 'confirm' ? 'Confirmed' : 'Cancelled';
        if (exec_db("UPDATE hotel_bookings SET status=? WHERE id=? AND LOWER(TRIM(status))='pending'",'si',[$status,$id]) !== 1) throw new RuntimeException('Only Pending hotel bookings can be changed.');
        flash("Hotel booking #$id marked $status."); redirect_to('hotel_bookings.php');
    } catch (Throwable $e) { admin_error($e,'hotel_bookings.php'); }
}
$search = substr(getv('search'),0,70); $like = '%'.$search.'%';
$records = fetch_all_db("SELECT hb.id,hb.user_id,hb.checkin_date,hb.checkout_date,hb.payment_method,hb.status,u.username,h.hotel_name,h.location,t.vehicle_name FROM hotel_bookings hb LEFT JOIN users u ON u.id=hb.user_id LEFT JOIN hotels h ON h.id=hb.hotel_id LEFT JOIN transport t ON t.id=hb.transport_id WHERE (?='' OR u.username LIKE ? OR h.hotel_name LIKE ? OR CAST(hb.id AS CHAR)=?) ORDER BY hb.id DESC LIMIT 200",'ssss',[$search,$like,$like,$search]);
page_start('Hotel Bookings','hotel_bookings','Review hotel stays, customer dates and selected transport.');
?>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">HOTEL OPERATIONS</p><h2>Hotel Reservation Requests</h2></div><span class="count-chip"><?= count($records) ?> shown</span></div>
<form method="get" class="filter-bar"><label class="search-group">Search<input type="search" name="search" maxlength="70" placeholder="Customer, hotel or booking ID" value="<?= h($search) ?>"></label><button class="btn btn-primary">Search</button><a class="btn btn-muted" href="hotel_bookings.php">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Hotel</th><th>Transport</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$records): ?><tr><td colspan="8" class="empty-cell">No hotel reservations found.</td></tr><?php endif; ?>
<?php foreach ($records as $b): ?><tr><td><strong>#<?= (int)$b['id'] ?></strong></td><td><?= h($b['username'] ?: 'Unknown') ?></td><td><strong><?= h($b['hotel_name'] ?: 'Unavailable') ?></strong><small><?= h($b['location']) ?></small></td><td><?= h($b['vehicle_name'] ?: 'Not selected') ?></td><td><?= h($b['checkin_date']) ?></td><td><?= h($b['checkout_date']) ?></td><td><?= status_badge($b['status']) ?></td><td class="actions-cell"><?php if (strtolower(trim((string)$b['status'])) === 'pending'): action_button((int)$b['id'],'confirm','Confirm','good'); action_button((int)$b['id'],'cancel','Cancel','danger'); else: ?><span class="muted">Read-only</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Hotel status updates do not process/refund any payments. The existing database has no verified hotel payment ledger.</p></section>
<?php page_end(); ?>
