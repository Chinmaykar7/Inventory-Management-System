# 📊 DemandForge - Inventory Forecasting & Optimization System

## 1. 📌 Project Title & Description

**DemandForge** is an intelligent, data-driven Inventory Forecasting and Optimization System. 

Designed to empower businesses with actionable insights, this system accurately predicts product demand, calculates stock duration, and evaluates potential stock risks. By leveraging historical transaction data and an advanced Machine Learning backend, DemandForge moves beyond static rule-based inventory management to provide dynamic, real-time recommendations, preventing both stockouts and overstocking.

**Key Features:**
* Accurate demand forecasting using Machine Learning.
* Dynamic calculation of stock duration and reorder quantities.
* Intelligent stock risk assessment (LOW / MEDIUM / HIGH).
* Seamless integration between a responsive web UI and a predictive backend.

---

## 2. 🚀 Tech Stack

**Frontend:**
* HTML5, CSS3 (Vanilla)
* JavaScript (ES6+)

**Backend:**
* PHP (runs on XAMPP - Apache)
* MySQL Database

**Machine Learning Backend:**
* Python (Flask API)
* XGBoost (Extreme Gradient Boosting model)
* Pandas, NumPy, Scikit-learn, Joblib

**Tools Used:**
* XAMPP (Local development environment)
* phpMyAdmin (Database Management)
* Jupyter Notebook / VS Code (ML Training & Development)

---

## 3. 🧠 System Architecture

The application follows a modern decoupled architecture where the PHP backend handles data retrieval and user serving, while a dedicated Flask API powers the heavy lifting for mathematical and predictive tasking.

**Application Flow:**
`User` → `Frontend (HTML/JS)` → `PHP Backend` → `MySQL Database` → `Flask API (Python)` → `XGBoost ML Model` → `Response (JSON)` → `Frontend UI`

---

## 4. ⚙️ Setup & Installation Guide

Follow these steps to get the project up and running on your local machine.

### 🔹 Prerequisites

* **XAMPP** (with Apache and MySQL)
* **Python 3.8+**
* Python required libraries. Install them via pip:
  ```bash
  pip install Flask pandas numpy scikit-learn xgboost joblib flask-cors requests
  ```

### 🔹 Steps to Run the Project

1. **Start XAMPP:**
   Open the XAMPP Control Panel and start the **Apache** and **MySQL** modules.

2. **Database Setup:**
   * Open your browser and go to `http://localhost/phpmyadmin`
   * Create a new database named `inventory_db` (or as per your configuration).
   * Import the provided SQL dump or CSV files to create the necessary tables (`transaction_master`, `stock_master`, etc.).

3. **Deploy the Web App:**
   * Place the entire project folder inside the `htdocs` directory of your XAMPP installation (usually `C:\xampp\htdocs\inventory`).

4. **Launch the ML Server:**
   * Open your terminal or command prompt.
   * Navigate to the `ml_backend` folder inside the project.
   * Run the Flask server:
     ```bash
     python app.py
     ```
   * *Note: Ensure that the model file (e.g., `inventory_xgboost_model.pkl`) is present in the directory.*

5. **Access the Application:**
   * Open your web browser and navigate to:
     ```
     http://localhost/inventory
     ```

---

## 5. 🗄️ Database & CSV Files

The system relies on historical business data separated into logical structures. These datasets are imported into MySQL and actively used to engineer features for the ML model.

### 📄 Transaction Master
* **Columns:** `Date`, `Stock_Code`, `Units Sold`, `UnitPrice`, `CustomerID`, `Country` (or similar)
* **Purpose:** Acts as the primary historical log of sales. It is used to calculate rolling averages and historical demand patterns, which are critical for the ML model to learn trends over time.

### 📄 Lost Table
* **Columns:** `Date`, `Stock_Code`, `Lost_Qty`
* **Purpose:** Tracks quantities that were lost or unaccounted for. Used to adjust true stock levels and identify areas of operational inefficiency.

### 📄 Waste Table
* **Columns:** `Date`, `Stock_Code`, `Waste_Qty`
* **Purpose:** Records damaged or expired stock. This helps in understanding product perishability and adjusting future purchasing decisions.

### 📄 Stock Master
* **Columns:** `Stock_Code`, `Description`, `Category`, `Unit_Cost`
* **Purpose:** Provides static product details. The `Stock_Code` acts as the primary key linking transactions across all tables.

---

## 6. 🧠 Machine Learning Model

The predictive engine of Stocklytics AI is built to handle non-linear relationships and complex time-series data.

* **Model Used:** **XGBoost (Extreme Gradient Boosting)**
  * Chosen for its high performance, execution speed, and ability to handle tabular datatypes efficiently.
