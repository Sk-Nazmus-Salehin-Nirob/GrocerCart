<?php
require_once __DIR__ . '/db.php';
$sql = 'SELECT p.*, v.vendor_name FROM Products p LEFT JOIN Vendors v ON p.vendor_id=v.vendor_id ORDER BY p.product_id DESC';
$products = ociQuery($sql);
echo getHeader('Products'); echo getNav();
?>
<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-box text-green-600 mr-2"></i>Products</h1>
        <p class="text-gray-500 mt-1 text-sm">LEFT JOIN with Vendors &mdash; Oracle OCI8</p>
      </div>
      <div class="flex gap-3">
        <button onclick="toggleSQL('sql-p')" class="toggle-sql px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
        <a href="products_form.php" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 transition">
          <i class="fas fa-plus mr-1"></i>Add Product
        </a>
      </div>
    </div>
    <div id="sql-p" class="sql-box mt-4"><?= htmlspecialchars($sql) ?></div>
  </div>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Vendor</th>
            <th class="px-4 py-3 text-left">Category</th>
            <th class="px-4 py-3 text-left">Price</th>
            <th class="px-4 py-3 text-left">Tag</th>
            <th class="px-4 py-3 text-left">Stock</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php $stock_cls=['In Stock'=>'bg-green-100 text-green-800','Low Stock'=>'bg-yellow-100 text-yellow-800','Out of Stock'=>'bg-red-100 text-red-800']; ?>
          <?php foreach ($products as $p): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-gray-500"><?= htmlspecialchars($p['product_id']) ?></td>
            <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($p['name']) ?></td>
            <td class="px-4 py-3 text-purple-600"><?= htmlspecialchars($p['vendor_name']??'—') ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs"><?= htmlspecialchars($p['category']??'') ?></span></td>
            <td class="px-4 py-3 font-bold text-green-700">$<?= number_format($p['price'],2) ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs"><?= htmlspecialchars($p['sustainability_tag']??'') ?></span></td>
            <td class="px-4 py-3">
              <?php $s=$p['stock_status']??'In Stock'; ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $stock_cls[$s]??'bg-gray-100 text-gray-800' ?>"><?= $s ?></span>
            </td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <a href="products_form.php?id=<?= urlencode($p['product_id']) ?>"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 mr-2 text-xs font-medium transition">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="products_delete.php?id=<?= urlencode($p['product_id']) ?>"
                 onclick="return confirm('Delete this product?')"
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
