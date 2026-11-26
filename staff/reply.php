<?php
require_once __DIR__ . '/_common.php';
$id=sid(sget('id'));if(!$id)sredirect('inquiries.php');
$item=sone('SELECT * FROM inquiries WHERE id=?','i',[$id]);if(!$item){sflash('Inquiry not found.','error');sredirect('inquiries.php');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();$text=svalid_text(spost('reply'),'Reply note',3000);
        sexec('UPDATE inquiries SET reply=? WHERE id=?','si',[$text,$id]);
        sflash('Reply note saved. No email has been sent.');sredirect('inquiries.php');
    }catch(Throwable $e){sfail($e,'reply.php?id='.$id);}
}
sstart('Reply to Inquiry','inquiries','Record your response as an internal note.');
?>
<section class="panel"><div class="panel-head"><h2><?= sh($item['subject']) ?></h2><a href="inquiries.php" class="btn btn-muted">← All Inquiries</a></div><div class="detail-grid"><div><small>Customer</small><strong><?= sh($item['name']) ?></strong></div><div><small>Email</small><strong><?= sh($item['email']) ?></strong></div><div><small>Received</small><strong><?= sh($item['created_at']) ?></strong></div></div>
<div class="inquiry-message"><?= nl2br(sh($item['message'])) ?></div>
<form class="admin-form" method="post"><?php stoken_field(); ?><div class="form-field"><label for="reply">Reply note *</label><textarea id="reply" name="reply" maxlength="3000" rows="7" required><?= sh($item['reply']) ?></textarea><p class="form-help">This is an internal database note; it does not send an email.</p></div><div class="form-actions"><button class="btn btn-primary">Save Note</button><a href="inquiries.php" class="btn btn-muted">Cancel</a></div></form></section>
<?php send(); ?>
