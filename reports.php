<?php
require_once 'config.php';

// Only administrators and managers can access reports
roles(['admin', 'manager']);

// Get selected dates
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Ensure dates are strings
if (!is_string($from) || !is_string($to)) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}

// Validate dates
$validFrom = DateTime::createFromFormat('!Y-m-d', $from);
$validTo = DateTime::createFromFormat('!Y-m-d', $to);

if (
    !$validFrom ||
    !$validTo ||
    $validFrom->format('Y-m-d') !== $from ||
    $validTo->format('Y-m-d') !== $to ||
    $from > $to
) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}

try {

    // 1. SALES REPORT
    $s = $pdo->prepare(
        'SELECT COUNT(*) AS transactions,
                COALESCE(SUM(total_amount), 0) AS total
         FROM sales
         WHERE DATE(sale_date) BETWEEN ? AND ?'
    );

    $s->execute([$from, $to]);
    $sales = $s->fetch();

    // 2. PURCHASE REPORT
    $s = $pdo->prepare(
        'SELECT COUNT(*) AS transactions,
                COALESCE(SUM(total_amount), 0) AS total
         FROM purchases
         WHERE DATE(purchase_date) BETWEEN ? AND ?'
    );

    $s->execute([$from, $to]);
    $purchases = $s->fetch();

    // 3. LOW STOCK REPORT
    $low = $pdo->query(
        'SELECT product_code, product_name,
                quantity_in_stock, reorder_level
         FROM products
         WHERE quantity_in_stock <= reorder_level
         ORDER BY quantity_in_stock ASC'
    )->fetchAll();

    // 4. BEST SELLING PRODUCTS
    $s = $pdo->prepare(
        'SELECT p.product_name,
                SUM(si.quantity) AS units,
                SUM(si.quantity * si.unit_price) AS revenue
         FROM sale_items si
         JOIN sales s ON s.sale_id = si.sale_id
         JOIN products p ON p.product_id = si.product_id
         WHERE DATE(s.sale_date) BETWEEN ? AND ?
         GROUP BY p.product_id, p.product_name
         ORDER BY units DESC
         LIMIT 10'
    );

    $s->execute([$from, $to]);
    $top = $s->fetchAll();

} catch (PDOException $e) {

    http_response_code(500);
    exit('Database error: ' . h($e->getMessage()));

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>William Hardware - Management Reports</title>

<link rel="stylesheet" href="style.css">
<style>

/* GENERAL SETTINGS */

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: var(--bg);
    margin: 0;
    padding: 20px;
    color: var(--text);
}

.container {
    max-width: 1200px;
    margin: auto;
}

/* HEADER */

header {
    background: var(--navy);
    color: white;
    padding: 22px;
    border-radius: 8px;
    margin-bottom: 20px;
}

header h1 {
    margin: 0 0 8px;
}

header p {
    margin-bottom: 0;
}

/* BACK TO DASHBOARD */

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: var(--navy);
    text-decoration: none;
    font-weight: bold;
}

.back:hover {
    text-decoration: underline;
}

/* PANELS */

