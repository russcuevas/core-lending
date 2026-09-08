@extends('layouts.app')

@section('title', 'Releasing Officer Request Center')
@section('page_title', 'Admin Releasing Officer - Request & Disbursement Center')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <!-- Messenger Notification Alert Box -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-md);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="brand-icon" style="background: #059669;">⚡</div>
            <div>
                <h3 style="font-size: 16px; margin-bottom: 2px; color: white;">Real-Time Incoming Transaction Requests</h3>
                <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                    You have <strong>{{ $pendingReviews->count() }}</strong> new requests to review and <strong>{{ $approvedReadyToRelease->count() + $loansReadyToDisburse->count() }}</strong> approved items ready for physical disbursement.
                </p>
            </div>
        </div>
        <div>
            <span class="badge badge-emerald" style="font-size: 13px; padding: 6px 14px;">Live Center Active</span>
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
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No approved items awaiting physical execution at this moment.
            </div>
        @else
            <!-- Wallet Cash In / Out execution cards -->
            @foreach($approvedReadyToRelease as $tx)
                <div class="releasing-request-card" style="border-left-color: #059669;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="badge {{ $tx->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}" style="margin-bottom: 6px;">
                                {{ strtoupper(str_replace('_', ' ', $tx->type)) }} APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 16px; margin: 4px 0;">{{ $tx->user->name }} ({{ $tx->user->role }})</h4>
                            <div style="font-size: 13px; color: var(--text-secondary);">Contact No: {{ $tx->user->phone_number }} | Address: {{ $tx->user->address }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 20px; font-weight: 700; color: {{ $tx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                                ₱{{ number_format($tx->amount, 2) }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">Host Approved</div>
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

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 14px;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.requests.execute', $tx->id) }}', '{{ strtoupper(str_replace('_', ' ', $tx->type)) }}', '{{ $tx->user->name }}', '{{ number_format($tx->amount, 2) }}')">
                            📷 Meet Client, Verify PIN & Capture Photo Proof
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Loan Principal Releases -->
            @foreach($loansReadyToDisburse as $loan)
                <div class="releasing-request-card" style="border-left-color: #4f46e5;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="badge badge-indigo" style="margin-bottom: 6px;">
                                LOAN PRINCIPAL DISBURSEMENT APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 16px; margin: 4px 0;">{{ $loan->client->user->name }}</h4>
                            <div style="font-size: 13px; color: var(--text-secondary);">Contact No: {{ $loan->client->user->phone_number }} | Collector: {{ $loan->collector->user->name ?? 'None' }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 20px; font-weight: 700; color: #4f46e5;">
                                ₱{{ number_format($loan->principal_amount, 2) }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">Principal Cash to Disburse</div>
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

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 14px;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.loans.disburse', $loan->id) }}', 'LOAN PRINCIPAL DISBURSEMENT', '{{ $loan->client->user->name }}', '{{ number_format($loan->principal_amount, 2) }}')">
                            📷 Disburse Loan Funds, Verify PIN & Take Proof Photo
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
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No new incoming requests.
            </div>
        @else
            @foreach($pendingReviews as $req)
                <div class="releasing-request-card urgent">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="badge {{ $req->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}">
                                {{ strtoupper(str_replace('_', ' ', $req->type)) }} REQUEST
                            </span>
                            <h4 style="font-size: 16px; margin: 6px 0 2px 0;">{{ $req->user->name }}</h4>
                            <div style="font-size: 13px; color: var(--text-secondary);">
                                Role: {{ ucfirst($req->user->role) }} | Contact No: {{ $req->user->phone_number }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">
                                ₱{{ number_format($req->amount, 2) }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">{{ $req->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); margin: 12px 0; font-size: 13px;">
                        <strong>User Remarks:</strong> {{ $req->releasing_notes ?? 'None provided' }}
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
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
        <div class="modal-box modal-lg">
            <div class="modal-header">
                <h3 class="modal-title" id="disburseModalTitle">Execute Physical Transaction</h3>
                <button type="button" class="modal-close" onclick="closeDisburseModal()">&times;</button>
            </div>
            <form id="disburseForm" method="POST">
                @csrf
                <input type="hidden" name="photo_proof" id="photoProofInput">

                <div class="modal-body">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 14px; border-radius: var(--radius-md); margin-bottom: 20px;">
                        <h4 style="font-size: 15px; margin-bottom: 4px;" id="disburseSummaryTitle">Transaction Details</h4>
                        <div style="font-size: 13px; color: var(--text-secondary);">
                            Step 1: Have the client enter their 4-digit PIN code. <br>
                            Step 2: Capture a live camera photo of client and cash handover (automatic date/time stamp overlay).
                        </div>
                    </div>

                    <!-- Client PIN Input -->
                    <div class="form-group">
                        <label class="form-label" style="font-size: 14px; font-weight: 700;">Client 4-Digit PIN Code *</label>
                        <input type="password" name="client_pin" maxlength="4" class="form-control" placeholder="••••" required style="letter-spacing: 6px; font-size: 20px; text-align: center; max-width: 200px;">
                    </div>

                    <!-- Camera Section -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Camera Photo Proof with Date/Time Stamp *</label>
                        
                        <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="startCamera('webcamVideo')">
                                📹 Turn On Camera
                            </button>
                            <button type="button" class="btn btn-sm btn-emerald" onclick="captureSnapshotWithTimestamp('webcamVideo', 'proofCanvas', 'photoProofInput', 'proofPreviewImg')">
                                📸 Capture Photo with Timestamp
                            </button>
                        </div>

                        <div class="camera-container" style="max-height: 280px;">
                            <video id="webcamVideo" autoplay playsinline></video>
                            <canvas id="proofCanvas" style="display: none;"></canvas>
                        </div>

                        <!-- Captured Preview -->
                        <div style="margin-top: 12px;">
                            <img id="proofPreviewImg" src="" alt="Captured Proof Preview" style="display: none; width: 100%; max-height: 200px; object-fit: contain; border-radius: var(--radius-sm); border: 2px solid #059669;">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeDisburseModal()">Cancel</button>
                    <button type="submit" class="btn btn-emerald btn-lg" id="submitDisbursementBtn">
                        ✓ Post & Complete Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.js') }}"></script>
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
            document.getElementById('proofPreviewImg').style.display = 'none';
            openModal('disburseModal');
            startCamera('webcamVideo');
        }

        function closeDisburseModal() {
            stopCamera();
            closeModal('disburseModal');
        }
    </script>
@endpush
