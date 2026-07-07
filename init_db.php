<?php
/**
 * init_db.php  –  Create Oracle tables, sequences, triggers, views & sample data
 * Run once: http://localhost/GrocerCart/init_db.php
 * Reset  :  http://localhost/GrocerCart/init_db.php?reset=1
 */
require_once __DIR__ . '/db.php';

$conn = getOCI();
$log  = [];

function exec_sql(string $sql, string $label): void {
    global $conn, $log;
    $stmt = @oci_parse($conn, $sql);
    if (!$stmt) { $e = oci_error($conn); $log[] = ['label'=>$label,'ok'=>false,'msg'=>$e['message']]; return; }
    $ok = @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    if (!$ok) { $e = oci_error($stmt); $log[] = ['label'=>$label,'ok'=>false,'msg'=>$e['message']]; }
    else       { $log[] = ['label'=>$label,'ok'=>true,'msg'=>'']; }
    oci_free_statement($stmt);
}

function drop_if_exists(string $type, string $name): void {
    global $conn;
    $col = strtoupper($type) === 'TABLE' ? 'TABLE_NAME' : (strtoupper($type) === 'SEQUENCE' ? 'SEQUENCE_NAME' : 'VIEW_NAME');
    $dict = strtoupper($type) === 'TABLE' ? 'USER_TABLES' : (strtoupper($type) === 'SEQUENCE' ? 'USER_SEQUENCES' : 'USER_VIEWS');
    $check = oci_parse($conn, "SELECT COUNT(*) cnt FROM {$dict} WHERE {$col} = '".strtoupper($name)."'");
    oci_execute($check, OCI_DEFAULT);
    $row = oci_fetch_assoc($check);
    oci_free_statement($check);
    if ((int)$row['CNT'] > 0) {
        $cascade = strtoupper($type) === 'TABLE' ? ' CASCADE CONSTRAINTS' : '';
        exec_sql("DROP {$type} " . strtoupper($name) . $cascade, "Drop {$type} {$name}");
    }
}

function drop_trigger(string $name): void {
    global $conn;
    $check = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_TRIGGERS WHERE TRIGGER_NAME = '".strtoupper($name)."'");
    oci_execute($check, OCI_DEFAULT);
    $row = oci_fetch_assoc($check);
    oci_free_statement($check);
    if ((int)$row['CNT'] > 0) exec_sql("DROP TRIGGER ".strtoupper($name), "Drop trigger {$name}");
}

function drop_procedure(string $name): void {
    global $conn;
    $check = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_PROCEDURES WHERE OBJECT_NAME = '".strtoupper($name)."'");
    oci_execute($check, OCI_DEFAULT);
    $row = oci_fetch_assoc($check);
    oci_free_statement($check);
    if ((int)$row['CNT'] > 0) exec_sql("DROP PROCEDURE ".strtoupper($name), "Drop procedure {$name}");
}

/* ── RESET ──────────────────────────────────────────── */
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    foreach (['trg_loyalty_points','trg_check_price','trg_check_discount',
              'trg_customers_bi','trg_vendors_bi','trg_products_bi','trg_orders_bi',
              'trg_delivery_bi'] as $t) drop_trigger($t);
    foreach (['CustomerOrderSummary'] as $v) drop_if_exists('VIEW', $v);
    foreach (['get_product_details'] as $p) drop_procedure($p);
    foreach (['Delivery','Orders','Products','Vendors','Customers'] as $t) drop_if_exists('TABLE', $t);
    foreach (['seq_customers','seq_vendors','seq_products','seq_orders','seq_delivery'] as $s) drop_if_exists('SEQUENCE', $s);
    oci_commit($conn);
    header('Location: init_db.php');
    exit;
}

/* ── SEQUENCES (replaces AUTO_INCREMENT) ────────────── */
$sequences = ['seq_customers','seq_vendors','seq_products','seq_orders','seq_delivery'];
foreach ($sequences as $seq) {
    $check = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_SEQUENCES WHERE SEQUENCE_NAME = '".strtoupper($seq)."'");
    oci_execute($check, OCI_DEFAULT);
    $row = oci_fetch_assoc($check);
    oci_free_statement($check);
    if ((int)$row['CNT'] === 0) {
        exec_sql("CREATE SEQUENCE {$seq} START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE", "Create sequence {$seq}");
    } else {
        $log[] = ['label'=>"Sequence {$seq}",'ok'=>true,'msg'=>'Already exists'];
    }
}

/* ── TABLES ─────────────────────────────────────────── */

