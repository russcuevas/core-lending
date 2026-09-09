// Lending Corporation - Common Utility, DataTables & Responsive Mobile Scripts

// Universal Sidebar Toggle (Desktop Collapse & Mobile Drawer)
function toggleSidebar() {
    if (window.innerWidth <= 991) {
        toggleMobileSidebar();
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        try {
            localStorage.setItem('core_sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    }
}

// Restore desktop sidebar collapsed state on load
try {
    if (window.innerWidth > 991 && localStorage.getItem('core_sidebar_collapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
    }
} catch (e) {}

// Mobile Sidebar Drawer Navigation
function toggleMobileSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('active');
        document.body.classList.toggle('sidebar-locked');
    }
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar && backdrop) {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('active');
        document.body.classList.remove('sidebar-locked');
    }
}

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
        confirmButtonColor: '#0077b6',
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

// Format currency helper (PHP Peso)
function formatMoney(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Auto-close modal when clicking background overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Auto-close mobile sidebar when clicking a navigation link
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            closeMobileSidebar();
        });
    });

    // Initialize Simple-DataTables for enhanced tables
    if (typeof simpleDatatables !== 'undefined') {
        document.querySelectorAll('.data-table-enhanced').forEach(table => {
            // Only initialize if table has tbody with rows and not marked static
            if (table.querySelector('tbody tr') && !table.dataset.noDatatable) {
                new simpleDatatables.DataTable(table, {
                    searchable: true,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    labels: {
                        placeholder: "Search data...",
                        perPage: "entries per page",
                        noRows: "No records found",
                        info: "Showing {start} to {end} of {rows} entries",
                    }
                });
            }
        });
    }

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

    // Auto-open modal if there are form validation errors inside it
    const firstInvalidInModal = document.querySelector('.modal-overlay .is-invalid');
    if (firstInvalidInModal) {
        const modal = firstInvalidInModal.closest('.modal-overlay');
        if (modal && modal.id) {
            openModal(modal.id);
            setTimeout(() => {
                firstInvalidInModal.focus();
            }, 100);
        }
    }

    // Initialize Universal Image Upload Preview with Cancel/Remove [✕] Button
    document.querySelectorAll('.image-upload-input').forEach(input => {
        const targetWrapperId = input.dataset.previewWrapper;
        const targetImgId = input.dataset.previewImg;
        const targetNameId = input.dataset.previewName;

        if (targetWrapperId && targetImgId) {
            input.addEventListener('change', function() {
                handleImagePreview(this, targetWrapperId, targetImgId, targetNameId);
            });
        }
    });

    // Universal Real-Time Running Date & Time Clock
    function updateGlobalLiveClock() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };

        const dateElem = document.getElementById('live_date_display');
        const timeElem = document.getElementById('live_time_display');

        if (dateElem) dateElem.innerText = now.toLocaleDateString('en-US', dateOptions);
        if (timeElem) timeElem.innerText = now.toLocaleTimeString('en-US', timeOptions);
    }
    setInterval(updateGlobalLiveClock, 1000);
    updateGlobalLiveClock();
});

// Universal Image Upload Preview Helper Functions
function handleImagePreview(input, wrapperId, imgId, nameId) {
    const wrapper = document.getElementById(wrapperId);
    const img = document.getElementById(imgId);
    const nameSpan = nameId ? document.getElementById(nameId) : null;

    if (!input || !wrapper || !img) return;

    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select an image file (PNG, JPG, JPEG).');
            input.value = '';
            wrapper.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            if (nameSpan) {
                nameSpan.innerText = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            }
            wrapper.style.display = 'inline-block';
        };
        reader.readAsDataURL(file);
    } else {
        wrapper.style.display = 'none';
    }
}

function removeImageUpload(inputId, wrapperId, imgId, nameId) {
    const input = document.getElementById(inputId);
    const wrapper = document.getElementById(wrapperId);
    const img = document.getElementById(imgId);
    const nameSpan = nameId ? document.getElementById(nameId) : null;

    if (input) input.value = '';
    if (img) img.src = '';
    if (nameSpan) nameSpan.innerText = '';
    if (wrapper) wrapper.style.display = 'none';
}

