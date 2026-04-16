<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Insights | Inventory Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="insights.css">
</head>
<body>
    <div class="background-overlay"></div>

    <div class="insights-container">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="nav-brand">
                <div class="logo-mini">✨</div>
                <h2>Inventory Insights</h2>
            </div>
            <div class="nav-actions">
                <a href="dashboard.php" class="btn-back">⬅️ Back to Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </nav>

        <!-- Page Header -->
        <header class="page-header">
            <span class="header-icon">🔮</span>
            <h1>Smart Inventory Forecasting</h1>
            <p>Predict demand, analyze stock duration, and calculate safety reorder levels.</p>
        </header>

        <!-- Input Section -->
        <div class="input-section glass-card">
            <div class="card-header">
                <h3>Parameters</h3>
                <p>Enter current inventory details to generate insights</p>
            </div>
            <form id="insightsForm" class="insights-form">
                <div class="form-group">
                    <input type="text" id="productName" placeholder=" " required>
                    <label>Product Name (e.g. Milk, Bread)</label>
                    <span class="input-icon">🏷️</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <input type="number" id="currentStock" placeholder=" " required>
                        <label>Current Stock</label>
                        <span class="input-icon">📦</span>
                    </div>
                    <div class="form-group half">
                        <input type="number" id="minThreshold" placeholder=" " required>
                        <label>Min Threshold</label>
                        <span class="input-icon">📉</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <input type="number" id="leadTime" placeholder=" " required>
                        <label>Lead Time (Days)</label>
                        <span class="input-icon">⏳</span>
                    </div>
                    <div class="form-group half">
                        <select id="timeRange">
                            <option value="7">7 Days Prediction</option>
                            <option value="15">15 Days Prediction</option>
                            <option value="30" selected>30 Days Prediction</option>
                            <option value="60">60 Days Prediction</option>
                        </select>
                        <label>Forecast Horizon</label>
                        <span class="input-icon">📅</span>
                    </div>
                </div>

                <button type="submit" id="generateBtn" class="generate-btn">
                    <span>Generate AI Insights</span>
                    <span class="btn-icon">⚡</span>
                </button>
            </form>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Analyzing Inventory Patterns...</div>
            <div class="loading-subtext">Executing ML Models</div>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="results-section">
            <div class="results-header">
                <h2>Analysis Complete</h2>
                <div id="productTag" class="product-tag">📦 Loading...</div>
            </div>

            <div id="riskBanner" class="risk-banner">
                <div id="bannerIcon" class="risk-banner-icon">⚠️</div>
                <div class="risk-banner-content">
                    <h4 id="bannerTitle">Fetching Data</h4>
                    <p id="bannerText">...</p>
                </div>
            </div>

            <div class="insights-grid">
                <!-- Demand Card -->
                <div class="insight-card demand">
                    <div class="insight-icon">📈</div>
                    <div class="insight-label">Predicted Demand</div>
                    <div id="demandValue" class="insight-value">-</div>
                    <div id="demandDetail" class="insight-detail">-</div>
                </div>

                <!-- Duration Card -->
                <div class="insight-card duration">
                    <div class="insight-icon">⏳</div>
                    <div class="insight-label">Stock Duration</div>
                    <div id="durationValue" class="insight-value">-</div>
                    <div id="durationDetail" class="insight-detail">-</div>
                </div>

                <!-- Reorder Card -->
                <div class="insight-card reorder">
                    <div class="insight-icon">🛒</div>
                    <div class="insight-label">Reorder Suggestion</div>
                    <div id="reorderValue" class="insight-value">-</div>
                    <div id="reorderDetail" class="insight-detail">-</div>
                </div>

                <!-- Risk Card -->
                <div class="insight-card risk">
                    <div class="insight-icon">🛡️</div>
                    <div class="insight-label">Risk Assessment</div>
                    <div id="riskValue" class="insight-value">-</div>
                    <div id="riskDetail" class="insight-detail">-</div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-card glass-card">
                <div class="card-header">
                    <h3>Inventory Projection</h3>
                    <p>Forecasted stock depletion vs minimum threshold</p>
                </div>
                <div class="chart-area">
                    <canvas id="chartCanvas" class="chart-canvas"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-dot stock"></span> Projected Stock</div>
                    <div class="legend-item"><span class="legend-dot demand"></span> Cumulative Demand</div>
                    <div class="legend-item"><span class="legend-dot threshold"></span> Min Threshold</div>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="summary-card glass-card">
                <div class="card-header">
                    <h3>Executive Summary</h3>
                </div>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Est. Demand</th>
                            <th>Duration</th>
                            <th>Reorder Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="insights.js"></script>
</body>
</html>