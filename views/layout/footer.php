    </main>

    <!-- Modal Global de Ficha TÃ©cnica -->
    <div class="modal-overlay" id="fichaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ficha TÃ©cnica</h3>
                <div class="modal-actions">
                    <button class="edit-btn" id="btnEditModal" title="Editar InformaciÃ³n">
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
                    <!-- El contenido se inyecta dinÃ¡micamente con JS -->
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-delete" id="btnDeleteModal" style="display: none;"><i class="ph ph-trash"></i> Eliminar</button>
                    <button type="button" class="btn" id="btnCancelEdit">Cancelar</button>
                    <button type="submit" class="btn-save" id="btnSaveModal">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de ImportaciÃ³n Masiva -->
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
                    <p>Arrastra y suelta tu archivo aquÃ­</p>
                    <p style="font-size: 0.8rem; margin-top: 5px;">o haz clic para seleccionar</p>
                    <input type="file" id="fileImport" accept=".csv, .xlsx, .xls" style="display: none;">
                </div>
                
                <p id="uploadStatus" style="text-align: center; font-weight: 500; min-height: 20px; color: var(--accent-green);"></p>
            </div>
        </div>
    </div>

    <!-- Script Principal -->
    <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
