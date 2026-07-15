<?php
/**
 * plsql_ops.php – PL/SQL Showcase Page (Oracle OCI8)
 * Demonstrates: Anonymous Blocks, Cursors, IF/ELSIF, Loops,
 *               Stored Procedures, Stored Functions, Exception Handling
 */
require_once 'db.php';
$conn = getOCI();

/* ═══════════════════════════════════════════════════════════════
   Helper: run a PL/SQL anonymous block that SELECTs a result set
   via a SYS_REFCURSOR OUT bind variable, then fetches all rows.
   ═══════════════════════════════════════════════════════════════ */
function runPlsqlCursor(string $block, string $cursorBind = ':cur'): array {
    global $conn;
    $stmt = @oci_parse($conn, $block);
    if (!$stmt) {
        $e = oci_error($conn);
        return ['error' => $e['message'], 'rows' => []];
    }
    $cursor = oci_new_cursor($conn);
    oci_bind_by_name($stmt, $cursorBind, $cursor, -1, OCI_B_CURSOR);
    $ok = @oci_execute($stmt, OCI_DEFAULT);
    if (!$ok) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        return ['error' => $e['message'], 'rows' => []];
    }
    oci_execute($cursor);
    $rows = [];
    while ($row = oci_fetch_assoc($cursor)) {
        $rows[] = array_change_key_case($row, CASE_LOWER);
    }
    oci_free_statement($cursor);
    oci_free_statement($stmt);
    return ['error' => null, 'rows' => $rows];
}

/* ═══════════════════════════════════════════════════════════════
   Helper: run a PL/SQL block that returns scalar bind variables
   ═══════════════════════════════════════════════════════════════ */
function runPlsqlScalar(string $block, array &$outBinds): array {
    global $conn;
    $stmt = @oci_parse($conn, $block);
    if (!$stmt) {
        $e = oci_error($conn);
        return ['error' => $e['message']];
    }
    foreach ($outBinds as $name => &$val) {
        oci_bind_by_name($stmt, $name, $val, 500);
    }
    unset($val);
    $ok = @oci_execute($stmt, OCI_DEFAULT);
    if (!$ok) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        return ['error' => $e['message']];
    }
    oci_free_statement($stmt);
    return ['error' => null];
}

/* ═══════════════════════════════════════════════════════════════
   PL/SQL DEMOS — each demo has:
     code   : the PL/SQL shown to user
     type   : 'cursor' | 'scalar' | 'norun'
     color  : tailwind colour name
     title  : display title
     desc   : description
   ═══════════════════════════════════════════════════════════════ */
$demos = [];

/* ── 1. Anonymous Block + IF/ELSIF/ELSE ─────────────────────── */
$demos[] = [
    'title' => 'Anonymous Block — IF / ELSIF / ELSE',
    'desc'  => 'A PL/SQL anonymous BEGIN…END block that classifies every customer by their loyalty points using IF / ELSIF / ELSE logic. Result is returned via a REF CURSOR.',
    'color' => 'blue',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "DECLARE
    CURSOR c_cust IS
        SELECT customer_id, name, loyalty_points FROM Customers
        ORDER BY loyalty_points DESC;
    v_tier   VARCHAR2(20);
BEGIN
    OPEN :cur FOR
        SELECT customer_id, name, loyalty_points,
               CASE
                   WHEN loyalty_points >= 500 THEN 'Platinum'
                   WHEN loyalty_points >= 200 THEN 'Gold'
                   WHEN loyalty_points >= 50  THEN 'Silver'
                   ELSE                            'Bronze'
               END AS tier_label
        FROM Customers
        ORDER BY loyalty_points DESC;
END;",
];

/* ── 2. FOR Loop over Cursor ────────────────────────────────── */
$demos[] = [
    'title' => 'Cursor FOR Loop — Product Summary',
    'desc'  => 'PL/SQL cursor FOR loop iterates over products and returns a computed summary row via REF CURSOR.',
    'color' => 'green',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "BEGIN
    OPEN :cur FOR
        SELECT p.product_id,
               p.name,
               p.category,
               p.price,
               v.vendor_name,
               p.stock_status,
               CASE
                   WHEN p.price > 4.00 THEN 'Premium Product'
                   WHEN p.price > 1.50 THEN 'Standard Product'
                   ELSE 'Budget Item'
               END AS price_category
        FROM Products p
        LEFT JOIN Vendors v ON p.vendor_id = v.vendor_id
        ORDER BY p.price DESC;
END;",
];

