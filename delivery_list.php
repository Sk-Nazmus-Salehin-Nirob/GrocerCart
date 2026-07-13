<?php
require_once __DIR__ . '/db.php';
$sql = 'SELECT d.*, o.order_id AS oid FROM Delivery d LEFT JOIN Orders o ON d.order_id=o.order_id ORDER BY d.delivery_id DESC';
$deliveries = ociQuery($sql);
echo getHeader('Delivery'); echo getNav();
$ds=['scheduled'=>'bg-blue-100 text-blue-800','in-transit'=>'bg-yellow-100 text-yellow-800',
     'delivered'=>'bg-green-100 text-green-800','failed'=>'bg-red-100 text-red-800','ready'=>'bg-emerald-100 text-emerald-800'];
?>
<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-shipping-fast text-red-600 mr-2"></i>Delivery</h1>
        <p class="text-gray-500 mt-1 text-sm">Delivery tracking &mdash; Oracle OCI8</p>
      </div>
      <div class="flex gap-3">
        <button onclick="toggleSQL('sql-d')" class="toggle-sql px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
        <a href="delivery_form.php" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 transition">
          <i class="fas fa-plus mr-1"></i>Add Delivery
        </a>
      </div>
    </div>
    <div id="sql-d" class="sql-box mt-4"><?= htmlspecialchars($sql) ?></div>
  </div>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Delivery ID</th>
            <th class="px-4 py-3 text-left">Order #</th>
            <th class="px-4 py-3 text-left">Method</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Estimated Time</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($deliveries as $d): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-gray-500"><?= htmlspecialchars($d['delivery_id']) ?></td>
            <td class="px-4 py-3 font-mono font-semibold">#<?= htmlspecialchars($d['order_id']) ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-medium"><?= htmlspecialchars($d['delivery_method']??'') ?></span></td>
            <td class="px-4 py-3">
              <?php $s=strtolower($d['delivery_status']??'scheduled'); ?>
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $ds[$s]??'bg-gray-100 text-gray-700' ?>"><?= ucfirst($s) ?></span>
            </td>
            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($d['estimated_time']??'—') ?></td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <a href="delivery_form.php?id=<?= urlencode($d['delivery_id']) ?>"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 mr-2 text-xs font-medium transition">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="delivery_delete.php?id=<?= urlencode($d['delivery_id']) ?>"
                 onclick="return confirm('Delete this delivery record?')"
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
