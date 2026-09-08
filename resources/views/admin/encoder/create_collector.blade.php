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
                    <input type="text" name="name" class="form-control" placeholder="e.g. Pedro Penduko" required value="{{ old('name') }}">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Email Address (Login Username) *</label>
                        <input type="email" name="email" class="form-control" placeholder="collector@lending.com" required value="{{ old('email') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number (CP No) *</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="09181234567" required value="{{ old('phone_number') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Assigned Collection Area / Territory *</label>
                    <input type="text" name="assigned_area" class="form-control" placeholder="e.g. Zone 4 - Brgy. San Antonio / Kapitolyo" required value="{{ old('assigned_area') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Residential Address *</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Complete address..." required>{{ old('address') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Login Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Collector Valid Government ID (Image Upload)</label>
                    <input type="file" name="valid_id" class="form-control" accept="image/*">
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