.panel {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

/* DATE FILTER */

.formgrid {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

label {
    display: block;
    flex: 1;
    min-width: 180px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 11px;
    margin-top: 8px;
    border: 1px solid var(--border);
    border-radius: 5px;
}

/* BUTTONS */

button {
    padding: 12px 20px;
    background: var(--navy);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

button:hover {
    background: var(--navy-dark);
}

/* SUMMARY CARDS */

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.metric {
    border-left: 5px solid var(--navy);
}

.metric h2 {
    color: var(--navy);
}

/* TABLES */

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid var(--border-soft);
}

th {
    background: var(--navy-tint);
    color: var(--navy);
}

tr:hover td {
    background: #f5f8fc;
}

/* EMPTY TABLE MESSAGE */

.empty {
    text-align: center;
    padding: 20px;
    color: var(--muted);
}

/* PRINT BUTTON */

.actions {
    margin-bottom: 20px;
}

/* FOOTER */

footer {
    text-align: center;
    color: var(--muted);
    margin-top: 30px;
}

/* MOBILE RESPONSIVENESS */

@media (max-width: 600px) {

    body {
        padding: 10px;
    }

    header {
        padding: 18px;
    }

    .panel {
        padding: 15px;
    }

    .formgrid {
        flex-direction: column;
        align-items: stretch;
    }

    label {
        width: 100%;
    }

    button {
        width: 100%;
    }

}

/* PRINT SETTINGS */

@media print {

    body {
        background: white;
        padding: 0;
    }

    .back,
    .filter,
    .actions {
        display: none !important;
    }

    .panel {
        box-shadow: none;
        border: 1px solid var(--border-soft);
    }

    header {
        background: white;
        color: black;
        border-bottom: 2px solid var(--navy);
    }

    th {
        background: var(--navy-tint) !important;
        color: black !important;
    }

    tr:hover {
        background: white;
    }

}

</style>

</head>

<body>

<div class="container">

<!-- PAGE HEADER -->

<header>

    <h1>William Hardware Shop</h1>

    <p>Management Information System - Reports</p>

</header>

<!-- DASHBOARD LINK -->

<a href="index.php" class="back">
    &larr; Back to Dashboard
</a>

<!-- PRINT BUTTON -->

<div class="actions">

    <button onclick="window.print()">
        Print / Save as PDF
    </button>

</div>

<h2>Management Reports</h2>

<!-- DATE FILTER -->

<div class="panel filter">

    <h3>Select Report Period</h3>

    <form method="GET" class="formgrid">

        <label>
            From Date

            <input
                type="date"
                name="from"
                value="<?php echo h($from); ?>"
                required
            >
        </label>

        <label>
            To Date

            <input
                type="date"
                name="to"
                value="<?php echo h($to); ?>"
                required
            >
        </label>

        <button type="submit">
            Generate Report
        </button>

    </form>

</div>

<!-- SALES AND PURCHASE SUMMARY -->

<h3>Sales and Purchase Summary</h3>

<div class="cards">

    <!-- SALES TRANSACTIONS -->

    <section class="panel metric">

        <h2>
            <?php echo h($sales['transactions']); ?>
        </h2>

        Sales Transactions

    </section>

    <!-- SALES REVENUE -->

    <section class="panel metric">

        <h2>
            UGX <?php echo number_format((float)$sales['total'], 2); ?>
        </h2>

        Sales Revenue

    </section>

    <!-- PURCHASE TRANSACTIONS -->

    <section class="panel metric">

        <h2>
            <?php echo h($purchases['transactions']); ?>
        </h2>

        Purchase Transactions

    </section>

    <!-- PURCHASE EXPENDITURE -->

    <section class="panel metric">

        <h2>
            UGX <?php echo number_format((float)$purchases['total'], 2); ?>
        </h2>

        Purchase Expenditure

    </section>

</div>

<!-- BEST SELLING PRODUCTS -->

<div class="panel">

    <h3>Best-Selling Products</h3>

    <div class="table-wrap">

        <table>

            <tr>
                <th>Product</th>
                <th>Units Sold</th>
                <th>Revenue (UGX)</th>
            </tr>

            <?php if (count($top) > 0): ?>

                <?php foreach ($top as $r): ?>

                    <tr>

                        <td>
                            <?php echo h($r['product_name']); ?>
                        </td>

                        <td>
                            <?php echo h($r['units']); ?>
                        </td>

                        <td>
                            <?php echo number_format((float)$r['revenue'], 2); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="3" class="empty">
                        No sales records found for this date range.
                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

<!-- LOW STOCK REPORT -->

<div class="panel">

    <h3>Low-Stock Report</h3>

    <div class="table-wrap">

        <table>

            <tr>
                <th>Product Code</th>
                <th>Product</th>
                <th>Available Stock</th>
                <th>Reorder Level</th>
            </tr>

            <?php if (count($low) > 0): ?>

                <?php foreach ($low as $r): ?>

                    <tr>

                        <td>
                            <?php echo h($r['product_code']); ?>
                        </td>

                        <td>
                            <?php echo h($r['product_name']); ?>
                        </td>

                        <td>
                            <?php echo h($r['quantity_in_stock']); ?>
                        </td>

                        <td>
                            <?php echo h($r['reorder_level']); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" class="empty">
                        No low-stock products found.
                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

<!-- FOOTER -->

<footer>

    William Hardware Shop Management Information System

</footer>

</div>

</body>

</html>
