<?php
require_once __DIR__ . '/db.php';
$sql = 'SELECT * FROM Customers ORDER BY customer_id DESC';
$customers = ociQuery($sql);
echo getHeader('Customers'); echo getNav();
?>
<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-users text-blue-600 mr-2"></i>Customers</h1>
        <p class="text-gray-500 mt-1 text-sm">Full CRUD &mdash; Oracle OCI8</p>
      </div>
      <div class="flex gap-3">
        <button onclick="toggleSQL('sql-c')" class="toggle-sql px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
        <a href="customers_form.php" class="sql-tip px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 transition">
          <i class="fas fa-plus mr-1"></i>Add Customer
          <span class="tip-text">INSERT INTO Customers
(name,email,phone,address,preferences)
VALUES (:name,:email,:phone,:address,:pref)</span>
        </a>
      </div>
    </div>
    <div id="sql-c" class="sql-box mt-4"><?= htmlspecialchars($sql) ?></div>
  </div>

  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Phone</th>
            <th class="px-4 py-3 text-left">Address</th>
            <th class="px-4 py-3 text-left">Preferences</th>
            <th class="px-4 py-3 text-left">Tier</th>
            <th class="px-4 py-3 text-left">Points</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($customers)): ?>
          <tr><td colspan="9" class="text-center py-12 text-gray-400">
            No customers yet. <a href="init_db.php" class="text-blue-600 underline">Initialize DB</a>
          </td></tr>
          <?php endif; ?>
          <?php foreach ($customers as $c): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-gray-500"><?= htmlspecialchars($c['customer_id']) ?></td>
            <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($c['name']) ?></td>
            <td class="px-4 py-3 text-blue-600"><?= htmlspecialchars($c['email'] ?? '') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($c['phone'] ?? '') ?></td>
            <td class="px-4 py-3 text-gray-600 max-w-xs truncate"><?= htmlspecialchars($c['address'] ?? '') ?></td>
            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($c['preferences'] ?? '') ?></td>
            <td class="px-4 py-3">
              <?php $t=$c['customer_tier']??'Bronze';
                    $tc=['Bronze'=>'bg-orange-100 text-orange-800','Silver'=>'bg-gray-100 text-gray-800','Gold'=>'bg-yellow-100 text-yellow-800','Platinum'=>'bg-blue-100 text-blue-800'];
              ?><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $tc[$t]??'bg-gray-100 text-gray-800' ?>"><?= $t ?></span>
            </td>
            <td class="px-4 py-3 font-semibold text-green-700"><?= $c['loyalty_points']??0 ?></td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <a href="customers_form.php?id=<?= urlencode($c['customer_id']) ?>"
                 class="sql-tip inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 mr-2 text-xs font-medium transition">
                <i class="fas fa-edit"></i> Edit
                <span class="tip-text">UPDATE Customers SET name=:n,email=:e,phone=:p,
address=:a,preferences=:pref
WHERE customer_id=<?= $c['customer_id'] ?></span>
              </a>
              <a href="customers_delete.php?id=<?= urlencode($c['customer_id']) ?>"
                 onclick="return confirm('Delete this customer?')"
                 class="sql-tip inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-xs font-medium transition">
                <i class="fas fa-trash"></i> Delete
                <span class="tip-text">DELETE FROM Customers
WHERE customer_id=<?= $c['customer_id'] ?></span>
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
