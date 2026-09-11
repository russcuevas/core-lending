<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SystemSetting;
use App\Models\SystemNotification;

class HostSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');

        $loanInterest = SystemSetting::get('loan_interest_rate_percent', 10.00);
        $loanTerm = SystemSetting::get('loan_term_days', 60);
        $loanInsurance = SystemSetting::get('loan_insurance_premium_daily', 5.00);
        $savingsInterest = SystemSetting::get('savings_interest_rate_percent', 10.00);
        $savingsLockIn = SystemSetting::get('savings_lock_in_days', 60);
        $collectorLoanComm = SystemSetting::get('collector_loan_commission_fixed', 300.00);
        $collectorSavingsComm = SystemSetting::get('collector_savings_commission_percent', 5.00);

        return view('host.settings.index', compact(
            'settings',
            'loanInterest',
            'loanTerm',
            'loanInsurance',
            'savingsInterest',
            'savingsLockIn',
            'collectorLoanComm',
            'collectorSavingsComm'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'loan_interest_rate_percent' => 'required|numeric|min:0|max:100',
            'loan_term_days' => 'required|integer|min:1|max:365',
            'loan_insurance_premium_daily' => 'required|numeric|min:0',
            'savings_interest_rate_percent' => 'required|numeric|min:0|max:100',
            'savings_lock_in_days' => 'required|integer|min:1|max:365',
            'collector_loan_commission_fixed' => 'required|numeric|min:0',
            'collector_savings_commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        SystemSetting::set('loan_interest_rate_percent', $request->loan_interest_rate_percent, 'float', 'loan');
        SystemSetting::set('loan_term_days', $request->loan_term_days, 'int', 'loan');
        SystemSetting::set('loan_insurance_premium_daily', $request->loan_insurance_premium_daily, 'float', 'loan', 'Default Insurance Premium (₱)', 'Standard daily insurance premium added to daily installment.');
        SystemSetting::set('savings_interest_rate_percent', $request->savings_interest_rate_percent, 'float', 'savings');
        SystemSetting::set('savings_lock_in_days', $request->savings_lock_in_days, 'int', 'savings');
        SystemSetting::set('collector_loan_commission_fixed', $request->collector_loan_commission_fixed, 'float', 'collector');
        SystemSetting::set('collector_savings_commission_percent', $request->collector_savings_commission_percent, 'float', 'collector');

        SystemNotification::sendNotification(
            null,
            'host',
            'System Interest Rates Updated',
            "Host Superadmin updated global parameters: Loan Interest to {$request->loan_interest_rate_percent}%, Repayment Term to {$request->loan_term_days} days, Insurance Premium to ₱{$request->loan_insurance_premium_daily}/day, Savings Interest to {$request->savings_interest_rate_percent}%. Past completed & encoded loans remain protected.",
            'info',
            '/host/settings'
        );

        return back()->with('success', 'Global system rates & parameters updated successfully! Future loans and savings will use these new rates, while all previous records remain protected.');
    }
}
