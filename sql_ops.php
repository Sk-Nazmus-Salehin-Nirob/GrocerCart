<?php
require_once 'db.php';

/* ── All Oracle-syntax SQL demos ──────────────────────── */
$demos = [];

// INNER JOIN
$demos['INNER JOIN'] = [
    'sql'  => "SELECT * FROM (
  SELECT o.order_id, c.name AS customer, o.total_amount, o.status, TO_CHAR(o.order_date,'YYYY-MM-DD') AS order_date
  FROM Orders o
  INNER JOIN Customers c ON o.customer_id = c.customer_id
  ORDER BY o.order_id DESC
) WHERE ROWNUM <= 10",
    'desc' => 'Combines rows from Orders and Customers where customer_id matches.',
    'color'=> 'blue',
];

// LEFT JOIN
$demos['LEFT JOIN'] = [
    'sql'  => "SELECT c.customer_id, c.name, c.email,
COUNT(o.order_id) AS total_orders,
NVL(SUM(o.total_amount), 0) AS total_spent
FROM Customers c
LEFT JOIN Orders o ON c.customer_id = o.customer_id
GROUP BY c.customer_id, c.name, c.email
ORDER BY total_spent DESC",
    'desc' => 'Returns all customers even if they have no orders.',
    'color'=> 'green',
];

// RIGHT JOIN (Oracle compatible)
$demos['RIGHT JOIN'] = [
    'sql'  => "SELECT p.product_id, p.name AS product, p.price, v.vendor_name
FROM Products p
RIGHT JOIN Vendors v ON p.vendor_id = v.vendor_id
ORDER BY v.vendor_name",
    'desc' => 'All vendors shown even if they have no products.',
    'color'=> 'purple',
];

// Scalar Subquery
$demos['Scalar Subquery'] = [
    'sql'  => "SELECT product_id, name, category, price,
(SELECT AVG(price) FROM Products) AS avg_price,
price - (SELECT AVG(price) FROM Products) AS price_diff
FROM Products
WHERE price > (SELECT AVG(price) FROM Products)
ORDER BY price DESC",
    'desc' => 'Returns single value. Products priced above average.',
    'color'=> 'orange',
];

// Subquery with IN
$demos['Subquery with IN'] = [
    'sql'  => "SELECT * FROM Customers
WHERE customer_id IN (
    SELECT customer_id FROM Orders
    WHERE total_amount > 20
)
ORDER BY name",
    'desc' => 'Find customers who placed orders over $20.',
    'color'=> 'red',
];

// Correlated Subquery
$demos['Correlated Subquery'] = [
    'sql'  => "SELECT c.customer_id, c.name, c.email,
(SELECT COUNT(*) FROM Orders o WHERE o.customer_id = c.customer_id) AS order_count,
(SELECT NVL(SUM(total_amount),0) FROM Orders o WHERE o.customer_id = c.customer_id) AS total_spent
FROM Customers c
WHERE (SELECT COUNT(*) FROM Orders o WHERE o.customer_id = c.customer_id) > 0
ORDER BY total_spent DESC",
    'desc' => 'Subquery references the outer query row-by-row.',
    'color'=> 'indigo',
];

// EXISTS
$demos['EXISTS Subquery'] = [
    'sql'  => "SELECT * FROM Products p
WHERE EXISTS (
    SELECT 1 FROM Order_Details od WHERE od.product_id = p.product_id
)
ORDER BY name",
    'desc' => 'Find products that have been ordered at least once.',
    'color'=> 'teal',
];

// NOT EXISTS
$demos['NOT EXISTS'] = [
    'sql'  => "SELECT * FROM Products p
WHERE NOT EXISTS (
    SELECT 1 FROM Order_Details od WHERE od.product_id = p.product_id
)
ORDER BY name",
    'desc' => 'Find products that have NEVER been ordered.',
    'color'=> 'pink',
];

// UNION
$demos['UNION'] = [
    'sql'  => "SELECT email AS contact_email, 'Customer' AS type FROM Customers
UNION
SELECT contact_email, 'Vendor' AS type FROM Vendors WHERE contact_email IS NOT NULL
ORDER BY contact_email",
    'desc' => 'Combines results, removes duplicates.',
    'color'=> 'yellow',
];

