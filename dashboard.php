<?php
session_start();

// 1. Connect to the database
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$update_msg = "";
$search_msg = "";

// 2. Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action_type'])) {
        $action = $_POST['action_type'];
        
        // ==========================================
        // ACTION: STOCK IN
        // ==========================================
        if ($action === "stock_in") {
            $stock_code = htmlspecialchars($_POST['stock_code']);
            $qty = (int)$_POST['quantity'];
            $mfg_date = $_POST['mfg_date'];
            $exp_date = $_POST['exp_date'];
            $arrival_date = date("Y-m-d"); // Today's date

            // Calculate Shelf Life & Lead Time dynamically
            $mfg = new DateTime($mfg_date);
            $exp = new DateTime($exp_date);
            $arr = new DateTime($arrival_date);
            
            $shelf_life = $mfg->diff($exp)->days;
            $lead_time = $mfg->diff($arr)->days;

            // Find the next Product Code (Batch ID) for this specific Stock Code
            $stmt = $conn->prepare("SELECT MAX(product_code) AS max_code FROM stock_master WHERE stock_code = ?");
            $stmt->bind_param("s", $stock_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // If it exists, add 1. If it's a brand new item, start at batch 1.
            $next_product_code = ($row['max_code'] !== null) ? $row['max_code'] + 1 : 1;
            $stmt->close();

            // Insert the new batch into stock_master
            $insert_query = "INSERT INTO stock_master (stock_code, product_code, manufacturing_date, expiry_date, initial_qty, remaining_qty, arrival_date, shelf_life, lead_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sisssisdd", $stock_code, $next_product_code, $mfg_date, $exp_date, $qty, $qty, $arrival_date, $shelf_life, $lead_time);
            
            if ($insert_stmt->execute()) {
                $update_msg = "🟢 Successfully added $qty units to $stock_code. (Assigned to Batch: $next_product_code)";
            } else {
                $update_msg = "🔴 Error adding stock: " . $conn->error;
            }
            $insert_stmt->close();
        } 
        
        // ==========================================
        // ACTION: SEARCH (Bonus - Let's make this live too!)
        // ==========================================
        elseif ($action === "search") {
            $stock_code = htmlspecialchars($_POST['stock_code']);
            
            // Query the total remaining stock for the searched item
            $stmt = $conn->prepare("SELECT SUM(remaining_qty) AS total_stock FROM stock_master WHERE stock_code = ? AND remaining_qty > 0 AND expiry_date >= CURDATE()");
            $stmt->bind_param("s", $stock_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $total_stock = ($row['total_stock'] !== null) ? $row['total_stock'] : 0;
            $search_msg = "Current valid stock for <strong>$stock_code</strong>: $total_stock units available.";
            $stmt->close();
        }
        
        // (Stock Out logic will go here next)
        // ==========================================
        // ACTION: STOCK OUT (FIFO & LOST DEMAND)
        // ==========================================
        elseif ($action === "stock_out") {
            $stock_code = htmlspecialchars($_POST['stock_code']);
            $demand_qty = (int)$_POST['quantity'];
            $discount = (float)$_POST['discount']; // e.g., 0.15 for 15%
            $sale_date = date("Y-m-d");
            
            $qty_to_fulfill = $demand_qty;
            $fulfilled_qty = 0;
            
            // 1. Fetch available batches ordered by Expiry Date (FIFO)
            $stmt = $conn->prepare("SELECT id, product_code, remaining_qty FROM stock_master WHERE stock_code = ? AND remaining_qty > 0 AND expiry_date >= CURDATE() ORDER BY expiry_date ASC");
            $stmt->bind_param("s", $stock_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 2. Process FIFO Deduction
            while ($batch = $result->fetch_assoc()) {
                if ($qty_to_fulfill <= 0) break; // Demand met
                
                $available = $batch['remaining_qty'];
                $take = min($available, $qty_to_fulfill);
                
                // Deduct from this batch
                $new_qty = $available - $take;
                $update_stmt = $conn->prepare("UPDATE stock_master SET remaining_qty = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_qty, $batch['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $qty_to_fulfill -= $take;
                $fulfilled_qty += $take;
            }
            $stmt->close();
            
            // 3. Get Base Data for Math & Categories
            $meta_stmt = $conn->prepare("SELECT category, sub_category, unit_cost, profit, sales, units_sold, rolling_avg_4weeks FROM transaction_master WHERE stock_code = ? ORDER BY transaction_date DESC LIMIT 1");
            $meta_stmt->bind_param("s", $stock_code);
            $meta_stmt->execute();
            $meta = $meta_stmt->get_result()->fetch_assoc();
            $meta_stmt->close();
            
            $category = $meta ? $meta['category'] : 'Unknown';
            $sub_category = $meta ? $meta['sub_category'] : 'Unknown';
            $unit_cost = $meta ? $meta['unit_cost'] : 0.00;
            $rolling_avg = $meta ? $meta['rolling_avg_4weeks'] : 0.00;
            
            // Base Historical Math
            $hist_sales_per_unit = ($meta && $meta['units_sold'] > 0) ? ($meta['sales'] / $meta['units_sold']) : 0.00;
            $hist_profit_per_unit = ($meta && $meta['units_sold'] > 0) ? ($meta['profit'] / $meta['units_sold']) : 0.00;

            // 4. Log the Successful Transaction
            if ($fulfilled_qty > 0) {
                // Generate Sequential Order ID
                $oid_stmt = $conn->query("SELECT order_id FROM transaction_master ORDER BY CAST(SUBSTRING(order_id, 3) AS UNSIGNED) DESC LIMIT 1");
                $last_oid = $oid_stmt->fetch_assoc();
                $next_id = 1;
                if ($last_oid && isset($last_oid['order_id'])) {
                    $next_id = (int)substr($last_oid['order_id'], 2) + 1;
                }
                $order_id = "OD" . $next_id;

                // City & Random Region
                $city = "Ooty"; 
                $regions = ['Central', 'East', 'South', 'West'];
                $region = $regions[array_rand($regions)];

                // Calculate Live Sales & Profit with Discount applied
                $gross_sales = $hist_sales_per_unit * $fulfilled_qty;
                $discount_amount = $gross_sales * $discount;
                $final_sales = $gross_sales - $discount_amount;
                
                $total_cost = $unit_cost * $fulfilled_qty;
                $final_profit = $final_sales - $total_cost;

                // Dates & Booleans
                $day_of_week = date('N'); 
                $is_weekend = ($day_of_week >= 6) ? 1 : 0;
                $week_date = date('Y-m-d', strtotime('last Sunday', strtotime($sale_date)));
                
                // Weather API Integration
                $api_key = "YOUR_API_KEY_HERE"; 
                $api_url = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$api_key}";
                $weather_data = @file_get_contents($api_url);
                
                if ($weather_data !== FALSE) {
                    $weather_json = json_decode($weather_data);
                    $main_weather = $weather_json->weather[0]->main;
                    switch ($main_weather) {
                        case 'Clear': $weather = "Pleasant"; break;
                        case 'Clouds': $weather = "Cloudy"; break;
                        case 'Rain': case 'Drizzle': case 'Thunderstorm': $weather = "Rainy"; break;
                        case 'Wind': case 'Squall': $weather = "Windy"; break;
                        default: $weather = "Pleasant"; 
                    }
                } else {
                    $weather = "Pleasant"; 
                }
                
                $trans_stmt = $conn->prepare("INSERT INTO transaction_master (stock_code, order_id, category, sub_category, city, region, sales, discount, profit, day_of_week, is_weekend, weather, unit_cost, units_sold, transaction_date, week_date, rolling_avg_4weeks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $trans_stmt->bind_param("ssssssdddiisdisss", $stock_code, $order_id, $category, $sub_category, $city, $region, $final_sales, $discount, $final_profit, $day_of_week, $is_weekend, $weather, $unit_cost, $fulfilled_qty, $sale_date, $week_date, $rolling_avg);
                $trans_stmt->execute();
                $trans_stmt->close();
            }
            
            // 5. Handle Unmet Demand (Lost Profit)
            if ($qty_to_fulfill > 0) {
                // Lost profit is calculated using historical base profit (without discounts)
                $lost_profit = $hist_profit_per_unit * $qty_to_fulfill;
                
                $lost_stmt = $conn->prepare("INSERT INTO lost_table (lost_date, stock_code, lost_qty, lost_profit) VALUES (?, ?, ?, ?)");
                $lost_stmt->bind_param("ssid", $sale_date, $stock_code, $qty_to_fulfill, $lost_profit);
                $lost_stmt->execute();
                $lost_stmt->close();
                
                $update_msg = "⚠️ Partial Fill: Sold $fulfilled_qty units (Order ID: $order_id). Short by $qty_to_fulfill units.";
            } else {
                $update_msg = "🟢 Successfully sold all $fulfilled_qty units of $stock_code! (Order ID: $order_id)";
            }
        } // Closes: elseif ($action === "stock_out")
        
    } // Closes: if (isset($_POST['action_type']))
} // Closes: if ($_SERVER["REQUEST_METHOD"] == "POST")
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Inventory Management System</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-overlay"></div>

    <div class="dashboard-container">
        <nav class="navbar">
            <div class="nav-brand">
                <div class="logo-mini">📦</div>
                <h2>Inventory System</h2>
            </div>
            <div class="nav-actions">
                <a href="download.php" class="btn-download">
                    <span>📥 Download Data</span>
                </a>
                <a href="insights.php" class="btn-insights">
                    <span>✨ Inventory Insights</span>
                </a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </nav>

        <main class="main-content">
            <header class="welcome-section">
                <h1>Welcome Back, <?php echo ucfirst($_SESSION['user']); ?>!</h1>
                <p>Manage your inventory flow and monitor stock levels.</p>
            </header>

            <div class="dashboard-grid">
                
                <div class="grid-left">
                    
                    <div class="glass-card search-card">
                        <div class="card-header">
                            <h3>🔍 Check Stock Levels</h3>
                            <p>Quickly search remaining inventory</p>
                        </div>
                        <form method="POST" class="search-form">
                            <input type="hidden" name="action_type" value="search">
                            <div class="search-input-group">
                                <input type="text" name="stock_code" required placeholder="Enter Stock Code (e.g. BAK-BIS)">
                                <button type="submit" class="search-btn">Search</button>
                            </div>
                        </form>
                        <?php if($search_msg != ""): ?>
                            <div class="search-result"><?php echo $search_msg; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="glass-card alert-card mt-4">
                        <div class="card-header">
                            <h3>⚠️ Low Stock Alerts</h3>
                            <p>Items running out quickly</p>
                        </div>
                        <div class="alert-list compact-scroll">
                            <div class="alert-item"><div class="item-info"><span class="item-name">BEV-SOF (Soft Drinks)</span><span class="item-stock critical">0 Left</span></div></div>
                            <div class="alert-item"><div class="item-info"><span class="item-name">BAK-BIS (Biscuits)</span><span class="item-stock warning">12 Left</span></div></div>
                            <div class="alert-item"><div class="item-info"><span class="item-name">EGG-CHI (Chicken)</span><span class="item-stock warning">15 Left</span></div></div>
                            <div class="alert-item"><div class="item-info"><span class="item-name">SNA-NOO (Noodles)</span><span class="item-stock warning">22 Left</span></div></div>
                        </div>
                    </div>
                </div>

                <div class="grid-right">
                    <div class="glass-card update-card">
                        
                        <div class="tab-container">
                            <button class="tab-btn active" onclick="switchTab('stock_in')">📦 Stock In</button>
                            <button class="tab-btn" onclick="switchTab('stock_out')">🛒 Stock Out</button>
                        </div>

                        <?php if($update_msg != ""): ?>
                            <div class="success-msg"><?php echo $update_msg; ?></div>
                        <?php endif; ?>

                        <form method="POST" id="form_stock_in" class="update-form active-form">
                            <input type="hidden" name="action_type" value="stock_in">
                            
                            <div class="form-group">
                                <input type="text" name="stock_code" required placeholder=" ">
                                <label>Stock Code (e.g. BAK-BIS)</label>
                                <span class="input-icon">🏷️</span>
                            </div>

                            <div class="form-row">
                                <div class="form-group half">
                                    <input type="number" name="quantity" min="1" required placeholder=" ">
                                    <label>Quantity</label>
                                    <span class="input-icon">📦</span>
                                </div>
                                <div class="form-group half">
                                    <input type="number" step="0.01" name="price" required placeholder=" ">
                                    <label>Unit Cost (₹)</label>
                                    <span class="input-icon">💰</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group half">
                                    <input type="date" name="mfg_date" required placeholder=" ">
                                    <label class="date-label">Mfg Date</label>
                                </div>
                                <div class="form-group half">
                                    <input type="date" name="exp_date" required placeholder=" ">
                                    <label class="date-label">Expiry Date</label>
                                </div>
                            </div>

                            <button type="submit" class="action-btn btn-green">
                                <span>Add to Inventory</span>
                            </button>
                        </form>

                        <form method="POST" id="form_stock_out" class="update-form" style="display: none;">
                            <input type="hidden" name="action_type" value="stock_out">
                            
                            <div class="form-group">
                                <input type="text" name="stock_code" required placeholder=" ">
                                <label>Stock Code (e.g. BAK-BIS)</label>
                                <span class="input-icon">🏷️</span>
                            </div>

                            <div class="form-group">
                                <input type="number" name="quantity" min="1" required placeholder=" ">
                                <label>Quantity Sold</label>
                                <span class="input-icon">🛒</span>
                            </div>

                            <div class="form-group">
                                <input type="number" step="0.01" name="discount" placeholder=" ">
                                <label>Discount (e.g. 0.15 for 15%)</label>
                                <span class="input-icon">✂️</span>
                            </div>

                            <button type="submit" class="action-btn btn-blue">
                                <span>Process Sale</span>
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>