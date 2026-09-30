    </main>

    <!-- Modal Global de Ficha Técnica -->
    <div class="modal-overlay" id="fichaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ficha Técnica</h3>
                <div class="modal-actions">
                    <button class="edit-btn" id="btnEditModal" title="Editar Información">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                    <button class="close-btn" id="btnCloseModal">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
            
            <form id="formFichaTecnica">
                <input type="hidden" id="modalItemId" name="id">
                <input type="hidden" id="modalItemCat" name="entity_cat">
                
                <div class="modal-body" id="modalBody">
                    <!-- El contenido se inyecta dinámicamente con JS -->
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-delete" id="btnDeleteModal" style="display: none;"><i class="ph ph-trash"></i> Eliminar</button>
                    <button type="button" class="btn" id="btnCancelEdit">Cancelar</button>
                    <button type="submit" class="btn-save" id="btnSaveModal">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Importación Masiva -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Importar Datos</h3>
                <div class="modal-actions">
                    <button class="close-btn" onclick="document.getElementById('importModal').style.display = 'none'">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body" style="display: block;">
                <div class="template-link-container">
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 10px;">Para asegurar la compatibilidad, utiliza nuestra plantilla con el formato correcto.</p>
                    <a href="#" id="linkDescargarPlantilla" target="_blank" download>
                        <i class="ph ph-microsoft-excel-logo"></i> Descargar Plantilla .xlsx
                    </a>
                </div>

                <div class="drop-zone" id="dropZone">
                    <i class="ph ph-upload-simple"></i>
                    <p>Arrastra y suelta tu archivo aquí</p>
                    <p style="font-size: 0.8rem; margin-top: 5px;">o haz clic para seleccionar</p>
                    <input type="file" id="fileImport" accept=".csv, .xlsx, .xls" style="display: none;">
                </div>
                
                <p id="uploadStatus" style="text-align: center; font-weight: 500; min-height: 20px; color: var(--accent-green);"></p>
            </div>
        </div>
    </div>

    <!-- Modal Popup para Ver / Agregar / Editar Nota -->
    <div class="modal-overlay" id="notaModal" style="z-index: 1050;">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header">
                <h3 id="notaModalTitle"><i class="ph ph-note" style="color: var(--accent-green);"></i> Detalle de Nota</h3>
                <div class="modal-actions">
                    <button type="button" class="close-btn" id="btnCloseNotaModal">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body" style="display: block; padding-top: 15px;">
                <form id="formNotaModal" onsubmit="event.preventDefault();">
                    <input type="hidden" id="notaModalIdNota" value="">
                    <input type="hidden" id="notaModalCat" value="">
                    <input type="hidden" id="notaModalIdActivo" value="">
                    <input type="hidden" id="notaModalMode" value="add">

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Fecha</label>
                        <div class="form-value" id="notaModalViewFecha" style="display: none; font-size: 0.95rem;"></div>
                        <input type="date" id="notaModalInputFecha" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Título de la Nota</label>
                        <div class="form-value" id="notaModalViewTitulo" style="display: none; font-size: 1.1rem; color: var(--accent-green); font-weight: 600;"></div>
                        <input type="text" id="notaModalInputTitulo" placeholder="Ej. Mantenimiento, Cambio de disco, Reporte..." required>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Descripción / Contenido</label>
                        <div class="form-value" id="notaModalViewContenido" style="display: none; white-space: pre-wrap; min-height: 80px; font-size: 0.95rem; line-height: 1.5; background: rgba(255,255,255,0.02); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);"></div>
                        <textarea id="notaModalInputContenido" rows="4" placeholder="Escriba los detalles de la nota técnica..." required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical;"></textarea>
                    </div>

                    <div id="notaModalViewAuthorBox" style="display: none; margin-bottom: 15px; font-size: 0.85rem; color: var(--text-secondary); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                        <i class="ph ph-user"></i> <span id="notaModalViewAuthor"></span>
                    </div>

                    <div class="modal-footer" style="padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <button type="button" class="btn-delete" id="btnDeleteNotaModal" style="display: none;"><i class="ph ph-trash"></i> Eliminar</button>
                        <div style="display: flex; gap: 10px; margin-left: auto;">
                            <button type="button" class="btn" id="btnCancelNotaModal" style="display: flex;">Cancelar</button>
                            <button type="button" class="btn-action" id="btnEditNotaModal" style="display: none;"><i class="ph ph-pencil-simple"></i> Editar</button>
                            <button type="submit" class="btn-save" id="btnSaveNotaModal" style="display: flex;">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Popup para Registrar Baja / Retiro de Equipo -->
    <div class="modal-overlay" id="bajaModal" style="z-index: 1080;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="ph ph-package" style="color: var(--danger-color, #ef4444);"></i> Registrar Baja / Retiro</h3>
                <div class="modal-actions">
                    <button type="button" class="close-btn" id="btnCloseBajaModal">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body" style="display: block; padding-top: 15px;">
                <form id="formBajaModal">
                    <input type="hidden" id="bajaItemId" value="">
                    <input type="hidden" id="bajaItemCat" value="">

                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px;">
                        El equipo no se eliminará físicamente de la base de datos; su estatus cambiará a <strong style="color: var(--danger-color, #ef4444);">BAJA / RETIRADO</strong>.
                    </p>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Fecha de Baja / Retiro</label>
                        <input type="date" id="bajaInputFecha" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 12px; border-radius: 6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Nuevo Estatus</label>
                        <select id="bajaInputEstatus" required style="width: 100%; padding: 8px 12px; border-radius: 6px; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary);">
                            <option value="RETIRADO" selected>RETIRADO</option>
                            <option value="BAJA">BAJA</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Motivo / Observación del Movimiento</label>
                        <textarea id="bajaInputObservaciones" rows="3" placeholder="Escriba la causa u observación por la cual se da de baja el activo..." required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Matrícula de quien Autoriza</label>
                        <input type="text" id="bajaInputMatricula" placeholder="Ingrese su matrícula..." required autocomplete="off" style="width: 100%; background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 12px; border-radius: 6px;">
                    </div>

                    <div class="modal-footer" style="padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn" id="btnCancelBajaModal">Cancelar</button>
                        <button type="submit" class="btn-delete" id="btnConfirmBajaModal" style="display: flex; align-items: center; gap: 5px;">
                            <i class="ph ph-check-circle"></i> Confirmar Baja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Autorización por Matrícula -->
    <div class="modal-overlay" id="authModal" style="z-index: 1100;">
        <div class="modal-content auth-modal-content">
            <div class="modal-header" style="justify-content: center; border-bottom: none; padding-bottom: 0;">
                <h3><i class="ph ph-shield-check" style="color: var(--accent-green);"></i> Autorización</h3>
            </div>
            <div class="modal-body" style="display: block; padding-top: 10px;">
                <p id="authModalPrompt">Ingrese su matrícula para confirmar la acción:</p>
                <form id="formAuthModal" onsubmit="event.preventDefault();">
                    <input type="text" id="inputAuthMatricula" placeholder="Matrícula..." required autocomplete="off">
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="btn" id="btnCancelAuth" style="padding: 10px 20px;">Cancelar</button>
                        <button type="submit" class="btn-save" id="btnConfirmAuth" style="padding: 10px 20px;">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script Principal -->
    <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
