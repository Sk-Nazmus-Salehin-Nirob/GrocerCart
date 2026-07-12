<?php
require_once __DIR__ . '/db.php';
$sql = 'SELECT o.*, c.name AS customer_name FROM Orders o LEFT JOIN Customers c ON o.customer_id=c.customer_id ORDER BY o.order_id DESC';
$orders = ociQuery($sql);
echo getHeader('Orders'); echo getNav();
$sc=['delivered'=>'bg-green-100 text-green-800','shipped'=>'bg-blue-100 text-blue-800',
     'processing'=>'bg-yellow-100 text-yellow-800','pending'=>'bg-gray-100 text-gray-700','cancelled'=>'bg-red-100 text-red-800'];
?>
<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-shopping-bag text-orange-600 mr-2"></i>Orders</h1>
        <p class="text-gray-500 mt-1 text-sm">LEFT JOIN Customers &mdash; Oracle OCI8</p>
      </div>
      <div class="flex gap-3">
        <button onclick="toggleSQL('sql-o')" class="toggle-sql px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
        <a href="orders_form.php" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 transition">
          <i class="fas fa-plus mr-1"></i>Add Order
        </a>
      </div>
    </div>
    <div id="sql-o" class="sql-box mt-4"><?= htmlspecialchars($sql) ?></div>
  </div>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Order #</th>
            <th class="px-4 py-3 text-left">Customer</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Total</th>
            <th class="px-4 py-3 text-left">Discount</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($orders as $o): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono font-semibold">#<?= htmlspecialchars($o['order_id']) ?></td>
            <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($o['customer_name']??'—') ?></td>
            <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($o['order_date']??'') ?></td>
            <td class="px-4 py-3 font-bold text-green-700">$<?= number_format($o['total_amount']??0,2) ?></td>
            <td class="px-4 py-3 text-gray-500">$<?= number_format($o['discount_applied']??0,2) ?></td>
            <td class="px-4 py-3">
              <?php $s=strtolower($o['status']??'pending'); ?>
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $sc[$s]??'bg-gray-100 text-gray-700' ?>"><?= ucfirst($s) ?></span>
            </td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <a href="orders_form.php?id=<?= urlencode($o['order_id']) ?>"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 mr-2 text-xs font-medium transition">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="orders_delete.php?id=<?= urlencode($o['order_id']) ?>"
                 onclick="return confirm('Delete this order?')"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-xs font-medium transition">
                <i class="fas fa-trash"></i> Delete
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php echo getFooter(); ?>
