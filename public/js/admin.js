// Admin Encoder & Releasing Officer Scripts

// Dynamic 60-Day Loan Schedule Calculator for Encoder
function calculateLoanSchedule() {
    const principalInput = document.getElementById('principal_amount');
    const interestInput = document.getElementById('interest_rate_percent');
    const totalPayableDisplay = document.getElementById('total_payable_display');
    const dailyInstallmentDisplay = document.getElementById('daily_installment_display');
    const schedulePreviewTable = document.getElementById('schedule_preview_body');

    if (!principalInput || !interestInput) return;

    const principal = parseFloat(principalInput.value) || 0;
    const interestPercent = parseFloat(interestInput.value) || 10;
    
    if (principal <= 0) {
        if (totalPayableDisplay) totalPayableDisplay.value = '₱0.00';
        if (dailyInstallmentDisplay) dailyInstallmentDisplay.value = '₱0.00';
        if (schedulePreviewTable) schedulePreviewTable.innerHTML = '<tr><td colspan="4" class="text-center">Enter loan amount to preview schedule</td></tr>';
        return;
    }

    const interestAmount = principal * (interestPercent / 100);
    const totalPayable = principal + interestAmount;
    const dailyInstallment = (totalPayable / 60);

    if (totalPayableDisplay) totalPayableDisplay.value = formatMoney(totalPayable);
    if (dailyInstallmentDisplay) dailyInstallmentDisplay.value = formatMoney(dailyInstallment);

    if (schedulePreviewTable) {
        let rows = '';
        let today = new Date();
        for (let i = 1; i <= 60; i++) {
            let dueDate = new Date();
            dueDate.setDate(today.getDate() + i);
            let dateStr = dueDate.toISOString().split('T')[0];

            rows += `<tr>
                <td>Day ${i}</td>
                <td>${dateStr}</td>
                <td>${formatMoney(dailyInstallment)}</td>
                <td><span class="badge badge-amber">Scheduled</span></td>
            </tr>`;
        }
        schedulePreviewTable.innerHTML = rows;
    }
}

// Camera Capture with Dynamic Live Timestamp Overlay for Releasing Officer
let currentVideoStream = null;

async function startCamera(videoElementId) {
    const video = document.getElementById(videoElementId);
    if (!video) return;

    try {
        currentVideoStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' }
        });
        video.srcObject = currentVideoStream;
        video.play();
    } catch (err) {
        console.error("Camera access error:", err);
        showToast('error', 'Unable to access camera. Please check camera permissions.');
    }
}

function stopCamera() {
    if (currentVideoStream) {
        currentVideoStream.getTracks().forEach(track => track.stop());
        currentVideoStream = null;
    }
}

function captureSnapshotWithTimestamp(videoId, canvasId, outputInputId, previewImgId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const outputInput = document.getElementById(outputInputId);
    const previewImg = document.getElementById(previewImgId);

    if (!video || !canvas) return;

    const width = video.videoWidth || 640;
    const height = video.videoHeight || 480;

    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    // Draw camera image
    ctx.drawImage(video, 0, 0, width, height);

    // Add Timestamp & Core Lending Watermark Overlay
    const now = new Date();
    const timestampStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString() + ' | Core Lending Official Proof';

    // Midnight navy translucent background bar
    ctx.fillStyle = 'rgba(9, 30, 58, 0.88)';
    ctx.fillRect(0, height - 45, width, 45);

    // Text overlay
    ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
    ctx.fillStyle = '#10b981';
    ctx.fillText('✓ VERIFIED TRANSACTION PROOF', 20, height - 24);

    ctx.font = '14px monospace';
    ctx.fillStyle = '#ffffff';
    ctx.fillText(timestampStr, width - 420, height - 24);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

    if (outputInput) outputInput.value = dataUrl;
    if (previewImg) {
        previewImg.src = dataUrl;
        previewImg.style.display = 'block';
    }

    stopCamera();
    showToast('success', 'Photo proof captured with official timestamp!');
}