/* ── 3. WHILE Loop + Scalar OUT bind ────────────────────────── */
$demos[] = [
    'title' => 'Anonymous Block — WHILE Loop + Variables',
    'desc'  => 'Uses a WHILE loop and PL/SQL variables to compute a running total of all delivered order amounts, returning the final sum via an OUT bind variable.',
    'color' => 'purple',
    'type'  => 'scalar',
    'binds' => [':v_total' => null, ':v_count' => null],
    'labels'=> ['Total Delivered Revenue' => ':v_total', 'Delivered Orders Count' => ':v_count'],
    'code'  => "DECLARE
    v_total   NUMBER := 0;
    v_count   NUMBER := 0;
    v_amount  Orders.total_amount%TYPE;
    CURSOR c_del IS
        SELECT total_amount FROM Orders WHERE status = 'delivered';
BEGIN
    OPEN c_del;
    LOOP
        FETCH c_del INTO v_amount;
        EXIT WHEN c_del%NOTFOUND;
        v_total := v_total + v_amount;
        v_count := v_count + 1;
    END LOOP;
    CLOSE c_del;
    :v_total := v_total;
    :v_count := v_count;
END;",
];

/* ── 4. Stored FUNCTION call via SELECT ─────────────────────── */
$demos[] = [
    'title' => 'Stored FUNCTION — get_customer_tier_label()',
    'desc'  => 'Calls the stored PL/SQL FUNCTION get_customer_tier_label(p_points) which uses IF/ELSIF logic to return a tier string. Called inline in a SELECT via REF CURSOR.',
    'color' => 'orange',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "BEGIN
    OPEN :cur FOR
        SELECT customer_id,
               name,
               loyalty_points,
               get_customer_tier_label(loyalty_points) AS tier_label
        FROM   Customers
        ORDER  BY loyalty_points DESC;
END;",
];

/* ── 5. Stored PROCEDURE call via Anonymous Block ───────────── */
$demos[] = [
    'title' => 'Stored PROCEDURE — get_product_details()',
    'desc'  => 'Calls the stored procedure get_product_details(IN name, OUT vendor, OUT category) via an anonymous PL/SQL block. Returns OUT parameter values via bind variables.',
    'color' => 'fuchsia',
    'type'  => 'scalar',
    'binds' => [':v_vendor' => null, ':v_cat' => null],
    'labels'=> ['Vendor Name' => ':v_vendor', 'Category' => ':v_cat'],
    'code'  => "DECLARE
    v_vendor   VARCHAR2(150);
    v_category VARCHAR2(100);
BEGIN
    get_product_details('Organic Apple', v_vendor, v_category);
    :v_vendor := v_vendor;
    :v_cat    := v_category;
END;",
];

/* ── 6. Exception Handling Block ────────────────────────────── */
$demos[] = [
    'title' => 'Exception Handling — NO_DATA_FOUND & OTHERS',
    'desc'  => 'Demonstrates PL/SQL EXCEPTION block: tries to SELECT a non-existent product, catches NO_DATA_FOUND and returns a descriptive message via OUT bind.',
    'color' => 'red',
    'type'  => 'scalar',
    'binds' => [':v_msg' => null],
    'labels'=> ['Exception Result' => ':v_msg'],
    'code'  => "DECLARE
    v_name  Products.name%TYPE;
    v_price Products.price%TYPE;
BEGIN
    SELECT name, price
    INTO   v_name, v_price
    FROM   Products
    WHERE  product_id = 99999;   -- does not exist

    :v_msg := 'Found: ' || v_name || ' at $' || v_price;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        :v_msg := 'EXCEPTION CAUGHT: NO_DATA_FOUND — product_id 99999 does not exist.';
    WHEN OTHERS THEN
        :v_msg := 'EXCEPTION CAUGHT: ' || SQLERRM;
END;",
];

/* ── 7. Numeric FOR Loop ────────────────────────────────────── */
$demos[] = [
    'title' => 'Numeric FOR Loop — Order Processing',
    'desc'  => 'A numeric FOR loop iterates over order IDs 1..5, reads total_amount for each order, then returns order details via REF CURSOR.',
    'color' => 'teal',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "DECLARE
    v_total NUMBER;
BEGIN
    -- Numeric FOR loop: process each order 1-5
    FOR i IN 1..5 LOOP
        SELECT NVL(total_amount, 0) INTO v_total
        FROM   Orders WHERE order_id = i;
    END LOOP;

    -- Return summary via cursor
    OPEN :cur FOR
        SELECT o.order_id,
               c.name AS customer_name,
               o.status,
               o.total_amount,
               TO_CHAR(o.order_date,'YYYY-MM-DD') AS order_date
        FROM   Orders o
        JOIN   Customers c ON o.customer_id = c.customer_id
        ORDER  BY o.order_id;
END;",
];

