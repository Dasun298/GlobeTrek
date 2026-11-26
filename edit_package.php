<?php
// Old broken package editor. Use the maintained Admin/Staff catalog instead.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php', true, 303); exit; }
$role = (string)($_SESSION['role'] ?? 'user');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($role === 'admin') $target = 'admin/catalog.php?type=packages' . ($id && $id > 0 ? '&edit='.$id : '');
elseif ($role === 'staff') $target = 'staff/packages.php';
else $target = 'index.php';
header('Location: ' . $target, true, 303);
exit;
