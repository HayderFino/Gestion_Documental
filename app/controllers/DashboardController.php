<?php

namespace app\controllers;

/**
 * Controlador para el Panel de Control (Dashboard).
 * Muestra resúmenes, estadísticas y accesos rápidos a expedientes y préstamos según el rol.
 */
class DashboardController extends Controller {
    
    /**
     * Constructor del controlador del dashboard.
     * Protege el acceso requiriendo sesión iniciada.
     */
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Muestra la vista principal del Dashboard.
     * Calcula estadísticas basándose en el rol del usuario (Administrador, Jefe de Línea o Usuario).
     */
    public function index() {
        /** @var string $title Título de la vista */
        $title = "Panel de Control";
        /** @var string $active Menú activo en navegación */
        $active = "dashboard";
        
        /** @var string $role Rol del usuario actual */
        $role = $_SESSION['user_role'];
        /** @var string $userName Nombre del usuario actual */
        $userName = $_SESSION['user_name'];
        /** @var string|int $userId ID del usuario actual */
        $userId = $_SESSION['user_id'];
        
        /** @var \app\helpers\JsonDB $expDb Conexión a la tabla de expedientes */
        $expDb = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $preDb Conexión a la tabla de préstamos */
        $preDb = new \app\helpers\JsonDB('prestamos');
        
        /** @var array $allPrestamos Lista completa de préstamos para calcular estadísticas */
        $allPrestamos = $preDb->all();
        
        /** @var array $stats Estadísticas a mostrar en las tarjetas superiores */
        if ($role !== 'Administrador' && $role !== 'Jefe de Línea') {
            /** @var \app\helpers\JsonDB $asigDb Conexión a la tabla de asignaciones para roles estándar */
            $asigDb = new \app\helpers\JsonDB('asignaciones');
            /** @var array $misAsignaciones Expedientes asignados a este usuario */
            $misAsignaciones = $asigDb->where('usuario_id', $userId);
            
            $stats = [
                'total_expedientes' => count($misAsignaciones),
                'prestamos_activos' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'entregado';
                })),
                'solicitudes_pendientes' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'pendiente_prestamo';
                })),
                'devoluciones_pendientes' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'pendiente_devolucion';
                }))
            ];
        } else {
            $stats = [
                'total_expedientes' => count($expDb->all()),
                'prestamos_activos' => count($preDb->where('estado', 'entregado')),
                'solicitudes_pendientes' => count($preDb->where('estado', 'pendiente_prestamo')),
                'devoluciones_pendientes' => count($preDb->where('estado', 'pendiente_devolucion'))
            ];
        }

        /** @var string $targetActivos Identificador del ancla para préstamos activos */
        $targetActivos = ($role === 'Administrador') ? '#dashboard-activos' : '#dashboard-mis-expedientes';
        /** @var string $targetSolicitudes Identificador del ancla para solicitudes */
        $targetSolicitudes = ($role === 'Administrador') ? '#dashboard-solicitudes' : '#dashboard-mis-expedientes';
        /** @var string $targetDevoluciones Identificador del ancla para devoluciones */
        $targetDevoluciones = ($role === 'Administrador') ? '#dashboard-devoluciones' : '#dashboard-mis-expedientes';

        ob_start();
        ?>
        <div class="stats-grid">
            <a href="<?= $targetActivos ?>" class="stat-card stat-card-link">
                <div class="label">Total Expedientes</div>
                <div class="value"><?= number_format($stats['total_expedientes']) ?></div>
                <i class="fas fa-file-archive" style="color: var(--primary-color);"></i>
            </a>
            <a href="<?= $targetActivos ?>" class="stat-card stat-card-link" style="border-left-color: var(--info-color);">
                <div class="label">Préstamos Activos</div>
                <div class="value"><?= $stats['prestamos_activos'] ?></div>
                <i class="fas fa-hand-holding" style="color: var(--info-color);"></i>
            </a>
            <a href="<?= $targetSolicitudes ?>" class="stat-card stat-card-link" style="border-left-color: var(--warning-color);">
                <div class="label">Solicitudes Pendientes</div>
                <div class="value"><?= $stats['solicitudes_pendientes'] ?></div>
                <i class="fas fa-clock" style="color: var(--warning-color);"></i>
            </a>
            <a href="<?= $targetDevoluciones ?>" class="stat-card stat-card-link" style="border-left-color: var(--success-color);">
                <div class="label">Devoluciones x Confirmar</div>
                <div class="value"><?= $stats['devoluciones_pendientes'] ?></div>
                <i class="fas fa-undo" style="color: var(--success-color);"></i>
            </a>
        </div>

        <?php if ($role === 'Administrador'): 
            $pendientes = $preDb->where('estado', 'pendiente_prestamo');
            $devoluciones = $preDb->where('estado', 'pendiente_devolucion');
            $activos = array_filter($allPrestamos, function($p) {
                return $p['estado'] === 'entregado' || $p['estado'] === 'pendiente_devolucion';
            });
            $hasTasks = !empty($pendientes) || !empty($devoluciones);
        ?>
            <!-- Tareas Pendientes (Solicitudes y Devoluciones por Aprobar) -->
            <?php if ($hasTasks): ?>
                <div class="dashboard-tasks-grid">
                    <?php if (!empty($pendientes)): ?>
                        <div id="dashboard-solicitudes" tabindex="-1" class="table-container">
                            <h3 style="margin-bottom: 1rem;"><i class="fas fa-bell"></i> Solicitudes por Aprobar</h3>
                            <table class="no-auto-page">
                                <thead>
                                    <tr>
                                        <th>Expediente</th>
                                        <th>Solicitante</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendientes as $prestamoItem): ?>
                                    <tr>
                                        <td data-label="Expediente"><strong><?= htmlspecialchars($prestamoItem['numero_expediente']) ?></strong></td>
                                        <td data-label="Solicitante"><?= htmlspecialchars($prestamoItem['solicitante_nombre']) ?></td>
                                        <td data-label="Acción">
                                            <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/ver-solicitud/<?= $prestamoItem['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--accent-color);" title="Verificar Solicitud">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($devoluciones)): ?>
                        <div id="dashboard-devoluciones" tabindex="-1" class="table-container">
                            <h3 style="margin-bottom: 1rem;"><i class="fas fa-file-signature"></i> Verificar Devoluciones</h3>
                            <table class="no-auto-page">
                                <thead>
                                    <tr>
                                        <th>Expediente</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devoluciones as $prestamoItem): ?>
                                    <tr>
                                        <td data-label="Expediente"><strong><?= htmlspecialchars($prestamoItem['numero_expediente']) ?></strong></td>
                                        <td data-label="Usuario"><?= htmlspecialchars($prestamoItem['solicitante_nombre']) ?></td>
                                        <td data-label="Acción">
                                            <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/devolver/<?= $prestamoItem['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--success-color);" title="Verificar Entrega">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Inbox Zero State -->
                <div class="inbox-zero-card">
                    <div class="inbox-zero-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h4>¡Todo al día!</h4>
                        <p>No tienes solicitudes ni devoluciones pendientes de aprobación.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ubicación de Expedientes (Búsqueda, Filtros y Paginación) -->
            <div id="dashboard-activos" tabindex="-1" class="table-container" style="margin-top: 2rem;">
                <div class="table-header-flex">
                    <h3><i class="fas fa-map-marker-alt"></i> Ubicación de Expedientes</h3>
                    
                    <div class="table-actions-flex">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="activos-search" placeholder="Buscar expediente o poseedor..." class="form-control">
                        </div>
                        
                        <button id="btn-toggle-advanced" class="btn btn-secondary btn-sm" style="height: 38px;">
                            <i class="fas fa-sliders-h"></i> Filtros <span class="badge badge-primary" id="active-filters-count" style="display: none; padding: 2px 6px; margin-left: 5px;">0</span>
                        </button>
                        
                        <div class="filter-pills">
                            <div class="pill-indicator" id="pill-indicator"></div>
                            <button class="pill-btn active" data-filter="all">Todos</button>
                            <button class="pill-btn" data-filter="entregado">En poder del usuario</button>
                            <button class="pill-btn" data-filter="pendiente_devolucion">En tránsito</button>
                        </div>
                    </div>
                </div>

                <!-- Panel de Filtros Avanzados -->
                <div class="advanced-filters-panel" id="advanced-filters-panel" style="display: none;">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label>Fecha Préstamo Desde</label>
                            <input type="date" id="filter-date-start" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Fecha Préstamo Hasta</label>
                            <input type="date" id="filter-date-end" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Línea / Área</label>
                            <select id="filter-linea" class="form-control">
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vinculación</label>
                            <select id="filter-vinculacion" class="form-control">
                                <option value="">Todas</option>
                                <option value="Funcionario">Funcionario</option>
                                <option value="Contratista">Contratista</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-actions">
                        <button id="btn-clear-filters" class="btn btn-secondary btn-sm"><i class="fas fa-eraser"></i> Limpiar</button>
                        <button id="btn-apply-filters" class="btn btn-primary btn-sm" style="background: var(--primary-color);"><i class="fas fa-filter"></i> Aplicar</button>
                    </div>
                </div>

                <table id="activos-table" class="no-auto-page">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="numero_expediente">Expediente <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="solicitante_nombre">Poseedor <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="estado">Estado Actual <i class="fas fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody id="activos-tbody">
                        <!-- Se llena dinámicamente con JS -->
                    </tbody>
                </table>

                <div class="table-pagination" id="activos-pagination">
                    <div class="pagination-left">
                        Mostrando <span id="pag-start">0</span> - <span id="pag-end">0</span> de <span id="pag-total">0</span> registros
                    </div>
                    <div class="pagination-center">
                        <select id="pag-size" class="form-control">
                            <option value="10">10 por página</option>
                            <option value="25" selected>25 por página</option>
                            <option value="50">50 por página</option>
                            <option value="100">100 por página</option>
                        </select>
                    </div>
                    <div class="pagination-right">
                        <button id="pag-prev" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-left"></i> Anterior</button>
                        <button id="pag-next" class="btn btn-secondary btn-sm">Siguiente <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Vista para Usuario -->
            <div id="dashboard-mis-expedientes" tabindex="-1" class="table-container" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-book-reader"></i> Mis Expedientes Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Fecha Préstamo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        /** @var array $misPrestamos Préstamos activos o pendientes de devolución pertenecientes al usuario */
                        $misPrestamos = array_filter($allPrestamos, function($p) use ($userId) {
                            return ($p['usuario_solicitante_id'] ?? null) == $userId && ($p['estado'] == 'entregado' || $p['estado'] == 'pendiente_devolucion');
                        });
                        if (empty($misPrestamos)): ?>
                            <tr><td colspan="4" style="text-align: center;">No tienes expedientes en tu poder.</td></tr>
                        <?php endif;
                        foreach ($misPrestamos as $prestamoItem): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prestamoItem['numero_expediente']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($prestamoItem['fecha_prestamo'] ?? '')) ?></td>
                            <td>
                                <span class="badge badge-<?= $prestamoItem['estado'] == 'entregado' ? 'info' : 'warning' ?>">
                                    <?= $prestamoItem['estado'] == 'entregado' ? 'En mi poder' : 'Entrega solicitada' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($prestamoItem['estado'] == 'entregado'): ?>
                                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/solicitar-devolucion/<?= $prestamoItem['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--warning-color);">
                                        <i class="fas fa-undo"></i> Entregar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Agregar comportamiento de desplazamiento suave a las tarjetas del dashboard
                document.querySelectorAll('.stat-card-link').forEach(function(card) {
                    card.addEventListener('click', function(event) {
                        var href = this.getAttribute('href');
                        var target = document.querySelector(href);
                        if (!target && (href === '#dashboard-solicitudes' || href === '#dashboard-devoluciones')) {
                            // Si la sección específica no existe, ir a la sección de Inbox Zero
                            target = document.querySelector('.inbox-zero-card');
                        }
                        if (target) {
                            event.preventDefault();
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            target.focus({ preventScroll: true });
                        }
                    });
                });
            });

            <?php if ($role === 'Administrador'): ?>
            (function() {
                const rawData = <?php echo json_encode(array_values($activos)); ?>;
                let data = [...rawData];
                
                let currentPage = 1;
                let pageSize = 25;
                let sortColumn = 'numero_expediente';
                let sortDirection = 'asc';
                let searchTerm = '';
                let statusFilter = 'all';
                
                // Filtros avanzados
                let filterDateStart = '';
                let filterDateEnd = '';
                let filterLinea = '';
                let filterVinculacion = '';

                // Elementos DOM
                const searchInput = document.getElementById('activos-search');
                const pillButtons = document.querySelectorAll('.pill-btn');
                const tableBody = document.getElementById('activos-tbody');
                const pagSizeSelect = document.getElementById('pag-size');
                const btnPrev = document.getElementById('pag-prev');
                const btnNext = document.getElementById('pag-next');
                const pagStart = document.getElementById('pag-start');
                const pagEnd = document.getElementById('pag-end');
                const pagTotal = document.getElementById('pag-total');
                const tableHeaders = document.querySelectorAll('th.sortable');
                
                const btnToggleAdvanced = document.getElementById('btn-toggle-advanced');
                const advancedPanel = document.getElementById('advanced-filters-panel');
                const selectLinea = document.getElementById('filter-linea');
                const selectVinculacion = document.getElementById('filter-vinculacion');
                const inputDateStart = document.getElementById('filter-date-start');
                const inputDateEnd = document.getElementById('filter-date-end');
                const btnClearFilters = document.getElementById('btn-clear-filters');
                const btnApplyFilters = document.getElementById('btn-apply-filters');
                const filtersBadgeCount = document.getElementById('active-filters-count');

                // Llenar select de líneas de manera dinámica
                const lineas = [...new Set(rawData.map(item => item.linea_expediente).filter(Boolean))].sort();
                lineas.forEach(linea => {
                    const opt = document.createElement('option');
                    opt.value = linea;
                    opt.textContent = linea;
                    selectLinea.appendChild(opt);
                });

                // Toggle panel de filtros
                btnToggleAdvanced.addEventListener('click', function() {
                    const isHidden = advancedPanel.style.display === 'none';
                    advancedPanel.style.display = isHidden ? 'block' : 'none';
                    this.classList.toggle('btn-primary', isHidden);
                    this.classList.toggle('btn-secondary', !isHidden);
                });

                // Event Listeners
                searchInput.addEventListener('input', function() {
                    searchTerm = this.value.toLowerCase().trim();
                    currentPage = 1;
                    render();
                });

                // Indicador de Píldoras Deslizantes
                const pillIndicator = document.getElementById('pill-indicator');
                
                function updateIndicator(activeBtn) {
                    if (!pillIndicator || !activeBtn) return;
                    const container = activeBtn.parentElement;
                    const containerRect = container.getBoundingClientRect();
                    const btnRect = activeBtn.getBoundingClientRect();
                    
                    const leftOffset = btnRect.left - containerRect.left;
                    
                    pillIndicator.style.left = `${leftOffset}px`;
                    pillIndicator.style.width = `${btnRect.width}px`;
                }

                // Inicializar posición
                const activePill = document.querySelector('.pill-btn.active');
                if (activePill) {
                    setTimeout(() => updateIndicator(activePill), 50);
                }

                window.addEventListener('resize', function() {
                    const activePill = document.querySelector('.pill-btn.active');
                    if (activePill) updateIndicator(activePill);
                });

                const sidebarBtn = document.getElementById('hamburgerBtn');
                if (sidebarBtn) {
                    sidebarBtn.addEventListener('click', function() {
                        setTimeout(() => {
                            const activePill = document.querySelector('.pill-btn.active');
                            if (activePill) updateIndicator(activePill);
                        }, 350);
                    });
                }

                pillButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        pillButtons.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        statusFilter = this.dataset.filter;
                        
                        updateIndicator(this);
                        
                        currentPage = 1;
                        render();
                    });
                });

                pagSizeSelect.addEventListener('change', function() {
                    pageSize = parseInt(this.value);
                    currentPage = 1;
                    render();
                });

                btnPrev.addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        render();
                        document.getElementById('dashboard-activos').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });

                btnNext.addEventListener('click', function() {
                    const totalPages = Math.ceil(filteredData().length / pageSize);
                    if (currentPage < totalPages) {
                        currentPage++;
                        render();
                        document.getElementById('dashboard-activos').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });

                tableHeaders.forEach(th => {
                    th.addEventListener('click', function() {
                        const column = this.dataset.sort;
                        if (sortColumn === column) {
                            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortColumn = column;
                            sortDirection = 'asc';
                        }
                        
                        tableHeaders.forEach(h => {
                            const icon = h.querySelector('i');
                            if (h === th) {
                                icon.className = sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                icon.className = 'fas fa-sort';
                            }
                        });

                        render();
                    });
                });

                // Aplicar filtros avanzados
                btnApplyFilters.addEventListener('click', function() {
                    filterDateStart = inputDateStart.value;
                    filterDateEnd = inputDateEnd.value;
                    filterLinea = selectLinea.value;
                    filterVinculacion = selectVinculacion.value;
                    currentPage = 1;
                    
                    // Contar filtros activos
                    let activeCount = 0;
                    if (filterDateStart) activeCount++;
                    if (filterDateEnd) activeCount++;
                    if (filterLinea) activeCount++;
                    if (filterVinculacion) activeCount++;
                    
                    if (activeCount > 0) {
                        filtersBadgeCount.textContent = activeCount;
                        filtersBadgeCount.style.display = 'inline-block';
                    } else {
                        filtersBadgeCount.style.display = 'none';
                    }
                    
                    render();
                });

                // Limpiar filtros avanzados
                btnClearFilters.addEventListener('click', function() {
                    inputDateStart.value = '';
                    inputDateEnd.value = '';
                    selectLinea.value = '';
                    selectVinculacion.value = '';
                    
                    filterDateStart = '';
                    filterDateEnd = '';
                    filterLinea = '';
                    filterVinculacion = '';
                    currentPage = 1;
                    
                    filtersBadgeCount.style.display = 'none';
                    render();
                });

                function filteredData() {
                    return rawData.filter(item => {
                        // Búsqueda global
                        const matchesSearch = 
                            (item.numero_expediente && item.numero_expediente.toLowerCase().includes(searchTerm)) ||
                            (item.solicitante_nombre && item.solicitante_nombre.toLowerCase().includes(searchTerm));
                        
                        // Estado rápido
                        const matchesStatus = statusFilter === 'all' || item.estado === statusFilter;
                        
                        // Filtros avanzados
                        let matchesDate = true;
                        if (filterDateStart || filterDateEnd) {
                            const itemDate = item.fecha_prestamo ? new Date(item.fecha_prestamo.split(' ')[0]) : null;
                            if (itemDate) {
                                if (filterDateStart && itemDate < new Date(filterDateStart)) matchesDate = false;
                                if (filterDateEnd && itemDate > new Date(filterDateEnd)) matchesDate = false;
                            } else {
                                matchesDate = false;
                            }
                        }
                        
                        const matchesLinea = !filterLinea || item.linea_expediente === filterLinea;
                        const matchesVinculacion = !filterVinculacion || item.tipo_vinculacion === filterVinculacion;
                        
                        return matchesSearch && matchesStatus && matchesDate && matchesLinea && matchesVinculacion;
                    });
                }

                function sortData(list) {
                    return list.sort((a, b) => {
                        let valA = a[sortColumn] ? a[sortColumn].toString().toLowerCase() : '';
                        let valB = b[sortColumn] ? b[sortColumn].toString().toLowerCase() : '';
                        
                        if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                        if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    });
                }

                function render() {
                    let list = filteredData();
                    list = sortData(list);

                    const totalItems = list.length;
                    const totalPages = Math.ceil(totalItems / pageSize) || 1;

                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }

                    const startIndex = totalItems === 0 ? 0 : (currentPage - 1) * pageSize;
                    const endIndex = Math.min(startIndex + pageSize, totalItems);
                    const paginatedList = list.slice(startIndex, endIndex);

                    if (paginatedList.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fas fa-info-circle"></i> No hay expedientes en los registros activos con estos criterios.</td></tr>`;
                    } else {
                        tableBody.innerHTML = paginatedList.map(item => {
                            const isEntregado = item.estado === 'entregado';
                            const badgeClass = isEntregado ? 'badge-info' : 'badge-warning';
                            const badgeText = isEntregado ? 'En poder del usuario' : 'En tránsito';
                            
                            return `
                                <tr>
                                    <td data-label="Expediente"><strong>${escapeHtml(item.numero_expediente)}</strong></td>
                                    <td data-label="Poseedor">${escapeHtml(item.solicitante_nombre)}</td>
                                    <td data-label="Estado Actual">
                                        <span class="badge ${badgeClass}">${badgeText}</span>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }

                    pagStart.textContent = totalItems === 0 ? 0 : startIndex + 1;
                    pagEnd.textContent = endIndex;
                    pagTotal.textContent = totalItems;

                    btnPrev.disabled = currentPage === 1;
                    btnNext.disabled = currentPage === totalPages || totalItems === 0;
                }

                function escapeHtml(str) {
                    if (!str) return '';
                    return str
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                render();
            })();
            <?php endif; ?>
        </script>
        <?php
        /** @var string $content HTML generado de la vista index del dashboard */
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }
}
