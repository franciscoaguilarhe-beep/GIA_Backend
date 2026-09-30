<?php
require_once 'views/layout/header.php';
?>

<!-- Flatpickr for Date Range -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<div class="layout-container">
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <h2 class="sidebar-title"><i class="ph ph-funnel"></i> Filtros de Informes</h2>
        
        <div class="local-search" style="margin-bottom: 20px;">
            <label style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 5px; display: block;">Rango de Fechas</label>
            <div class="search-input-container">
                <i class="ph ph-calendar"></i>
                <input type="text" id="filterFecha" class="search-input" placeholder="Seleccionar fechas..." style="padding-left: 45px;">
            </div>
        </div>

        <div class="local-search" style="margin-bottom: 20px;">
            <label style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 5px; display: block;">Búsqueda por Área</label>
            <div class="search-input-container">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="filterArea" class="search-input" placeholder="Buscador Local...">
            </div>
        </div>

        <div class="local-search">
            <label style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 5px; display: block;">Registrado Por</label>
            <div class="search-input-container">
                <i class="ph ph-user"></i>
                <input type="text" id="filterRegistrado" class="search-input" placeholder="Buscar empleado...">
            </div>
        </div>
    </aside>

    <!-- SECCIÓN PRINCIPAL -->
    <section class="data-section">
        <div class="action-menu" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="action-buttons">
                <button class="btn-action" id="btnAgregar">
                    <i class="ph ph-plus-circle"></i> Agregar
                </button>
                <label class="btn-action" style="margin: 0; cursor: pointer;">
                    <i class="ph ph-upload-simple"></i> Importar
                    <input type="file" id="btnImportar" accept=".xlsx, .xls" multiple style="display: none;">
                </label>
                <button class="btn-action" id="btnExportar">
                    <i class="ph ph-download-simple"></i> Exportar
                </button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">FECHA</th>
                        <th>ÁREA</th>
                        <th>ACCIONES</th>
                        <th style="width: 120px;">HORA INICIO</th>
                        <th style="width: 120px;">DURACIÓN</th>
                        <th>REGISTRADO POR</th>
                    </tr>
                </thead>
                <tbody id="informesTableBody">
                    <!-- Data will be loaded here via JS -->
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Modal Agregar / Editar -->
<div class="modal-overlay" id="modalAgregar" style="display: none; z-index: 1050;">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="ph ph-file-text" style="color: var(--accent-green);"></i> Agregar Informe</h3>
            <div class="modal-actions">
                <button type="button" class="close-btn" id="closeModal">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>
        <div class="modal-body" style="display: block; padding-top: 15px;">
            <form id="formAgregarInforme">
                <input type="hidden" id="informeId" value="">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Fecha</label>
                    <input type="date" id="addFecha" class="form-control" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Área</label>
                    <input type="text" id="addArea" class="form-control" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Acciones</label>
                    <textarea id="addAcciones" class="form-control" rows="3" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px; resize: vertical;"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Hora de Inicio</label>
                    <input type="time" id="addHoraInicio" class="form-control" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Duración (minutos)</label>
                    <input type="number" id="addDuracion" class="form-control" min="1" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px;">
                </div>
                
                <hr style="border-color: rgba(255,255,255,0.05); margin: 15px 0;">
                
                <div class="form-group" id="matriculaGroup" style="margin-bottom: 15px;">
                    <label>Matrícula (Firma)</label>
                    <input type="text" id="addMatricula" class="form-control" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px;">
                    <small id="matriculaFeedback" style="color: #ff6b6b; display: none; margin-top: 5px;">Matrícula no encontrada.</small>
                </div>
                
                <div class="modal-footer" style="padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; align-items: center; margin-top: 10px;">
                    <button type="button" class="btn" id="btnCancelModal">Cancelar</button>
                    <button type="submit" class="btn-save" id="btnSaveModal" style="margin-left: 10px;">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detalle Informe (Ficha) -->
<div class="modal-overlay" id="detalleModal" style="display: none; z-index: 1040;">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3><i class="ph ph-file-text" style="color: var(--accent-green);"></i> Detalle del Informe</h3>
            <div class="modal-actions">
                <button class="edit-btn" id="btnEditDetalle" title="Editar Información">
                    <i class="ph ph-pencil-simple"></i>
                </button>
                <button type="button" class="close-btn" id="btnCloseDetalle">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>
        <div class="modal-body" style="display: block; padding-top: 15px;">
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="color: var(--text-secondary); font-size: 0.85rem;">Fecha</label>
                <div class="form-value" id="detFecha" style="font-size: 0.95rem;"></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="color: var(--text-secondary); font-size: 0.85rem;">Área</label>
                <div class="form-value" id="detArea" style="font-size: 1.1rem; color: var(--accent-green); font-weight: 600;"></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="color: var(--text-secondary); font-size: 0.85rem;">Acciones</label>
                <div class="form-value" id="detAcciones" style="white-space: pre-wrap; min-height: 60px; font-size: 0.95rem; line-height: 1.5; background: rgba(255,255,255,0.02); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);"></div>
            </div>
            
            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label style="color: var(--text-secondary); font-size: 0.85rem;">Hora de Inicio</label>
                    <div class="form-value" id="detHora" style="font-size: 0.95rem;"></div>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label style="color: var(--text-secondary); font-size: 0.85rem;">Duración</label>
                    <div class="form-value" id="detDuracion" style="font-size: 0.95rem;"></div>
                </div>
            </div>
            
            <div id="detRegistradoPor" style="font-size: 0.85rem; color: var(--text-secondary); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                <i class="ph ph-user"></i> <span id="detRegistradoTexto"></span>
            </div>
        </div>
    </div>
</div>

<script src="js/informes.js?v=<?= time() ?>"></script>

<?php require_once 'views/layout/footer.php'; ?>
