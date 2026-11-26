<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_admin_post();
        $action=postv('action'); $id=recid(postv('id'));
        if ($action==='save') {
            $username=postv('username');
            $password=is_string($_POST['password']??null)?$_POST['password']:'';
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/',$username)) throw new RuntimeException('Staff username must be 3–100 valid characters.');
            if ($password!=='' && (strlen($password)<10||strlen($password)>72)) throw new RuntimeException('Staff password must be 10–72 characters.');
            if (!$id && !$password) throw new RuntimeException('Choose a password for the new staff member.');
            if ((int)scalar_db('SELECT COUNT(*) FROM staff WHERE username=? AND id<>?','si',[$username,$id])>0) throw new RuntimeException('That staff username is already registered.');
            if ($id) {
                if (!fetch_one_db('SELECT id FROM staff WHERE id=?','i',[$id])) throw new RuntimeException('Staff member not found.');
                if ($password!=='') {
                    $hash=password_hash($password,PASSWORD_DEFAULT);
                    exec_db('UPDATE staff SET username=?,password=? WHERE id=?','ssi',[$username,$hash,$id]);
                } else exec_db('UPDATE staff SET username=? WHERE id=?','si',[$username,$id]);
                flash('Staff account updated.');
            } else {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                exec_db('INSERT INTO staff (username,password) VALUES (?,?)','ss',[$username,$hash]); flash('Staff account created.');
            }
        } elseif ($action==='delete') {
            if (!$id || exec_db('DELETE FROM staff WHERE id=?','i',[$id])!==1) throw new RuntimeException('Staff account not found.');
            flash('Staff account deleted. Existing signed-in sessions may remain until logout.');
        } else throw new RuntimeException('Invalid staff action.');
        redirect_to('staff_accounts.php');
    } catch (Throwable $e) { admin_error($e,'staff_accounts.php'); }
}
$editId=recid(getv('edit')); $editing=$editId?fetch_one_db('SELECT id,username FROM staff WHERE id=?','i',[$editId]):null;
$staff=fetch_all_db('SELECT id,username FROM staff ORDER BY id DESC LIMIT 200');
page_start('Staff Accounts','staff','Create, edit and manage staff access credentials.');
?>
<div class="admin-two"><section class="panel"><div class="panel-head"><div><p class="eyebrow">STAFF MANAGEMENT</p><h2><?= $editing?'Edit Staff':'Create Staff' ?></h2></div><?php if ($editing): ?><a href="staff_accounts.php" class="btn btn-muted btn-sm">+ New</a><?php endif; ?></div><form method="post" class="admin-form"><?php csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id']??0) ?>"><div class="form-field"><label for="staff-name">Staff username *</label><input id="staff-name" name="username" type="text" minlength="3" maxlength="100" pattern="[A-Za-z0-9_.-]+" value="<?= h($editing['username']??'') ?>" required></div><div class="form-field"><label for="staff-pass"><?= $editing?'New password (optional)':'Password *' ?></label><input id="staff-pass" name="password" type="password" minlength="10" maxlength="72" autocomplete="new-password" <?= $editing?'':'required' ?>></div><button class="btn btn-primary" type="submit"><?= $editing?'Save Changes':'Create Staff Account' ?> ↗</button></form><p class="panel-note">New passwords are stored as hashes. Deleting staff does not instantly revoke existing sessions in the older Staff module.</p></section>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">STAFF DIRECTORY</p><h2>Staff Members</h2></div><span class="count-chip"><?= count($staff) ?> staff</span></div><div class="table-scroll"><table><thead><tr><th>ID</th><th>Username</th><th>Actions</th></tr></thead><tbody><?php if (!$staff): ?><tr><td colspan="3" class="empty-cell">No staff accounts.</td></tr><?php endif; ?><?php foreach ($staff as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><?= h($r['username']) ?></td><td class="actions-cell"><a href="staff_accounts.php?edit=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm">Edit</a><?php action_button((int)$r['id'],'delete','Delete','danger','Delete this staff account?'); ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php page_end(); ?>
