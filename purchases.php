<?php
require 'config.php'; roles(['admin','procurement']); verify_csrf(); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $supplier=positive_int($_POST['supplier']??0);$product=positive_int($_POST['product']??0);$qty=positive_int($_POST['qty']??0);
 $cost=filter_var($_POST['cost']??null,FILTER_VALIDATE_FLOAT);
 if(!$supplier||!$product||!$qty||$cost===false||$cost<0)$msg='Enter a valid supplier, product, quantity and unit cost.';
 else try{
  $pdo->beginTransaction();
  $check=$pdo->prepare('SELECT product_id FROM products WHERE product_id=?');$check->execute([$product]);if(!$check->fetch())throw new Exception();
  $s=$pdo->prepare('INSERT INTO purchases(supplier_id,user_id,total_amount) VALUES(?,?,?)');$s->execute([$supplier,$_SESSION['user_id'],$qty*$cost]);$purchase=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO purchase_items(purchase_id,product_id,quantity,unit_cost) VALUES(?,?,?,?)')->execute([$purchase,$product,$qty,$cost]);
  $pdo->prepare('UPDATE products SET quantity_in_stock=quantity_in_stock+? WHERE product_id=?')->execute([$qty,$product]);
  $pdo->prepare("INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,notes) VALUES(?,?,'IN',?,'Purchase received')")->execute([$product,$_SESSION['user_id'],$qty]);
  $pdo->commit();$msg='Purchase #'.$purchase.' saved. Stock increased by '.$qty.'.';
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$msg='Purchase could not be saved. Confirm the supplier and product exist.';}
}
$suppliers=$pdo->query('SELECT supplier_id,supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll();
$products=$pdo->query('SELECT product_id,product_name FROM products ORDER BY product_name')->fetchAll();
$history=$pdo->query('SELECT p.purchase_id,p.purchase_date,p.total_amount,s.supplier_name,u.username FROM purchases p JOIN suppliers s ON s.supplier_id=p.supplier_id JOIN users u ON u.user_id=p.user_id ORDER BY p.purchase_id DESC LIMIT 50')->fetchAll();
page_header('Purchase Recording');if($msg)flash($msg,'notice');
echo '<form class="panel" method="post"><input type="hidden" name="csrf_token" value="'.h(csrf_token()).'"><h3>Record stock purchase</h3><label>Supplier<select name="supplier" required><option value="">Choose supplier</option>';
foreach($suppliers as $s)echo '<option value="'.h($s['supplier_id']).'">'.h($s['supplier_name']).'</option>';
echo '</select></label><label>Product<select name="product" required><option value="">Choose product</option>';
foreach($products as $p)echo '<option value="'.h($p['product_id']).'">'.h($p['product_name']).'</option>';
echo '</select></label><div class="formgrid"><label>Quantity received<input type="number" name="qty" min="1" required></label><label>Unit cost (UGX)<input type="number" name="cost" min="0" step="0.01" required></label></div><button>Save purchase</button></form><h3>Recent purchases</h3><div class="table-wrap"><table><tr><th>Purchase #</th><th>Date</th><th>Supplier</th><th>Recorded by</th><th>Total (UGX)</th></tr>';
foreach($history as $r)echo '<tr><td>'.h($r['purchase_id']).'</td><td>'.h($r['purchase_date']).'</td><td>'.h($r['supplier_name']).'</td><td>'.h($r['username']).'</td><td>'.number_format((float)$r['total_amount'],2).'</td></tr>';
echo '</table></div>';page_footer();