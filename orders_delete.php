<?php
require_once __DIR__ . '/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) ociExec('DELETE FROM Orders WHERE order_id=:id',[':id'=>$id]);
header('Location: orders_list.php'); exit;
