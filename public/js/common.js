// Lending Corporation - Common Utility & Alert Scripts

// Toast notification helper using SweetAlert2
function showToast(icon, title) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: '#ffffff',
            color: '#0f172a',
            customClass: {
                popup: 'minimal-swal-toast'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon, // 'success', 'error', 'warning', 'info'
            title: title
        });
    } else {
        alert(title);
    }
}

// Confirmation Dialog Helper
function confirmAction(title, text, confirmBtnText = 'Yes, proceed', icon = 'warning') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'minimal-swal-modal'
        }
    });
}

// Modal open/close helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Auto-close modal when clicking background overlay
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Check flash session messages from Laravel Blade
    const flashSuccess = document.querySelector('meta[name="flash-success"]');
    const flashError = document.querySelector('meta[name="flash-error"]');
    const flashWarning = document.querySelector('meta[name="flash-warning"]');

    if (flashSuccess && flashSuccess.content) {
        showToast('success', flashSuccess.content);
    }
    if (flashError && flashError.content) {
        showToast('error', flashError.content);
    }
    if (flashWarning && flashWarning.content) {
        showToast('warning', flashWarning.content);
    }
});

// Format currency helper (PHP Peso)
function formatMoney(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
