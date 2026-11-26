<?php
require_once __DIR__ . '/_common.php';
$id=(int)$_SESSION['user_id'];
$staff=sone('SELECT id,username,password FROM staff WHERE id=?','i',[$id]);
if(!$staff){
    // Existing staff accounts deleted by Admin should not keep access on pages using this route.
    unset($_SESSION['user_id'],$_SESSION['role'],$_SESSION['username']);
    sredirect('../login.php');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();
        $name=spost('username');$current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
        if(!preg_match('/^[A-Za-z0-9_.-]{3,100}$/D',$name))throw new RuntimeException('Username must be 3–100 valid characters.');
        if($current==='')throw new RuntimeException('Enter your current password.');
        $stored=(string)$staff['password'];$isHash=password_get_info($stored)['algoName']!=='unknown';
        $matches=$isHash&&password_verify($current,$stored);
        if(!$matches)throw new RuntimeException('Current password is incorrect.');
        if($new!==''||$confirm!==''){
            if(strlen($new)<10||strlen($new)>72)throw new RuntimeException('New password must be 10–72 characters.');
            if(!hash_equals($new,$confirm))throw new RuntimeException('New passwords do not match.');
        }
        $existing=snumber('SELECT COUNT(*) FROM staff WHERE username=? AND id<>?','si',[$name,$id]);
        if($existing)throw new RuntimeException('This staff username is already used.');
        $rehash= !$isHash || ($isHash && password_needs_rehash($stored,PASSWORD_DEFAULT)) || $new!=='';
        if($rehash){
            $hash=password_hash($new!==''?$new:$current,PASSWORD_DEFAULT);
            sexec('UPDATE staff SET username=?,password=? WHERE id=?','ssi',[$name,$hash,$id]);
        }else{ sexec('UPDATE staff SET username=? WHERE id=?','si',[$name,$id]); }
        $_SESSION['username']=$name;
        if($new!=='')session_regenerate_id(true);
        sflash('Your staff account was updated successfully.');sredirect('profile.php');
    }catch(Throwable $e){sfail($e,'profile.php');}
}
sstart('My Account','profile','Update your Staff username and password securely.');
?>
<div class="admin-two"><section class="panel"><h2>Staff Profile</h2><div class="detail-grid" style="grid-template-columns:1fr"><div><small>Staff Account</small><strong><?= sh($staff['username']) ?></strong></div><div><small>Account ID</small><strong>#<?= $id ?></strong></div></div><div class="profile-intro">Changing your username or password requires your current password. Passwords are verified against secure hashes.</div><a class="btn btn-muted" href="dashboard.php">← Dashboard</a></section>
<section class="panel"><h2>Edit Account Details</h2><form class="admin-form" method="post"><?php stoken_field(); ?>
<div class="form-field"><label>Username *</label><input name="username" maxlength="100" pattern="[A-Za-z0-9_.-]{3,100}" value="<?= sh($staff['username']) ?>" required></div>
<div class="form-field"><label>Current Password *</label><input type="password" name="current_password" autocomplete="current-password" required></div>
<div class="form-field"><label>New Password (optional)</label><input type="password" name="new_password" minlength="10" maxlength="72" autocomplete="new-password" placeholder="Leave empty to keep current"></div>
<div class="form-field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="10" maxlength="72" autocomplete="new-password"></div>
<div class="form-actions"><button class="btn btn-primary">Save Changes</button></div>
</form></section></div>
<?php send(); ?>
