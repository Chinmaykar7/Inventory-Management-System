/* ============================================================
   Inventory Insights — Frontend Logic
   Handles form validation, API calls, chart rendering,
   and smooth reveal animations.
   ============================================================ */

// ========================= //
// DOM REFERENCES
// ========================= //
const insightsForm    = document.getElementById('insightsForm');
const resultsSection  = document.getElementById('resultsSection');
const loadingOverlay  = document.getElementById('loadingOverlay');
const generateBtn     = document.getElementById('generateBtn');

// ========================= //
// FORM SUBMISSION & ML INTEGRATION
// ========================= //
insightsForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Gather form values
    const productName   = document.getElementById('productName').value.trim();
    const currentStock  = parseInt(document.getElementById('currentStock').value);
    const minThreshold  = parseInt(document.getElementById('minThreshold').value);
    const leadTime      = parseInt(document.getElementById('leadTime').value);
    const timeRange     = parseInt(document.getElementById('timeRange').value);

    // Basic validation
    if (!productName || isNaN(currentStock) || isNaN(minThreshold) || isNaN(leadTime)) {
        alert("Please fill in all fields correctly.");
        return;
    }

    // Show loading overlay
    loadingOverlay.classList.add('active');
    generateBtn.disabled = true;

    // Safety timeout — never load forever (30s max)
    const safetyTimeout = setTimeout(() => {
        loadingOverlay.classList.remove('active');
        generateBtn.disabled = false;
        alert("Request timed out. Please check that the Flask server is running on http://127.0.0.1:5000 and try again.");
    }, 30000);

    try {
        // ========================= //
        // STEP 1: PREPARE ML PAYLOAD
        // ========================= //
        const requestData = {
            stock_code: productName,
            current_stock: currentStock,
            min_stock: minThreshold,
            lead_time: leadTime,
            forecast_horizon: timeRange
        };

        console.log("[Insights] Step 1: Sending to Flask /predict:", requestData);

        // ========================= //
        // STEP 3: CALL FLASK ML API
        // ========================= //
        const response = await fetch("http://127.0.0.1:5000/predict", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            throw new Error(`Flask API returned HTTP ${response.status}`);
        }

        const result = await response.json();
        console.log("[Insights] Step 3 Flask response:", result);

        if (result.status === "success") {
            const res = result.data;
            
            // Map API response to the structure expected by renderResults and drawChart
            const insights = {
                product: productName,
                stock: currentStock,
                threshold: minThreshold,
                lead: leadTime,
                range: timeRange,
                demandRate: parseFloat((res.predicted_demand / timeRange).toFixed(1)) || 0,
                predictedDemand: res.predicted_demand,
                stockDuration: res.stock_duration,
                reorderQty: res.reorder_quantity,
                reorderInDays: Math.max(0, res.stock_duration - leadTime),
                riskLevel: getRiskLevelClass(res.risk),
                riskMessage: res.risk
            };

            console.log("[Insights] Step 4: Rendering results:", insights);

            clearTimeout(safetyTimeout);
            loadingOverlay.classList.remove('active');
            generateBtn.disabled = false;
            
            renderResults(insights);

            // Smooth scroll to results
            setTimeout(() => {
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        } else {
            clearTimeout(safetyTimeout);
            loadingOverlay.classList.remove('active');
            generateBtn.disabled = false;
            alert("ML Model Error: " + (result.message || "Unknown error"));
        }

    } catch (error) {
        clearTimeout(safetyTimeout);
        console.error("[Insights] ERROR:", error);
        loadingOverlay.classList.remove('active');
        generateBtn.disabled = false;
        alert("Error: " + error.message + "\n\nCheck browser console for details.");
    }
});

// Helper for mapping risk text to CSS classes used by banner
function getRiskLevelClass(riskText) {
    const txt = (riskText || '').toLowerCase();
    if (txt.includes('critical') || txt.includes('high')) return 'critical';
    if (txt.includes('warning') || txt.includes('moderate') || txt.includes('medium')) return 'warning';
    return 'safe';
}

