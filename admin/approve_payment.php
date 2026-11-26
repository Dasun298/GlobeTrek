<?php
require_once __DIR__ . '/_common.php';
// An old ?id= link is for viewing ONLY; changes are made via payments.php POST + CSRF.
$id=recid(getv('id'));
if (!$id) redirect_to('payments.php');
$p=fetch_one_db("SELECT py.id,py.booking_id,py.amount,py.status,b.customer_name,b.status AS booking_status,b.travel_date,b.payment_method,pk.package_name,pk.destination FROM payments py LEFT JOIN bookings b ON b.id=py.booking_id LEFT JOIN packages pk ON pk.id=b.package_id WHERE py.id=?",'i',[$id]);
page_start('Payment Review','payments','Check the booking and payment record before any manual approval.');
?>
<section class="panel"><div class="panel-head"><h2>Payment #<?= $id ?></h2><a class="btn btn-muted" href="payments.php">← All Payments</a></div>
<?php if (!$p): ?><div class="manager-alert manager-error">Payment record not found.</div>
<?php else: ?><div class="detail-grid"><div><small>Payment ID</small><strong>#<?= (int)$p['id'] ?></strong></div><div><small>Booking ID</small><strong>#<?= (int)$p['booking_id'] ?></strong></div><div><small>Customer</small><strong><?= h($p['customer_name']?:'Unknown') ?></strong></div><div><small>Package</small><strong><?= h($p['package_name']?:'Unavailable') ?></strong></div><div><small>Destination</small><strong><?= h($p['destination']) ?></strong></div><div><small>Travel date</small><strong><?= h($p['travel_date']) ?></strong></div><div><small>Amount</small><strong><?= money($p['amount']) ?></strong></div><div><small>Payment status</small><?= status_badge($p['status']) ?></div><div><small>Booking status</small><?= status_badge($p['booking_status']) ?></div></div>
<?php $allowed=strtolower(trim((string)$p['status']))==='pending' && (float)$p['amount']>0 && !empty($p['booking_status']) && !in_array(strtolower(trim((string)$p['booking_status'])),['cancelled','canceled'],true); ?>
<?php if ($allowed): ?><form class="verification-box" action="payments.php" method="post" data-confirm="Approve this payment record? Only proceed after independently verifying a real payment."><?php csrf_field(); ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="approve"><label><input type="checkbox" name="verified" value="yes" required> I have independently verified the actual payment outside GlobeTrek.</label><button type="submit" class="btn btn-primary">Approve Payment Record ↗</button></form>
<?php else: ?><div class="manager-alert manager-error">This record cannot be approved: it must be Pending, have a positive amount and link to a non-cancelled booking.</div><?php endif; ?><?php endif; ?>
<p class="panel-note">Approval changes payments.status only; it does not transfer money or automatically confirm the tour booking.</p></section>
<?php page_end(); ?>
