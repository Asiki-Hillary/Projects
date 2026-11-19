<?php
require 'config.php'; login_required();
$role=$_SESSION['role'];
$counts=[];
if (in_array($role,['admin','manager','storekeeper'],true)) {
 $counts['Products']=$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
 $counts['Units in stock']=$pdo->query('SELECT COALESCE(SUM(quantity_in_stock),0) FROM products')->fetchColumn();
 $counts['Low-stock products']=$pdo->query('SELECT COUNT(*) FROM products WHERE quantity_in_stock<=reorder_level')->fetchColumn();
}
if (in_array($role,['admin','manager','sales'],true)) {
 $counts['Sales today']='UGX '.number_format((float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date)=CURDATE()')->fetchColumn(),2);
}
if (in_array($role,['admin','manager','procurement'],true)) {
 $counts['Suppliers']=$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
 $counts['Purchases this month']='UGX '.number_format((float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM purchases WHERE YEAR(purchase_date)=YEAR(CURDATE()) AND MONTH(purchase_date)=MONTH(CURDATE())')->fetchColumn(),2);
}
$low=[];
if (in_array($role,['admin','manager','storekeeper'],true)) $low=$pdo->query('SELECT product_code,product_name,quantity_in_stock,reorder_level FROM products WHERE quantity_in_stock<=reorder_level ORDER BY quantity_in_stock')->fetchAll();
page_header('Dashboard');
echo '<section class="welcome"><h3>Welcome, '.h($_SESSION['username']).'</h3><p>You are signed in as <strong>'.h(ucfirst($role)).'</strong>. The menu shows only the modules assigned to your role.</p></section><div class="cards">';
foreach($counts as $label=>$value) echo '<section class="panel metric"><h2>'.h($value).'</h2>'.h($label).'</section>';
echo '</div>';
if ($low) { echo '<h3>Low-stock alerts</h3><div class="table-wrap"><table><tr><th>Code</th><th>Product</th><th>Available</th><th>Reorder level</th></tr>';
foreach($low as $r) echo '<tr><td>'.h($r['product_code']).'</td><td>'.h($r['product_name']).'</td><td>'.h($r['quantity_in_stock']).'</td><td>'.h($r['reorder_level']).'</td></tr>';
echo '</table></div>'; }
$messages=['admin'=>'You have full access to system administration and all business modules.','manager'=>'You can view management reports and business performance.','sales'=>'You can record sales. Each completed sale automatically reduces stock.','storekeeper'=>'You can register products and monitor stock levels.','procurement'=>'You can register suppliers and record purchases. Purchases automatically increase stock.'];
echo '<p class="notice">'.h($messages[$role] ?? '').'</p>';
page_footer();
