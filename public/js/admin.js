// Admin Encoder & Releasing Officer Scripts

// Dynamic Loan Schedule Calculator for Encoder
function calculateLoanSchedule() {
    const principalInput = document.getElementById('principal_amount');
    const interestInput = document.getElementById('interest_rate_percent');
    const termDaysElem = document.getElementById('loan_term_days_display');
    const totalPayableDisplay = document.getElementById('total_payable_display');
    const dailyInstallmentDisplay = document.getElementById('daily_installment_display');
    const schedulePreviewTable = document.getElementById('schedule_preview_body');

    if (!principalInput || !interestInput) return;

    const principal = parseFloat(principalInput.value) || 0;
    const interestPercent = parseFloat(interestInput.value) || 0;
    const termDays = termDaysElem ? parseInt(termDaysElem.getAttribute('data-term-days')) || 60 : 60;

    function setDisplay(elem, text) {
        if (!elem) return;
        if (elem.tagName === 'INPUT') {
            elem.value = text;
        } else {
            elem.innerText = text;
        }
    }

    if (principal <= 0) {
        setDisplay(totalPayableDisplay, '₱0.00');
        setDisplay(dailyInstallmentDisplay, '₱0.00 / day');
        if (schedulePreviewTable) schedulePreviewTable.innerHTML = `<tr><td colspan="4" class="text-center">Enter loan amount to preview ${termDays}-day schedule</td></tr>`;
        return;
    }

    const interestAmount = principal * (interestPercent / 100);
    const totalPayable = principal + interestAmount;
    const dailyInstallment = (totalPayable / termDays);

    setDisplay(totalPayableDisplay, formatMoney(totalPayable));
    setDisplay(dailyInstallmentDisplay, formatMoney(dailyInstallment) + (dailyInstallmentDisplay.tagName === 'INPUT' ? '' : ' / day'));

    if (schedulePreviewTable) {
        let rows = '';
        let today = new Date();
        for (let i = 1; i <= termDays; i++) {
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

// ----------------------------------------------------
// Enhanced Mobile-First Camera & Photo Proof System for Releasing Officer
// ----------------------------------------------------
let currentVideoStream = null;
let releasingFacingMode = "environment"; // default rear camera
let isReleasingTorchOn = false;

async function startCamera(videoElementId) {
    const video = document.getElementById(videoElementId);
    if (!video) return;

    stopCamera();

    const videoContainer = document.getElementById('camera-stream-wrapper');
    const torchBtn = document.getElementById('releasing-torch-btn');

    try {
        currentVideoStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1920, min: 640 },
                height: { ideal: 1080, min: 480 },
                facingMode: { ideal: releasingFacingMode }
            },
            audio: false
        });

        video.srcObject = currentVideoStream;
        await video.play();

        if (videoContainer) videoContainer.style.display = 'block';

        // Check if Torch is supported by the active video track
        try {
            const track = currentVideoStream.getVideoTracks()[0];
            const capabilities = track ? track.getCapabilities() : {};
            if (torchBtn && capabilities.torch) {
                torchBtn.style.display = 'inline-flex';
            } else if (torchBtn) {
                torchBtn.style.display = 'none';
            }
        } catch (e) {
            if (torchBtn) torchBtn.style.display = 'none';
        }

    } catch (err) {
        console.error("Camera access error:", err);
        showToast('error', 'Camera access failed. Please ensure camera permissions are allowed, or use "Take Photo via Native App".');
    }
}

function stopCamera() {
    if (currentVideoStream) {
        currentVideoStream.getTracks().forEach(track => track.stop());
        currentVideoStream = null;
    }
    isReleasingTorchOn = false;
    const torchBtn = document.getElementById('releasing-torch-btn');
    if (torchBtn) {
        torchBtn.classList.remove('active');
        torchBtn.innerHTML = '💡 Flash';
    }
}

async function flipReleasingCamera(videoElementId) {
    releasingFacingMode = (releasingFacingMode === "environment") ? "user" : "environment";
    await startCamera(videoElementId);
}

async function toggleReleasingTorch() {
    if (!currentVideoStream) return;

    try {
        const track = currentVideoStream.getVideoTracks()[0];
        if (!track) return;

        isReleasingTorchOn = !isReleasingTorchOn;
        await track.applyConstraints({
            advanced: [{ torch: isReleasingTorchOn }]
        });

        const torchBtn = document.getElementById('releasing-torch-btn');
        if (torchBtn) {
            torchBtn.classList.toggle('active', isReleasingTorchOn);
            torchBtn.innerHTML = isReleasingTorchOn ? '⚡ Flash ON' : '💡 Flash';
        }
    } catch (e) {
        console.warn("Torch failed on releasing camera:", e);
    }
}

