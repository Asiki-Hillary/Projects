<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
    session_start();
}
try {
    $pdo = new PDO('mysql:host=dhrsea.h.filess.io;port=3305;dbname=hardware_inventory_db_thumbcard;charset=utf8mb4', 'hardware_inventory_db_thumbcard', getenv('DB_PASS') ?: 'Hillr100%stud', [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . $e->getMessage());
}
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                    
}
function login_required(): void {
    if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function roles(array $allowed): void {
    login_required();
    if (!in_array($_SESSION['role'] ?? '', $allowed, true)) {
        http_response_code(403);
        exit('<h2>403 - Access denied</h2><p>Your account is not permitted to open this page.</p><p><a href="index.php">Return to dashboard</a></p>');
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            http_response_code(419); exit('Invalid or expired form token. Go back and try again.');
        }
    }
}
function nav(): void {
    $role = $_SESSION['role'] ?? '';
    echo '<nav><a href="index.php">Dashboard</a>';
    if (in_array($role,['admin','storekeeper'],true)) echo '<a href="products.php">Products & Stock</a>';
    if (in_array($role,['admin','procurement'],true)) echo '<a href="suppliers.php">Suppliers</a><a href="purchases.php">Purchases</a>';
    if (in_array($role,['admin','sales'],true)) echo '<a href="sales.php">Sales</a>';
    if (in_array($role,['admin','manager'],true)) echo '<a href="reports.php">Reports</a>';
    if ($role==='admin') echo '<a href="users.php">Manage Users</a>';
    echo '<a href="logout.php">Logout</a></nav>';
}
function page_header(string $title): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).' | William Hardware</title><link rel="stylesheet" href="style.css"></head><body><header>William Hardware Shop <small>'.h($_SESSION['username'] ?? '').' · '.h(ucfirst($_SESSION['role'] ?? '')).'</small></header>';
    nav(); echo '<main><h2>'.h($title).'</h2>';
}
function page_footer(): void { echo '</main><footer>William Hardware Shop · Inventory Management Information System</footer></body></html>'; }
function flash(string $message='', string $type='success'): void {
    if ($message !== '') $_SESSION['flash'] = [$message,$type];
    if (!empty($_SESSION['flash'])) {
        [$m,$t] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<p class="notice '.h($t).'">'.h($m).'</p>';
    }
}
