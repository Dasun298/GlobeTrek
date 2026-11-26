<?php
require_once __DIR__ . '/_common.php';
$stats=[
    ['Customers',"SELECT COUNT(*) FROM users WHERE role='customer'"],
    ['Tour Bookings','SELECT COUNT(*) FROM bookings'],
    ['Hotel Bookings','SELECT COUNT(*) FROM hotel_bookings'],
    ['Packages','SELECT COUNT(*) FROM packages'],
    ['Hotels','SELECT COUNT(*) FROM hotels'],
    ['Pending Tour Bookings',"SELECT COUNT(*) FROM bookings WHERE LOWER(TRIM(status))='pending'"]
];
$tour=srows('SELECT status,COUNT(*) AS total FROM bookings GROUP BY status ORDER BY total DESC');
$hotels=srows('SELECT status,COUNT(*) AS total FROM hotel_bookings GROUP BY status ORDER BY total DESC');
$dest=srows('SELECT p.destination,COUNT(*) AS total FROM bookings b JOIN packages p ON p.id=b.package_id GROUP BY p.destination ORDER BY total DESC LIMIT 8');
$total=(int)snumber('SELECT COUNT(*) FROM bookings');
sstart('Staff Reports','reports','Read-only operations analytics for tour and hotel bookings.');
?>
<section class="metrics"><?php foreach($stats as [$label,$sql]): ?><article class="metric-card"><p><?= sh($label) ?></p><strong><?= number_format(snumber($sql)) ?></strong><small>Current records</small></article><?php endforeach; ?></section>
<div class="insights-grid"><section class="panel"><div class="panel-head"><h2>Tour Status Mix</h2><span class="count-chip"><?= $total ?> tours</span></div>
<?php if(!$tour): ?><p class="panel-subtitle">No tour booking data.</p><?php endif; ?>
<?php foreach($tour as $r):$count=(int)$r['total'];$percent=$total?round($count*100/$total):0; ?><div class="status-row"><div class="status-row-label"><span><?= sbadge($r['status']) ?></span><strong><?= $count ?> (<?= $percent ?>%)</strong></div><div class="progress-track"><div class="progress-fill fill-destination" style="width:<?= $percent ?>%"></div></div></div><?php endforeach; ?>
</section><section class="panel"><h2>Popular Destinations</h2><div class="table-scroll"><table><thead><tr><th>Destination</th><th>Bookings</th></tr></thead><tbody>
<?php if(!$dest): ?><tr><td colspan="2" class="empty-cell">No destinations booked yet.</td></tr><?php endif; ?>
<?php foreach($dest as $r): ?><tr><td><?= sh($r['destination']) ?></td><td><strong><?= (int)$r['total'] ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div></section></div>
<section class="panel"><h2>Hotel Status Summary</h2><div class="table-scroll"><table><thead><tr><th>Status</th><th>Records</th></tr></thead><tbody>
<?php if(!$hotels): ?><tr><td class="empty-cell" colspan="2">No hotel bookings.</td></tr><?php endif; ?>
<?php foreach($hotels as $r): ?><tr><td><?= sbadge($r['status']) ?></td><td><?= (int)$r['total'] ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">These are database counts, not verified payments or audited financial results. Payment status management belongs to Admin.</p></section>
<?php send(); ?>
