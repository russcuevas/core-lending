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

// Savings interest preview calculator (dynamic interest % and lock-in days)
function previewSavingsCalculation() {
    const depositInput = document.getElementById('savings_deposit_amount');
    const dailyInterestSpan = document.getElementById('preview_daily_interest');
    const totalInterestSpan = document.getElementById('preview_total_interest');
    const totalMaturitySpan = document.getElementById('preview_total_maturity');

    if (!depositInput) return;

    const deposit = parseFloat(depositInput.value) || 0;
    const ratePercent = parseFloat(depositInput.getAttribute('data-rate')) || 10;
    const lockInDays = parseInt(depositInput.getAttribute('data-days')) || 60;

    if (deposit <= 0) {
        if (dailyInterestSpan) dailyInterestSpan.innerText = '₱0.00 / day';
        if (totalInterestSpan) totalInterestSpan.innerText = '₱0.00';
        if (totalMaturitySpan) totalMaturitySpan.innerText = '₱0.00';
        return;
    }

    const totalInterest = deposit * (ratePercent / 100);
    const dailyInterest = totalInterest / lockInDays;
    const totalMaturity = deposit + totalInterest;

    if (dailyInterestSpan) dailyInterestSpan.innerText = formatMoney(dailyInterest) + ' / day';
    if (totalInterestSpan) totalInterestSpan.innerText = formatMoney(totalInterest);
    if (totalMaturitySpan) totalMaturitySpan.innerText = formatMoney(totalMaturity);
}
