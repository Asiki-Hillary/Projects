<?php
require 'config.php'; roles(['admin','sales']); verify_csrf(); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $product=positive_int($_POST['product']??0);$qty=positive_int($_POST['qty']??0);$method=$_POST['method']??'Cash';
 if(!$product||!$qty||!in_array($method,['Cash','Mobile Money','Bank'],true))$msg='Enter valid sale details.';
 else try{
  $pdo->beginTransaction();
  $s=$pdo->prepare('SELECT * FROM products WHERE product_id=? FOR UPDATE');$s->execute([$product]);$p=$s->fetch();
  if(!$p||$p['quantity_in_stock']<$qty)throw new Exception('Insufficient stock. Available quantity: '.($p['quantity_in_stock']??0));
  $total=$qty*(float)$p['unit_price'];
  $s=$pdo->prepare('INSERT INTO sales(user_id,total_amount,payment_method) VALUES(?,?,?)');$s->execute([$_SESSION['user_id'],$total,$method]);$sale=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO sale_items(sale_id,product_id,quantity,unit_price) VALUES(?,?,?,?)')->execute([$sale,$product,$qty,$p['unit_price']]);
  $pdo->prepare('UPDATE products SET quantity_in_stock=quantity_in_stock-? WHERE product_id=?')->execute([$qty,$product]);
  $pdo->prepare("INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,notes) VALUES(?,?,'OUT',?,'Customer sale')")->execute([$product,$_SESSION['user_id'],$qty]);
  $pdo->commit();$msg='Sale completed. Receipt number: '.$sale.' | Total: UGX '.number_format($total,2);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$msg=$e->getMessage()?:'Sale could not be completed.';}
}
$products=$pdo->query('SELECT product_id,product_name,product_code,unit_price,quantity_in_stock FROM products WHERE quantity_in_stock>0 ORDER BY product_name')->fetchAll();
$history=$pdo->query('SELECT s.sale_id,s.sale_date,s.total_amount,s.payment_method,u.username FROM sales s JOIN users u ON u.user_id=s.user_id ORDER BY s.sale_id DESC LIMIT 50')->fetchAll();
page_header('Sales Recording');if($msg)flash($msg,'notice');
echo '<form class="panel" method="post"><input type="hidden" name="csrf_token" value="'.h(csrf_token()).'"><h3>Record customer sale</h3><label>Product<select name="product" required><option value="">Choose product</option>';
foreach($products as $p)echo '<option value="'.h($p['product_id']).'">'.h($p['product_code'].' - '.$p['product_name'].' | Stock: '.$p['quantity_in_stock'].' | UGX '.number_format((float)$p['unit_price'],2)).'</option>';
echo '</select></label><div class="formgrid"><label>Quantity sold<input type="number" name="qty" min="1" required></label><label>Payment method<select name="method"><option>Cash</option><option>Mobile Money</option><option>Bank</option></select></label></div><button>Complete sale</button></form><h3>Recent sales</h3><div class="table-wrap"><table><tr><th>Receipt #</th><th>Date</th><th>Attendant</th><th>Payment</th><th>Total (UGX)</th></tr>';
foreach($history as $r)echo '<tr><td>'.h($r['sale_id']).'</td><td>'.h($r['sale_date']).'</td><td>'.h($r['username']).'</td><td>'.h($r['payment_method']).'</td><td>'.number_format((float)$r['total_amount'],2).'</td></tr>';
echo '</table></div>';page_footer();
