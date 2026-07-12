<?php
require_once __DIR__ . '/db.php';
$customers = ociQuery('SELECT customer_id, name FROM Customers ORDER BY name');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$o  = ['customer_id'=>'','order_date'=>'','total_amount'=>'0.00','status'=>'pending','discount_applied'=>'0'];
if ($id) $o = ociOne('SELECT order_id, customer_id, TO_CHAR(order_date,\'YYYY-MM-DD\') order_date, total_amount, status, discount_applied FROM Orders WHERE order_id=:id',[':id'=>$id]) ?? $o;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $cid  = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $dt   = trim($_POST['order_date'] ?? '');
    $tot  = (float)($_POST['total_amount'] ?? 0);
    $st   = $_POST['status'] ?? 'pending';
    $disc = (float)($_POST['discount_applied'] ?? 0);

    $binds = [
        ':cid'  => $cid,
        ':tot'  => $tot,
        ':st'   => $st,
        ':disc' => $disc
    ];

    if ($dt !== '') {
        $dtSql = "TO_DATE(:dt,'YYYY-MM-DD')";
        $binds[':dt'] = $dt;
    } else {
        $dtSql = "SYSDATE";
    }

    if (!empty($_POST['order_id'])) {
        $binds[':id'] = (int)$_POST['order_id'];
        ociExec("UPDATE Orders SET customer_id=:cid,order_date={$dtSql},total_amount=:tot,status=:st,discount_applied=:disc WHERE order_id=:id", $binds);
    } else {
        ociExec("INSERT INTO Orders (customer_id,order_date,total_amount,status,discount_applied) VALUES (:cid,{$dtSql},:tot,:st,:disc)", $binds);
    }
    header('Location: orders_list.php'); exit;
}
echo getHeader(($id?'Edit':'Add').' Order'); echo getNav();
?>
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-shopping-bag text-orange-600"></i>
      </div>
      <h1 class="text-2xl font-bold text-gray-800"><?= $id?'Edit':'Add' ?> Order</h1>
    </div>
    <form method="post" class="space-y-5">
      <?php if($id): ?><input type="hidden" name="order_id" value="<?=$id?>"><?php endif; ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Customer</label>
        <select name="customer_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
          <option value="">-- Select Customer --</option>
          <?php foreach($customers as $c): ?>
          <option value="<?=$c['customer_id']?>" <?=($o['customer_id']==$c['customer_id'])?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Order Date</label>
        <input type="date" name="order_date" value="<?=htmlspecialchars(substr($o['order_date']??'',0,10))?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
      </div>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Total ($)</label>
          <input type="number" step="0.01" min="0" name="total_amount" value="<?=htmlspecialchars($o['total_amount']??'0.00')?>"
                 class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
            <?php foreach(['pending','processing','shipped','delivered','cancelled'] as $s): ?>
            <option <?=($o['status']==$s)?'selected':''?>><?=$s?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Discount ($)</label>
          <input type="number" step="0.01" min="0" name="discount_applied" value="<?=htmlspecialchars($o['discount_applied']??0)?>"
                 class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-xl font-semibold transition">
          <i class="fas fa-save mr-2"></i>Save Order
        </button>
        <a href="orders_list.php" class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php echo getFooter(); ?>
