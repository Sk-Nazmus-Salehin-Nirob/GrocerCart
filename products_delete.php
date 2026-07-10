<?php
require_once __DIR__ . '/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) ociExec('DELETE FROM Products WHERE product_id=:id',[':id'=>$id]);
header('Location: products_list.php'); exit;
