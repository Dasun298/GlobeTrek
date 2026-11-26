<?php
require_once __DIR__ . '/_common.php';
$counts = [
 ['Tour Packages','SELECT COUNT(*) FROM packages','✈','packages.php'],
 ['Tour Bookings','SELECT COUNT(*) FROM bookings','▤','bookings.php'],
 ['Hotel Bookings','SELECT COUNT(*) FROM hotel_bookings','⌂','hotel_bookings.php'],
 ['Customers',"SELECT COUNT(*) FROM users WHERE role='customer'",'♙','customers.php'],
 ['Hotels','SELECT COUNT(*) FROM hotels','⌂','hotels.php'],
 ['Tour Vehicles','SELECT COUNT(*) FROM transportation','↗','transport.php']
];
$recent=srows('SELECT b.id,b.customer_name,b.travel_date,b.status,u.username,p.package_name FROM bookings b LEFT JOIN users u ON u.id=b.user_id LEFT JOIN packages p ON p.id=b.package_id ORDER BY b.id DESC LIMIT 7');
$hotelRecent=srows('SELECT hb.id,u.username,h.hotel_name,hb.checkin_date,hb.status FROM hotel_bookings hb LEFT JOIN users u ON u.id=hb.user_id LEFT JOIN hotels h ON h.id=hb.hotel_id ORDER BY hb.id DESC LIMIT 5');
$pending=(int)snumber("SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='pending'");
$pendingHotels=(int)snumber("SELECT COUNT(*) FROM hotel_bookings WHERE LOWER(TRIM(status))='pending'");
sstart('Staff Dashboard','dashboard','Your operations overview, bookings and travel services.');
?>
<section class="welcome-banner"><div><p class="eyebrow light">STAFF OPERATIONS</p><h2>Welcome back, <?= sh($_SESSION['username'] ?? 'Traveller') ?>.</h2><p>Coordinate tours, support hotel reservations and keep the customer experience running smoothly.</p><div class="welcome-actions"><a href="bookings.php" class="btn btn-light">Review Bookings ↗</a><a href="add_package.php" class="btn btn-ghost">Add Tour Package</a></div></div><div class="welcome-mark" aria-hidden="true">✈</div></section>
<section class="metrics" aria-label="Staff summary">
<?php foreach($counts as [$label,$sql,$icon,$href]): ?>
<a class="metric-card" href="<?= sh($href) ?>"><span class="metric-icon" aria-hidden="true"><?= sh($icon) ?></span><p><?= sh($label) ?></p><strong><?= number_format(snumber($sql)) ?></strong><small>View details ↗</small></a>
<?php endforeach; ?>
</section>
<div class="admin-two equal"><section class="panel"><div class="panel-head"><h2>Items to Review</h2><span class="count-chip">Live Database</span></div><div class="staff-summary-list"><a href="bookings.php">Pending tour bookings <strong><?= $pending ?> ↗</strong></a><a href="hotel_bookings.php">Pending hotel bookings <strong><?= $pendingHotels ?> ↗</strong></a><a href="inquiries.php">Customer inquiries <strong><?= (int)snumber('SELECT COUNT(*) FROM inquiries') ?> ↗</strong></a></div></section>
<section class="panel"><div class="panel-head"><h2>Quick Actions</h2></div><div class="tag-nav"><a href="add_package.php">+ Add Package</a><a href="hotels.php">Coordinate Hotels</a><a href="hotel_transport.php">Hotel Transport</a><a href="transport.php">Tour Transportation</a><a href="reports.php">Reports</a></div><p class="panel-note">Changes here update the existing GlobeTrek database. Customer payment records are managed by Admin.</p></section></div>
<section class="panel"><div class="panel-head"><h2>Recent Tour Bookings</h2><a class="text-link" href="bookings.php">View all ↗</a></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Package</th><th>Travel Date</th><th>Status</th></tr></thead><tbody>
<?php if(!$recent): ?><tr><td class="empty-cell" colspan="5">No bookings yet.</td></tr><?php endif; ?>
<?php foreach($recent as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= sh($r['customer_name'] ?: ($r['username'] ?: 'Unknown')) ?></td><td><?= sh($r['package_name'] ?: 'Removed package') ?></td><td><?= sh($r['travel_date'] ?: 'Not set') ?></td><td><?= sbadge($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="panel"><div class="panel-head"><h2>Recent Hotel Bookings</h2><a class="text-link" href="hotel_bookings.php">View all ↗</a></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Hotel</th><th>Check-in</th><th>Status</th></tr></thead><tbody>
<?php if(!$hotelRecent): ?><tr><td class="empty-cell" colspan="5">No hotel bookings yet.</td></tr><?php endif; ?>
<?php foreach($hotelRecent as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><?= sh($r['username'] ?: 'Unknown') ?></td><td><?= sh($r['hotel_name'] ?: 'Removed hotel') ?></td><td><?= sh($r['checkin_date'] ?: 'Not set') ?></td><td><?= sbadge($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php send(); ?>