* **Target Variable:** `Next_30d_Demand` (The anticipated number of units to be sold over the next 30 days).
* **Feature Engineering:**
  * **Lag Features:** Previous days' sales demand (e.g., Sales 7 days ago, 14 days ago).
  * **Rolling Averages:** 7-day or 30-day moving average of sales to smooth out volatility.
  * **Date Features:** Day of the week, month, and quarter extracted to capture seasonal variations.
  * **Categorical Variables:** Handled efficiently to ensure the model distinguishes between disparate product behaviors.
* **Training Process:** The model was trained using historical data splitted into training and testing datasets, with hyperparameters tuned to prevent overfitting.
* **Evaluation Metrics:** Evaluated primarily using Root Mean Square Error (**RMSE**), Mean Absolute Error (**MAE**), and **R²** score to validate the prediction accuracy.

---

## 7. 🔄 Data Flow 

**Step-by-step process of how an insight is generated:**

1. **User Interaction:** The user inputs a specific `Stock_Code` along with current inventory parameters on the frontend UI.
2. **Data Fetching:** The PHP backend queries the MySQL database to pull historical transaction strings for that specific product.
3. **Feature Engineering:** The historical data is passed to the Flask backend where engineered features (lags, rolling stats) are reconstructed dynamically purely from the live historical array.
4. **Demand Prediction:** The pre-trained XGBoost model inferences based on the engineered features to forecast the specific demand.
5. **Business Logic Execution:** Finally, standard business formulas are applied over the prediction to compute output metrics:
   * **Stock Duration:** Evaluates how long the current stock will last based on the predicted run rate.
   * **Reorder Quantity:** Computes how much replenishment is needed.
   * **Risk Assessment:** Flags the product based on imminent shortages.

---

## 8. 📥 Inputs Explained

Users provide the following parameters on the "Inventory Insights" page:

* **Product Name (Stock_Code):** The unique identifier for the product to be analyzed.
* **Current Stock:** The exact number of units currently available in the warehouse.
* **Minimum Threshold:** The absolute minimum number of units the business wishes to keep on hand (safety stock) at any time.
* **Lead Time (Days):** The amount of time it takes for a newly placed order to arrive at the warehouse.
* **Forecast Horizon (Days):** The timeframe for the prediction (e.g., 30 days).

---

## 9. 📤 Outputs Explained

The system generates actionable metrics based on the predicted demand.

* **Predicted Demand:** 
  The raw ML output indicating the expected unit sales over the given horizon.
* **Stock Duration:** 
  An estimate of how many days the `Current Stock` will last. 
  *(Formula: Current Stock / Daily Consumption Rate)*
* **Reorder Suggestion:** 
  The exact number of units to procure *today* to ensure stock levels remain safe over the lead time.
  *(Formula: (Predicted Demand + Minimum Threshold) - Current Stock)*
* **Risk Level:**
  * 🔴 **HIGH:** Current stock will deplete before the lead time ends. Immediate action needed.
  * 🟡 **MEDIUM:** Stock levels are nearing the minimum threshold. Monitoring required.
  * 🟢 **LOW:** Sufficient stock is available to comfortably cover the forecast period.

---

## 10. 🖥️ Web Pages Overview

* **Login Page:** Secure entry point ensuring only authorized personnel can view sensitive supply chain metrics.
* **Dashboard Page:** A high-level overview featuring aggregate metrics, total products, recent transactions, and system health.
* **Inventory Insights Page:** The core interactive module where users input stock parameters, triggering the ML pipeline, and view dynamically generated analytical cards and charts.

---

## 11. 📊 Features of the Project

* **Real-Time Predictions:** Connects live database records to ML inference for up-to-date forecasting.
* **ML-Based Optimization:** Surpasses standard min-max rule setups by factoring in seasonality, trends, and complex behaviors.
* **Data-Driven Decisions:** Provides exact quantitative recommendations preventing manual guesswork.
* **Interactive UI:** Smooth transitions, responsive design, and intuitive dashboards.

---

## 12. ⚠️ Challenges Faced

During the development, several complex issues were identified and resolved:

* **Feature Mismatch Issues:** Ensuring the real-time runtime data features engineered in the Flask backend exactly mirrored the structural schema used during the initial XGBoost model training.
* **ML Integration Hurdles:** Designing a robust pipeline to pass multi-dimensional historical data arrays between PHP and Flask seamlessly without noticeable latency.
* **API Connection Problems:** Managing asynchronous JavaScript (Fetch API) requests and tackling CORS configuration between the Apache and Flask servers.

---

## 13. 🚀 Future Improvements

* **Advanced Algorithms:** Implementing Deep Learning approaches like **LSTMs** or utilizing **Facebook Prophet** for enhanced sequential time-series forecasting.
* **External Variable Integration:** Utilizing a real-time **Weather API** to correlate climate conditions to specific product demand (e.g., umbrellas, cold beverages).
* **Advanced Dashboards:** Integrating more visual data using tools like Chart.js or D3.js to dynamically plot historical vs. predicted trends on the insights page.

---

