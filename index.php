<?php
/**
 * index.php – Admin Dashboard (Oracle OCI8)
 */
require_once 'db.php';

// ── Stats ──────────────────────────────────────────────────────────────
$sql_log = [];
function dashStat(string $label, string $sql): mixed {
    global $sql_log;
    $sql_log[$label] = $sql;
    $rows = ociQuery($sql);
    return array_values($rows[0] ?? [null])[0];
}

$stats = [
    'customers' => dashStat('Total Customers',  'SELECT COUNT(*) total FROM Customers'),
    'products'  => dashStat('Total Products',   'SELECT COUNT(*) total FROM Products'),
    'orders'    => dashStat('Total Orders',     'SELECT COUNT(*) total FROM Orders'),
    'revenue'   => dashStat('Total Revenue',    "SELECT NVL(SUM(total_amount),0) total FROM Orders WHERE status <> 'cancelled'"),
    'vendors'   => dashStat('Active Vendors',   'SELECT COUNT(*) total FROM Vendors'),
    'pending'   => dashStat('Pending Orders',   "SELECT COUNT(*) total FROM Orders WHERE status IN ('pending','processing')"),
    'avg_order' => dashStat('Average Order',    "SELECT NVL(AVG(total_amount),0) avg FROM Orders WHERE status <> 'cancelled'"),
    'delivered' => dashStat('Delivered Orders', "SELECT COUNT(*) total FROM Orders WHERE status='delivered'"),
];

// ── Top 5 Products ─────────────────────────────────────────────────────
$sql_top = "SELECT p.product_id, p.name, p.category, p.price,
            NVL(SUM(od.quantity),0) AS total_sold,
            NVL(SUM(od.subtotal),0) AS revenue
            FROM Products p
            LEFT JOIN Order_Details od ON p.product_id = od.product_id
            GROUP BY p.product_id, p.name, p.category, p.price
            ORDER BY total_sold DESC
            FETCH FIRST 5 ROWS ONLY";
$sql_log['Top Products'] = $sql_top;
$top_products = ociQuery($sql_top);

// ── Recent Orders ──────────────────────────────────────────────────────
$sql_orders = "SELECT o.order_id, TO_CHAR(o.order_date,'YYYY-MM-DD') AS order_date,
               o.status, o.total_amount, c.name AS customer_name,
               COUNT(od.order_detail_id) AS items
               FROM Orders o
               INNER JOIN Customers c ON o.customer_id = c.customer_id
               LEFT JOIN Order_Details od ON o.order_id = od.order_id
               GROUP BY o.order_id, o.order_date, o.status, o.total_amount, c.name
               ORDER BY o.order_date DESC
               FETCH FIRST 5 ROWS ONLY";
$sql_log['Recent Orders'] = $sql_orders;
$recent_orders = ociQuery($sql_orders);

// ── Top Vendors ────────────────────────────────────────────────────────
$sql_vendors = "SELECT v.vendor_name, v.location,
                COUNT(DISTINCT p.product_id) AS products,
                NVL(SUM(od.subtotal),0) AS sales
                FROM Vendors v
                LEFT JOIN Products p ON v.vendor_id = p.vendor_id
                LEFT JOIN Order_Details od ON p.product_id = od.product_id
                GROUP BY v.vendor_name, v.location
                ORDER BY sales DESC
                FETCH FIRST 5 ROWS ONLY";
$sql_log['Top Vendors'] = $sql_vendors;
$top_vendors = ociQuery($sql_vendors);

