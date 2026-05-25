/**
 * Main JS for Sistema de Gestión Documental
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('Document Management System initialized');

    // Sidebar Toggle for Mobile
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'mobile-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    
    // Add active class to current nav link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href').includes(currentPath) && currentPath !== '/') {
            link.classList.add('active');
        }
    });

    // Search Box Table Filter
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const term = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(row => {
                if (row.cells.length === 1 && (row.innerText.includes('No hay') || row.innerText.includes('no hay'))) {
                    return;
                }
                const text = row.innerText.toLowerCase();
                if (text.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Form validation and confirmation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const confirmAction = form.dataset.confirm;
            if (confirmAction && !confirm(confirmAction)) {
                e.preventDefault();
            }
        });
    });

    // Toast notifications placeholder
    window.showToast = (message, type = 'info') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} glass`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };
});
