<?php
require_once __DIR__ . '/_common.php';
$id=sid(sget('id'));
sredirect($id?'transport.php?edit='.$id:'transport.php');
