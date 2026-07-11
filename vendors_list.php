<?php
require_once __DIR__ . '/db.php';
$sql = 'SELECT * FROM Vendors ORDER BY vendor_id DESC';
$vendors = ociQuery($sql);
echo getHeader('Vendors'); echo getNav();
?>
<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-truck text-purple-600 mr-2"></i>Vendors</h1>
        <p class="text-gray-500 mt-1 text-sm">Manage vendor records &mdash; Oracle OCI8</p>
      </div>
      <div class="flex gap-3">
        <button onclick="toggleSQL('sql-v')" class="toggle-sql px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
        <a href="vendors_form.php" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 transition">
          <i class="fas fa-plus mr-1"></i>Add Vendor
        </a>
      </div>
    </div>
    <div id="sql-v" class="sql-box mt-4"><?= htmlspecialchars($sql) ?></div>
  </div>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Vendor Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Phone</th>
            <th class="px-4 py-3 text-left">Location</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($vendors as $v): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-gray-500"><?= htmlspecialchars($v['vendor_id']) ?></td>
            <td class="px-4 py-3 font-semibold text-purple-700"><?= htmlspecialchars($v['vendor_name']) ?></td>
            <td class="px-4 py-3 text-blue-600"><?= htmlspecialchars($v['contact_email']??'') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($v['phone']??'') ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-lg text-xs"><?= htmlspecialchars($v['location']??'') ?></span></td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <a href="vendors_form.php?id=<?= urlencode($v['vendor_id']) ?>"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 mr-2 text-xs font-medium transition">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="vendors_delete.php?id=<?= urlencode($v['vendor_id']) ?>"
                 onclick="return confirm('Delete this vendor?')"
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
