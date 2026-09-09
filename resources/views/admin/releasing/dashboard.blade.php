@extends('layouts.app')

@section('title', 'Releasing Officer Request Center')
@section('page_title', 'Admin Releasing Officer - Request & Disbursement Center')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
@endpush

@section('content')
    <!-- Messenger Notification Alert Box -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; border-radius: var(--radius-lg); padding: 16px 20px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-md); flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
            <div class="brand-icon" style="background: #059669; width: 38px; height: 38px; font-size: 18px;">⚡</div>
            <div>
                <h3 style="font-size: 15px; margin-bottom: 2px; color: white;">Real-Time Incoming Transaction Requests</h3>
                <p style="font-size: 12.5px; color: #94a3b8; margin: 0;">
                    You have <strong>{{ $pendingReviews->count() }}</strong> new requests to review and <strong>{{ $approvedReadyToRelease->count() + $loansReadyToDisburse->count() }}</strong> approved items ready for physical disbursement.
                </p>
            </div>
        </div>
        <div>
            <span class="badge badge-emerald" style="font-size: 11.5px; padding: 4px 10px;">Live Center Active</span>
        </div>
    </div>

    <!-- SECTION 1: Approved by Host - Ready for Physical Release (PIN & Camera Photo Proof Required) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="color: #059669;">
                <span>🎯</span> Ready for Physical Execution (Approved by Host) ({{ $approvedReadyToRelease->count() + $loansReadyToDisburse->count() }})
            </h3>
            <span class="badge badge-emerald">Requires Client PIN & Timestamp Photo</span>
        </div>

        @if($approvedReadyToRelease->isEmpty() && $loansReadyToDisburse->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                ✓ No approved items awaiting physical execution at this moment.
            </div>
        @else
            <!-- Wallet Cash In / Out execution cards -->
            @foreach($approvedReadyToRelease as $tx)
                <div class="releasing-request-card" style="border-left-color: #059669;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge {{ $tx->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}" style="margin-bottom: 4px;">
                                {{ strtoupper(str_replace('_', ' ', $tx->type)) }} APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0;">{{ $tx->user->name }} ({{ $tx->user->role }})</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Contact No: {{ $tx->user->phone_number }} | Address: {{ $tx->user->address }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: {{ $tx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                                ₱{{ number_format($tx->amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">Host Approved</div>
                        </div>
                    </div>

                    <div class="request-meta-grid">
                        <div class="request-item">
                            <span class="request-label">Scheduled Date:</span>
                            <span class="request-value">{{ $tx->releasing_scheduled_date ?? 'Today' }}</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Releasing Notes:</span>
                            <span class="request-value">{{ $tx->releasing_notes ?? 'None' }}</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Host Approver Notes:</span>
                            <span class="request-value">{{ $tx->host_notes ?? 'Approved' }}</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.requests.execute', $tx->id) }}', '{{ strtoupper(str_replace('_', ' ', $tx->type)) }}', '{{ $tx->user->name }}', '{{ number_format($tx->amount, 2) }}')">
                            📷 Verify PIN & Capture Photo Proof
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Loan Principal Releases -->
            @foreach($loansReadyToDisburse as $loan)
                <div class="releasing-request-card" style="border-left-color: #4f46e5;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge badge-indigo" style="margin-bottom: 4px;">
                                LOAN PRINCIPAL DISBURSEMENT APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0;">{{ $loan->client->user->name }}</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Contact No: {{ $loan->client->user->phone_number }} | Collector: {{ $loan->collector->user->name ?? 'None' }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: #4f46e5;">
                                ₱{{ number_format($loan->principal_amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">Principal Cash to Disburse</div>
                        </div>
                    </div>

                    <div class="request-meta-grid">
                        <div class="request-item">
                            <span class="request-label">Total Payable:</span>
                            <span class="request-value">₱{{ number_format($loan->total_payable, 2) }} (60 Days)</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Daily Installment:</span>
                            <span class="request-value">₱{{ number_format($loan->daily_installment, 2) }} / day</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Host Approver:</span>
                            <span class="request-value">{{ $loan->hostApprover->name ?? 'Superadmin' }}</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.loans.disburse', $loan->id) }}', 'LOAN PRINCIPAL DISBURSEMENT', '{{ $loan->client->user->name }}', '{{ number_format($loan->principal_amount, 2) }}')">
                            📷 Disburse Loan Funds & Capture Photo Proof
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- SECTION 2: Pending Initial Review Requests from Clients / Collectors -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>📩</span> Incoming Requests Waiting for Releasing Officer Review ({{ $pendingReviews->count() }})
            </h3>
            <span class="badge badge-amber">Step 1: Review & Submit to Host</span>
        </div>

        @if($pendingReviews->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                ✓ No new incoming requests.
            </div>
        @else
            @foreach($pendingReviews as $req)
                <div class="releasing-request-card urgent">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge {{ $req->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}">
                                {{ strtoupper(str_replace('_', ' ', $req->type)) }} REQUEST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0 2px 0;">{{ $req->user->name }}</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">
                                Role: {{ ucfirst($req->user->role) }} | Contact No: {{ $req->user->phone_number }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">
                                ₱{{ number_format($req->amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $req->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: var(--radius-sm); margin: 10px 0; font-size: 12.5px;">
                        <strong>User Remarks:</strong> {{ $req->releasing_notes ?? 'None provided' }}
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald btn-sm" onclick="openReviewModal('{{ route('admin.releasing.requests.review', $req->id) }}', 'approve', '{{ $req->user->name }}', '{{ number_format($req->amount, 2) }}')">
                            ✓ Approve & Submit to Host
                        </button>
                        <button type="button" class="btn btn-rose btn-sm" onclick="openReviewModal('{{ route('admin.releasing.requests.review', $req->id) }}', 'decline', '{{ $req->user->name }}', '{{ number_format($req->amount, 2) }}')">
                            ✕ Decline Request
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Review & Submit to Host Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="reviewModalTitle">Review Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
            </div>
            <form id="reviewForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="reviewAction">

                <div class="modal-body">
                    <div id="approveFields">
                        <div class="form-group">
                            <label class="form-label">Scheduled Target Release / Collection Date</label>
                            <input type="date" name="scheduled_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes for Superadmin / Host</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Client verified in person. Recommended for approval."></textarea>
                        </div>
                    </div>

                    <div id="declineFields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Reason for Decline *</label>
                            <textarea name="decline_reason" id="decline_reason" class="form-control" rows="3" placeholder="State reason to inform the client..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" id="reviewSubmitBtn">Submit to Host</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Physical Execution Modal with Live Camera & Date/Time Stamp Overlay -->
    <div class="modal-overlay" id="disburseModal">
        <div class="modal-box modal-lg" style="max-width: 620px;">
            <div class="modal-header">
                <h3 class="modal-title" id="disburseModalTitle">Execute Physical Transaction</h3>
                <button type="button" class="modal-close" onclick="closeDisburseModal()">&times;</button>
            </div>
            <form id="disburseForm" method="POST" onsubmit="return validateDisbursementForm()">
                @csrf
                <input type="hidden" name="photo_proof" id="photoProofInput" required>

                <div class="modal-body" style="padding: 16px;">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-md); margin-bottom: 14px;">
                        <h4 style="font-size: 14.5px; margin-bottom: 2px; font-weight: 700; color: var(--text-primary);" id="disburseSummaryTitle">Transaction Details</h4>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Enter client 4-digit PIN & capture real-time verified photo proof with automatic timestamp watermark.
                        </div>
                    </div>

                    <!-- Client PIN Input -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; display: flex; justify-content: space-between;">
                            <span>Client 4-Digit PIN Code *</span>
                            <span style="font-size: 11.5px; color: var(--text-muted); font-weight: 400;">Ask borrower to enter</span>
                        </label>
                        <input type="password" name="client_pin" id="releasing_client_pin" maxlength="4" inputmode="numeric" class="form-control" placeholder="••••" required style="letter-spacing: 10px; font-size: 24px; text-align: center; max-width: 220px; font-weight: 700; margin: 0 auto;">
                    </div>

                    <!-- Camera Section -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span>Photo Proof (with Auto Timestamp) *</span>
                            <span style="font-size: 11.5px; color: #059669; font-weight: 600;">Live Camera Ready</span>
                        </label>

                        <!-- Option 1: Live Video Camera Viewport -->
                        <div id="camera-stream-wrapper" style="position: relative;">
                            <div class="camera-box-viewport">
                                <!-- Floating Quick Actions Bar -->
                                <div class="camera-top-toolbar">
                                    <button type="button" class="camera-tool-pill" onclick="flipReleasingCamera('webcamVideo')" title="Switch Front/Back Camera">
                                        🔄 Flip Camera
                                    </button>
                                    <button type="button" class="camera-tool-pill" id="releasing-torch-btn" onclick="toggleReleasingTorch()" style="display: none;" title="Toggle Flashlight">
                                        💡 Flash
                                    </button>
                                </div>

                                <video id="webcamVideo" autoplay playsinline muted></video>
                                <canvas id="proofCanvas" style="display: none;"></canvas>

                                <!-- Big Mobile Camera Shutter Button -->
                                <div class="camera-shutter-bar">
                                    <button type="button" class="camera-shutter-btn" onclick="captureSnapshotWithTimestamp('webcamVideo', 'proofCanvas', 'photoProofInput', 'proofPreviewImg')" title="Take Photo">
                                        <div class="camera-shutter-btn-inner">📸</div>
                                    </button>
                                </div>
                            </div>

                            <!-- Native Phone Camera / Photo Upload Fallback Bar -->
                            <div style="text-align: center; margin-top: 10px;">
                                <label class="btn btn-outline btn-sm" style="font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                    <span>📱 Open Phone Camera App / Upload Photo</span>
                                    <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'proofCanvas', 'photoProofInput', 'proofPreviewImg')">
                                </label>
                            </div>
                        </div>

                        <!-- Option 2: Captured Photo Preview & Retake Bar -->
                        <div id="photo-preview-wrapper" style="display: none;">
                            <div class="photo-preview-card">
                                <img id="proofPreviewImg" src="" alt="Captured Proof Preview">
                                <div class="photo-preview-actions">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="retakeReleasingPhoto('webcamVideo', 'proofPreviewImg', 'photoProofInput')" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3);">
                                        🔄 Retake Photo
                                    </button>
                                    <label class="btn btn-sm btn-outline" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3); cursor: pointer;">
                                        📁 Choose Different Photo
                                        <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'proofCanvas', 'photoProofInput', 'proofPreviewImg')">
                                    </label>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #059669; font-weight: 700; text-align: center; margin-top: 8px;">
                                ✓ Photo Stamped with Verified Timestamp and Ready for Submission.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 12px 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeDisburseModal()">Cancel</button>
                    <button type="submit" class="btn btn-emerald" id="submitDisbursementBtn" style="font-weight: 700;">
                        ✓ Complete & Release Cash
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/admin.js') }}"></script>
    <script>
        function openReviewModal(actionUrl, actionType, userName, amount) {
            document.getElementById('reviewForm').action = actionUrl;
            document.getElementById('reviewAction').value = actionType;

            if (actionType === 'approve') {
                document.getElementById('reviewModalTitle').innerText = 'Approve & Submit to Host (' + userName + ' - ₱' + amount + ')';
                document.getElementById('approveFields').style.display = 'block';
                document.getElementById('declineFields').style.display = 'none';
                document.getElementById('reviewSubmitBtn').className = 'btn btn-emerald';
                document.getElementById('reviewSubmitBtn').innerText = 'Forward to Host for Approval';
            } else {
                document.getElementById('reviewModalTitle').innerText = 'Decline Request (' + userName + ' - ₱' + amount + ')';
                document.getElementById('approveFields').style.display = 'none';
                document.getElementById('declineFields').style.display = 'block';
                document.getElementById('reviewSubmitBtn').className = 'btn btn-rose';
                document.getElementById('reviewSubmitBtn').innerText = 'Confirm Decline';
            }
            openModal('reviewModal');
        }

        function openDisburseModal(actionUrl, typeName, clientName, amount) {
            document.getElementById('disburseForm').action = actionUrl;
            document.getElementById('disburseModalTitle').innerText = typeName + ' - ' + clientName;
            document.getElementById('disburseSummaryTitle').innerText = clientName + ' | Amount: ₱' + amount;
            document.getElementById('photoProofInput').value = '';
            document.getElementById('releasing_client_pin').value = '';
            
            document.getElementById('camera-stream-wrapper').style.display = 'block';
            document.getElementById('photo-preview-wrapper').style.display = 'none';
            document.getElementById('proofPreviewImg').src = '';

            openModal('disburseModal');
            startCamera('webcamVideo');
        }

        function closeDisburseModal() {
            stopCamera();
            closeModal('disburseModal');
        }

        function validateDisbursementForm() {
            const photoInput = document.getElementById('photoProofInput');
            const pinInput = document.getElementById('releasing_client_pin');

            if (!pinInput.value || pinInput.value.length !== 4) {
                showToast('error', 'Please enter a valid 4-digit client PIN.');
                pinInput.focus();
                return false;
            }

            if (!photoInput.value) {
                showToast('error', 'Please capture or upload photo proof before submitting.');
                return false;
            }

            return true;
        }
    </script>
@endpush
