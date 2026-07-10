<?php
require_once __DIR__ . '/db.php';
$vendors = ociQuery('SELECT vendor_id, vendor_name FROM Vendors ORDER BY vendor_name');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p  = ['vendor_id'=>'','name'=>'','category'=>'','price'=>'','sustainability_tag'=>'','stock_status'=>'In Stock'];
if ($id) $p = ociOne('SELECT * FROM Products WHERE product_id=:id',[':id'=>$id]) ?? $p;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $vid=$_POST['vendor_id']??null; $nm=$_POST['name']??'';
    $cat=$_POST['category']??''; $pr=(float)($_POST['price']??0);
    $tag=$_POST['sustainability_tag']??''; $ss=$_POST['stock_status']??'In Stock';
    if (!empty($_POST['product_id'])) {
        ociExec('UPDATE Products SET vendor_id=:vid,name=:n,category=:c,price=:pr,sustainability_tag=:tag,stock_status=:ss WHERE product_id=:id',
                [':vid'=>$vid,':n'=>$nm,':c'=>$cat,':pr'=>$pr,':tag'=>$tag,':ss'=>$ss,':id'=>(int)$_POST['product_id']]);
    } else {
        ociExec('INSERT INTO Products (vendor_id,name,category,price,sustainability_tag,stock_status) VALUES (:vid,:n,:c,:pr,:tag,:ss)',
                [':vid'=>$vid,':n'=>$nm,':c'=>$cat,':pr'=>$pr,':tag'=>$tag,':ss'=>$ss]);
    }
    header('Location: products_list.php'); exit;
}
echo getHeader(($id?'Edit':'Add').' Product'); echo getNav();
?>
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
        <i class="fas fa-box text-green-600"></i>
      </div>
      <h1 class="text-2xl font-bold text-gray-800"><?= $id?'Edit':'Add' ?> Product</h1>
    </div>
    <form method="post" class="space-y-5">
      <?php if($id): ?><input type="hidden" name="product_id" value="<?=$id?>"><?php endif; ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor</label>
        <select name="vendor_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
          <option value="">-- Select Vendor --</option>
          <?php foreach($vendors as $v): ?>
          <option value="<?=$v['vendor_id']?>" <?=($p['vendor_id']==$v['vendor_id'])?'selected':''?>><?=htmlspecialchars($v['vendor_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" required value="<?=htmlspecialchars($p['name']??'')?>"
               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
          <input type="text" name="category" value="<?=htmlspecialchars($p['category']??'')?>"
                 class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
          <input type="number" step="0.01" min="0" name="price" required value="<?=htmlspecialchars($p['price']??0)?>"
                 class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Sustainability Tag</label>
          <input type="text" name="sustainability_tag" value="<?=htmlspecialchars($p['sustainability_tag']??'')?>"
                 placeholder="organic, vegan…"
                 class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Stock Status</label>
          <select name="stock_status" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500">
            <?php foreach(['In Stock','Low Stock','Out of Stock'] as $s): ?>
            <option <?=($p['stock_status']==$s)?'selected':''?>><?=$s?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-semibold transition">
          <i class="fas fa-save mr-2"></i>Save Product
        </button>
        <a href="products_list.php" class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php echo getFooter(); ?>
