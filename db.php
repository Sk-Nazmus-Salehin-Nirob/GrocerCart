<?php
/**
 * GrocerCart – Oracle OCI8 Database Connection Helper
 * Username : salehin_076923
 * Service  : localhost/XE  (Oracle XE 11g, port 1521)
 */

define('OCI_USER',    'salehin_076923');
define('OCI_PASS',    '2207045');
define('OCI_CONNSTR', 'localhost/XE');

/**
 * Return a persistent OCI connection.
 * Call once at the top of every page: $conn = getOCI();
 */
function getOCI(): mixed {
    static $conn = null;
    if ($conn === null) {
        $conn = @oci_connect(OCI_USER, OCI_PASS, OCI_CONNSTR, 'AL32UTF8');
        if (!$conn) {
            $e = oci_error();
            die('<div style="font-family:monospace;color:#c00;padding:20px;background:#fee;border:2px solid #c00;border-radius:8px;margin:40px auto;max-width:700px">
                <h2>Oracle Connection Failed</h2>
                <p>' . htmlspecialchars($e['message']) . '</p>
                <p>Check: Oracle XE is running &amp; credentials in db.php are correct.</p>
             </div>');
        }
    }
    return $conn;
}

/**
 * Execute a query and return all rows as an associative array.
 * Supports named bind variables: e.g. ':id'
 */
function ociQuery(string $sql, array $binds = []): array {
    $conn  = getOCI();
    $stmt  = oci_parse($conn, $sql);
    foreach ($binds as $key => $val) {
        oci_bind_by_name($stmt, $key, $binds[$key]);
    }
    oci_execute($stmt, OCI_DEFAULT);
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $rows[] = array_change_key_case($row, CASE_LOWER);
    }
    oci_free_statement($stmt);
    return $rows;
}

/**
 * Execute an INSERT / UPDATE / DELETE and commit.
 * Returns true on success, throws on error.
 */
function ociExec(string $sql, array $binds = []): bool {
    $conn = getOCI();
    $stmt = oci_parse($conn, $sql);
    foreach ($binds as $key => $val) {
        oci_bind_by_name($stmt, $key, $binds[$key]);
    }
    $ok = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    if (!$ok) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        throw new \RuntimeException('OCI Error: ' . $e['message']);
    }
    oci_commit($conn);
    oci_free_statement($stmt);
    return true;
}

/**
 * Fetch a single row.
 */
function ociOne(string $sql, array $binds = []): ?array {
    $rows = ociQuery($sql, $binds);
    return $rows[0] ?? null;
}

/**
 * Get the last generated ID from a sequence.
 */
function ociLastId(string $sequence): int {
    $row = ociOne("SELECT {$sequence}.CURRVAL AS id FROM DUAL");
    return (int)($row['id'] ?? 0);
}

/* ─── Shared HTML helpers ─────────────────────────────────────────── */

function getHeader(string $title = 'GrocerCart'): string {
    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title) . ' – GrocerCart</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: "Inter", sans-serif; }
    .gradient-nav { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
    .sql-box { display:none; background:#0f172a; color:#e2e8f0; padding:16px; border-radius:10px;
               margin-top:12px; font-family:monospace; font-size:13px; white-space:pre-wrap;
               border:2px solid #3b82f6; box-shadow:0 4px 24px rgba(59,130,246,.15); }
    .sql-box.active { display:block; animation:fadeIn .25s ease; }
    .toggle-sql i.arrow { transition:transform .3s; }
    .toggle-sql.active i.arrow { transform:rotate(180deg); }
    .sql-tip { position:relative; }
    .sql-tip:hover .tip-text { visibility:visible; opacity:1; }
    .tip-text { visibility:hidden; opacity:0; position:absolute; z-index:9999; background:#0f172a;
                color:#e2e8f0; padding:10px 14px; border-radius:8px; font-family:monospace;
                font-size:11px; white-space:pre; border:2px solid #3b82f6; transition:opacity .25s;
                bottom:110%; left:50%; transform:translateX(-50%); min-width:280px; max-width:480px;
                box-shadow:0 8px 24px rgba(0,0,0,.4); }
    .tip-text::after { content:""; position:absolute; top:100%; left:50%; margin-left:-7px;
                       border:7px solid transparent; border-top-color:#3b82f6; }
    .stat-card { transition:transform .2s, box-shadow .2s; }
    .stat-card:hover { transform:translateY(-4px); box-shadow:0 16px 32px rgba(0,0,0,.12); }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
    tr { transition:background .15s; }
  </style>
  <script>
    function toggleSQL(id) {
      document.getElementById(id).classList.toggle("active");
      event.currentTarget.classList.toggle("active");
    }
  </script>
</head>
<body class="bg-gray-50">';
}

function getNav(): string {
    $cur = basename($_SERVER['PHP_SELF']);
    $link = fn($href,$label) =>
        '<a href="'.$href.'" class="'.($cur===$href
            ? 'text-green-400 font-semibold'
            : 'text-gray-300 hover:text-white').' px-3 py-2 rounded-lg hover:bg-white/10 transition text-sm">'.$label.'</a>';
    return '<nav class="gradient-nav shadow-2xl sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between h-16">
      <a href="index.php" class="flex items-center gap-3">
        <div class="w-9 h-9 bg-green-500 rounded-xl flex items-center justify-center shadow-lg">
          <i class="fas fa-shopping-cart text-white text-sm"></i>
        </div>
        <span class="text-white text-xl font-bold tracking-tight">Grocer<span class="text-green-400">Cart</span></span>
        <span class="text-xs text-blue-300 bg-blue-900/50 px-2 py-0.5 rounded-full">Oracle XE</span>
      </a>
      <div class="flex items-center gap-1">
        '.$link('index.php','<i class="fas fa-tachometer-alt mr-1"></i>Dashboard').'
        '.$link('customers_list.php','<i class="fas fa-users mr-1"></i>Customers').'
        '.$link('vendors_list.php','<i class="fas fa-truck mr-1"></i>Vendors').'
        '.$link('products_list.php','<i class="fas fa-box mr-1"></i>Products').'
        '.$link('orders_list.php','<i class="fas fa-shopping-bag mr-1"></i>Orders').'
        '.$link('order_details_list.php','<i class="fas fa-list mr-1"></i>Order Details').'
        '.$link('delivery_list.php','<i class="fas fa-shipping-fast mr-1"></i>Delivery').'
        '.$link('sql_ops.php','<i class="fas fa-code mr-1"></i>SQL Ops').'
      </div>
    </div>
  </div>
</nav>';
}

function getFooter(): string {
    return '<footer class="bg-gray-900 text-gray-400 mt-16 py-8">
  <div class="max-w-7xl mx-auto px-4 text-center">
    <div class="flex items-center justify-center gap-2 mb-2">
      <i class="fas fa-database text-green-500"></i>
      <span class="text-white font-semibold">GrocerCart</span>
      <span class="text-xs bg-blue-900 text-blue-300 px-2 py-0.5 rounded-full">Oracle XE 11g</span>
    </div>
    <p class="text-sm">Database Systems Course Project &mdash; PHP + Oracle OCI8</p>
    <p class="text-xs mt-1 text-gray-600">Connected as: <code class="text-green-400">' . OCI_USER . '@' . OCI_CONNSTR . '</code></p>
  </div>
</footer>
</body></html>';
}
