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

// Burn official Core Lending timestamp watermark onto canvas
function applyTimestampWatermark(ctx, width, height) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const fullTimestamp = `${dateStr} • ${timeStr} | Core Lending Official Disbursement Proof`;

    const barHeight = Math.max(50, Math.floor(height * 0.08));

    // Dark gradient overlay footer
    ctx.fillStyle = 'rgba(9, 30, 58, 0.92)';
    ctx.fillRect(0, height - barHeight, width, barHeight);

    // Green top accent line
    ctx.fillStyle = '#10b981';
    ctx.fillRect(0, height - barHeight, width, 3);

    // Text details
    const fontSize = Math.max(13, Math.floor(barHeight * 0.32));
    ctx.font = `bold ${fontSize}px "Plus Jakarta Sans", -apple-system, sans-serif`;
    ctx.fillStyle = '#10b981';
    ctx.fillText('✓ VERIFIED TRANSACTION PROOF', 18, height - Math.floor(barHeight * 0.38));

    ctx.font = `${Math.max(11, fontSize - 2)}px monospace`;
    ctx.fillStyle = '#ffffff';
    const textWidth = ctx.measureText(fullTimestamp).width;
    const xPos = Math.max(width - textWidth - 18, 18);
    ctx.fillText(fullTimestamp, xPos, height - Math.floor(barHeight * 0.38));
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

    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
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
