function switchTab(tabId) {
    // Update Buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    // Update Forms
    document.getElementById('form_stock_in').style.display = 'none';
    document.getElementById('form_stock_out').style.display = 'none';
    
    document.getElementById('form_' + tabId).style.display = 'flex';
}