// UNION ALL
$demos['UNION ALL'] = [
    'sql'  => "SELECT name AS person_name FROM Customers
UNION ALL
SELECT vendor_name FROM Vendors
ORDER BY person_name",
    'desc' => 'Combines results keeping all rows including duplicates.',
    'color'=> 'cyan',
];

// INTERSECT (Oracle supports it natively!)
$demos['INTERSECT'] = [
    'sql'  => "SELECT email FROM Customers
INTERSECT
SELECT contact_email FROM Vendors",
    'desc' => 'Oracle native INTERSECT – emails in both tables.',
    'color'=> 'emerald',
];

// MINUS (Oracle's EXCEPT)
$demos['MINUS (EXCEPT)'] = [
    'sql'  => "SELECT email FROM Customers
MINUS
SELECT contact_email FROM Vendors",
    'desc' => 'Oracle MINUS (equivalent to SQL EXCEPT) – customer emails not in vendors.',
    'color'=> 'rose',
];

// GROUP BY + HAVING
$demos['GROUP BY + HAVING'] = [
    'sql'  => "SELECT p.category,
COUNT(*) AS product_count,
ROUND(AVG(p.price),2) AS avg_price,
MIN(p.price) AS min_price,
MAX(p.price) AS max_price
FROM Products p
GROUP BY p.category
HAVING COUNT(*) >= 1
ORDER BY avg_price DESC",
    'desc' => 'Groups by category, filters with HAVING.',
    'color'=> 'violet',
];

// CASE WHEN
$demos['CASE WHEN'] = [
    'sql'  => "SELECT product_id, name, price,
CASE
    WHEN price < 3 THEN 'Budget'
    WHEN price < 5 THEN 'Standard'
    WHEN price < 10 THEN 'Premium'
    ELSE 'Luxury'
END AS price_tier,
CASE
    WHEN category IN ('Vegetables','Fruits') THEN 'Fresh Produce'
    ELSE 'Other'
END AS product_group
FROM Products
ORDER BY price",
    'desc' => 'Conditional logic in SELECT – categorises products.',
    'color'=> 'amber',
];

// View query
$demos['VIEW (CustomerOrderSummary)'] = [
    'sql'  => "SELECT * FROM CustomerOrderSummary ORDER BY total_spent DESC",
    'desc' => 'Querying the Oracle VIEW CustomerOrderSummary.',
    'color'=> 'sky',
];

// Stored Function call (runnable inside SELECT)
$demos['PL/SQL Function — get_customer_tier_label'] = [
    'sql'  => "SELECT customer_id,
       name,
       loyalty_points,
       get_customer_tier_label(loyalty_points) AS tier_label
FROM   Customers
ORDER  BY loyalty_points DESC",
    'desc' => 'Calls the stored PL/SQL FUNCTION inside a regular SELECT. Function uses IF/ELSIF/ELSE to classify customer loyalty tiers.',
    'color'=> 'fuchsia',
];

// Stored Procedure demo (no_run — needs DECLARE...BEGIN block)
$demos['PL/SQL Procedure — get_product_details'] = [
    'sql'  => "DECLARE
    v_vendor   VARCHAR2(150);
    v_category VARCHAR2(100);
BEGIN
    get_product_details('Organic Apple', v_vendor, v_category);
    DBMS_OUTPUT.PUT_LINE('Vendor:   ' || v_vendor);
    DBMS_OUTPUT.PUT_LINE('Category: ' || v_category);
END;",
    'desc' => 'PL/SQL anonymous block calling stored procedure get_product_details (IN name → OUT vendor, OUT category). Run in SQL*Plus or SQL Developer to see DBMS_OUTPUT.',
    'color'=> 'amber',
    'no_run'=> true,
];

// update_order_status procedure reference
$demos['PL/SQL Procedure — update_order_status'] = [
    'sql'  => "DECLARE
BEGIN
    -- Updates order #2 to shipped status.
    -- Business rule enforced inside procedure:
    --   cannot reopen a cancelled order.
    update_order_status(2, 'shipped');
    DBMS_OUTPUT.PUT_LINE('Order 2 status updated to shipped.');
END;",
    'desc' => 'PL/SQL procedure with IF/EXCEPTION business rules: validates order exists, prevents un-cancelling. Run in SQL*Plus or SQL Developer.',
    'color'=> 'rose',
    'no_run'=> true,
];