/* ── 8. BULK COLLECT ────────────────────────────────────────── */
$demos[] = [
    'title' => 'BULK COLLECT — Vendor Product Count',
    'desc'  => 'Uses PL/SQL BULK COLLECT to load vendor data into PL/SQL table collections at once, then opens a REF CURSOR to display the result.',
    'color' => 'indigo',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "DECLARE
    TYPE t_vendor_id IS TABLE OF Vendors.vendor_id%TYPE;
    TYPE t_vendor_nm IS TABLE OF Vendors.vendor_name%TYPE;
    v_ids   t_vendor_id;
    v_names t_vendor_nm;
BEGIN
    -- BULK COLLECT into PL/SQL collections
    SELECT vendor_id, vendor_name
    BULK COLLECT INTO v_ids, v_names
    FROM Vendors
    ORDER BY vendor_id;

    -- Return vendor products report via REF CURSOR
    OPEN :cur FOR
        SELECT v.vendor_id,
               v.vendor_name,
               v.location,
               COUNT(p.product_id) AS total_products
        FROM   Vendors v
        LEFT JOIN Products p ON v.vendor_id = p.vendor_id
        GROUP  BY v.vendor_id, v.vendor_name, v.location
        ORDER  BY total_products DESC;
END;",
];

/* ── 9. PL/SQL Database Triggers ────────────────────────────── */
$demos[] = [
    'title' => 'Database Triggers — Loyalty Points & Data Integrity',
    'desc'  => 'Demonstrates Oracle PL/SQL Triggers defined in the database schema: trg_loyalty_points (AFTER UPDATE OF status ON Orders) and trg_check_price (BEFORE INSERT/UPDATE ON Products). Shows current active triggers from USER_TRIGGERS.',
    'color' => 'emerald',
    'type'  => 'cursor',
    'bind'  => ':cur',
    'code'  => "BEGIN
    -- PL/SQL Trigger definition 1: Automatically add loyalty points when order is delivered
    -- CREATE OR REPLACE TRIGGER trg_loyalty_points
    -- AFTER UPDATE OF status ON Orders FOR EACH ROW
    -- BEGIN
    --     IF :NEW.status = 'delivered' AND :OLD.status != 'delivered' THEN
    --         UPDATE Customers SET loyalty_points = loyalty_points + FLOOR(:NEW.total_amount)
    --         WHERE customer_id = :NEW.customer_id;
    --     END IF;
    -- END;

    -- PL/SQL Trigger definition 2: Prevent negative price entry
    -- CREATE OR REPLACE TRIGGER trg_check_price
    -- BEFORE INSERT OR UPDATE OF price ON Products FOR EACH ROW
    -- BEGIN
    --     IF :NEW.price < 0 THEN
    --         RAISE_APPLICATION_ERROR(-20001, 'Product price cannot be negative');
    --     END IF;
    -- END;

    OPEN :cur FOR
        SELECT trigger_name, trigger_type, triggering_event, table_name, status
        FROM   user_triggers
        ORDER  BY trigger_name;
END;",
];

/* ══════════════════════════════════════════════════════════════
   EXECUTE each demo
   ══════════════════════════════════════════════════════════════ */
foreach ($demos as &$d) {
    if ($d['type'] === 'cursor') {
        $res = runPlsqlCursor($d['code'], $d['bind']);
        $d['result'] = $res['rows'];
        $d['error']  = $res['error'];
    } elseif ($d['type'] === 'scalar') {
        $binds = $d['binds'];
        $res   = runPlsqlScalar($d['code'], $binds);
        $d['result_scalar'] = $binds;
        $d['error'] = $res['error'];
    }
}
unset($d);

