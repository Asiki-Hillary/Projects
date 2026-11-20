<?php
require_once 'config.php'; roles(['admin','storekeeper']); verify_csrf();

/* Save a message and redirect so a page refresh never re-submits the form */
function done(string $m, string $t='success'): void { $_SESSION['flash']=[$m,$t]; header('Location: products.php'); exit; }

$msg=''; $msgType='error';
$editing=null;
$editId=positive_int($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
 $action=$_POST['action'] ?? 'create';

 /* ---------- DELETE ---------- */
 if ($action==='delete') {
  $id=positive_int($_POST['product_id'] ?? 0);
  if(!$id) $msg='Invalid product selected.';
  else {
   try {
    $pdo->beginTransaction();
    $c=$pdo->prepare('SELECT (SELECT COUNT(*) FROM sale_items WHERE product_id=?) + (SELECT COUNT(*) FROM purchase_items WHERE product_id=?)');
    $c->execute([$id,$id]);
    if((int)$c->fetchColumn()>0) throw new RuntimeException('This product has sales or purchase records and cannot be deleted. Set its stock to 0 or edit it instead.');
    $pdo->prepare('DELETE FROM stock_movements WHERE product_id=?')->execute([$id]);
    $d=$pdo->prepare('DELETE FROM products WHERE product_id=?'); $d->execute([$id]);
    if(!$d->rowCount()) throw new RuntimeException('Product not found.');
    $pdo->commit();
    done('Product deleted successfully.');
   } catch(Throwable $e) {
    if($pdo->inTransaction())$pdo->rollBack();
    $msg=($e instanceof RuntimeException)?$e->getMessage():'Could not delete the product.';
   }
  }
 }
 /* ---------- CREATE / UPDATE ---------- */
 else {
  $id=positive_int($_POST['product_id'] ?? 0);
  $code=trim($_POST['code']??''); $name=trim($_POST['name']??'');
  $price=filter_var($_POST['price']??null,FILTER_VALIDATE_FLOAT);
  $qty=filter_var($_POST['qty']??null,FILTER_VALIDATE_INT);
  $reorder=filter_var($_POST['reorder']??null,FILTER_VALIDATE_INT);
  $cat=($_POST['category_id']??'')===''?null:positive_int($_POST['category_id']);
  if ($code===''||$name===''||$price===false||$price<0||$qty===false||$qty<0||$reorder===false||$reorder<0) {
   $msg='Enter valid product details. Values cannot be negative.';
  } elseif ($action==='update' && !$id) {
   $msg='Invalid product selected.';
  } else {
   try {
    $pdo->beginTransaction();
    if ($action==='update') {
     $s=$pdo->prepare('SELECT quantity_in_stock FROM products WHERE product_id=? FOR UPDATE'); $s->execute([$id]);
     $old=$s->fetchColumn();
     if($old===false) throw new RuntimeException('Product not found.');
     $pdo->prepare('UPDATE products SET category_id=?,product_code=?,product_name=?,unit_price=?,quantity_in_stock=?,reorder_level=? WHERE product_id=?')
         ->execute([$cat,$code,$name,$price,$qty,$reorder,$id]);
     $diff=$qty-(int)$old;
     if($diff!==0) $pdo->prepare("INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,notes) VALUES(?,?,'ADJUSTMENT',?,?)")
         ->execute([$id,$_SESSION['user_id'],abs($diff),'Stock corrected from '.$old.' to '.$qty]);
     $pdo->commit();
     done('Product updated successfully.');
    } else {
     $pdo->prepare('INSERT INTO products(category_id,product_code,product_name,unit_price,quantity_in_stock,reorder_level) VALUES(?,?,?,?,?,?)')
         ->execute([$cat,$code,$name,$price,$qty,$reorder]);
     $newId=(int)$pdo->lastInsertId();
     if($qty>0) $pdo->prepare("INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,notes) VALUES(?,?,'IN',?,'Opening stock')")->execute([$newId,$_SESSION['user_id'],$qty]);
     $pdo->commit();
     done('Product registered successfully.');
    }
   } catch(Throwable $e) {
    if($pdo->inTransaction())$pdo->rollBack();
    $msg=($e instanceof RuntimeException)?$e->getMessage():'Could not save product. Check that the product code is unique.';
   }
   if($action==='update') $editId=$id;
  }
 }
}

