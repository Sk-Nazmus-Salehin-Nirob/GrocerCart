<?php
require_once __DIR__ . '/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) ociExec('DELETE FROM Delivery WHERE delivery_id=:id',[':id'=>$id]);
header('Location: delivery_list.php'); exit;
