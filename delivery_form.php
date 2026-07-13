<?php
require_once __DIR__ . '/db.php';
$orders = ociQuery('SELECT order_id FROM Orders ORDER BY order_id DESC');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$d  = ['order_id'=>'','delivery_method'=>'Standard','delivery_status'=>'scheduled','estimated_time'=>''];
if ($id) $d = ociOne('SELECT delivery_id,order_id,delivery_method,delivery_status,TO_CHAR(estimated_time,\'YYYY-MM-DD\') estimated_time FROM Delivery WHERE delivery_id=:id',[':id'=>$id]) ?? $d;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $oid = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $mth = $_POST['delivery_method'] ?? 'Standard';
    $st  = $_POST['delivery_status'] ?? 'scheduled';
    $et  = trim($_POST['estimated_time'] ?? '');

    $binds = [
        ':oid' => $oid,
        ':mth' => $mth,
        ':st'  => $st
    ];

    if ($et !== '') {
        $dtSql = "TO_DATE(:et,'YYYY-MM-DD')";
        $binds[':et'] = $et;
    } else {
        $dtSql = "NULL";
    }

    if (!empty($_POST['delivery_id'])) {
        $binds[':id'] = (int)$_POST['delivery_id'];
        ociExec("UPDATE Delivery SET order_id=:oid,delivery_method=:mth,delivery_status=:st,estimated_time={$dtSql} WHERE delivery_id=:id", $binds);
    } else {
        ociExec("INSERT INTO Delivery (order_id,delivery_method,delivery_status,estimated_time) VALUES (:oid,:mth,:st,{$dtSql})", $binds);
    }
    header('Location: delivery_list.php'); exit;
}
echo getHeader(($id?'Edit':'Add').' Delivery'); echo getNav();
?>
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-shipping-fast text-red-600"></i>
      </div>
      <h1 class="text-2xl font-bold text-gray-800"><?= $id?'Edit':'Add' ?> Delivery</h1>
    </div>
    <form method="post" class="space-y-5">
      <?php if($id): ?><input type="hidden" name="delivery_id" value="<?=$id?>"><?php endif; ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Order #</label>
        <select name="order_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500">
          <option value="">-- Select Order --</option>
          <?php foreach($orders as $o): ?>
          <option value="<?=$o['order_id']?>" <?=($d['order_id']==$o['order_id'])?'selected':''?>>#<?=htmlspecialchars($o['order_id'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Method</label>
          <select name="delivery_method" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500">
            <?php foreach(['Standard','Express','Pickup','Same Day'] as $m): ?>
            <option <?=($d['delivery_method']==$m)?'selected':''?>><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <select name="delivery_status" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500">
            <?php foreach(['scheduled','in-transit','delivered','failed','ready'] as $s): ?>
            <option <?=($d['delivery_status']==$s)?'selected':''?>><?=$s?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Estimated Delivery Date</label>
        <input type="date" name="estimated_time" value="<?=htmlspecialchars($d['estimated_time']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-semibold transition">
          <i class="fas fa-save mr-2"></i>Save Delivery
        </button>
        <a href="delivery_list.php" class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php echo getFooter(); ?>
