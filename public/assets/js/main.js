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
    // PAGINACIÓN Y FILTRO DE TABLAS
    // ===========================
    const itemsPerPage = 20;
    const searchInput = document.querySelector('.search-box input');
    
    document.querySelectorAll('.table-container table').forEach(table => {
        if (table.classList.contains('no-auto-page')) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > 1); // Excluir fila de "sin datos"
        if (allRows.length === 0) return; // Nada que paginar
        
        let currentPage = 1;
        let filteredRows = [...allRows];
        
        // Crear contenedor de paginación
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-controls';
        table.parentElement.parentElement.insertBefore(paginationContainer, table.parentElement.nextSibling);

        function renderTable() {
            allRows.forEach(row => row.style.display = 'none'); // Ocultar todo
            
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;
            
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            
            // Mostrar solo los elementos de la página actual
            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
            });
            
            renderPagination(totalPages);
        }
        
        function renderPagination(totalPages) {
            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;
            
            const createBtn = (text, page, disabled = false, active = false) => {
                const btn = document.createElement('button');
                btn.innerHTML = text;
                btn.className = `btn btn-sm ${active ? 'btn-primary' : 'btn-secondary'} pagination-btn`;
                btn.type = 'button';
                if (disabled) btn.disabled = true;
                if (!disabled && !active) {
                    btn.addEventListener('click', () => {
                        currentPage = page;
                        renderTable();
                        // Opcional: hacer scroll suave hacia arriba de la tabla
                        table.parentElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
                return btn;
            };
            
            paginationContainer.appendChild(createBtn('<i class="fas fa-chevron-left"></i>', currentPage - 1, currentPage === 1));
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                paginationContainer.appendChild(createBtn('1', 1));
                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.innerHTML = '...';
                    dots.className = 'pagination-dots';
                    paginationContainer.appendChild(dots);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationContainer.appendChild(createBtn(i, i, false, i === currentPage));
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.innerHTML = '...';
                    dots.className = 'pagination-dots';
                    paginationContainer.appendChild(dots);
                }
                paginationContainer.appendChild(createBtn(totalPages, totalPages));
            }
            
            paginationContainer.appendChild(createBtn('<i class="fas fa-chevron-right"></i>', currentPage + 1, currentPage === totalPages));
            
            // Información de resultados
            const info = document.createElement('div');
            info.className = 'pagination-info';
            const total = filteredRows.length;
            const startStr = total === 0 ? 0 : ((currentPage - 1) * itemsPerPage) + 1;
            const endStr = Math.min(currentPage * itemsPerPage, total);
            info.innerHTML = `Mostrando ${startStr} - ${endStr} de ${total} registros`;
            paginationContainer.appendChild(info);
        }
        
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase().trim();
                filteredRows = allRows.filter(row => {
                    return row.innerText.toLowerCase().includes(term);
                });
                currentPage = 1;
                renderTable();
            });
        }
        
        renderTable();
    });

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
    // NOTIFICACIONES TOAST
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

    // ===========================
    // CAPA DE CARGA GLOBAL
    // ===========================
    const globalLoadingOverlay = document.getElementById('globalLoadingOverlay');

    window.showLoading = () => {
        if (globalLoadingOverlay) {
            globalLoadingOverlay.classList.add('active');
        }
    };

    window.hideLoading = () => {
        if (globalLoadingOverlay) {
            globalLoadingOverlay.classList.remove('active');
        }
    };

    // Activar carga en enlaces regulares
    document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript:"])').forEach(link => {
        link.addEventListener('click', (e) => {
            // No activar si se presiona ctrl/cmd (abrir en nueva pestaña)
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            // No activar si tiene la clase 'no-loader' o similar (opcional)
            if (link.classList.contains('no-loader')) return;
            
            showLoading();
        });
    });

    // Activar carga al enviar formularios (si no lo previene el diálogo de confirmación)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!e.defaultPrevented) {
                showLoading();
            }
        });
    });

    // Ocultar carga cuando la página se restaura desde la caché (bfcache)
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hideLoading();
        }
    });

});
