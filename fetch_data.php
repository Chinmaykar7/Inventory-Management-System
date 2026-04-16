<?php
// ============================================
// fetch_data.php — Fetch product stats from DB
// Fixed: column names match SQL schema (Option A)
// ============================================

header('Content-Type: application/json');

// Enable error display for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Custom error handler to output errors as JSON
set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(200); // Return 200 so JS can read the error
    echo json_encode(["php_error" => $message, "file" => basename($file), "line" => $line]);
    exit();
});

set_exception_handler(function($e) {
    http_response_code(200);
    echo json_encode(["php_exception" => $e->getMessage(), "file" => basename($e->getFile()), "line" => $e->getLine()]);
    exit();
});

$conn = new mysqli("localhost", "root", "", "inventory_db");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

// Validate input
if (!isset($_GET['product']) || trim($_GET['product']) === '') {
    echo json_encode([
        "error" => "No product specified",
        "avg_sales_7" => 0,
        "avg_sales_30" => 0,
        "last_day_sales" => 0,
        "loss_30" => 0,
        "waste_30" => 0
    ]);
    exit();
}

$product = trim($_GET['product']);

// ============================================
// FIND THE LATEST DATE IN THE DATASET
// Data is historical (2015-2018), so CURDATE()
// would return nothing. Use MAX date instead.
// 
// DB COLUMN: transaction_date (NOT "Date")
// ============================================
$maxDateResult = $conn->query("SELECT MAX(`transaction_date`) as max_date FROM `transaction_master`");
if (!$maxDateResult || $maxDateResult->num_rows === 0) {
    echo json_encode([
        "error" => "Could not determine max date",
        "avg_sales_7" => 0, "avg_sales_30" => 0,
        "last_day_sales" => 0, "loss_30" => 0, "waste_30" => 0
    ]);
    exit();
}
$maxDateRow = $maxDateResult->fetch_assoc();
$maxDate = $maxDateRow['max_date'];

if (!$maxDate) {
    echo json_encode([
        "error" => "No transaction data found",
        "avg_sales_7" => 0, "avg_sales_30" => 0,
        "last_day_sales" => 0, "loss_30" => 0, "waste_30" => 0
    ]);
    exit();
}

// ==========================
// LAST 7 DAYS SALES
// DB Columns: units_sold, stock_code, transaction_date
// ==========================
$stmt7 = $conn->prepare(
    "SELECT SUM(`units_sold`) as total 
     FROM `transaction_master` 
     WHERE `stock_code` = ? 
     AND `transaction_date` >= DATE_SUB(?, INTERVAL 7 DAY)"
);
$stmt7->bind_param("ss", $product, $maxDate);
$stmt7->execute();
$res7 = $stmt7->get_result();
$row7 = $res7->fetch_assoc();
$avg7 = ($row7['total'] ?? 0) / 7;
$stmt7->close();

// ==========================
// LAST 30 DAYS SALES
// ==========================
$stmt30 = $conn->prepare(
    "SELECT SUM(`units_sold`) as total 
     FROM `transaction_master` 
     WHERE `stock_code` = ? 
     AND `transaction_date` >= DATE_SUB(?, INTERVAL 30 DAY)"
);
$stmt30->bind_param("ss", $product, $maxDate);
$stmt30->execute();
$res30 = $stmt30->get_result();
$row30 = $res30->fetch_assoc();
$avg30 = ($row30['total'] ?? 0) / 30;
$stmt30->close();

// ==========================
// LAST DAY SALES
// ==========================
$stmt1 = $conn->prepare(
    "SELECT `units_sold` 
     FROM `transaction_master` 
     WHERE `stock_code` = ? 
     ORDER BY `transaction_date` DESC LIMIT 1"
);
$stmt1->bind_param("s", $product);
$stmt1->execute();
$res1 = $stmt1->get_result();
$row1 = $res1->fetch_assoc();
$last = $row1['units_sold'] ?? 0;
$stmt1->close();

// ==========================
// LOSS (LAST 30 DAYS)
// DB Columns: stock_code, lost_qty, lost_date
// ==========================
$stmtLoss = $conn->prepare(
    "SELECT SUM(`lost_qty`) as loss 
     FROM `lost_table` 
     WHERE `stock_code` = ? 
     AND `lost_date` >= DATE_SUB(?, INTERVAL 30 DAY)"
);
$stmtLoss->bind_param("ss", $product, $maxDate);
$stmtLoss->execute();
$resLoss = $stmtLoss->get_result();
$rowLoss = $resLoss->fetch_assoc();
$loss = $rowLoss['loss'] ?? 0;
$stmtLoss->close();

// ==========================
// WASTE (LAST 30 DAYS)
// DB Columns: stock_code, expired_qty, waste_date
// ==========================
$stmtWaste = $conn->prepare(
    "SELECT SUM(`expired_qty`) as waste 
     FROM `waste_table` 
     WHERE `stock_code` = ? 
     AND `waste_date` >= DATE_SUB(?, INTERVAL 30 DAY)"
);
$stmtWaste->bind_param("ss", $product, $maxDate);
$stmtWaste->execute();
$resWaste = $stmtWaste->get_result();
$rowWaste = $resWaste->fetch_assoc();
$waste = $rowWaste['waste'] ?? 0;
$stmtWaste->close();

// ==========================
// OUTPUT JSON
// ==========================
echo json_encode([
    "avg_sales_7"    => round($avg7, 2),
    "avg_sales_30"   => round($avg30, 2),
    "last_day_sales" => (int)$last,
    "loss_30"        => (int)$loss,
    "waste_30"       => (int)$waste,
    "debug" => [
        "product"  => $product,
        "max_date" => $maxDate
    ]
]);

$conn->close();
?>