// Execute all runnable queries
foreach ($demos as $title => &$demo) {
    if (!empty($demo['no_run'])) { $demo['result'] = []; continue; }
    try {
        $demo['result'] = ociQuery($demo['sql']);
    } catch (\Throwable $e) {
        $demo['result'] = [];
        $demo['error']  = $e->getMessage();
    }
}
unset($demo);

echo getHeader('SQL Operations'); echo getNav();
?>
<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Hero -->
  <div class="bg-gradient-to-r from-gray-900 to-indigo-900 text-white rounded-2xl shadow-2xl p-8 mb-8">
    <h1 class="text-4xl font-extrabold mb-2"><i class="fas fa-code text-indigo-400 mr-3"></i>SQL Operations Showcase</h1>
    <p class="text-indigo-200 text-lg">Oracle SQL syntax &mdash; every major concept demonstrated live</p>
    <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
      <?php $counts=[ ['JOINs',4,'link','blue'], ['Subqueries',4,'search','green'], ['Set Ops',4,'object-group','purple'], ['Aggregations',1,'chart-bar','orange'], ['PL/SQL',4,'terminal','fuchsia'] ]; ?>
      <?php foreach($counts as [$lbl,$n,$icon,$c]): ?>
      <div class="bg-white/10 rounded-xl p-3 flex items-center gap-2">
        <i class="fas fa-<?=$icon?> text-<?=$c?>-300"></i>
        <span class="font-semibold"><?=$n?> <?=$lbl?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="space-y-8">
  <?php $i=0; foreach ($demos as $title => $demo): $i++; $sqlId='sql-demo-'.$i; $c=$demo['color']??'blue'; ?>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:-translate-y-1 hover:shadow-xl transition-all duration-200">
      <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div>
            <h2 class="text-xl font-bold"><i class="fas fa-database text-<?=$c?>-400 mr-2"></i><?= htmlspecialchars($title) ?></h2>
            <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($demo['desc']) ?></p>
          </div>
          <div class="flex items-center gap-3">
            <button onclick="toggleSQL('<?=$sqlId?>')" class="toggle-sql px-4 py-2 bg-<?=$c?>-600 hover:bg-<?=$c?>-700 text-white rounded-lg text-sm transition">
              <i class="fas fa-code mr-1"></i>View SQL <i class="fas fa-chevron-down arrow ml-1"></i>
            </button>
            <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-semibold">
              <?= isset($demo['error']) ? '⚠ Error' : (empty($demo['no_run']) ? count($demo['result']).' rows' : 'PL/SQL') ?>
            </span>
          </div>
        </div>
      </div>

      <div class="p-6">
        <div id="<?=$sqlId?>" class="sql-box mb-5"><?= htmlspecialchars($demo['sql']) ?></div>

        <?php if (!empty($demo['no_run'])): ?>
          <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-6 text-center text-gray-500">
            <i class="fas fa-terminal text-3xl mb-2 block text-gray-400"></i>
            Run this in Oracle SQL Developer or SQL*Plus to see results.
          </div>
        <?php elseif (isset($demo['error'])): ?>
          <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-red-700 text-sm font-mono"><i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($demo['error']) ?></p>
          </div>
        <?php elseif (empty($demo['result'])): ?>
          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center text-yellow-700">
            <i class="fas fa-inbox mr-2"></i>No results found.
          </div>
        <?php else: ?>
          <div class="overflow-x-auto max-h-80 rounded-xl border border-gray-200">
            <table class="w-full text-xs">
              <thead class="sticky top-0 bg-gray-100">
                <tr>
                  <?php foreach(array_keys($demo['result'][0]) as $col): ?>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700 uppercase tracking-wider"><?= htmlspecialchars($col) ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody class="divide-y">
                <?php foreach($demo['result'] as $row): ?>
                <tr class="hover:bg-gray-50">
                  <?php foreach($row as $val): ?>
                    <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($val ?? 'NULL') ?></td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
<?php echo getFooter(); ?>
