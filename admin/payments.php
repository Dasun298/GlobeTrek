<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $started=false;
    try {
        require_admin_post();
        $id=recid(postv('id'));
        if (postv('action')!=='approve' || !$id || postv('verified')!=='yes') throw new RuntimeException('Confirm that this payment was independently verified before approval.');
        $conn->begin_transaction(); $started=true;
        $p=fetch_one_db('SELECT id,booking_id,amount,status FROM payments WHERE id=? FOR UPDATE','i',[$id]);
        if (!$p || strtolower(trim((string)$p['status']))!=='pending') throw new RuntimeException('Only Pending payment records can be approved.');
        if ((float)$p['amount'] <= 0) throw new RuntimeException('This payment record has a zero amount. Correct it through a verified process before approval.');
        $booking=fetch_one_db('SELECT id,status FROM bookings WHERE id=? FOR UPDATE','i',[$p['booking_id']]);
        if (!$booking || in_array(strtolower(trim((string)$booking['status'])),['cancelled','canceled'],true)) throw new RuntimeException('The linked booking is missing or cancelled.');
        if (exec_db("UPDATE payments SET status='Approved' WHERE id=? AND LOWER(TRIM(status))='pending'",'i',[$id])!==1) throw new RuntimeException('The payment status changed. Refresh this page.');
        $conn->commit(); $started=false;
        flash("Payment record #$id marked Approved after manual verification. No actual bank transaction was processed.");
        redirect_to('payments.php');
    } catch (Throwable $e) { if ($started) $conn->rollback(); admin_error($e,'payments.php'); }
}
$search=substr(getv('search'),0,80); $like='%'.$search.'%';
$records=fetch_all_db("SELECT py.id,py.booking_id,py.amount,py.status,b.customer_name,b.status AS booking_status,p.package_name FROM payments py LEFT JOIN bookings b ON b.id=py.booking_id LEFT JOIN packages p ON p.id=b.package_id WHERE (?='' OR CAST(py.id AS CHAR)=? OR CAST(py.booking_id AS CHAR)=? OR b.customer_name LIKE ?) ORDER BY py.id DESC LIMIT 200",'ssss',[$search,$search,$search,$like]);
$total=(float)scalar_db("SELECT COALESCE(SUM(amount),0) FROM payments WHERE LOWER(TRIM(status)) IN ('paid','approved')");
$pendingCount=(int)scalar_db("SELECT COUNT(*) FROM payments WHERE LOWER(TRIM(status))='pending'");
page_start('Payment Records','payments','Review and manually mark payment records after independent verification.');
?>
<div class="metrics two"><article class="metric-card"><p>Pending Records</p><strong><?= $pendingCount ?></strong><small>Awaiting review</small></article><article class="metric-card"><p>Recorded Paid / Approved Amounts</p><strong class="medium-number"><?= money($total) ?></strong><small>Not validated bank revenue</small></article></div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">FINANCE RECORDS</p><h2>Payment Ledger</h2></div><span class="count-chip"><?= count($records) ?> shown</span></div><p class="panel-note">GlobeTrek does not charge cards here. Mark a payment Approved only after independently verifying the transfer or receipt.</p><form class="filter-bar" method="get"><label class="search-group">Search<input type="search" name="search" maxlength="80" placeholder="Payment ID, booking ID or customer" value="<?= h($search) ?>"></label><button class="btn btn-primary">Search</button><a class="btn btn-muted" href="payments.php">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Payment</th><th>Booking</th><th>Customer / Package</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if (!$records): ?><tr><td colspan="6" class="empty-cell">No payment records found.</td></tr><?php endif; ?><?php foreach ($records as $p): ?><tr><td><strong>#<?= (int)$p['id'] ?></strong></td><td>#<?= (int)$p['booking_id'] ?> <small><?= h($p['booking_status']?:'Missing') ?></small></td><td><strong><?= h($p['customer_name']?:'Unknown') ?></strong><small><?= h($p['package_name']) ?></small></td><td><?= money($p['amount']) ?></td><td><?= status_badge($p['status']) ?></td><td><a class="btn btn-outline btn-sm" href="approve_payment.php?id=<?= (int)$p['id'] ?>">Review</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_end(); ?>
