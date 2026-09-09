// Collector Dashboard Scripts - Enhanced Mobile-First QR Scanner & Payment Handling

let html5QrCode = null;
let currentFacingMode = "environment"; // "environment" (rear) or "user" (front)
let isTorchOn = false;
let isScannerRunning = false;
let audioContext = null;

// Play subtle success chime using Web Audio API (no external asset needed)
function playScanSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        if (!audioContext) audioContext = new AudioCtx();
        if (audioContext.state === 'suspended') audioContext.resume();

        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        osc.connect(gain);
        gain.connect(audioContext.destination);

        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioContext.currentTime); // A5
        osc.frequency.setValueAtTime(1760, audioContext.currentTime + 0.08); // A6
        gain.gain.setValueAtTime(0.2, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.22);

        osc.start();
        osc.stop(audioContext.currentTime + 0.22);
    } catch (e) {
        console.warn("Audio playback not allowed:", e);
    }
}

// Vibrate device if supported
function vibrateDevice() {
    if (navigator.vibrate) {
        navigator.vibrate([80, 40, 80]);
    }
}

function initQrScanner(onSuccessCallback) {
    const readerElem = document.getElementById('qr-reader-container');
    if (!readerElem) return;

    if (typeof Html5Qrcode === 'undefined') {
        console.error("Html5Qrcode library not loaded.");
        return;
    }

    if (html5QrCode && isScannerRunning) {
        return;
    }

    html5QrCode = new Html5Qrcode("qr-reader-container");

    const config = {
        fps: 15,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            const edgeSize = Math.floor(minEdge * 0.72);
            return { width: Math.max(edgeSize, 200), height: Math.max(edgeSize, 200) };
        },
        aspectRatio: 1.0,
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        }
    };

    startScanning(config, onSuccessCallback);
}

function startScanning(config, onSuccessCallback) {
    const statusMsg = document.getElementById('scanner-status-msg');
    const laser = document.getElementById('scanner-laser-line');
    const torchBtn = document.getElementById('scanner-torch-btn');

    if (statusMsg) statusMsg.innerText = "Camera initializing...";

    html5QrCode.start(
        { facingMode: currentFacingMode },
        config,
        (decodedText) => {
            handleScanSuccess(decodedText, onSuccessCallback);
        },
        (errorMessage) => {
            // Frame error - ignore
        }
    ).then(() => {
        isScannerRunning = true;
        if (statusMsg) statusMsg.innerText = "Align Client QR code within the box";
        if (laser) laser.style.display = 'block';

        // Check if torch/flash is supported
        try {
            const track = html5QrCode.getRunningTrackCameraCapabilities();
            if (torchBtn && track && track.torchFeature && track.torchFeature().isSupported()) {
                torchBtn.style.display = 'inline-flex';
            } else if (torchBtn) {
                torchBtn.style.display = 'none';
            }
        } catch (e) {
            if (torchBtn) torchBtn.style.display = 'none';
        }
    }).catch((err) => {
        console.warn("Error starting scanner with facingMode:", err);
        if (statusMsg) {
            statusMsg.innerHTML = `<span style="color: #ef4444; font-weight: 600;">⚠ Camera permission required or camera in use.</span><br><small>You can also use "Upload / Take Photo" or select client manually below.</small>`;
        }
    });
}

function handleScanSuccess(decodedText, onSuccessCallback) {
    if (!isScannerRunning) return;
    isScannerRunning = false;

    playScanSound();
    vibrateDevice();

    const hudBox = document.getElementById('scanner-hud-box');
    const statusMsg = document.getElementById('scanner-status-msg');
    const laser = document.getElementById('scanner-laser-line');

    if (hudBox) hudBox.classList.add('scan-success-pulse');
    if (laser) laser.style.display = 'none';
    if (statusMsg) {
        statusMsg.innerHTML = `<span style="color: #10b981; font-weight: 700;">✓ QR Code Verified! Opening payment...</span>`;
    }

    // Stop scanner track
    if (html5QrCode) {
        html5QrCode.stop().catch(e => console.warn(e));
    }

    setTimeout(() => {
        if (typeof onSuccessCallback === 'function') {
            onSuccessCallback(decodedText);
        } else {
            window.location.href = `/collector/payments/collect?qr_token=${encodeURIComponent(decodedText)}`;
        }
    }, 450);
}

// Flip between Front and Rear camera
function toggleCameraFacing(onSuccessCallback) {
    if (!html5QrCode || !isScannerRunning) return;

    currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
    const statusMsg = document.getElementById('scanner-status-msg');
    if (statusMsg) statusMsg.innerText = "Switching camera...";

    html5QrCode.stop().then(() => {
        isScannerRunning = false;
        isTorchOn = false;
        initQrScanner(onSuccessCallback);
    }).catch(err => {
        console.error("Failed to stop scanner on flip:", err);
    });
}

// Toggle Flashlight / Torch if supported on mobile
async function toggleScannerTorch() {
    if (!html5QrCode || !isScannerRunning) return;

    try {
        isTorchOn = !isTorchOn;
        await html5QrCode.applyVideoConstraints({
            advanced: [{ torch: isTorchOn }]
        });
        const torchBtn = document.getElementById('scanner-torch-btn');
        if (torchBtn) {
            torchBtn.classList.toggle('active', isTorchOn);
            torchBtn.innerHTML = isTorchOn ? '⚡ Torch ON' : '💡 Torch';
        }
    } catch (e) {
        console.warn("Torch constraint not applied:", e);
    }
}

// Scan from an uploaded image file or native photo capture
function scanQrFromImageFile(fileInput, onSuccessCallback) {
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;

    const file = fileInput.files[0];
    const statusMsg = document.getElementById('scanner-status-msg');
    if (statusMsg) statusMsg.innerText = "Analyzing uploaded photo...";

    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("qr-reader-container");
    }

    // If scanner is actively running video, stop it first
    const stopPromise = isScannerRunning ? html5QrCode.stop() : Promise.resolve();

    stopPromise.then(() => {
        isScannerRunning = false;
        return html5QrCode.scanFile(file, true);
    }).then(decodedText => {
        handleScanSuccess(decodedText, onSuccessCallback);
    }).catch(err => {
        console.warn("Could not find QR code in image:", err);
        if (statusMsg) {
            statusMsg.innerHTML = `<span style="color: #ef4444; font-weight: 600;">⚠ No valid Core Lending QR code found in photo. Please try again or select client manually.</span>`;
        }
    });
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