// Check table exists helper
function table_exists(string $name): bool {
    global $conn;
    $s = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_TABLES WHERE TABLE_NAME='".strtoupper($name)."'");
    oci_execute($s, OCI_DEFAULT);
    $r = oci_fetch_assoc($s); oci_free_statement($s);
    return (int)$r['CNT'] > 0;
}

if (!table_exists('Customers')) {
    exec_sql("CREATE TABLE Customers (
        customer_id    NUMBER PRIMARY KEY,
        name           VARCHAR2(100) NOT NULL,
        email          VARCHAR2(150),
        phone          VARCHAR2(30),
        address        VARCHAR2(500),
        preferences    VARCHAR2(300),
        loyalty_points NUMBER DEFAULT 0,
        customer_tier  VARCHAR2(20) DEFAULT 'Bronze',
        CONSTRAINT uq_cust_email UNIQUE (email),
        CONSTRAINT ck_cust_tier CHECK (customer_tier IN ('Bronze','Silver','Gold','Platinum'))
    )", 'Create Customers');
}

if (!table_exists('Vendors')) {
    exec_sql("CREATE TABLE Vendors (
        vendor_id     NUMBER PRIMARY KEY,
        vendor_name   VARCHAR2(150) NOT NULL,
        contact_email VARCHAR2(150),
        phone         VARCHAR2(30),
        location      VARCHAR2(200)
    )", 'Create Vendors');
}

if (!table_exists('Products')) {
    exec_sql("CREATE TABLE Products (
        product_id         NUMBER PRIMARY KEY,
        vendor_id          NUMBER,
        name               VARCHAR2(150) NOT NULL,
        category           VARCHAR2(100),
        price              NUMBER(10,2) DEFAULT 0 NOT NULL,
        sustainability_tag VARCHAR2(100),
        stock_status       VARCHAR2(20) DEFAULT 'In Stock',
        CONSTRAINT fk_prod_vendor FOREIGN KEY (vendor_id) REFERENCES Vendors(vendor_id),
        CONSTRAINT ck_stock CHECK (stock_status IN ('In Stock','Low Stock','Out of Stock'))
    )", 'Create Products');
}

if (!table_exists('Orders')) {
    exec_sql("CREATE TABLE Orders (
        order_id         NUMBER PRIMARY KEY,
        customer_id      NUMBER,
        order_date       DATE DEFAULT SYSDATE,
        total_amount     NUMBER(10,2) DEFAULT 0,
        status           VARCHAR2(20) DEFAULT 'pending',
        discount_applied NUMBER(10,2) DEFAULT 0,
        CONSTRAINT fk_ord_cust FOREIGN KEY (customer_id) REFERENCES Customers(customer_id),
        CONSTRAINT ck_order_status CHECK (status IN ('pending','processing','shipped','delivered','cancelled'))
    )", 'Create Orders');
}

if (!table_exists('Delivery')) {
    exec_sql("CREATE TABLE Delivery (
        delivery_id     NUMBER PRIMARY KEY,
        order_id        NUMBER,
        delivery_method VARCHAR2(100),
        delivery_status VARCHAR2(20) DEFAULT 'scheduled',
        estimated_time  DATE,
        CONSTRAINT fk_del_order FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
        CONSTRAINT ck_del_status CHECK (delivery_status IN ('scheduled','in-transit','delivered','failed','ready'))
    )", 'Create Delivery');
}

