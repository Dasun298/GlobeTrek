<?php
require_once __DIR__ . '/_common.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        require_admin_post(); $id=recid(postv('id')); $reply=postv('reply');
        if (!$id || $reply==='' || strlen($reply)>10000) throw new RuntimeException('Write a reply note of 1 to 10000 characters.');
        if (!fetch_one_db('SELECT id FROM inquiries WHERE id=?','i',[$id])) throw new RuntimeException('Inquiry not found.');
        exec_db('UPDATE inquiries SET reply=? WHERE id=?','si',[$reply,$id]);
        flash('Internal reply note saved (no email was sent).'); redirect_to('inquiries.php');
    } catch (Throwable $e) { admin_error($e,'inquiries.php'); }
}
$items=fetch_all_db('SELECT id,name,email,subject,message,reply,created_at FROM inquiries ORDER BY id DESC LIMIT 100');
page_start('Customer Inquiries','inquiries','Read messages and save internal response notes.');
?>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">INBOX</p><h2>Customer Messages</h2></div><span class="count-chip"><?= count($items) ?> latest</span></div><p class="panel-note">Reply notes are stored in the database and are not emailed to the customer.</p>
<?php if (!$items): ?><div class="empty-state">No inquiries have been received yet.</div><?php endif; ?>
<div class="inquiries-list"><?php foreach ($items as $r): ?><article class="inquiry-card"><div class="inquiry-heading"><div><span class="eyebrow">MESSAGE #<?= (int)$r['id'] ?> · <?= h($r['created_at']) ?></span><h3><?= h($r['subject']) ?></h3><p><?= h($r['name']) ?> · <?= h($r['email']) ?></p></div></div><div class="inquiry-message"><?= nl2br(h($r['message'])) ?></div><form method="post" class="admin-form"><?php csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><div class="form-field"><label for="reply-<?= (int)$r['id'] ?>">Internal reply note</label><textarea id="reply-<?= (int)$r['id'] ?>" name="reply" maxlength="10000" rows="3" required><?= h($r['reply']??'') ?></textarea></div><button type="submit" class="btn btn-primary btn-sm">Save Note</button></form></article><?php endforeach; ?></div></section>
<?php page_end(); ?>
