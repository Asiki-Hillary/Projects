WILLIAM HARDWARE SHOP - FULL HIMIS

REQUIREMENTS
- Windows computer
- XAMPP with Apache, MySQL and PHP
- A modern web browser

INSTALLATION
1. Extract this folder into C:\xampp\htdocs\
2. Rename the extracted folder to william_hardware_himis if necessary.
3. Open XAMPP Control Panel and start Apache and MySQL.
4. Open http://localhost/phpmyadmin
5. Select Import, choose database.sql from this folder, then click Import/Go.
   IMPORTANT: database.sql drops and recreates the project tables. Use it on a
   new project database or back up existing records first.
6. Open http://localhost/william_hardware_himis/setup_admin.php ONCE.
7. The page creates the first administrator:
      Username: admin
      Password: Admin123!
8. Immediately delete setup_admin.php from the project folder.
   (Optional) Import sample_data.sql in phpMyAdmin for demo products and suppliers.
9. Sign in at http://localhost/william_hardware_himis/login.php
10. Open Manage Users and create accounts for staff with their correct roles.

USER PERMISSIONS
Administrator: all modules, including user account management.
Manager: dashboard and management reports (read-only).
Sales Attendant: dashboard and sales recording/history.
Storekeeper: dashboard and product/stock register.
Procurement Officer: dashboard, supplier register and purchase recording.

HOW STOCK WORKS
- Recording a purchase increases product stock.
- Recording a sale checks available stock and decreases product stock.
- Opening stock entered when registering a product is recorded as an IN movement.
- All sale/purchase stock changes are performed inside database transactions.

SECURITY NOTES
- Every restricted PHP page checks the user's role on the server. Hiding a menu
  link is not the security control; direct URL access is also checked.
- Forms use session CSRF tokens and database queries use prepared statements.
- Passwords are stored using PHP password_hash().
- This is an academic/local XAMPP system, not yet a production deployment.
  Before public hosting, add HTTPS, backups, stronger audit logging, rate limits,
  account deactivation, and a secure personal password-change page.


  USER Passwords
  admin ~ admin

  Procurement Officer
  liz ~ liz121212

  Sales attendants
  Hillary ~ hillary121212
  John ~ John121212
  Priscy ~ priscy121212
  Drake ~ drake121212

  Storekeepers
  Mark ~ mark121212
  Ritah ~ Ritah121212

  
