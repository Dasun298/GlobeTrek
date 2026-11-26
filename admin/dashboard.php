<?php
require_once __DIR__ . '/_common.php';
$metrics = [
    ['Tour Bookings', 'SELECT COUNT(*) FROM bookings', '✈'],
    ['Hotel Bookings', 'SELECT COUNT(*) FROM hotel_bookings', '⌂'],
    ['Customers', "SELECT COUNT(*) FROM users WHERE role='customer'", '♙'],
    ['Tour Packages', 'SELECT COUNT(*) FROM packages', '▧'],
    ['Hotels', 'SELECT COUNT(*) FROM hotels', '⌂'],
    ['Staff Members', 'SELECT COUNT(*) FROM staff', '♧']
];
$stat = fetch_all_db("SELECT LOWER(TRIM(status)) AS status, COUNT(*) AS total FROM bookings GROUP BY LOWER(TRIM(status))");
$hotelStat = fetch_all_db("SELECT LOWER(TRIM(status)) AS status, COUNT(*) AS total FROM hotel_bookings GROUP BY LOWER(TRIM(status))");
$counts = ['pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0,'other'=>0];
foreach (array_merge($stat, $hotelStat) as $r) {
    $status = $r['status'];
    if ($status === 'approved') $status = 'confirmed';
    if (!array_key_exists($status, $counts)) $status = 'other';
    $counts[$status] += (int)$r['total'];
}
$total = array_sum($counts);
$pending = $counts['pending'];
$paidAmount = (float)scalar_db("SELECT COALESCE(SUM(amount),0) FROM payments WHERE LOWER(TRIM(status)) IN ('paid','approved')");
$latest = fetch_all_db('SELECT b.id,b.customer_name,b.travel_date,b.status,p.package_name,p.destination FROM bookings b LEFT JOIN packages p ON p.id=b.package_id ORDER BY b.id DESC LIMIT 6');
$latestHotels = fetch_all_db('SELECT hb.id,hb.checkin_date,hb.status,h.hotel_name,u.username FROM hotel_bookings hb LEFT JOIN hotels h ON h.id=hb.hotel_id LEFT JOIN users u ON u.id=hb.user_id ORDER BY hb.id DESC LIMIT 5');
page_start('Admin Dashboard','dashboard','Your travel operations, recent requests and bookings at a glance.');
?>
<div class="welcome-banner"><div><span class="eyebrow light">GLOBETREK ADVENTURES</span><h2>Welcome back to your control center.</h2><p>Manage tour packages, hotel stays, transport and customer bookings—all from one dashboard.</p><div class="welcome-actions"><a class="btn btn-light" href="bookings.php">Review Bookings ↗</a><a class="btn btn-ghost" href="catalog.php?type=packages">Manage Packages</a></div></div><div class="welcome-mark" aria-hidden="true">✈</div></div>
<section class="metrics" aria-label="Platform statistics">
<?php foreach ($metrics as [$label,$sql,$icon]): ?><article class="metric-card"><span class="metric-icon"><?= h($icon) ?></span><p><?= h($label) ?></p><strong><?= number_format((int)scalar_db($sql)) ?></strong><small>Current database records</small></article><?php endforeach; ?>
</section>
<div class="insights-grid">
<section class="panel"><div class="panel-head"><div><p class="eyebrow">BOOKING ACTIVITY</p><h2>Status Overview</h2></div><span class="count-chip"><?= $total ?> bookings</span></div><p class="panel-subtitle">Tour and hotel requests combined</p>
<?php foreach ($counts as $label=>$value): $percent = $total ? round($value*100/$total) : 0; ?><div class="status-row"><div class="status-row-label"><span><?= h(ucfirst($label)) ?></span><strong><?= $value ?></strong></div><div class="progress-track"><div class="progress-fill fill-<?= h($label) ?>" style="width:<?= $percent ?>%"></div></div></div><?php endforeach; ?>
</section>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">OPERATIONS</p><h2>Platform Snapshot</h2></div></div><div class="snapshot-row"><span>Pending requests</span><strong><?= $pending ?></strong></div><div class="snapshot-row"><span>Confirmed requests</span><strong><?= $counts['confirmed'] ?></strong></div><div class="snapshot-row"><span>Hotel vehicles</span><strong><?= (int)scalar_db('SELECT COUNT(*) FROM transport') ?></strong></div><div class="snapshot-row"><span>Tour transportation</span><strong><?= (int)scalar_db('SELECT COUNT(*) FROM transportation') ?></strong></div><div class="snapshot-payment"><span>Recorded Paid / Approved amounts</span><strong><?= money($paidAmount) ?></strong><small>These are database payment records, not independently verified revenue.</small></div></section>
</div>
<section class="panel records-panel"><div class="panel-head"><div><p class="eyebrow">RECENT REQUESTS</p><h2>Latest Tour Bookings</h2></div><a class="text-link" href="bookings.php">Manage all ↗</a></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Package</th><th>Travel date</th><th>Status</th></tr></thead><tbody>
<?php if (!$latest): ?><tr><td colspan="5" class="empty-cell">No tour bookings found.</td></tr><?php endif; ?>
<?php foreach ($latest as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= h($r['customer_name'] ?: 'Unknown') ?></td><td><strong><?= h($r['package_name'] ?: 'Removed package') ?></strong><small><?= h($r['destination']) ?></small></td><td><?= h($r['travel_date']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="panel records-panel"><div class="panel-head"><div><p class="eyebrow">HOTEL STAYS</p><h2>Latest Hotel Bookings</h2></div><a class="text-link" href="hotel_bookings.php">View all ↗</a></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Hotel</th><th>Check-in</th><th>Status</th></tr></thead><tbody>
<?php if (!$latestHotels): ?><tr><td colspan="5" class="empty-cell">No hotel bookings found.</td></tr><?php endif; ?>
<?php foreach ($latestHotels as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= h($r['username'] ?: 'Unknown') ?></td><td><?= h($r['hotel_name'] ?: 'Removed hotel') ?></td><td><?= h($r['checkin_date']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php page_end(); ?>
