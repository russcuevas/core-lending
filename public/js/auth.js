// Auth Page Tabs and Helpers
function switchAuthTab(type) {
    const staffTab = document.getElementById('tab-staff');
    const clientTab = document.getElementById('tab-client');
    const staffForm = document.getElementById('staff-login-form');
    const clientForm = document.getElementById('client-login-form');

    if (type === 'client') {
        if (staffTab) staffTab.classList.remove('active');
        if (clientTab) clientTab.classList.add('active');
        if (staffForm) staffForm.style.display = 'none';
        if (clientForm) clientForm.style.display = 'block';
    } else {
        if (clientTab) clientTab.classList.remove('active');
        if (staffTab) staffTab.classList.add('active');
        if (clientForm) clientForm.style.display = 'none';
        if (staffForm) staffForm.style.display = 'block';
    }
}

// Development Phase Alerts for Password & PIN recovery
function showForgotAlert(type) {
    if (type === 'password') {
        Swal.fire({
            icon: 'info',
            title: 'Forgot Password',
            html: `
                <div style="font-size: 13.5px; line-height: 1.5; color: var(--text-secondary); text-align: center; padding: 6px 0;">
                    <span style="display: inline-block; background: #fef3c7; color: #b45309; font-weight: 700; font-size: 11.5px; padding: 3px 8px; border-radius: 6px; margin-bottom: 8px;">
                        🚧 Development Phase
                    </span>
                    <p style="margin: 6px 0 0 0;">
                        Self-service password recovery is currently under development. Please contact your <strong>Host Superadmin</strong> to reset or re-issue your account credentials.
                    </p>
                </div>
            `,
            confirmButtonColor: '#0077b6',
            confirmButtonText: 'Understood'
        });
    } else if (type === 'pin') {
        Swal.fire({
            icon: 'info',
            title: 'Forgot PIN Code',
            html: `
                <div style="font-size: 13.5px; line-height: 1.5; color: var(--text-secondary); text-align: center; padding: 6px 0;">
                    <span style="display: inline-block; background: #fef3c7; color: #b45309; font-weight: 700; font-size: 11.5px; padding: 3px 8px; border-radius: 6px; margin-bottom: 8px;">
                        🚧 Development Phase
                    </span>
                    <p style="margin: 6px 0 0 0;">
                        Self-service PIN reset is currently under development. Please reach out to your <strong>Assigned Field Collector</strong> or <strong>Host Superadmin</strong> to verify your identity and reset your 4-digit security PIN.
                    </p>
                </div>
            `,
            confirmButtonColor: '#0077b6',
            confirmButtonText: 'Understood'
        });
    }
}