echo getHeader('Dashboard');
echo getNav();
?>
<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Hero -->
  <div class="mb-8 flex items-center justify-between">
    <div>
      <h1 class="text-4xl font-extrabold text-gray-900 mb-1">
        <i class="fas fa-tachometer-alt text-green-600 mr-2"></i>Admin Dashboard
      </h1>
      <p class="text-gray-500">GrocerCart &mdash; powered by <span class="text-orange-600 font-semibold">Oracle XE 11g</span> · OCI8</p>
    </div>
    <a href="init_db.php" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-semibold text-sm shadow transition">
      <i class="fas fa-database mr-2"></i>Init / Reset DB
    </a>
  </div>

  <!-- Stat Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10">
    <?php
    $cards = [
      ['Revenue','$'.number_format($stats['revenue'],2),'dollar-sign','from-blue-500 to-blue-600','Total Revenue','revenue'],
      ['Orders',$stats['orders'],'shopping-cart','from-green-500 to-green-600','Total Orders','orders'],
      ['Customers',$stats['customers'],'users','from-purple-500 to-purple-600','Total Customers','customers'],
      ['Avg Order','$'.number_format($stats['avg_order'],2),'chart-line','from-orange-500 to-orange-600','Average Order','avg_order'],
      ['Vendors',$stats['vendors'],'truck','from-red-500 to-red-600','Active Vendors','vendors'],
      ['Pending',$stats['pending'],'clock','from-yellow-500 to-yellow-600','Pending Orders','pending'],
      ['Delivered',$stats['delivered'],'check-circle','from-emerald-500 to-emerald-600','Delivered Orders','delivered'],
      ['Products',$stats['products'],'box','from-indigo-500 to-indigo-600','Total Products','products'],
    ];
    foreach ($cards as [$label,$val,$icon,$grad,$sqLabel,$sqKey]):
    ?>
    <div class="stat-card bg-gradient-to-br <?= $grad ?> rounded-2xl shadow-lg p-5 text-white relative overflow-hidden group cursor-help"
         title="SQL: <?= htmlspecialchars($sql_log[$sqLabel] ?? '') ?>">
      <div class="flex justify-between items-start">
        <div>
          <p class="text-white/70 text-xs font-medium uppercase tracking-wider"><?= $label ?></p>
          <p class="text-3xl font-extrabold mt-1"><?= $val ?></p>
        </div>
        <i class="fas fa-<?= $icon ?> text-5xl text-white/20 group-hover:text-white/30 transition"></i>
      </div>
      <div class="absolute bottom-0 left-0 right-0 bg-black/20 px-3 py-1.5 text-xs font-mono opacity-0 group-hover:opacity-100 transition truncate">
        <?= htmlspecialchars($sql_log[$sqLabel] ?? '') ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

    <!-- Top Products -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-5">
        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-fire text-orange-500 mr-2"></i>Top Products</h2>
        <button onclick="toggleSQL('sq-top-prod')" class="toggle-sql px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
      </div>
      <div id="sq-top-prod" class="sql-box mb-4"><?= htmlspecialchars($sql_top) ?></div>
      <?php if (empty($top_products)): ?>
        <p class="text-gray-400 text-center py-8">No data yet. <a href="init_db.php" class="text-blue-600 underline">Initialize DB</a></p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($top_products as $i => $p): ?>
          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
            <span class="w-7 h-7 flex-shrink-0 bg-orange-100 text-orange-700 font-bold text-sm rounded-full flex items-center justify-center"><?= $i+1 ?></span>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm truncate"><?= htmlspecialchars($p['name']) ?></p>
              <p class="text-xs text-gray-500"><?= htmlspecialchars($p['category']) ?> &middot; $<?= number_format($p['price'],2) ?></p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-bold text-sm"><?= $p['total_sold'] ?> units</p>
              <p class="text-xs text-green-600">$<?= number_format($p['revenue'],2) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Top Vendors -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-5">
        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-truck text-purple-500 mr-2"></i>Top Vendors</h2>
        <button onclick="toggleSQL('sq-top-vend')" class="toggle-sql px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition">
          <i class="fas fa-code mr-1"></i>SQL <i class="fas fa-chevron-down arrow ml-1"></i>
        </button>
      </div>
      <div id="sq-top-vend" class="sql-box mb-4"><?= htmlspecialchars($sql_vendors) ?></div>
      <?php if (empty($top_vendors)): ?>
        <p class="text-gray-400 text-center py-8">No vendors yet.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($top_vendors as $i => $v): ?>
          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
            <span class="w-7 h-7 flex-shrink-0 bg-purple-100 text-purple-700 font-bold text-sm rounded-full flex items-center justify-center"><?= $i+1 ?></span>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm truncate"><?= htmlspecialchars($v['vendor_name']) ?></p>
              <p class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($v['location']) ?></p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-bold text-sm"><?= $v['products'] ?> products</p>
              <p class="text-xs text-green-600">$<?= number_format($v['sales'],2) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
    <div class="flex justify-between items-center mb-5">
      <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-clock text-blue-500 mr-2"></i>Recent Orders</h2>
      <button onclick="toggleSQL('sq-recent')" class="toggle-sql px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition">
        <i class="fas fa-code mr-1"></i>SQL <i class="fas fa-chevron-down arrow ml-1"></i>
      </button>
    </div>
    <div id="sq-recent" class="sql-box mb-4"><?= htmlspecialchars($sql_orders) ?></div>
    <?php if (empty($recent_orders)): ?>
      <p class="text-gray-400 text-center py-8">No orders yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead><tr class="bg-gray-50 border-b">
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Order</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Items</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Amount</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
          </tr></thead>
          <tbody class="divide-y">
            <?php
            $sc = ['delivered'=>'bg-green-100 text-green-800','shipped'=>'bg-blue-100 text-blue-800',
                   'processing'=>'bg-yellow-100 text-yellow-800','pending'=>'bg-gray-100 text-gray-800','cancelled'=>'bg-red-100 text-red-800'];
            foreach ($recent_orders as $o):
            $cls = $sc[$o['status']] ?? 'bg-gray-100 text-gray-800'; ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-semibold">#<?= $o['order_id'] ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($o['customer_name']) ?></td>
              <td class="px-4 py-3 text-gray-500"><?= $o['order_date'] ?></td>
              <td class="px-4 py-3"><?= $o['items'] ?></td>
              <td class="px-4 py-3 font-semibold text-green-700">$<?= number_format($o['total_amount'],2) ?></td>
              <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $cls ?>"><?= ucfirst($o['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Quick Links -->
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    <?php $links=[
      ['customers_list.php','users','Customers','blue'],
      ['vendors_list.php','truck','Vendors','purple'],
      ['products_list.php','box','Products','green'],
      ['orders_list.php','shopping-bag','Orders','orange'],
      ['order_details_list.php','list','Order Details','indigo'],
      ['delivery_list.php','shipping-fast','Delivery','red'],
    ]; foreach($links as [$href,$icon,$lbl,$col]): ?>
    <a href="<?= $href ?>" class="bg-white rounded-xl shadow p-4 flex flex-col items-center gap-2 hover:shadow-lg hover:-translate-y-1 transition">
      <div class="w-10 h-10 bg-<?= $col ?>-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-<?= $icon ?> text-<?= $col ?>-600"></i>
      </div>
      <span class="text-sm font-medium text-gray-700"><?= $lbl ?></span>
    </a>
    <?php endforeach; ?>
  </div>

</div>
<?php echo getFooter(); ?>