// ========================= //
// RENDER RESULTS
// ========================= //
function renderResults(data) {
    // Product tag
    document.getElementById('productTag').textContent = `📦 ${data.product}  •  ${data.range}-Day Analysis`;

    // Insight cards
    document.getElementById('demandValue').textContent = `${data.predictedDemand} units`;
    document.getElementById('demandDetail').textContent =
        `Avg. ${data.demandRate} units/day over the next ${data.range} days`;

    document.getElementById('durationValue').textContent = `${data.stockDuration} days`;
    document.getElementById('durationDetail').textContent =
        `Current stock of ${data.stock} units at ${data.demandRate} units/day`;

    const reorderText = data.reorderQty > 0
        ? `${data.reorderQty} units`
        : 'No reorder needed';
    document.getElementById('reorderValue').textContent = reorderText;
    document.getElementById('reorderDetail').textContent = data.reorderQty > 0
        ? `Reorder within ${data.reorderInDays} days (Lead time: ${data.lead}d)`
        : `Stock sufficient for current demand cycle`;

    // Risk card
    const riskLabels = { critical: 'High Risk', warning: 'Medium Risk', safe: 'Low Risk' };
    const riskIcons  = { critical: '🔴', warning: '🟡', safe: '🟢' };
    document.getElementById('riskValue').textContent = riskLabels[data.riskLevel];
    document.getElementById('riskDetail').textContent = data.riskMessage;

    // Risk banner
    const banner = document.getElementById('riskBanner');
    banner.className = 'risk-banner'; // reset
    const bannerIcon = document.getElementById('bannerIcon');
    const bannerTitle = document.getElementById('bannerTitle');
    const bannerText  = document.getElementById('bannerText');

    if (data.riskLevel === 'critical') {
        banner.classList.add('critical-level');
        bannerIcon.textContent = '🚨';
        bannerTitle.textContent = '⚠️ High Risk of Stockout';
        bannerText.textContent = data.riskMessage;
    } else if (data.riskLevel === 'warning') {
        banner.classList.add('warning-level');
        bannerIcon.textContent = '⚠️';
        bannerTitle.textContent = 'Moderate Stockout Risk';
        bannerText.textContent = data.riskMessage;
    } else {
        banner.classList.add('safe-level');
        bannerIcon.textContent = '✅';
        bannerTitle.textContent = 'Stock Levels Healthy';
        bannerText.textContent = data.riskMessage;
    }

    // Summary table
    const statusBadge = `<span class="status-badge ${data.riskLevel}">${riskIcons[data.riskLevel]} ${riskLabels[data.riskLevel]}</span>`;
    document.getElementById('summaryBody').innerHTML = `
        <tr>
            <td>${data.product}</td>
            <td>${data.stock}</td>
            <td>${data.threshold}</td>
            <td>${data.predictedDemand}</td>
            <td>${data.stockDuration}d</td>
            <td>${data.reorderQty > 0 ? data.reorderQty + ' units' : '—'}</td>
            <td>${statusBadge}</td>
        </tr>
    `;

    // Draw chart
    drawChart(data);

    // Reveal results section
    resultsSection.classList.remove('visible');
    void resultsSection.offsetWidth; // force reflow for re-animation
    resultsSection.classList.add('visible');
}


