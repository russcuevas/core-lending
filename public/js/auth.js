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

// Fill quick demo credentials
function fillDemo(role) {
    if (role === 'host') {
        switchAuthTab('staff');
        document.getElementById('staff-email').value = 'host@lending.com';
        document.getElementById('staff-password').value = 'password123';
    } else if (role === 'encoder') {
        switchAuthTab('staff');
        document.getElementById('staff-email').value = 'encoder@lending.com';
        document.getElementById('staff-password').value = 'password123';
    } else if (role === 'releasing') {
        switchAuthTab('staff');
        document.getElementById('staff-email').value = 'releasing@lending.com';
        document.getElementById('staff-password').value = 'password123';
    } else if (role === 'collector') {
        switchAuthTab('staff');
        document.getElementById('staff-email').value = 'collector@lending.com';
        document.getElementById('staff-password').value = 'password123';
    } else if (role === 'client') {
        switchAuthTab('client');
        document.getElementById('client-phone').value = '09171234567';
        document.getElementById('client-pin').value = '1234';
    }
}
