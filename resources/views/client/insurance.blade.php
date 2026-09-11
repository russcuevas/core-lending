@extends('layouts.app')

@section('title', 'My Insurance Policy')
@section('page_title', 'My Insurance Policy - Community Micro-Insurance')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/client.css') }}">
    <style>
        .policy-hero-card {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #0284c7 100%);
            color: #ffffff;
            border-radius: var(--radius-xl, 16px);
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px -5px rgba(6, 95, 70, 0.35);
            position: relative;
            overflow: hidden;
        }
        .policy-hero-card::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .policy-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 14px;
            border-radius: 9999px;
            margin-bottom: 12px;
            backdrop-filter: blur(4px);
        }
        .policy-hero-title {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
        }
        .policy-hero-subtitle {
            font-size: 14.5px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            line-height: 1.5;
        }
        .policy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .policy-card {
            background: var(--bg-card, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius-lg, 12px);
            padding: 24px;
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .policy-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1));
        }
        .policy-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color, #f1f5f9);
        }
        .policy-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .policy-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            margin: 0;
        }
        .policy-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .policy-list-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13.5px;
            color: var(--text-primary, #334155);
            line-height: 1.5;
        }
        .policy-list-bullet {
            color: #059669;
            font-weight: 800;
            font-size: 16px;
            line-height: 1;
            margin-top: 2px;
        }
        .benefit-highlight-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .benefit-amount {
            font-size: 34px;
            font-weight: 800;
            color: #047857;
            letter-spacing: -0.5px;
            margin: 4px 0;
        }
        .claim-step-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0284c7;
            border-radius: 8px;
            padding: 16px;
            margin-top: 14px;
        }
        .coverage-status-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }
    </style>
@endpush