// ========================= //
// CANVAS CHART (Projected Stock & Demand)
// ========================= //
function drawChart(data) {
    const canvas = document.getElementById('chartCanvas');
    const ctx = canvas.getContext('2d');

    // Hi-DPI support
    const rect = canvas.parentElement.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    canvas.style.width = rect.width + 'px';
    canvas.style.height = rect.height + 'px';

    const W = rect.width;
    const H = rect.height;
    const padding = { top: 30, right: 30, bottom: 40, left: 50 };
    const plotW = W - padding.left - padding.right;
    const plotH = H - padding.top - padding.bottom;

    ctx.clearRect(0, 0, W, H);

    // Generate data points
    const days = data.range;
    const stockLine = [];
    const demandLine = [];
    const thresholdLine = [];

    let currentStock = data.stock;
    let cumulativeDemand = 0;

    for (let d = 0; d <= days; d++) {
        stockLine.push(Math.max(0, currentStock));
        demandLine.push(cumulativeDemand);
        thresholdLine.push(data.threshold);

        // Daily variation ±30%
        const dailyDemand = data.demandRate * (0.7 + Math.random() * 0.6);
        currentStock -= dailyDemand;
        cumulativeDemand += dailyDemand;

        // Simulate a restock event mid-cycle
        if (currentStock <= data.threshold * 0.5 && d === Math.round(days * 0.4)) {
            currentStock += data.reorderQty * 0.8;
        }
    }

    const allValues = [...stockLine, ...demandLine, ...thresholdLine];
    const maxVal = Math.max(...allValues) * 1.15;

    // Helper: data → canvas coords
    function toX(i) { return padding.left + (i / days) * plotW; }
    function toY(v) { return padding.top + plotH - (v / maxVal) * plotH; }

    // Grid lines
    ctx.strokeStyle = 'rgba(255,255,255,0.04)';
    ctx.lineWidth = 1;
    const gridSteps = 5;
    for (let i = 0; i <= gridSteps; i++) {
        const y = padding.top + (plotH / gridSteps) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(W - padding.right, y);
        ctx.stroke();

        // Y-axis labels
        const val = Math.round(maxVal - (maxVal / gridSteps) * i);
        ctx.fillStyle = '#64748b';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(val, padding.left - 8, y + 4);
    }

    // X-axis labels
    const xLabelCount = Math.min(days, 6);
    ctx.textAlign = 'center';
    for (let i = 0; i <= xLabelCount; i++) {
        const dayIdx = Math.round((days / xLabelCount) * i);
        const x = toX(dayIdx);
        ctx.fillStyle = '#64748b';
        ctx.font = '11px Inter, sans-serif';
        ctx.fillText(`Day ${dayIdx}`, x, H - padding.bottom + 24);
    }

    // --- Draw Threshold line (dashed) ---
    ctx.setLineDash([6, 4]);
    ctx.strokeStyle = 'rgba(239, 68, 68, 0.5)';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(toX(0), toY(data.threshold));
    ctx.lineTo(toX(days), toY(data.threshold));
    ctx.stroke();
    ctx.setLineDash([]);

    // Threshold label
    ctx.fillStyle = '#fca5a5';
    ctx.font = '10px Inter, sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText('Min Threshold', toX(days) - 80, toY(data.threshold) - 6);

    // --- Draw Stock line with gradient fill ---
    ctx.beginPath();
    ctx.moveTo(toX(0), toY(stockLine[0]));
    for (let i = 1; i <= days; i++) {
        const cx = (toX(i - 1) + toX(i)) / 2;
        ctx.quadraticCurveTo(toX(i - 1), toY(stockLine[i - 1]), cx, (toY(stockLine[i - 1]) + toY(stockLine[i])) / 2);
    }
    ctx.lineTo(toX(days), toY(stockLine[days]));
    ctx.strokeStyle = '#3b82f6';
    ctx.lineWidth = 2.5;
    ctx.stroke();

    // Fill under stock line
    ctx.lineTo(toX(days), toY(0));
    ctx.lineTo(toX(0), toY(0));
    ctx.closePath();
    const stockGrad = ctx.createLinearGradient(0, padding.top, 0, H - padding.bottom);
    stockGrad.addColorStop(0, 'rgba(59, 130, 246, 0.15)');
    stockGrad.addColorStop(1, 'rgba(59, 130, 246, 0.01)');
    ctx.fillStyle = stockGrad;
    ctx.fill();

    // --- Draw Demand line with gradient fill ---
    ctx.beginPath();
    ctx.moveTo(toX(0), toY(demandLine[0]));
    for (let i = 1; i <= days; i++) {
        const cx = (toX(i - 1) + toX(i)) / 2;
        ctx.quadraticCurveTo(toX(i - 1), toY(demandLine[i - 1]), cx, (toY(demandLine[i - 1]) + toY(demandLine[i])) / 2);
    }
    ctx.lineTo(toX(days), toY(demandLine[days]));
    ctx.strokeStyle = '#8b5cf6';
    ctx.lineWidth = 2.5;
    ctx.stroke();

    // Fill under demand line
    ctx.lineTo(toX(days), toY(0));
    ctx.lineTo(toX(0), toY(0));
    ctx.closePath();
    const demandGrad = ctx.createLinearGradient(0, padding.top, 0, H - padding.bottom);
    demandGrad.addColorStop(0, 'rgba(139, 92, 246, 0.12)');
    demandGrad.addColorStop(1, 'rgba(139, 92, 246, 0.01)');
    ctx.fillStyle = demandGrad;
    ctx.fill();

    // --- Draw Data Points ---
    const pointInterval = Math.max(1, Math.floor(days / 10));
    for (let i = 0; i <= days; i += pointInterval) {
        // Stock points
        ctx.beginPath();
        ctx.arc(toX(i), toY(stockLine[i]), 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#3b82f6';
        ctx.fill();
        ctx.strokeStyle = 'rgba(59, 130, 246, 0.3)';
        ctx.lineWidth = 4;
        ctx.stroke();

        // Demand points
        ctx.beginPath();
        ctx.arc(toX(i), toY(demandLine[i]), 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#8b5cf6';
        ctx.fill();
        ctx.strokeStyle = 'rgba(139, 92, 246, 0.3)';
        ctx.lineWidth = 4;
        ctx.stroke();
    }
}


// ========================= //
// RESIZE HANDLER (Redraw chart)
// ========================= //
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (resultsSection.classList.contains('visible')) {
            const data = getLastInsight();
            if (data) drawChart(data);
        }
    }, 250);
});

// Store latest insight data for resize redraws
let lastInsightData = null;
const _origRender = renderResults;
renderResults = function(data) {
    lastInsightData = data;
    _origRender(data);
};
function getLastInsight() { return lastInsightData; }
