/**
 * Main JS — Sistema de Gestión Documental
 * Incluye: sidebar móvil, filtro de tabla, validación de formularios, toasts
 */

document.addEventListener('DOMContentLoaded', () => {

    // ===========================
    // SIDEBAR MÓVIL
    // ===========================
    const sidebar       = document.getElementById('sidebar');
    const overlay       = document.getElementById('sidebarOverlay');
    const hamburgerBtn  = document.getElementById('hamburgerBtn');
    const sidebarClose  = document.getElementById('sidebarClose');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        overlay.classList.add('active');
        hamburgerBtn.classList.add('open');
        hamburgerBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden'; // evitar scroll del fondo
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        hamburgerBtn.classList.remove('open');
        hamburgerBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
    if (sidebarClose)  sidebarClose.addEventListener('click', closeSidebar);
    if (overlay)       overlay.addEventListener('click', closeSidebar);

    // Cerrar sidebar al hacer click en un enlace del menú (en móvil)
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) closeSidebar();
            });
        });
    }

    // Cerrar sidebar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Restaurar scroll y estado al redimensionar la ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // ===========================
    // ACTIVE NAV LINK
    // ===========================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '/' && currentPath.endsWith(href.split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ===========================
    // FILTRO DE TABLA (búsqueda)
    // ===========================
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                // Omitir filas de "no hay registros"
                if (row.cells.length === 1) return;
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // ===========================
    // DATA-LABEL EN TD (para responsive)
    // Agrega automáticamente el atributo data-label a cada <td>
    // basado en el texto del <th> correspondiente
    // ===========================
    document.querySelectorAll('table').forEach(table => {
        const headers = [...table.querySelectorAll('thead th')].map(th => th.innerText.trim());
        table.querySelectorAll('tbody tr').forEach(row => {
            [...row.cells].forEach((cell, i) => {
                if (headers[i]) {
                    cell.setAttribute('data-label', headers[i]);
                }
            });
        });
    });

    // ===========================
    // CONFIRMACIÓN DE FORMULARIOS
    // ===========================
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const confirmMsg = form.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    });

    // ===========================
    // TOAST NOTIFICATIONS
    // ===========================
    window.showToast = (message, type = 'info') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} glass`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"
               style="color: var(--${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'info'}-color)"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        // Animación de salida
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

});