@section('content')
    <!-- 1. Hero Policy Banner -->
    <div class="policy-hero-card">
        <div class="policy-badge">
            <span>🛡️ Community Micro-Insurance Protection</span>
        </div>
        <h1 class="policy-hero-title">COMMUNITY MICRO-INSURANCE POLICY</h1>
        <p class="policy-hero-subtitle">
            Guaranteed Financial Safety Net for Active Community Members
        </p>
    </div>

    <!-- 2. Real-Time Coverage Status Strip -->
    <div class="coverage-status-strip">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="font-size: 28px;">
                @if ($isCoveredToday)
                    <span>🛡️</span>
                @else
                    <span>⏳</span>
                @endif
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Today's Coverage Status ({{ \Carbon\Carbon::today()->format('M d, Y') }})
                </div>
                <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">
                    @if ($isCoveredToday)
                        <span style="color: #059669;">✓ Active & Fully Covered Today</span>
                    @elseif ($activeLoan && $activeLoan->status === 'active')
                        <span style="color: #d97706;">⚠️ Pending Daily Payment for Today (₱{{ number_format($insurancePremiumDaily, 2) }}/day)</span>
                    @else
                        <span style="color: #64748b;">No Active Loan Policy Currently</span>
                    @endif
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <div style="text-align: right; font-size: 12.5px; color: var(--text-secondary);">
                <div>Daily Insurance Premium: <strong style="color: #0284c7;">₱{{ number_format($insurancePremiumDaily, 2) }} / day</strong></div>
                <div>Lump-Sum Death Benefit: <strong style="color: #059669;">₱15,000.00</strong></div>
            </div>
            <a href="{{ route('client.dashboard') }}" class="btn btn-outline" style="font-size: 12.5px; font-weight: 600;">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if($client->beneficiary_name)
        <!-- Registered Beneficiary Card -->
        <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 26px;">👤</div>
                <div>
                    <div style="font-size: 11.5px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px;">
                        Registered Designated Beneficiary
                    </div>
                    <div style="font-size: 15px; font-weight: 800; color: var(--text-primary); margin-top: 1px;">
                        {{ $client->beneficiary_name }}
                        @if($client->beneficiary_phone)
                            <span style="font-size: 13px; font-weight: normal; color: var(--text-secondary); margin-left: 6px;">(📞 {{ $client->beneficiary_phone }})</span>
                        @endif
                    </div>
                </div>
            </div>
            <span class="badge badge-emerald" style="font-size: 11.5px; padding: 5px 12px;">✓ Verified Claim Recipient</span>
        </div>
    @endif

    <!-- 3. Policy Core Details Grid -->
    <div class="policy-grid">
        <!-- Card 1: Key Policy Details -->
        <div class="policy-card">
            <div class="policy-card-header">
                <div class="policy-icon-wrapper" style="background: rgba(5, 150, 105, 0.1); color: #059669;">
                    🎁
                </div>
                <div>
                    <h3 class="policy-card-title">Key Policy Details</h3>
                    <span style="font-size: 12px; color: var(--text-secondary);">Guaranteed safety net parameters</span>
                </div>
            </div>

            <div class="benefit-highlight-box">
                <div style="font-size: 12px; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px;">
                    Lump-Sum Death Benefit
                </div>
                <div class="benefit-amount">₱15,000</div>
                <div style="font-size: 12px; color: #047857; font-weight: 600;">
                    Guaranteed Payout to Designated Beneficiary
                </div>
            </div>

            <ul class="policy-list">
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Lump-Sum Death Benefit:</strong> ₱15,000 guaranteed cash assistance.
                    </div>
                </li>
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Covered Events:</strong> Death due to any cause (natural, illness, or accidental).
                    </div>
                </li>
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Waiting Period:</strong> Zero. Coverage begins on the exact day you make your first payment.
                    </div>
                </li>
            </ul>
        </div>

        <!-- Card 2: How Premium & Coverage Work -->
        <div class="policy-card">
            <div class="policy-card-header">
                <div class="policy-icon-wrapper" style="background: rgba(2, 132, 199, 0.1); color: #0284c7;">
                    💳
                </div>
                <div>
                    <h3 class="policy-card-title">How Premium & Coverage Work</h3>
                    <span style="font-size: 12px; color: var(--text-secondary);">Daily term & active coverage rules</span>
                </div>
            </div>

            <ul class="policy-list">
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Daily Premium:</strong> ₱{{ number_format($insurancePremiumDaily, 2) }} per day included in your daily installment.
                    </div>
                </li>
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Daily Term:</strong> Each ₱{{ number_format($insurancePremiumDaily, 2) }} payment secures coverage for that specific calendar day.
                    </div>
                </li>
                <li class="policy-list-item">
                    <span class="policy-list-bullet">✓</span>
                    <div>
                        <strong>Active Status:</strong> You are covered for as long as your daily payments are up to date. Coverage pauses immediately on any day a payment is missed.
                    </div>
                </li>
            </ul>

            <div class="claim-step-box" style="border-left-color: #059669; background: #f0fdf4;">
                <div style="font-size: 12px; font-weight: 700; color: #065f46; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <span>💡</span> Real-world Example:
                </div>
                <p style="font-size: 12.5px; color: #047857; margin: 0; line-height: 1.5;">
                    If you pay your ₱{{ number_format($insurancePremiumDaily, 2) }} premium today, you are fully covered. If death occurs on a paid day, the full ₱15,000 benefit is paid out.
                </p>
            </div>
        </div>

        <!-- Card 3: How to File a Claim -->
        <div class="policy-card" style="grid-column: 1 / -1;">
            <div class="policy-card-header">
                <div class="policy-icon-wrapper" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                    📝
                </div>
                <div>
                    <h3 class="policy-card-title">How to File a Claim</h3>
                    <span style="font-size: 12px; color: var(--text-secondary);">Fast, hassle-free 1-document claiming procedure</span>
                </div>
            </div>

            <p style="font-size: 13.5px; color: var(--text-secondary); margin: 0 0 16px 0; line-height: 1.6;">
                We keep the process fast and hassle-free. Because your ID and daily payment logs are already stored in our system, your beneficiary only needs to submit one document:
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <div style="background: #ffffff; border: 1.5px solid #0284c7; border-radius: 10px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                    <div style="font-size: 32px; color: #0284c7;">📄</div>
                    <div>
                        <div style="font-size: 14.5px; font-weight: 800; color: #0f172a;">Official Death Certificate</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">Sole physical document required from designated beneficiary</div>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1.5px solid #059669; border-radius: 10px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                    <div style="font-size: 32px; color: #059669;">💸</div>
                    <div>
                        <div style="font-size: 14.5px; font-weight: 800; color: #0f172a;">Direct Lump-Sum Payout</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">Full ₱15,000 released immediately upon verification</div>
                    </div>
                </div>
            </div>

            <div class="claim-step-box" style="margin-top: 18px;">
                <div style="font-size: 13px; font-weight: 700; color: #0369a1; margin-bottom: 4px;">
                    ⚡ Automatic System Verification & Claim Release
                </div>
                <div style="font-size: 12.5px; color: #334155; line-height: 1.5;">
                    Once verified against our system records, the full <strong>₱15,000</strong> lump-sum payout will be released directly to your designated beneficiary without unnecessary delays or red tape.
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Quick Account Actions -->
    <div class="card" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid var(--border-color);">
        <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div>
                <h4 style="font-size: 14.5px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Have questions about your insurance policy coverage?
                </h4>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin: 0;">
                    Coordinate with your assigned collector or check your daily payment schedule in your client portal dashboard.
                </p>
            </div>
            <a href="{{ route('client.dashboard') }}" class="btn btn-emerald" style="font-weight: 700;">
                📊 View Payment Schedule & History &rarr;
            </a>
        </div>
    </div>
@endsection