// Burn official Core Lending timestamp watermark onto canvas (Mobile-responsive adaptive layout)
function applyTimestampWatermark(ctx, width, height) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

    // Adaptive resolution scaling (calibrated against 640px base width)
    const scale = Math.max(0.75, Math.min(2.5, width / 640));

    const badgeText = '✓ VERIFIED TRANSACTION PROOF';
    const timeText = `${dateStr} • ${timeStr}`;
    const brandText = 'Core Lending Official Proof';
    const singleLineFullText = `${timeText}  |  ${brandText}`;

    const badgeFontSize = Math.max(11, Math.round(13 * scale));
    const timeFontSize = Math.max(10, Math.round(12 * scale));

    const badgeFont = `700 ${badgeFontSize}px "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`;
    const timeFont = `600 ${timeFontSize}px "Plus Jakarta Sans", "SF Pro Text", -apple-system, monospace`;

    // Measure text dimensions
    ctx.save();
    ctx.font = badgeFont;
    const badgeWidth = ctx.measureText(badgeText).width;

    ctx.font = timeFont;
    const fullTextWidth = ctx.measureText(singleLineFullText).width;
    const paddingX = Math.max(12, Math.round(16 * scale));

    // Determine if layout should be stacked (mobile portrait / narrow widths)
    const totalSingleLineWidth = badgeWidth + fullTextWidth + (paddingX * 3);
    const isStacked = width < totalSingleLineWidth || width < 580;

    let barHeight;
    if (isStacked) {
        barHeight = Math.max(56, Math.round(54 * scale));
    } else {
        barHeight = Math.max(44, Math.round(42 * scale));
    }

    const barY = height - barHeight;

    // 1. Sleek semi-transparent dark gradient background bar
    const grad = ctx.createLinearGradient(0, barY, 0, height);
    grad.addColorStop(0, 'rgba(8, 20, 39, 0.94)');
    grad.addColorStop(1, 'rgba(3, 10, 20, 0.98)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, barY, width, barHeight);

    // 2. High-contrast gradient accent line (Emerald to Cyan)
    const lineGrad = ctx.createLinearGradient(0, barY, width, barY);
    lineGrad.addColorStop(0, '#10b981');
    lineGrad.addColorStop(0.5, '#06b6d4');
    lineGrad.addColorStop(1, '#10b981');
    ctx.fillStyle = lineGrad;
    ctx.fillRect(0, barY, width, Math.max(2.5, Math.round(3 * scale)));

    // 3. Render watermark text
    if (isStacked) {
        // --- STACKED 2-ROW LAYOUT (Mobile Responsive) ---
        const row1Y = barY + Math.round(barHeight * 0.40);
        const row2Y = barY + Math.round(barHeight * 0.80);

        // Row 1: Pill Badge
        const pillHeight = Math.round(badgeFontSize * 1.5);
        const pillWidth = Math.min(badgeWidth + Math.round(14 * scale), width - (paddingX * 2));
        const pillY = row1Y - Math.round(badgeFontSize * 0.92);
        const pillRadius = Math.round(4 * scale);

        ctx.fillStyle = 'rgba(16, 185, 129, 0.18)';
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.5)';
        ctx.lineWidth = Math.max(1, Math.round(1 * scale));

        if (typeof ctx.roundRect === 'function') {
            ctx.beginPath();
            ctx.roundRect(paddingX, pillY, pillWidth, pillHeight, pillRadius);
            ctx.fill();
            ctx.stroke();
        } else {
            ctx.fillRect(paddingX, pillY, pillWidth, pillHeight);
        }

        ctx.font = badgeFont;
        ctx.fillStyle = '#34d399';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText(badgeText, paddingX + Math.round(7 * scale), row1Y);

        // Row 2: Clean High-Contrast Timestamp & Branding
        ctx.font = timeFont;
        ctx.fillStyle = '#f8fafc';

        let row2Text = `${timeText}  •  ${brandText}`;
        if (ctx.measureText(row2Text).width > width - (paddingX * 2)) {
            row2Text = `${timeText} • Core Lending`;
        }
        if (ctx.measureText(row2Text).width > width - (paddingX * 2)) {
            row2Text = timeText;
        }

        ctx.fillText(row2Text, paddingX, row2Y);
    } else {
        // --- SINGLE-ROW LAYOUT (Desktop / Wide Screen) ---
        const centerY = barY + Math.round(barHeight * 0.62);

        // Left Pill Badge
        const pillHeight = Math.round(badgeFontSize * 1.55);
        const pillWidth = badgeWidth + Math.round(16 * scale);
        const pillY = centerY - Math.round(badgeFontSize * 0.95);
        const pillRadius = Math.round(4 * scale);

        ctx.fillStyle = 'rgba(16, 185, 129, 0.18)';
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.5)';
        ctx.lineWidth = Math.max(1, Math.round(1 * scale));

        if (typeof ctx.roundRect === 'function') {
            ctx.beginPath();
            ctx.roundRect(paddingX, pillY, pillWidth, pillHeight, pillRadius);
            ctx.fill();
            ctx.stroke();
        } else {
            ctx.fillRect(paddingX, pillY, pillWidth, pillHeight);
        }

        ctx.font = badgeFont;
        ctx.fillStyle = '#34d399';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText(badgeText, paddingX + Math.round(8 * scale), centerY);

        // Right Timestamp & Branding
        ctx.font = timeFont;
        ctx.fillStyle = '#f8fafc';
        const rightTextWidth = ctx.measureText(singleLineFullText).width;
        const rightX = width - rightTextWidth - paddingX;
        ctx.fillText(singleLineFullText, rightX, centerY);
    }

    ctx.restore();
}

