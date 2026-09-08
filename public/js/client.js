// Client Dashboard Scripts
function toggleClientQr() {
    const qrDisplay = document.getElementById('client-qr-box');
    const toggleBtn = document.getElementById('qr-toggle-btn');
    
    if (!qrDisplay) return;

    if (qrDisplay.classList.contains('show')) {
        qrDisplay.classList.remove('show');
        if (toggleBtn) toggleBtn.innerHTML = '<span>👁</span> Show QR Code';
    } else {
        qrDisplay.classList.add('show');
        if (toggleBtn) toggleBtn.innerHTML = '<span>✕</span> Hide QR Code';
    }
}

// Savings interest preview calculator (60-day 10% daily payout)
function previewSavingsCalculation() {
    const depositInput = document.getElementById('savings_deposit_amount');
    const dailyInterestSpan = document.getElementById('preview_daily_interest');
    const totalInterestSpan = document.getElementById('preview_total_interest');
    const totalMaturitySpan = document.getElementById('preview_total_maturity');

    if (!depositInput) return;

    const deposit = parseFloat(depositInput.value) || 0;
    if (deposit <= 0) {
        if (dailyInterestSpan) dailyInterestSpan.innerText = '₱0.00 / day';
        if (totalInterestSpan) totalInterestSpan.innerText = '₱0.00';
        if (totalMaturitySpan) totalMaturitySpan.innerText = '₱0.00';
        return;
    }

    const totalInterest = deposit * 0.10; // 10%
    const dailyInterest = totalInterest / 60;
    const totalMaturity = deposit + totalInterest;

    if (dailyInterestSpan) dailyInterestSpan.innerText = formatMoney(dailyInterest) + ' / day';
    if (totalInterestSpan) totalInterestSpan.innerText = formatMoney(totalInterest);
    if (totalMaturitySpan) totalMaturitySpan.innerText = formatMoney(totalMaturity);
}