/* Load the product being edited (if any) */
if($editId){
 $s=$pdo->prepare('SELECT * FROM products WHERE product_id=?'); $s->execute([$editId]);
 $editing=$s->fetch() ?: null;
}
/* If a failed submit happened, keep what the user typed */
$f=[
 'code'=>$_POST['code'] ?? ($editing['product_code'] ?? ''),
 'name'=>$_POST['name'] ?? ($editing['product_name'] ?? ''),
 'cat'=>$_POST['category_id'] ?? ($editing['category_id'] ?? ''),
 'price'=>$_POST['price'] ?? ($editing['unit_price'] ?? ''),
 'qty'=>$_POST['qty'] ?? ($editing['quantity_in_stock'] ?? ''),
 'reorder'=>$_POST['reorder'] ?? ($editing['reorder_level'] ?? '5'),
];
if($editing && $_SERVER['REQUEST_METHOD']!=='POST'){ $f['price']=number_format((float)$editing['unit_price'],2,'.',''); }

$categories=$pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
$products=$pdo->query('SELECT p.*,c.category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id ORDER BY p.product_name')->fetchAll();

page_header('Products & Stock');
flash(); if($msg) flash($msg,$msgType);

echo '<form class="panel" method="post"><input type="hidden" name="csrf_token" value="'.h(csrf_token()).'">';
if($editing) echo '<input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="'.h($editing['product_id']).'"><h3>Edit product: '.h($editing['product_name']).'</h3>';
else echo '<input type="hidden" name="action" value="create"><h3>Register product</h3>';
echo '<div class="formgrid"><label>Product code<input name="code" required maxlength="50" value="'.h($f['code']).'"></label>'
    .'<label>Product name<input name="name" required maxlength="150" value="'.h($f['name']).'"></label>'
    .'<label>Category<select name="category_id"><option value="">No category</option>';
foreach($categories as $c) echo '<option value="'.h($c['category_id']).'"'.((string)$f['cat']===(string)$c['category_id']?' selected':'').'>'.h($c['category_name']).'</option>';
echo '</select></label><label>Selling price (UGX)<input type="number" name="price" min="0" step="0.01" required value="'.h($f['price']).'"></label>'
    .'<label>'.($editing?'Quantity in stock':'Opening quantity').'<input type="number" name="qty" min="0" step="1" required value="'.h($f['qty']).'"></label>'
    .'<label>Reorder level<input type="number" name="reorder" min="0" required value="'.h($f['reorder']).'"></label></div>';
if($editing) echo '<p class="muted">Changing the stock quantity records a stock adjustment in the movement history.</p><button>Save changes</button> <a class="btn secondary" href="products.php">Cancel</a>';
else echo '<button>Add product</button>';
echo '</form>';

echo '<h3>Product register</h3><div class="table-wrap"><table><tr><th>Code</th><th>Product</th><th>Category</th><th>Price (UGX)</th><th>Stock</th><th>Reorder</th><th>Status</th><th>Actions</th></tr>';
foreach($products as $p){
 $status=$p['quantity_in_stock']<=$p['reorder_level']?'Low stock':'Available';
 echo '<tr><td>'.h($p['product_code']).'</td><td>'.h($p['product_name']).'</td><td>'.h($p['category_name']??'—').'</td><td>'.number_format((float)$p['unit_price'],2).'</td><td>'.h($p['quantity_in_stock']).'</td><td>'.h($p['reorder_level']).'</td><td>'.h($status).'</td>'
  .'<td class="actions-cell"><a class="btn" href="products.php?edit='.h($p['product_id']).'">Edit</a> '
  .'<form method="post" class="inline" onsubmit="return confirm(\'Delete this product? This cannot be undone.\');">'
  .'<input type="hidden" name="csrf_token" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="'.h($p['product_id']).'"><button class="danger">Delete</button></form></td></tr>';
}
if(!$products) echo '<tr><td colspan="8" class="muted">No products registered yet.</td></tr>';
echo '</table></div>'; page_footer();
