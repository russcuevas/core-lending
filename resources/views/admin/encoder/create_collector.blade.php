@extends('layouts.app')

@section('title', 'Register New Collector')
@section('page_title', 'Admin Encoder - Register New Collector Account')

@section('content')
    <div style="max-width: 700px; margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Collector Registration Form</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 3px 0 0 0;">
                        New collector accounts will be submitted to Host Superadmin for verification and approval.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.encoder.collectors.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label class="form-label">Collector Full Name *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Pedro Penduko" required value="{{ old('name') }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Email Address (Login Username) *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="collector@lending.com" required value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number (CP No) *</label>
                        <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" placeholder="09181234567" required value="{{ old('phone_number') }}">
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Assigned Collection Area / Territory *</label>
                    <input type="text" name="assigned_area" class="form-control @error('assigned_area') is-invalid @enderror" placeholder="e.g. Zone 4 - Brgy. San Antonio / Kapitolyo" required value="{{ old('assigned_area') }}">
                    @error('assigned_area')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Residential Address *</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Complete address..." required>{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Login Password *</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Valid ID Upload with Live Preview & Remove [✕] Button -->
                <div class="form-group">
                    <label class="form-label">Collector Valid Government ID (Image Upload)</label>
                    <input type="file" name="valid_id" id="collector_valid_id" class="form-control image-upload-input @error('valid_id') is-invalid @enderror" accept="image/*"
                        data-preview-wrapper="collector_id_preview_wrapper" data-preview-img="collector_id_preview_img" data-preview-name="collector_id_preview_name">
                    @error('valid_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Upload valid government ID (JPG, PNG). Live preview will display below.</div>

                    <!-- Live Image Preview Container -->
                    <div class="image-preview-wrapper" id="collector_id_preview_wrapper" style="display: none;">
                        <div class="image-preview-box">
                            <button type="button" class="image-preview-remove-btn" onclick="removeImageUpload('collector_valid_id', 'collector_id_preview_wrapper', 'collector_id_preview_img', 'collector_id_preview_name')" title="Remove this image">✕</button>
                            <img id="collector_id_preview_img" class="image-preview-img" src="" alt="Collector ID Preview">
                        </div>
                        <div class="image-preview-filename" id="collector_id_preview_name"></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; flex-wrap: wrap;">
                    <a href="{{ route('admin.encoder.dashboard') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-emerald">
                        Submit Collector to Host for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