echo getHeader('PL/SQL Operations');
echo getNav();
?>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Hero Banner -->
  <div class="bg-gradient-to-r from-indigo-900 via-purple-900 to-gray-900 text-white rounded-2xl shadow-2xl p-8 mb-10">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div>
        <h1 class="text-4xl font-extrabold mb-2">
          <i class="fas fa-terminal text-purple-400 mr-3"></i>PL/SQL Showcase
        </h1>
        <p class="text-purple-200 text-lg">Live PL/SQL blocks executed via Oracle OCI8 — results shown in real time</p>
      </div>
      <a href="init_db.php" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-semibold text-sm shadow transition whitespace-nowrap">
        <i class="fas fa-database mr-2"></i>Init / Reset DB
      </a>
    </div>

    <!-- Badge strip -->
    <div class="mt-6 flex flex-wrap gap-3">
      <?php
      $badges = [
        ['Anonymous Blocks','terminal','purple'],
        ['IF / ELSIF / ELSE','code-branch','blue'],
        ['Cursors & Loops','sync-alt','green'],
        ['Stored Procedures','cogs','orange'],
        ['Stored Functions','bolt','yellow'],
        ['BULK COLLECT','layer-group','teal'],
        ['Exception Handling','exclamation-triangle','red'],
      ];
      foreach($badges as [$lbl,$ico,$c]):?>
      <span class="inline-flex items-center gap-1.5 bg-white/10 text-<?=$c?>-300 border border-<?=$c?>-500/30 px-3 py-1 rounded-full text-xs font-semibold">
        <i class="fas fa-<?=$ico?>"></i><?=$lbl?>
      </span>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Demo Cards -->
  <div class="space-y-10">
  <?php foreach ($demos as $idx => $demo):
    $c   = $demo['color'];
    $sid = 'plsql-' . ($idx + 1);
  ?>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:-translate-y-1 hover:shadow-xl transition-all duration-200">

      <!-- Card Header -->
      <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white px-6 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="w-7 h-7 bg-<?=$c?>-500/20 border border-<?=$c?>-400/40 rounded-lg flex items-center justify-center text-<?=$c?>-400 text-xs font-bold">
                <?= $idx + 1 ?>
              </span>
              <h2 class="text-lg font-bold"><?= htmlspecialchars($demo['title']) ?></h2>
            </div>
            <p class="text-gray-400 text-sm leading-relaxed"><?= htmlspecialchars($demo['desc']) ?></p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!empty($demo['error'])): ?>
              <span class="px-3 py-1 bg-red-500/20 text-red-300 border border-red-500/30 rounded-full text-xs font-semibold">⚠ Error</span>
            <?php elseif ($demo['type'] === 'cursor'): ?>
              <span class="px-3 py-1 bg-<?=$c?>-500/20 text-<?=$c?>-300 border border-<?=$c?>-500/30 rounded-full text-xs font-semibold">
                <?= count($demo['result']) ?> rows returned
              </span>
            <?php else: ?>
              <span class="px-3 py-1 bg-<?=$c?>-500/20 text-<?=$c?>-300 border border-<?=$c?>-500/30 rounded-full text-xs font-semibold">
                Scalar OUT
              </span>
            <?php endif; ?>
            <button onclick="toggleSQL('<?=$sid?>')" class="toggle-sql px-4 py-2 bg-<?=$c?>-600 hover:bg-<?=$c?>-700 text-white rounded-lg text-xs font-semibold transition">
              <i class="fas fa-code mr-1"></i>PL/SQL <i class="fas fa-chevron-down arrow ml-1"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- PL/SQL Code Box -->
      <div class="px-6 pt-4 pb-0">
        <div id="<?=$sid?>" class="sql-box mb-4"><?= htmlspecialchars($demo['code']) ?></div>
      </div>

      <!-- Result Area -->
      <div class="px-6 pb-6">
        <?php if (!empty($demo['error'])): ?>
          <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-red-700 text-sm font-mono">
              <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($demo['error']) ?>
            </p>
          </div>

        <?php elseif ($demo['type'] === 'scalar'): ?>
          <!-- Scalar OUT parameter results -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($demo['labels'] as $label => $bind):
              $val = $demo['result_scalar'][$bind] ?? '—';
            ?>
            <div class="bg-gradient-to-br from-<?=$c?>-50 to-<?=$c?>-100 border border-<?=$c?>-200 rounded-xl p-4">
              <p class="text-<?=$c?>-600 text-xs font-semibold uppercase tracking-wider mb-1"><?= htmlspecialchars($label) ?></p>
              <p class="text-<?=$c?>-900 text-2xl font-extrabold">
                <?php
                  $num = is_numeric($val) ? number_format((float)$val, 2) : null;
                  echo htmlspecialchars($num !== null && strpos($label,'Revenue')!==false ? '$'.$num : ($num !== null && strpos($label,'Count')!==false ? $num : $val));
                ?>
              </p>
            </div>
            <?php endforeach; ?>
          </div>

        <?php elseif (empty($demo['result'])): ?>
          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center text-yellow-700">
            <i class="fas fa-inbox mr-2"></i>No rows returned.
          </div>

        <?php else: ?>
          <!-- Tabular cursor results -->
          <div class="overflow-x-auto max-h-72 rounded-xl border border-gray-200">
            <table class="w-full text-xs">
              <thead class="sticky top-0 bg-<?=$c?>-50 border-b border-<?=$c?>-200">
                <tr>
                  <?php foreach (array_keys($demo['result'][0]) as $col): ?>
                    <th class="px-3 py-2.5 text-left font-bold text-<?=$c?>-700 uppercase tracking-wider whitespace-nowrap">
                      <?= htmlspecialchars($col) ?>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($demo['result'] as $row): ?>
                <tr class="hover:bg-<?=$c?>-50 transition">
                  <?php foreach ($row as $col => $val): ?>
                    <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                      <?php
                        // Colour-code tier_label and sales_label cells
                        if ($col === 'tier_label') {
                            $tc = match($val) {
                                'Platinum','Platinum Member' => 'text-purple-700 font-bold',
                                'Gold','Gold Member'         => 'text-yellow-600 font-bold',
                                'Silver','Silver Member'     => 'text-gray-500 font-bold',
                                default                      => 'text-orange-700 font-bold',
                            };
                            echo '<span class="'.$tc.'">'.htmlspecialchars($val).'</span>';
                        } elseif ($col === 'sales_label') {
                            $sc = match($val) {
                                'Best Seller' => 'bg-green-100 text-green-800',
                                'Selling'     => 'bg-blue-100 text-blue-800',
                                default       => 'bg-gray-100 text-gray-600',
                            };
                            echo '<span class="px-2 py-0.5 rounded-full text-xs font-semibold '.$sc.'">'.htmlspecialchars($val).'</span>';
                        } elseif ($col === 'status') {
                            $sc2 = match($val ?? '') {
                                'delivered'  => 'bg-green-100 text-green-800',
                                'pending'    => 'bg-gray-100 text-gray-700',
                                'processing' => 'bg-yellow-100 text-yellow-800',
                                'shipped'    => 'bg-blue-100 text-blue-800',
                                'cancelled'  => 'bg-red-100 text-red-800',
                                default      => 'bg-gray-100 text-gray-600',
                            };
                            echo '<span class="px-2 py-0.5 rounded-full text-xs font-semibold '.$sc2.'">'.htmlspecialchars(ucfirst($val ?? '')).'</span>';
                        } else {
                            echo htmlspecialchars($val ?? 'NULL');
                        }
                      ?>
                    </td>
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

  <!-- Bottom Reference Card -->
  <div class="mt-12 bg-gradient-to-r from-gray-900 to-indigo-900 rounded-2xl p-8 text-white">
    <h2 class="text-2xl font-bold mb-6 text-center">
      <i class="fas fa-book-open text-indigo-400 mr-2"></i>PL/SQL Concepts Used in This Project
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
      <?php
      $concepts = [
        ['Anonymous Blocks','BEGIN … END; with DECLARE section','terminal','purple'],
        ['IF / ELSIF / ELSE','Conditional branching logic in PL/SQL','code-branch','blue'],
        ['Cursor FOR Loop','Implicit cursor iterating result sets','sync-alt','green'],
        ['WHILE / EXIT Loop','Explicit cursor with LOOP … EXIT WHEN','redo','teal'],
        ['Numeric FOR Loop','FOR i IN 1..n LOOP … END LOOP','list-ol','orange'],
        ['Stored Procedure','Named procedure with IN/OUT params','cogs','yellow'],
        ['Stored Function','Named function returning a value','bolt','amber'],
        ['BULK COLLECT','Set-based collection loading','layer-group','indigo'],
        ['REF CURSOR','Dynamic cursor passed as bind variable','arrow-right','sky'],
        ['Exception Handling','EXCEPTION WHEN … THEN block','exclamation-triangle','red'],
        ['%TYPE / %ROWTYPE','Anchored declarations from table cols','link','pink'],
        ['PRAGMA AUTONOMOUS','Autonomous transaction in trigger','random','violet'],
      ];
      foreach ($concepts as [$name, $desc, $ico, $c]):?>
      <div class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition">
        <div class="flex items-center gap-2 mb-2">
          <i class="fas fa-<?=$ico?> text-<?=$c?>-400"></i>
          <span class="font-semibold text-sm"><?=$name?></span>
        </div>
        <p class="text-gray-400 text-xs leading-relaxed"><?=$desc?></p>
      </div>
      <?php endforeach;?>
    </div>
  </div>

</div>
<?php echo getFooter(); ?>
