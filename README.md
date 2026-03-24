<<<<<<< HEAD
<p align="center">
  <h1 align="center">📦 Intelligent Inventory Optimization &<br>Demand Analytics System</h1>
  <p align="center">
    A full-stack demand forecasting web application designed to help small vendors optimize
    inventory restocking and eliminate overstock/understock scenarios using historical sales data.
  </p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white" alt="XAMPP">
</p>

---

## 📋 Table of Contents

- [About the Project](#-about-the-project)
- [Problem Statement](#-problem-statement)
- [Features](#-features-implemented-so-far)
- [Tech Stack](#-tech-stack)
- [Dataset Description](#-dataset-description)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Database Setup (Step-by-Step)](#-database-setup-step-by-step)
- [Running the Application](#-running-the-application)
- [Application Walkthrough](#-application-walkthrough)
- [Future Scope](#-future-scope)
- [License](#-license)

---

## 🎯 About the Project

Small and medium vendors often struggle with two critical inventory problems — **overstocking** (leading to waste & expired goods) and **understocking** (leading to lost sales & unhappy customers). This project aims to solve both by building an **Intelligent Inventory Optimization & Demand Analytics System**.

The system uses **historical sales data from Kaggle** (containing ~4 years of transactions from 2015–2018 across 20+ product categories) to power a full-stack web application that allows vendors to:

- Track inventory in real-time with **batch-level granularity**
- Process sales using **FIFO (First-In, First-Out)** logic — automatically selling the oldest stock first
- Monitor **lost demand** when stock runs out mid-order
- Track **waste/expired inventory** that wasn't sold before its expiry date
- Download live dataset snapshots for offline analysis

> **Note:** The Machine Learning (demand forecasting) component is planned for a future phase. The current version focuses on the **full-stack web infrastructure** and **data pipeline**.

---

## ❗ Problem Statement

| Scenario | Impact |
|---|---|
| **Overstocking** | Products expire before being sold → direct financial loss (waste) |
| **Understocking** | Customer demand cannot be met → lost revenue + lost customer trust |
| **No Data Visibility** | Vendors make gut-feel restocking decisions → inefficient operations |

This system addresses all three by providing a **data-driven dashboard** backed by real historical sales data, and by automatically tracking every lost sale and every wasted item.

---

## ✅ Features Implemented So Far

### 🔐 Authentication System
- Secure login page with session management
- Protected dashboard — only authenticated users can access inventory operations
- Clean logout with session destruction

### 📦 Stock-In Module
- Add new stock batches with **stock code, quantity, manufacturing date, and expiry date**
- Automatic calculation of **shelf life** and **lead time**
- Auto-incrementing **batch/product code** per stock item

### 🛒 Stock-Out Module (FIFO Sales Processing)
- Sell stock using **FIFO (First-In, First-Out)** expiry-based logic
- Automatically deducts from the **oldest valid batch** first
- Calculates **sales revenue and profit** using historical pricing data
- Applies **discount** to transactions
- Generates unique **Order IDs** sequentially
- Integrates **OpenWeatherMap API** for weather context in transaction logs

### 📉 Lost Demand Tracking
- When a sale order exceeds available stock, the unfulfilled quantity is logged
- **Lost profit** is calculated using historical profit-per-unit rates
- Enables future analysis of demand gaps

### 📊 Waste & Expiry Tracking
- Pre-loaded dataset tracks expired inventory batches
- Links waste to specific batch numbers (product codes)
- Records potential profit lost due to expiration

### 🔍 Stock Search
- Instantly look up the **current remaining quantity** for any stock code
- Filters out expired batches — only shows valid, sellable stock

### ⚠️ Low Stock Alerts
- Dashboard displays items with critically low inventory levels

### 📥 Live Dataset Download
- Download all 4 database tables as a **ZIP file of CSVs** (live from the database)
- Timestamped filenames for version control

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3 (Glassmorphism UI), Vanilla JavaScript |
| **Backend** | PHP 8.x (Session handling, form processing, FIFO logic) |
| **Database** | MySQL 5.7+ / MariaDB (via phpMyAdmin) |
| **Server** | XAMPP (Apache + MySQL bundled) |
| **Typography** | Google Fonts — Inter (300–700 weights) |
| **API** | OpenWeatherMap (weather context for transactions) |
| **Dataset** | Kaggle — Historical inventory & sales data (2015–2018) |

---

## 📁 Dataset Description

The project uses **4 interrelated CSV datasets** sourced from Kaggle:

### 1. `Stock Master.csv` (2,215 records)
Tracks every batch of stock entering the inventory.

| Column | Description |
|---|---|
| `id` | Unique record identifier |
| `stock_code` | Product identifier (e.g., BAK-BIS, FRU-FRE) |
| `product_code` | Batch number for that specific stock item |
| `manufacturing_date` | Date the product was manufactured |
| `expiry_date` | Date the product expires |
| `initial_qty` | Quantity received in this batch |
| `remaining_qty` | Current unsold quantity |
| `arrival_date` | Date the batch arrived at the store |
| `shelf_life` | Number of days from manufacture to expiry |
| `lead_time` | Number of days from manufacture to arrival |

### 2. `Transaction Master.csv` (9,999 records)
Records every sales transaction with rich contextual features.

| Column | Description |
|---|---|
| `id` | Unique transaction identifier |
| `stock_code` | Product sold |
| `order_id` | Unique order identifier (e.g., OD245) |
| `category` / `sub_category` | Product classification |
| `city` / `region` | Geographic location of sale |
| `sales` | Revenue generated |
| `discount` | Discount applied (decimal, e.g., 0.15 = 15%) |
| `profit` | Net profit after cost and discount |
| `day_of_week` | Day of the week (0=Monday … 6=Sunday) |
| `is_weekend` | Binary flag (1 = weekend) |
| `weather` | Weather condition at time of sale |
| `unit_cost` | Cost price per unit |
| `units_sold` | Number of units sold |
| `transaction_date` | Exact date of sale |
| `week_date` | Start of the corresponding week |
| `rolling_avg_4weeks` | 4-week rolling average of sales |
| `product_code` | Batch reference number |

### 3. `Lost Table.csv` (2,210 records)
Logs unmet customer demand (understock events).

| Column | Description |
|---|---|
| `id` | Unique record identifier |
| `lost_date` | Date the demand was unmet |
| `stock_code` | Product that was out of stock |
| `lost_qty` | Number of units customers wanted but couldn't buy |
| `lost_profit` | Estimated profit lost due to stockout |

### 4. `Waste Table.csv` (1,371 records)
Tracks expired inventory (overstock events).

| Column | Description |
|---|---|
| `id` | Unique record identifier |
| `waste_date` | Date the product expired/was wasted |
| `stock_code` | Product that expired |
| `product_code` | Specific batch that expired |
| `expired_qty` | Number of units that expired |
| `waste_lost_profit` | Potential profit lost due to expiry |

---

## 📂 Project Structure

```
inventory/
├── db_connect.php            # Database connection configuration
├── login.php                 # Authentication page (login form + session handling)
├── login.css                 # Styling for the login page
├── dashboard.php             # Main dashboard (Stock In, Stock Out, Search, Alerts)
├── dashboard.css             # Glassmorphism-styled dashboard UI
├── dashboard.js              # Tab switching logic for Stock In / Stock Out
├── download.php              # Live CSV dataset export as ZIP
├── logout.php                # Session destruction and redirect
├── inventory_db.sql          # SQL schema file for phpMyAdmin import
├── Stock Master.csv          # Dataset: Stock batch records
├── Transaction Master.csv    # Dataset: Sales transaction records
├── Lost Table.csv            # Dataset: Lost demand records
├── Waste Table.csv           # Dataset: Waste/expiry records
└── README.md                 # This documentation file
```

---

## 🚀 Getting Started

### Prerequisites

| Requirement | Details |
|---|---|
| **XAMPP** | Download from [apachefriends.org](https://www.apachefriends.org/) (includes Apache, MySQL, PHP, phpMyAdmin) |
| **Web Browser** | Any modern browser (Chrome, Firefox, Edge) |
| **OS** | Windows 10/11 (also works on macOS/Linux with XAMPP) |

---

### ⚙️ Installation

#### Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Run the installer and follow the setup wizard
3. Install to the default location: `C:\xampp`

#### Step 2: Clone or Download the Project

```bash
# Option A: Clone with Git
git clone https://github.com/YOUR_USERNAME/inventory.git C:\xampp\htdocs\inventory

# Option B: Download ZIP
# Extract the ZIP contents into C:\xampp\htdocs\inventory\
```

> ⚠️ **Important:** The project folder must be placed inside `C:\xampp\htdocs\` for Apache to serve it.

#### Step 3: Start XAMPP Services

1. Open the **XAMPP Control Panel** (search for it in the Start menu)
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both services should turn green indicating they are running

---

## 🗄️ Database Setup (Step-by-Step)

### Step 1: Import the SQL Schema

1. Open your browser and go to **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
2. Click on the **Import** tab in the top navigation bar
3. Click **Choose File** and select `inventory_db.sql` from the project folder (`C:\xampp\htdocs\inventory\inventory_db.sql`)
4. Leave all settings as default
5. Click **Go** at the bottom
6. ✅ You should see a success message — the database `inventory_db` and all 4 tables have been created

### Step 2: Import CSV Data into Each Table

You need to import each of the 4 CSV files into its corresponding table:

| CSV File | → Import Into Table |
|---|---|
| `Stock Master.csv` | `stock_master` |
| `Transaction Master.csv` | `transaction_master` |
| `Lost Table.csv` | `lost_table` |
| `Waste Table.csv` | `waste_table` |

**For each CSV file, follow these steps:**

1. In phpMyAdmin, click on the **`inventory_db`** database in the left sidebar
2. Click on the **target table name** (e.g., `stock_master`)
3. Click the **Import** tab
4. Click **Choose File** and select the corresponding CSV file
5. Set the **Format** dropdown to **CSV**
6. Under **Format-specific options**:
   - ✅ Check **"The first line of the file contains the table column names"** (or set "Number of rows to skip" to `1`)
   - Set **Columns separated with** to `,` (comma)
   - Set **Columns enclosed with** to `"` (double quote)
7. Click **Go**
8. ✅ You should see a success message with the number of rows imported

**Repeat steps 1–8 for all 4 CSV files.**

> 💡 **Tip:** If you encounter an error during CSV import, check that the "Number of rows to skip" is set to `1` to skip the header row, since the column names in the CSV match the table column names.

---

## ▶️ Running the Application

1. Make sure **Apache** and **MySQL** are running in the XAMPP Control Panel
2. Open your browser
3. Navigate to: **[http://localhost/inventory/login.php](http://localhost/inventory/login.php)**
4. Login with the following credentials:

| Field | Value |
|---|---|
| **Username** | `admin` |
| **Password** | `Ch@130405` |

5. You will be redirected to the **Dashboard** where you can:
   - 🔍 Search stock levels
   - 📦 Add new stock (Stock In)
   - 🛒 Process sales (Stock Out)
   - 📥 Download live datasets

---

## 🖥️ Application Walkthrough

### Login Page
The login page features a **split-screen design** with a branded left panel highlighting key features (real-time tracking, advanced analytics, secure access) and a clean login form on the right. Built with the **Inter** font family and a modern glassmorphism aesthetic.

### Dashboard
The dashboard is organized into a **grid layout**:

- **Left Column:**
  - **Stock Search** — Enter any stock code (e.g., `BAK-BIS`) to check current inventory levels. Only valid (non-expired) stock is counted.
  - **Low Stock Alerts** — Displays items that are critically low or depleted.

- **Right Column:**
  - **Stock In Form** — Add new inventory batches with stock code, quantity, manufacturing date, and expiry date. Shelf life and lead time are calculated automatically.
  - **Stock Out Form** — Process sales by entering the stock code, quantity, and optional discount. The system uses **FIFO logic** to sell the oldest valid stock first, logs the transaction, and captures lost demand if stock runs out.

### Data Export
Click **📥 Download Data** in the navbar to export all 4 tables as a timestamped ZIP archive of CSVs — pulled live from the database.

---

## 🔮 Future Scope

| Phase | Feature | Description |
|---|---|---|
| **Phase 2** | 🤖 ML Demand Forecasting | Train models on historical sales data to predict future demand per product |
| **Phase 2** | 📈 Interactive Data Visualizations | Charts for sales trends, waste patterns, and lost demand analysis |
| **Phase 2** | 🔔 Smart Reorder Alerts | AI-powered restocking suggestions based on predicted demand |
| **Phase 3** | 🧠 Automated CRON Waste Detection | Automatic expiry scanning and waste logging |
| **Phase 3** | 📊 Vendor Analytics Dashboard | KPIs, profit margins, category performance breakdowns |
| **Phase 3** | 🌐 Multi-vendor / Multi-store Support | Scale the system for multiple stores and vendors |

---

## 📄 License

This project is built for educational and academic purposes.  
Dataset sourced from [Kaggle](https://www.kaggle.com/) — historical inventory and sales data.

---

<p align="center">
  Built with ❤️ using PHP, MySQL, and a passion for data-driven inventory management.
</p>
=======
# Inventory-Management-System
Built an end-to-end demand forecasting web application to help  small vendors and local shop owners optimize their inventory  restocking decisions.
>>>>>>> fbf769d96b66238673c405d51e0bc9cdac241cf4
