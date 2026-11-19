-- Optional demo data. Import AFTER database.sql and after running setup_admin.php.
USE hardware_inventory_db;
INSERT INTO suppliers(supplier_name,phone,address) VALUES
('Hima Cement Distributors','0700111222','Kampala'),
('Roofings Uganda Ltd','0700333444','Namanve'),
('Kato Plumbing Supplies','0700555666','Kampala');

INSERT INTO products(category_id,product_code,product_name,unit_price,quantity_in_stock,reorder_level) VALUES
((SELECT category_id FROM categories WHERE category_name='Cement'),'CEM-001','Hima Cement 50kg',33000,60,20),
((SELECT category_id FROM categories WHERE category_name='Cement'),'CEM-002','Tororo Cement 50kg',31000,8,15),
((SELECT category_id FROM categories WHERE category_name='Roofing'),'ROF-001','Iron Sheet 28G 3m',48000,40,10),
((SELECT category_id FROM categories WHERE category_name='Roofing'),'ROF-002','Roofing Nails 1kg',9000,25,10),
((SELECT category_id FROM categories WHERE category_name='Plumbing'),'PLB-001','PVC Pipe 1 inch 6m',18000,30,10),
((SELECT category_id FROM categories WHERE category_name='Plumbing'),'PLB-002','Water Tap Brass',12000,5,8),
((SELECT category_id FROM categories WHERE category_name='Electrical'),'ELC-001','Electric Cable 2.5mm 100m',165000,12,4),
((SELECT category_id FROM categories WHERE category_name='Electrical'),'ELC-002','Socket Outlet Double',8500,35,10);

INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,notes)
SELECT p.product_id,(SELECT MIN(user_id) FROM users WHERE role='admin'),'IN',p.quantity_in_stock,'Opening stock'
FROM products p WHERE p.product_code IN ('CEM-001','CEM-002','ROF-001','ROF-002','PLB-001','PLB-002','ELC-001','ELC-002');
