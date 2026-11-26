<?php
require_once __DIR__ . '/_common.php';
$search=substr(sget('search'),0,80);$like='%'.$search.'%';
$customers=srows("SELECT u.id,u.username,u.email,COUNT(DISTINCT b.id) AS tour_count,COUNT(DISTINCT hb.id) AS hotel_count FROM users u LEFT JOIN bookings b ON b.user_id=u.id LEFT JOIN hotel_bookings hb ON hb.user_id=u.id WHERE u.role='customer' AND (?='' OR u.username LIKE ? OR u.email LIKE ?) GROUP BY u.id,u.username,u.email ORDER BY u.id DESC LIMIT 200",'sss',[$search,$like,$like]);
sstart('Customers','customers','Look up customer accounts and their booking activity.');
?>
<section class="panel"><div class="panel-head"><h2>Customer Directory</h2><span class="count-chip">Read-only</span></div>
<form class="filter-bar" method="get" role="search"><label class="search-group">Search by username or email<input name="search" maxlength="80" value="<?= sh($search) ?>" placeholder="Customer name or email"></label><button class="btn btn-primary" type="submit">Search</button><a href="customers.php" class="btn btn-muted">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Email</th><th>Tour Bookings</th><th>Hotel Bookings</th></tr></thead><tbody>
<?php if(!$customers): ?><tr><td class="empty-cell" colspan="5">No customers found.</td></tr><?php endif; ?>
<?php foreach($customers as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><strong><?= sh($r['username']) ?></strong></td><td><?= sh($r['email']) ?></td><td><?= (int)$r['tour_count'] ?></td><td><?= (int)$r['hotel_count'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<p class="panel-note">Customer accounts are read-only for Staff. Passwords, NIC/passport numbers and card details are never shown here.</p></section>
<?php send(); ?>
