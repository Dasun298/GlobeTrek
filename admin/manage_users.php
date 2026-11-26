<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_admin_post();
        $action=postv('action'); $id=recid(postv('id'));
        if ($action === 'save') {
            $username=postv('username'); $email=postv('email');
            $password = is_string($_POST['password']??null) ? $_POST['password'] : '';
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/',$username)) throw new RuntimeException('Username must contain 3–100 letters, numbers, dots, dashes or underscores.');
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) throw new RuntimeException('Enter a valid email address.');
            if ($password !== '' && (strlen($password)<8 || strlen($password)>72)) throw new RuntimeException('Password must be 8–72 characters.');
            if (!$id && !$password) throw new RuntimeException('A password is required for a new customer.');
            $duplicate=(int)scalar_db('SELECT COUNT(*) FROM users WHERE (username=? OR email=?) AND id<>?','ssi',[$username,$email,$id]);
            if ($duplicate) throw new RuntimeException('That username or email already exists.');
            if ($id) {
                $old=fetch_one_db("SELECT id FROM users WHERE id=? AND role='customer'",'i',[$id]);
                if (!$old) throw new RuntimeException('Customer not found.');
                if ($password !== '') {
                    $hash=password_hash($password,PASSWORD_DEFAULT);
                    exec_db('UPDATE users SET username=?,email=?,password=? WHERE id=?','sssi',[$username,$email,$hash,$id]);
                } else { exec_db('UPDATE users SET username=?,email=? WHERE id=?','ssi',[$username,$email,$id]); }
                flash('Customer account updated.');
            } else {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                exec_db("INSERT INTO users (username,email,password,role) VALUES (?,?,?,'customer')",'sss',[$username,$email,$hash]);
                flash('Customer account created.');
            }
        } elseif ($action === 'delete') {
            if (!$id) throw new RuntimeException('Invalid customer ID.');
            $attached=(int)scalar_db('SELECT COUNT(*) FROM bookings WHERE user_id=?','i',[$id]) + (int)scalar_db('SELECT COUNT(*) FROM hotel_bookings WHERE user_id=?','i',[$id]);
            if ($attached) throw new RuntimeException('Customer has bookings. Cannot delete an account with booking history.');
            if (exec_db("DELETE FROM users WHERE id=? AND role='customer'",'i',[$id])!==1) throw new RuntimeException('Customer not found.');
            flash('Customer account deleted.');
        } else throw new RuntimeException('Invalid customer action.');
        redirect_to('manage_users.php');
    } catch (Throwable $e) { admin_error($e,'manage_users.php'); }
}
$editId=recid(getv('edit'));
$edit=$editId ? fetch_one_db("SELECT id,username,email,role FROM users WHERE id=? AND role='customer'",'i',[$editId]):null;
$search=substr(getv('search'),0,80); $like='%'.$search.'%';
$data=fetch_all_db("SELECT id,username,email,role FROM users WHERE role='customer' AND (?='' OR username LIKE ? OR email LIKE ?) ORDER BY id DESC LIMIT 200",'sss',[$search,$like,$like]);
page_start('Customer Management','users','Manage customer profiles, keeping historical bookings safe.');
?>
<div class="admin-two"><section class="panel"><div class="panel-head"><div><p class="eyebrow">CUSTOMER ACCOUNTS</p><h2><?= $edit?'Edit Customer':'Add Customer' ?></h2></div><?php if ($edit): ?><a href="manage_users.php" class="btn btn-muted btn-sm">+ New</a><?php endif; ?></div>
<form class="admin-form" method="post" autocomplete="off"><?php csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="form-field"><label for="username">Username *</label><input type="text" name="username" id="username" maxlength="100" minlength="3" pattern="[A-Za-z0-9_.-]+" value="<?= h($edit['username']??'') ?>" required></div><div class="form-field"><label for="email">Email *</label><input id="email" name="email" type="email" maxlength="100" value="<?= h($edit['email']??'') ?>" required></div><div class="form-field"><label for="password"><?= $edit?'New password (optional)':'Password *' ?></label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $edit?'':'required' ?>></div><button class="btn btn-primary" type="submit"><?= $edit?'Save Customer':'Create Customer' ?> ↗</button></form><p class="panel-note">Passwords are hashed before storing. Admin accounts cannot be created from this customer form.</p></section>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">CUSTOMER DIRECTORY</p><h2>Registered Customers</h2></div><a class="text-link" href="customer_reports.php">Activity report ↗</a></div><form class="filter-bar" method="get"><label class="search-group">Search<input type="search" name="search" maxlength="80" placeholder="Username or email" value="<?= h($search) ?>"></label><button class="btn btn-primary">Search</button><a class="btn btn-muted" href="manage_users.php">Reset</a></form><div class="table-scroll"><table><thead><tr><th>ID</th><th>Customer</th><th>Email</th><th>Actions</th></tr></thead><tbody>
<?php if (!$data): ?><tr><td colspan="4" class="empty-cell">No customers found.</td></tr><?php endif; ?>
<?php foreach ($data as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><strong><?= h($r['username']) ?></strong></td><td><?= h($r['email']) ?></td><td class="actions-cell"><a href="manage_users.php?edit=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm">Edit</a><?php action_button((int)$r['id'],'delete','Delete','danger','Delete only if the customer has no bookings?'); ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="panel-note">Up to 200 customer accounts shown. Accounts with tour or hotel bookings cannot be deleted.</p></section></div>
<?php page_end(); ?>
