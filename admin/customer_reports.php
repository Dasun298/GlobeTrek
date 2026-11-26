<?php
require_once __DIR__ . '/_common.php';
$records=fetch_all_db("SELECT u.id,u.username,u.email,COUNT(DISTINCT b.id) AS tours,COUNT(DISTINCT hb.id) AS hotels FROM users u LEFT JOIN bookings b ON b.user_id=u.id LEFT JOIN hotel_bookings hb ON hb.user_id=u.id WHERE u.role='customer' GROUP BY u.id,u.username,u.email ORDER BY u.id DESC LIMIT 200");
page_start('Customer Reports','users','Booking activity grouped by registered customer.');
?>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">CUSTOMER ACTIVITY</p><h2>Booking History Summary</h2></div><a class="btn btn-outline" href="manage_users.php">Customer Accounts ↗</a></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Email</th><th>Tour bookings</th><th>Hotel bookings</th><th>Total</th></tr></thead><tbody><?php if (!$records): ?><tr><td colspan="6" class="empty-cell">No customers found.</td></tr><?php endif; ?><?php foreach ($records as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><strong><?= h($r['username']) ?></strong></td><td><?= h($r['email']) ?></td><td><?= (int)$r['tours'] ?></td><td><?= (int)$r['hotels'] ?></td><td><strong><?= (int)$r['tours']+(int)$r['hotels'] ?></strong></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_end(); ?>