/* ── BI TRIGGERS (auto-increment via sequences) ─────── */
$bi_triggers = [
    'trg_customers_bi'     => ['Customers',    'customer_id',    'seq_customers'],
    'trg_vendors_bi'       => ['Vendors',       'vendor_id',      'seq_vendors'],
    'trg_products_bi'      => ['Products',      'product_id',     'seq_products'],
    'trg_orders_bi'        => ['Orders',        'order_id',       'seq_orders'],
    'trg_delivery_bi'      => ['Delivery',      'delivery_id',    'seq_delivery'],
];
foreach ($bi_triggers as $trg => [$tbl, $col, $seq]) {
    drop_trigger($trg);
    exec_sql("CREATE OR REPLACE TRIGGER {$trg}
BEFORE INSERT ON {$tbl}
FOR EACH ROW
BEGIN
    IF :NEW.{$col} IS NULL THEN
        SELECT {$seq}.NEXTVAL INTO :NEW.{$col} FROM DUAL;
    END IF;
END;", "BI trigger {$trg}");
}

/* ── BUSINESS TRIGGERS ──────────────────────────────── */

// Trigger 3: loyalty points AFTER UPDATE on Orders
drop_trigger('trg_loyalty_points');
exec_sql("CREATE OR REPLACE TRIGGER trg_loyalty_points
AFTER UPDATE OF status ON Orders
FOR EACH ROW
BEGIN
    IF :NEW.status = 'delivered' AND :OLD.status != 'delivered' THEN
        UPDATE Customers SET loyalty_points = loyalty_points + FLOOR(:NEW.total_amount)
        WHERE customer_id = :NEW.customer_id;
    END IF;
END;", 'Trigger: loyalty points');

// Trigger 2: check price >= 0
drop_trigger('trg_check_price');
exec_sql("CREATE OR REPLACE TRIGGER trg_check_price
BEFORE INSERT OR UPDATE OF price ON Products
FOR EACH ROW
BEGIN
    IF :NEW.price < 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Product price cannot be negative');
    END IF;
END;", 'Trigger: check price');

// Trigger 3: check discount >= 0
drop_trigger('trg_check_discount');
exec_sql("CREATE OR REPLACE TRIGGER trg_check_discount
BEFORE INSERT OR UPDATE OF discount_applied ON Orders
FOR EACH ROW
BEGIN
    IF :NEW.discount_applied < 0 THEN
        RAISE_APPLICATION_ERROR(-20002, 'Discount applied cannot be negative');
    END IF;
END;", 'Trigger: check discount');

/* ── VIEW ───────────────────────────────────────────── */
exec_sql("CREATE OR REPLACE VIEW CustomerOrderSummary AS
SELECT c.customer_id, c.name, c.email,
       COUNT(o.order_id) AS orders_count,
       NVL(SUM(o.total_amount),0) AS total_spent
FROM Customers c
LEFT JOIN Orders o ON c.customer_id = o.customer_id
GROUP BY c.customer_id, c.name, c.email", 'View: CustomerOrderSummary');

/* ── STORED PROCEDURE ───────────────────────────────── */
drop_procedure('get_product_details');
exec_sql("CREATE OR REPLACE PROCEDURE get_product_details(
    p_product_name  IN  Products.name%TYPE,
    p_vendor_name   OUT Vendors.vendor_name%TYPE,
    p_category      OUT Products.category%TYPE
) AS
BEGIN
    SELECT v.vendor_name, p.category
    INTO   p_vendor_name, p_category
    FROM   Products p
    LEFT JOIN Vendors v ON p.vendor_id = v.vendor_id
    WHERE  p.name = p_product_name
    AND    ROWNUM = 1;
EXCEPTION WHEN NO_DATA_FOUND THEN
    p_vendor_name := 'Unknown';
    p_category    := 'Unknown';
END;", 'Procedure: get_product_details');

/* ── STORED PROCEDURE 2: update_order_status ─────────── */
// Drop if exists (USER_PROCEDURES covers both procedures and functions)
$chk = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_OBJECTS WHERE OBJECT_NAME='UPDATE_ORDER_STATUS' AND OBJECT_TYPE='PROCEDURE'");
oci_execute($chk, OCI_DEFAULT); $r = oci_fetch_assoc($chk); oci_free_statement($chk);
if ((int)$r['CNT'] > 0) exec_sql('DROP PROCEDURE UPDATE_ORDER_STATUS', 'Drop procedure update_order_status');
exec_sql("CREATE OR REPLACE PROCEDURE update_order_status(
    p_order_id IN Orders.order_id%TYPE,
    p_new_status IN Orders.status%TYPE
) AS
    v_current_status Orders.status%TYPE;
    v_count NUMBER;
BEGIN
    -- Check order exists
    SELECT COUNT(*) INTO v_count FROM Orders WHERE order_id = p_order_id;
    IF v_count = 0 THEN
        RAISE_APPLICATION_ERROR(-20010, 'Order ID ' || p_order_id || ' does not exist.');
    END IF;
    -- Get current status
    SELECT status INTO v_current_status FROM Orders WHERE order_id = p_order_id;
    -- Business rule: cannot un-cancel an order
    IF v_current_status = 'cancelled' AND p_new_status != 'cancelled' THEN
        RAISE_APPLICATION_ERROR(-20011, 'Cannot reopen a cancelled order.');
    END IF;
    -- Apply update
    UPDATE Orders SET status = p_new_status WHERE order_id = p_order_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;", 'Procedure: update_order_status');

/* ── STORED FUNCTION: get_customer_tier_label ────────── */
$chk2 = oci_parse($conn, "SELECT COUNT(*) cnt FROM USER_OBJECTS WHERE OBJECT_NAME='GET_CUSTOMER_TIER_LABEL' AND OBJECT_TYPE='FUNCTION'");
oci_execute($chk2, OCI_DEFAULT); $r2 = oci_fetch_assoc($chk2); oci_free_statement($chk2);
if ((int)$r2['CNT'] > 0) exec_sql('DROP FUNCTION GET_CUSTOMER_TIER_LABEL', 'Drop function get_customer_tier_label');
exec_sql("CREATE OR REPLACE FUNCTION get_customer_tier_label(
    p_points IN NUMBER
) RETURN VARCHAR2 AS
    v_label VARCHAR2(50);
BEGIN
    IF p_points >= 500 THEN
        v_label := 'Platinum Member';
    ELSIF p_points >= 200 THEN
        v_label := 'Gold Member';
    ELSIF p_points >= 50 THEN
        v_label := 'Silver Member';
    ELSE
        v_label := 'Bronze Member';
    END IF;
    RETURN v_label;
END;", 'Function: get_customer_tier_label');

/* ── SAMPLE DATA (insert only if empty) ─────────────── */
$count_stmt = oci_parse($conn, 'SELECT COUNT(*) cnt FROM Customers');
oci_execute($count_stmt, OCI_DEFAULT);
$crow = oci_fetch_assoc($count_stmt);
oci_free_statement($count_stmt);

if ((int)$crow['CNT'] === 0) {
    // Customers
    $customers = [
        ['Alice Green','alice@example.com','111-222-3333','123 Green St','organic'],
        ['Bob Brown','bob@example.com','222-333-4444','456 Market Ave','vegan'],
        ['Carol White','carol@example.com','333-444-5555','789 Orchard Rd','gluten-free'],
        ['David Black','david@example.com','444-555-6666','321 Farm Blvd','low-carb'],
        ['Eve Stone','eve@example.com','555-666-7777','654 Hill Ln','vegetarian'],
        ['Frank Ocean','frank@example.com','666-777-8888','987 Coast Dr','organic'],
    ];
    foreach ($customers as $c) {
        exec_sql("INSERT INTO Customers (name,email,phone,address,preferences) VALUES ('{$c[0]}','{$c[1]}','{$c[2]}','{$c[3]}','{$c[4]}')", "Insert customer {$c[0]}");
    }
    oci_commit($conn); // commit customers so FK on Orders works

    // Vendors
    $vendors = [
        ['Fresh Farms','fresh@example.com','333-444-5555','North Town'],
        ['Eco Produce','eco@example.com','444-555-6666','West Side'],
        ['Green Valley','valley@example.com','555-444-3333','East Grove'],
        ['Urban Harvest','urban@example.com','777-888-9999','Downtown'],
        ['Local Organics','local@example.com','888-999-0000','South Market'],
        ['Sunshine Goods','sun@example.com','999-000-1111','Riverside'],
    ];
    foreach ($vendors as $v) {
        exec_sql("INSERT INTO Vendors (vendor_name,contact_email,phone,location) VALUES ('{$v[0]}','{$v[1]}','{$v[2]}','{$v[3]}')", "Insert vendor {$v[0]}");
    }
    oci_commit($conn); // commit vendors so FK on Products works

    // Products (vendor_ids will be 1-6 from sequences)
    $products = [
        [1,'Organic Apple','Fruits',0.99,'organic','In Stock'],
        [1,'Banana','Fruits',0.59,'fair-trade','In Stock'],
        [2,'Almond Milk','Dairy Alternatives',3.49,'vegan','In Stock'],
        [3,'Kale','Vegetables',2.50,'organic','Low Stock'],
        [4,'Brown Rice','Grains',4.00,'whole-grain','In Stock'],
        [5,'Quinoa','Grains',6.00,'superfood','In Stock'],
    ];
    foreach ($products as $p) {
        exec_sql("INSERT INTO Products (vendor_id,name,category,price,sustainability_tag,stock_status) VALUES ({$p[0]},'{$p[1]}','{$p[2]}',{$p[3]},'{$p[4]}','{$p[5]}')", "Insert product {$p[1]}");
    }
    oci_commit($conn); // commit products so FK on Order_Details works

    // Orders
    $sample_orders = [
        [1, 47.50],
        [2, 3.49],
        [3, 30.59],
        [4, 16.95],
        [5, 5.90],
        [1, 2.97],
    ];
    foreach ($sample_orders as $idx => [$c_id, $tot]) {
        $ord_num = $idx + 1;
        exec_sql("INSERT INTO Orders (customer_id,order_date,total_amount,status,discount_applied) VALUES ({$c_id},SYSDATE,{$tot},'pending',0)", "Insert order {$ord_num}");
    }

    // Delivery
    $deliveries = [
        [1,'Standard','scheduled',2],
        [2,'Express','scheduled',1],
        [3,'Standard','scheduled',3],
        [4,'Pickup','ready',0],
        [5,'Express','scheduled',2],
        [6,'Standard','scheduled',3],
    ];
    foreach ($deliveries as $d) {
        exec_sql("INSERT INTO Delivery (order_id,delivery_method,delivery_status,estimated_time) VALUES ({$d[0]},'{$d[1]}','{$d[2]}',SYSDATE+{$d[3]})", "Insert delivery");
    }

    // Mark some orders as delivered (triggers loyalty points)
    foreach ([1,3,4,6] as $oid) {
        exec_sql("UPDATE Orders SET status='delivered' WHERE order_id={$oid}", "Deliver order {$oid}");
    }

    oci_commit($conn);
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GrocerCart – DB Init</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen p-8">
<div class="max-w-3xl mx-auto">
  <div class="bg-gray-800 rounded-2xl p-8 shadow-2xl">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
        <i class="fas fa-database text-white text-xl"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-white">GrocerCart – Oracle DB Init</h1>
        <p class="text-gray-400 text-sm">Oracle XE 11g · <?= OCI_USER ?>@<?= OCI_CONNSTR ?></p>
      </div>
    </div>

    <div class="space-y-2 mb-8 max-h-96 overflow-y-auto">
      <?php foreach ($log as $entry): ?>
        <div class="flex items-start gap-3 p-3 <?= $entry['ok'] ? 'bg-green-900/30 border border-green-700/40' : 'bg-red-900/30 border border-red-700/40' ?> rounded-lg">
          <i class="fas fa-<?= $entry['ok'] ? 'check-circle text-green-400' : 'times-circle text-red-400' ?> mt-0.5"></i>
          <div>
            <span class="text-<?= $entry['ok'] ? 'green' : 'red' ?>-300 text-sm font-medium"><?= htmlspecialchars($entry['label']) ?></span>
            <?php if ($entry['msg']): ?><p class="text-red-400 text-xs mt-1 font-mono"><?= htmlspecialchars($entry['msg']) ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="bg-green-900/40 border border-green-700/50 rounded-xl p-4">
        <h3 class="text-green-400 font-bold mb-2"><i class="fas fa-bolt mr-2"></i>Triggers Created</h3>
        <ul class="text-green-300 text-xs space-y-1">
          <li>• trg_customers_bi / vendors_bi / products_bi</li>
          <li>• trg_orders_bi / order_details_bi / delivery_bi</li>
          <li>• trg_calc_subtotal (BEFORE INSERT)</li>
          <li>• trg_update_order_total (AFTER INSERT)</li>
          <li>• trg_loyalty_points (AFTER UPDATE)</li>
          <li>• trg_check_price (BEFORE INSERT/UPDATE)</li>
        </ul>
      </div>
      <div class="bg-blue-900/40 border border-blue-700/50 rounded-xl p-4">
        <h3 class="text-blue-400 font-bold mb-2"><i class="fas fa-layer-group mr-2"></i>Objects Created</h3>
        <ul class="text-blue-300 text-xs space-y-1">
          <li>&bull; 6 Tables (Customers, Vendors, Products…)</li>
          <li>&bull; 6 Sequences (seq_customers…)</li>
          <li>&bull; 1 View (CustomerOrderSummary)</li>
          <li>&bull; 2 Stored Procedures (get_product_details, update_order_status)</li>
          <li>&bull; 1 Stored Function (get_customer_tier_label)</li>
          <li>&bull; CHECK constraints on status ENUMs</li>
          <li>&bull; FOREIGN KEY constraints</li>
        </ul>
      </div>
    </div>

    <div class="flex gap-3">
      <a href="index.php" class="flex-1 text-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-semibold transition">
        <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
      </a>
      <a href="init_db.php?reset=1"
         onclick="return confirm('Drop ALL tables, sequences, triggers, views? This is DESTRUCTIVE!')"
         class="px-6 bg-red-700 hover:bg-red-800 text-white py-3 rounded-xl font-semibold transition">
        <i class="fas fa-trash mr-2"></i>Reset DB
      </a>
    </div>
  </div>
</div>
</body>
</html>
