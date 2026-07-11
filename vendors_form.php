<?php
require_once __DIR__ . '/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$v  = ['vendor_name'=>'','contact_email'=>'','phone'=>'','location'=>''];
if ($id) $v = ociOne('SELECT * FROM Vendors WHERE vendor_id=:id',[':id'=>$id]) ?? $v;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $n=$_POST['vendor_name']??''; $e=$_POST['contact_email']??'';
    $p=$_POST['phone']??''; $l=$_POST['location']??'';
    if (!empty($_POST['vendor_id'])) {
        ociExec('UPDATE Vendors SET vendor_name=:n,contact_email=:e,phone=:p,location=:l WHERE vendor_id=:id',
                [':n'=>$n,':e'=>$e,':p'=>$p,':l'=>$l,':id'=>(int)$_POST['vendor_id']]);
    } else {
        ociExec('INSERT INTO Vendors (vendor_name,contact_email,phone,location) VALUES (:n,:e,:p,:l)',
                [':n'=>$n,':e'=>$e,':p'=>$p,':l'=>$l]);
    }
    header('Location: vendors_list.php'); exit;
}
echo getHeader(($id?'Edit':'Add').' Vendor'); echo getNav();
?>
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-truck text-purple-600"></i>
      </div>
      <h1 class="text-2xl font-bold text-gray-800"><?= $id?'Edit':'Add' ?> Vendor</h1>
    </div>
    <form method="post" class="space-y-5">
      <?php if($id): ?><input type="hidden" name="vendor_id" value="<?=$id?>"><?php endif; ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor Name <span class="text-red-500">*</span></label>
        <input type="text" name="vendor_name" required value="<?=htmlspecialchars($v['vendor_name']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Email</label>
        <input type="email" name="contact_email" value="<?=htmlspecialchars($v['contact_email']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" value="<?=htmlspecialchars($v['phone']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Location</label>
        <input type="text" name="location" value="<?=htmlspecialchars($v['location']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-xl font-semibold transition">
          <i class="fas fa-save mr-2"></i>Save Vendor
        </button>
        <a href="vendors_list.php" class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php echo getFooter(); ?>
