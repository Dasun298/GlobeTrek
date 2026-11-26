<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction = false;
    try {
        require_admin_post();
        $id = recid(postv('id'));
        $action = postv('action');
        if (!$id || !in_array($action, ['confirm','cancel'], true)) throw new RuntimeException('Invalid booking action.');
        $conn->begin_transaction(); $transaction = true;
        $booking = fetch_one_db('SELECT status FROM bookings WHERE id=? FOR UPDATE','i',[$id]);
        if (!$booking || strtolower(trim((string)$booking['status'])) !== 'pending') throw new RuntimeException('Only Pending bookings can be changed.');
        if ($action === 'cancel') {
            $payments = fetch_all_db('SELECT status FROM payments WHERE booking_id=? FOR UPDATE','i',[$id]);
            foreach ($payments as $p) {
                if (in_array(strtolower(trim((string)$p['status'])), ['paid','approved','completed','successful'], true)) throw new RuntimeException('A paid/approved payment is recorded. Review refunds separately before cancelling.');
            }
        }
        $next = $action === 'confirm' ? 'Confirmed' : 'Cancelled';
        if (exec_db("UPDATE bookings SET status=? WHERE id=? AND LOWER(TRIM(status))='pending'",'si',[$next,$id]) !== 1) throw new RuntimeException('Booking was changed by another request.');
        if ($action === 'cancel') exec_db("UPDATE payments SET status='Cancelled' WHERE booking_id=? AND LOWER(TRIM(status))='pending'",'i',[$id]);
        $conn->commit(); $transaction = false;
        flash("Tour booking #$id marked $next."); redirect_to('bookings.php');
    } catch (Throwable $e) { if ($transaction) $conn->rollback(); admin_error($e,'bookings.php'); }
}
$search = substr(getv('search'),0,80);
$status = getv('status');
$allowed = ['all','pending','confirmed','approved','completed','cancelled'];
if (!in_array($status,$allowed,true)) $status = 'all';
$like = '%' . $search . '%';
$records = fetch_all_db(
    "SELECT b.id,b.user_id,b.customer_name,b.travel_date,b.status,b.payment_method,u.username,p.package_name,p.destination,p.price,
     (SELECT py.status FROM payments py WHERE py.booking_id=b.id ORDER BY py.id DESC LIMIT 1) AS payment_status,
     (SELECT py.amount FROM payments py WHERE py.booking_id=b.id ORDER BY py.id DESC LIMIT 1) AS payment_amount,
     (SELECT COUNT(*) FROM payments py WHERE py.booking_id=b.id AND LOWER(TRIM(py.status)) IN ('paid','approved','completed','successful')) AS settled
     FROM bookings b LEFT JOIN users u ON u.id=b.user_id LEFT JOIN packages p ON p.id=b.package_id
     WHERE (?='' OR b.customer_name LIKE ? OR u.username LIKE ? OR p.package_name LIKE ? OR p.destination LIKE ? OR CAST(b.id AS CHAR)=?)
     AND (?='all' OR LOWER(TRIM(b.status))=?) ORDER BY b.id DESC LIMIT 200",
    'ssssssss',[$search,$like,$like,$like,$like,$search,$status,$status]
);
$stats = fetch_one_db("SELECT COUNT(*) AS total,COALESCE(SUM(LOWER(TRIM(status))='pending'),0) AS pending,COALESCE(SUM(LOWER(TRIM(status)) IN ('confirmed','approved')),0) AS confirmed,COALESCE(SUM(LOWER(TRIM(status))='cancelled'),0) AS cancelled FROM bookings");
page_start('Tour Bookings','bookings','Search reservations and confirm or cancel pending tour requests.');
?>
<div class="metrics four">
<?php foreach (['All Bookings'=>'total','Pending Requests'=>'pending','Confirmed'=>'confirmed','Cancelled'=>'cancelled'] as $name=>$field): ?><article class="metric-card"><p><?= h($name) ?></p><strong><?= (int)$stats[$field] ?></strong><small>Tour bookings</small></article><?php endforeach; ?>
</div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">CUSTOMER REQUESTS</p><h2>Manage Tour Bookings</h2></div><span class="count-chip"><?= count($records) ?> shown</span></div>
<form class="filter-bar" method="get"><label class="search-group">Search<input type="search" name="search" maxlength="80" placeholder="Booking ID, customer, package, destination" value="<?= h($search) ?>"></label><label>Status<select name="status"><?php foreach ($allowed as $s): ?><option value="<?= h($s) ?>" <?= $s===$status?'selected':'' ?>><?= h($s==='all'?'All statuses':ucfirst($s)) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" type="submit">Search</button><a class="btn btn-muted" href="bookings.php">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Package</th><th>Travel Date</th><th>Price</th><th>Booking</th><th>Payment</th><th>Actions</th></tr></thead><tbody>
<?php if (!$records): ?><tr><td colspan="8" class="empty-cell">No matching tour bookings.</td></tr><?php endif; ?>
<?php foreach ($records as $r): $pending = strtolower(trim((string)$r['status'])) === 'pending'; ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= h($r['customer_name'] ?: ($r['username'] ?: 'Unknown')) ?><small>User #<?= (int)$r['user_id'] ?></small></td><td><strong><?= h($r['package_name'] ?: 'Unavailable') ?></strong><small><?= h($r['destination']) ?></small></td><td><?= h($r['travel_date']) ?></td><td><?= $r['price'] !== null ? money($r['price']):'—' ?></td><td><?= status_badge($r['status']) ?></td><td><?= status_badge($r['payment_status']) ?><small><?= $r['payment_amount'] !== null ? money($r['payment_amount']):'No recorded amount' ?></small></td><td class="actions-cell">
<?php if ($pending): action_button((int)$r['id'],'confirm','Confirm','good','Confirm this booking? This does not charge a payment.'); if (!(int)$r['settled']) { action_button((int)$r['id'],'cancel','Cancel','danger','Cancel this pending booking?'); } else { echo '<small>Payment review needed</small>'; } else: ?><span class="muted">Read-only</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Up to 200 records displayed. Confirming a booking never marks its payment as paid. Paid/approved bookings are protected against direct cancellation.</p></section>
<?php page_end(); ?>
