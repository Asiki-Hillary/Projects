<?php
require_once 'config.php'; roles(['admin','procurement']); verify_csrf();

function done(string $m, string $t='success'): void { $_SESSION['flash']=[$m,$t]; header('Location: suppliers.php'); exit; }

$msg='';
$editId=positive_int($_GET['edit'] ?? 0);

if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action'] ?? 'create';
 $id=positive_int($_POST['supplier_id'] ?? 0);

 if($action==='delete'){
  if(!$id) $msg='Invalid supplier selected.';
  else {
   $c=$pdo->prepare('SELECT COUNT(*) FROM purchases WHERE supplier_id=?'); $c->execute([$id]);
   if((int)$c->fetchColumn()>0) $msg='This supplier has purchase records and cannot be deleted.';
   else { $d=$pdo->prepare('DELETE FROM suppliers WHERE supplier_id=?'); $d->execute([$id]);
          if($d->rowCount()) done('Supplier deleted successfully.'); else $msg='Supplier not found.'; }
  }
 } else {
  $name=trim($_POST['name']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');
  if($name==='') $msg='Supplier name is required.';
  elseif($action==='update'){
   if(!$id) $msg='Invalid supplier selected.';
   else { $s=$pdo->prepare('UPDATE suppliers SET supplier_name=?,phone=?,address=? WHERE supplier_id=?'); $s->execute([$name,$phone,$address,$id]); done('Supplier updated successfully.'); }
   $editId=$id;
  } else {
   $s=$pdo->prepare('INSERT INTO suppliers(supplier_name,phone,address) VALUES(?,?,?)'); $s->execute([$name,$phone,$address]); done('Supplier saved successfully.');
  }
 }
}

$editing=null;
if($editId){ $s=$pdo->prepare('SELECT * FROM suppliers WHERE supplier_id=?'); $s->execute([$editId]); $editing=$s->fetch() ?: null; }
$rows=$pdo->query('SELECT * FROM suppliers ORDER BY supplier_name')->fetchAll();

page_header('Supplier Management'); flash(); if($msg) flash($msg,'error');
echo '<form class="panel" method="post"><input type="hidden" name="csrf_token" value="'.h(csrf_token()).'">';
if($editing) echo '<input type="hidden" name="action" value="update"><input type="hidden" name="supplier_id" value="'.h($editing['supplier_id']).'"><h3>Edit supplier</h3>';
else echo '<input type="hidden" name="action" value="create"><h3>Register supplier</h3>';
echo '<label>Supplier name<input name="name" required maxlength="150" value="'.h($_POST['name'] ?? ($editing['supplier_name'] ?? '')).'"></label>'
    .'<label>Phone<input name="phone" maxlength="30" value="'.h($_POST['phone'] ?? ($editing['phone'] ?? '')).'"></label>'
    .'<label>Address<input name="address" maxlength="255" value="'.h($_POST['address'] ?? ($editing['address'] ?? '')).'"></label>';
echo $editing ? '<button>Save changes</button> <a class="btn secondary" href="suppliers.php">Cancel</a>' : '<button>Save supplier</button>';
echo '</form><h3>Registered suppliers</h3><div class="table-wrap"><table><tr><th>Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr>';
foreach($rows as $r) echo '<tr><td>'.h($r['supplier_name']).'</td><td>'.h($r['phone']).'</td><td>'.h($r['address']).'</td>'
 .'<td class="actions-cell"><a class="btn" href="suppliers.php?edit='.h($r['supplier_id']).'">Edit</a> '
 .'<form method="post" class="inline" onsubmit="return confirm(\'Delete this supplier?\');"><input type="hidden" name="csrf_token" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="supplier_id" value="'.h($r['supplier_id']).'"><button class="danger">Delete</button></form></td></tr>';
if(!$rows) echo '<tr><td colspan="4" class="muted">No suppliers registered yet.</td></tr>';
echo '</table></div>'; page_footer();