// Live Camera Snapshot Capture
function captureSnapshotWithTimestamp(videoId, canvasId, outputInputId, previewImgId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const outputInput = document.getElementById(outputInputId);
    const previewImg = document.getElementById(previewImgId);
    const cameraWrapper = document.getElementById('camera-stream-wrapper');
    const previewWrapper = document.getElementById('photo-preview-wrapper');

    if (!video || !canvas) return;

    const width = video.videoWidth || 1280;
    const height = video.videoHeight || 720;

    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    // Draw frame
    ctx.drawImage(video, 0, 0, width, height);

    // Add Timestamp Watermark
    applyTimestampWatermark(ctx, width, height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

    if (outputInput) outputInput.value = dataUrl;
    if (previewImg) {
        previewImg.src = dataUrl;
    }

    if (cameraWrapper) cameraWrapper.style.display = 'none';
    if (previewWrapper) previewWrapper.style.display = 'block';

    stopCamera();
    showToast('success', 'Photo proof captured with official timestamp!');
}

// Native Mobile Camera / Gallery Photo Fallback
function processUploadedProofImage(fileInput, canvasId, outputInputId, previewImgId) {
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
            const canvas = document.getElementById(canvasId);
            const outputInput = document.getElementById(outputInputId);
            const previewImg = document.getElementById(previewImgId);
            const cameraWrapper = document.getElementById('camera-stream-wrapper');
            const previewWrapper = document.getElementById('photo-preview-wrapper');

            if (!canvas) return;

            // Maintain max 1920px bounds
            let width = img.width;
            let height = img.height;
            const maxDim = 1920;

            if (width > maxDim || height > maxDim) {
                if (width > height) {
                    height = Math.round((height * maxDim) / width);
                    width = maxDim;
                } else {
                    width = Math.round((width * maxDim) / height);
                    height = maxDim;
                }
            }

            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');

            ctx.drawImage(img, 0, 0, width, height);
            applyTimestampWatermark(ctx, width, height);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

            if (outputInput) outputInput.value = dataUrl;
            if (previewImg) previewImg.src = dataUrl;

            if (cameraWrapper) cameraWrapper.style.display = 'none';
            if (previewWrapper) previewWrapper.style.display = 'block';

            stopCamera();
            showToast('success', 'Photo uploaded and stamped with official timestamp!');
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// Retake photo: discards captured image and restarts live camera
function retakeReleasingPhoto(videoElementId, previewImgId, outputInputId) {
    const previewWrapper = document.getElementById('photo-preview-wrapper');
    const cameraWrapper = document.getElementById('camera-stream-wrapper');
    const outputInput = document.getElementById(outputInputId);
    const previewImg = document.getElementById(previewImgId);

    if (outputInput) outputInput.value = '';
    if (previewImg) previewImg.src = '';

    if (previewWrapper) previewWrapper.style.display = 'none';
    if (cameraWrapper) cameraWrapper.style.display = 'block';

    startCamera(videoElementId);
}
