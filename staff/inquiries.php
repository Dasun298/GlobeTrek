<?php
require_once __DIR__ . '/_common.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        srequire_post();if(spost('action')!=='delete')throw new RuntimeException('Invalid inquiry action.');
        $id=sid(spost('id'));if(!$id)throw new RuntimeException('Invalid inquiry ID.');
        if(sexec('DELETE FROM inquiries WHERE id=?','i',[$id])!==1)throw new RuntimeException('Inquiry not found.');
        sflash('Inquiry deleted.');sredirect('inquiries.php');
    }catch(Throwable $e){sfail($e,'inquiries.php');}
}
$search=substr(sget('search'),0,80);$like='%'.$search.'%';
$items=srows("SELECT id,name,email,subject,message,created_at,reply FROM inquiries WHERE (?='' OR name LIKE ? OR email LIKE ? OR subject LIKE ?) ORDER BY id DESC LIMIT 150",'ssss',[$search,$like,$like,$like]);
sstart('Customer Inquiries','inquiries','Read messages, record reply notes and keep inquiries organized.');
?>
<section class="panel"><div class="panel-head"><h2>Contact Messages</h2><span class="count-chip"><?= count($items) ?> shown</span></div>
<form class="filter-bar" method="get" role="search"><label class="search-group">Search name, email or subject<input name="search" maxlength="80" value="<?= sh($search) ?>" placeholder="Search inquiries"></label><button class="btn btn-primary">Search</button><a href="inquiries.php" class="btn btn-muted">Reset</a></form>
<?php if(!$items): ?><div class="empty-state">No inquiries found.</div><?php endif; ?>
<?php foreach($items as $r): ?><article class="inquiry-card"><div class="inquiry-heading"><p>INQUIRY #<?= (int)$r['id'] ?> · <?= sh($r['created_at']) ?></p><h3><?= sh($r['subject']) ?></h3><p><?= sh($r['name']) ?> · <?= sh($r['email']) ?></p></div><div class="inquiry-message"><?= nl2br(sh($r['message'])) ?></div>
<?php if($r['reply']): ?><p class="panel-note"><strong>Saved reply note:</strong> <?= nl2br(sh($r['reply'])) ?></p><?php endif; ?>
<div class="catalog-actions"><a href="reply.php?id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm"><?= $r['reply']?'Edit Reply Note':'Write Reply Note' ?></a><?php sconfirm_action((int)$r['id'],'delete','Delete Inquiry','danger','Permanently delete this customer inquiry?'); ?></div></article><?php endforeach; ?>
<p class="panel-note">Reply notes are saved to the database. They are not emailed. Deleting an inquiry is permanent; keep backups before testing.</p></section>
<?php send(); ?>
