<?php
require_once __DIR__ . '/_common.php';
$states=fetch_all_db('SELECT status,COUNT(*) AS total FROM bookings GROUP BY status ORDER BY total DESC');
$total=(int)scalar_db('SELECT COUNT(*) FROM bookings');
$dest=fetch_all_db('SELECT p.destination,COUNT(b.id) AS total FROM bookings b JOIN packages p ON p.id=b.package_id GROUP BY p.destination ORDER BY total DESC LIMIT 10');
$topPackages=fetch_all_db('SELECT p.package_name,COUNT(b.id) AS total FROM bookings b JOIN packages p ON p.id=b.package_id GROUP BY p.id,p.package_name ORDER BY total DESC LIMIT 10');
$maxDest=1;
foreach ($dest as $destinationRow) { $maxDest=max($maxDest,(int)$destinationRow['total']); }
page_start('Analytics','analytics','Tour bookings, statuses and most selected destinations.');
?>
<div class="metrics three"><article class="metric-card"><p>Tour Bookings</p><strong><?= $total ?></strong><small>All statuses</small></article><article class="metric-card"><p>Unique Destinations</p><strong><?= count($dest) ?></strong><small>Top 10 represented here</small></article><article class="metric-card"><p>Paid/Approved Amount</p><strong class="medium-number"><?= money(scalar_db("SELECT COALESCE(SUM(amount),0) FROM payments WHERE LOWER(TRIM(status)) IN ('paid','approved')")) ?></strong><small>Not verified revenue</small></article></div>
<div class="admin-two equal"><section class="panel"><div class="panel-head"><h2>Tour Booking Status</h2></div><?php if (!$states): ?><p class="panel-note">No tour booking records.</p><?php endif; ?><?php foreach ($states as $r): $pct=$total?round((int)$r['total']*100/$total):0; ?><div class="status-row"><div class="status-row-label"><span><?= h(ucfirst((string)$r['status'])) ?></span><strong><?= (int)$r['total'] ?> (<?= $pct ?>%)</strong></div><div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div></div><?php endforeach; ?></section>
<section class="panel"><div class="panel-head"><h2>Popular Destinations</h2></div><?php if (!$dest): ?><p class="panel-note">No destination data.</p><?php endif; ?><?php foreach ($dest as $r): $pct=round((int)$r['total']*100/$maxDest); ?><div class="status-row"><div class="status-row-label"><span><?= h($r['destination']) ?></span><strong><?= (int)$r['total'] ?></strong></div><div class="progress-track"><div class="progress-fill fill-destination" style="width:<?= $pct ?>%"></div></div></div><?php endforeach; ?></section></div>
<section class="panel"><div class="panel-head"><h2>Top Booked Tour Packages</h2></div><div class="table-scroll"><table><thead><tr><th>Package</th><th>Bookings</th></tr></thead><tbody><?php if (!$topPackages): ?><tr><td colspan="2" class="empty-cell">No package activity.</td></tr><?php endif; ?><?php foreach ($topPackages as $r): ?><tr><td><?= h($r['package_name']) ?></td><td><strong><?= (int)$r['total'] ?></strong></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_end(); ?>
