<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$c  = ['name'=>'','email'=>'','phone'=>'','address'=>'','preferences'=>''];
if ($id) {
    $c = ociOne('SELECT * FROM Customers WHERE customer_id = :id', [':id'=>$id]) ?? $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null;
    $phone = trim($_POST['phone'] ?? '') ?: null;
    $addr  = trim($_POST['address'] ?? '') ?: null;
    $pref  = trim($_POST['preferences'] ?? '') ?: null;

    if (!empty($_POST['customer_id'])) {
        ociExec(
            'UPDATE Customers SET name=:n,email=:e,phone=:p,address=:a,preferences=:pref WHERE customer_id=:id',
            [':n'=>$name,':e'=>$email,':p'=>$phone,':a'=>$addr,':pref'=>$pref,':id'=>(int)$_POST['customer_id']]
        );
    } else {
        ociExec(
            'INSERT INTO Customers (name,email,phone,address,preferences) VALUES (:n,:e,:p,:a,:pref)',
            [':n'=>$name,':e'=>$email,':p'=>$phone,':a'=>$addr,':pref'=>$pref]
        );
    }
    header('Location: customers_list.php'); exit;
}

echo getHeader(($id ? 'Edit' : 'Add') . ' Customer'); echo getNav();
?>
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-user-plus text-blue-600"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-gray-800"><?= $id ? 'Edit' : 'Add' ?> Customer</h1>
        <p class="text-gray-500 text-sm"><?= $id ? "Editing customer #$id" : 'Create a new customer record' ?></p>
      </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-6 font-mono text-xs text-blue-800">
      <?= $id
        ? "UPDATE Customers SET name=:n,email=:e,phone=:p,address=:a,preferences=:pref WHERE customer_id=$id"
        : "INSERT INTO Customers (name,email,phone,address,preferences) VALUES (:n,:e,:p,:a,:pref)" ?>
    </div>

    <form method="post" class="space-y-5">
      <?php if ($id): ?><input type="hidden" name="customer_id" value="<?= $id ?>"><?php endif; ?>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" required value="<?= htmlspecialchars($c['name']??'') ?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($c['email']??'') ?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($c['phone']??'') ?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
        <textarea name="address" rows="3"
                  class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($c['address']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Preferences</label>
        <input type="text" name="preferences" value="<?= htmlspecialchars($c['preferences']??'') ?>"
               placeholder="organic, vegan, gluten-free…"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">
          <i class="fas fa-save mr-2"></i>Save Customer
        </button>
        <a href="customers_list.php"
           class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">
          Cancel
        </a>
      </div>
    </form>
  </div>
</div>
<?php echo getFooter(); ?>
