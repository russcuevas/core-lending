// Collector Dashboard Scripts - QR Scanner & Payment Handling

let html5QrCodeScanner = null;

function initQrScanner(onSuccessCallback) {
    const scannerElement = document.getElementById('reader');
    if (!scannerElement) return;

    if (typeof Html5QrcodeScanner !== 'undefined') {
        html5QrCodeScanner = new Html5QrcodeScanner("reader", {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true
        });

        html5QrCodeScanner.render(
            (decodedText, decodedResult) => {
                if (html5QrCodeScanner) {
                    html5QrCodeScanner.clear();
                }
                if (typeof onSuccessCallback === 'function') {
                    onSuccessCallback(decodedText);
                } else {
                    window.location.href = `/collector/payments/collect?qr_token=${encodeURIComponent(decodedText)}`;
                }
            },
            (error) => {
                // Ignore frame-by-frame scanning noise
            }
        );
    }
}

// Payment calculation helper (carry over vs overpayment adjustment)
function onPaymentAmountChange() {
    const amountInput = document.getElementById('payment_amount');
    const dailyDueSpan = document.getElementById('daily_installment_due');
    const resultHint = document.getElementById('payment_calculation_hint');
    const remainingBalanceSpan = document.getElementById('client_remaining_balance');

    if (!amountInput || !dailyDueSpan || !resultHint) return;

    const amountPaid = parseFloat(amountInput.value) || 0;
    const dailyDue = parseFloat(dailyDueSpan.dataset.due) || 0;
    const currentBalance = parseFloat(remainingBalanceSpan ? remainingBalanceSpan.dataset.balance : 0) || 0;

    if (amountPaid <= 0) {
        resultHint.innerHTML = '<span class="text-muted">Enter payment amount to compute.</span>';
        return;
    }

    if (amountPaid < dailyDue) {
        const deficit = dailyDue - amountPaid;
        resultHint.innerHTML = `<span style="color: #d97706; font-weight: 600;">⚠ Partial Payment: ${formatMoney(amountPaid)}. Deficit of ${formatMoney(deficit)} will be carried over to succeeding days.</span>`;
    } else if (amountPaid > dailyDue) {
        const excess = amountPaid - dailyDue;
        resultHint.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Advance / Overpayment: ${formatMoney(excess)} will be deducted directly from remaining principal balance.</span>`;
    } else {
        resultHint.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Exact Daily Installment Met: ${formatMoney(amountPaid)}.</span>`;
    }
}
