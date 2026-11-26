<?php
require_once __DIR__ . '/_common.php';
page_start('Database Backup','backup','Protect your database before making or testing changes.');
?>
<section class="panel"><p class="eyebrow">SAFE LOCAL BACKUP</p><h2>Export a Backup with phpMyAdmin</h2><ol class="review-list"><li>Open <strong>XAMPP</strong> and start Apache and MySQL.</li><li>Open <code>http://localhost/phpmyadmin</code> on your computer.</li><li>Select your existing <strong>globetrek</strong> database.</li><li>Go to <strong>Export → Custom → SQL</strong>, then export.</li><li>Save the resulting SQL file privately <strong>outside C:\xampp\htdocs\</strong> and outside GitHub.</li><li>Protect or remove any historical card numbers, CVVs and plaintext passwords from test data.</li></ol><div class="manager-alert manager-success">No automatic browser-triggered shell commands are used here. Your live database is not modified by viewing this page.</div></section>
<?php page_end(); ?>
