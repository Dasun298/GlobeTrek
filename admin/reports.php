<?php
require_once __DIR__ . '/_common.php';
$metrics=[
    ['Customers',"SELECT COUNT(*) FROM users WHERE role='customer'"],
    ['Tour Bookings','SELECT COUNT(*) FROM bookings'],
    ['Hotel Bookings','SELECT COUNT(*) FROM hotel_bookings'],
    ['Tour Packages','SELECT COUNT(*) FROM packages'],
    ['Pending Tour Requests',"SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='pending'"],
    ['Pending Hotel Requests',"SELECT COUNT(*) FROM hotel_bookings WHERE LOWER(TRIM(status))='pending'"]
];
$tour=fetch_all_db('SELECT status,COUNT(*) AS total FROM bookings GROUP BY status ORDER BY total DESC');
$hotel=fetch_all_db('SELECT status,COUNT(*) AS total FROM hotel_bookings GROUP BY status ORDER BY total DESC');
page_start('Reports','reports','Live operational counts from the GlobeTrek database.');
?>
<section class="metrics"><?php foreach ($metrics as [$label,$query]): ?><article class="metric-card"><p><?= h($label) ?></p><strong><?= (int)scalar_db($query) ?></strong><small>Database records</small></article><?php endforeach; ?></section>
<div class="admin-two equal"><section class="panel"><div class="panel-head"><h2>Tour Booking Status</h2></div><div class="table-scroll"><table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody><?php if (!$tour): ?><tr><td colspan="2" class="empty-cell">No bookings found.</td></tr><?php endif; ?><?php foreach ($tour as $r): ?><tr><td><?= status_badge($r['status']) ?></td><td><strong><?= (int)$r['total'] ?></strong></td></tr><?php endforeach; ?></tbody></table></div></section><section class="panel"><div class="panel-head"><h2>Hotel Booking Status</h2></div><div class="table-scroll"><table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody><?php if (!$hotel): ?><tr><td colspan="2" class="empty-cell">No bookings found.</td></tr><?php endif; ?><?php foreach ($hotel as $r): ?><tr><td><?= status_badge($r['status']) ?></td><td><strong><?= (int)$r['total'] ?></strong></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<section class="panel"><h2>More Reports</h2><div class="tag-nav"><a href="analytics.php">Visual Analytics</a><a href="customer_reports.php">Customer Activity</a><a href="payments.php">Payment Ledger</a></div><p class="panel-note">These counts are based on records, not independent financial audits or proof of customer attendance.</p></section>
<?php page_end(); ?>
