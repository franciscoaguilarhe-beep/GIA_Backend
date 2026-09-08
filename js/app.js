document.addEventListener('DOMContentLoaded', () => {
    // ---- Búsqueda Global Predictiva ----
    const globalSearchInput = document.getElementById('globalSearch');
    const searchResultsContainer = document.getElementById('searchResults');

    if (globalSearchInput) {
        let debounceTimer;

        globalSearchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length >= 2) {
                debounceTimer = setTimeout(() => {
                    performGlobalSearch(query);
                }, 200);
            } else {
                hideSearchResults();
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-wrapper')) {
                hideSearchResults();
            }
        });
    }

    // ---- Lógica de Modal (Ficha Técnica) ----
    const modal = document.getElementById('fichaModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnEditModal = document.getElementById('btnEditModal');
    const btnCancelEdit = document.getElementById('btnCancelEdit');
    const formFichaTecnica = document.getElementById('formFichaTecnica');

    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    
    if (btnEditModal) {
        btnEditModal.addEventListener('click', (e) => {
            e.preventDefault();
            formFichaTecnica.classList.add('edit-mode');
        });
    }

    if (btnCancelEdit) {
        btnCancelEdit.addEventListener('click', (e) => {
            e.preventDefault();
            formFichaTecnica.classList.remove('edit-mode');
        });
    }

    // ---- Control de Modales con ESC ----
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const authModal = document.getElementById('authModal');
            if (authModal && authModal.style.display === 'flex') {
                authModal.style.display = 'none';
                return;
            }
            const bajaModal = document.getElementById('bajaModal');
            if (bajaModal && bajaModal.style.display === 'flex') {
                cerrarBajaModal();
                return;
            }
            const notaModal = document.getElementById('notaModal');
            if (notaModal && notaModal.style.display === 'flex') {
                cerrarNotaModal();
                return;
            }
            if (modal && modal.style.display === 'flex') {
                closeModal();
            }
        }
    });

    // ---- Eventos del Modal de Nota Popup ----
    const btnCloseNotaModal = document.getElementById('btnCloseNotaModal');
    const btnCancelNotaModal = document.getElementById('btnCancelNotaModal');
    const btnEditNotaModal = document.getElementById('btnEditNotaModal');
    const btnDeleteNotaModal = document.getElementById('btnDeleteNotaModal');
    const formNotaModal = document.getElementById('formNotaModal');

    if (btnCloseNotaModal) btnCloseNotaModal.addEventListener('click', cerrarNotaModal);
    if (btnCancelNotaModal) btnCancelNotaModal.addEventListener('click', cerrarNotaModal);

    if (btnEditNotaModal) {
        btnEditNotaModal.addEventListener('click', () => {
            activarEdicionNota();
        });
    }

    if (btnDeleteNotaModal) {
        btnDeleteNotaModal.addEventListener('click', async () => {
            const idNota = document.getElementById('notaModalIdNota').value;
            const cat = document.getElementById('notaModalCat').value;
            const idActivo = document.getElementById('notaModalIdActivo').value;

            if (!idNota) return;

            if (!confirm('¿Estás seguro de que deseas eliminar esta nota?')) return;
            const matricula = await solicitarMatricula('Ingrese su matrícula para confirmar la eliminación de la nota:');
            if (!matricula) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id_nota', idNota);
                formData.append('matricula', matricula);

                const res = await fetch('api/notas.php', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    cerrarNotaModal();
                    await fetchAndRenderNotesTags(cat, idActivo);
                } else {
                    alert(result.error || 'Error al eliminar nota.');
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión con el servidor.');
            }
        });
    }

    if (formNotaModal) {
        formNotaModal.addEventListener('submit', async (e) => {
            e.preventDefault();

            const mode = document.getElementById('notaModalMode').value;
            const idNota = document.getElementById('notaModalIdNota').value;
            const cat = document.getElementById('notaModalCat').value;
            const idActivo = document.getElementById('notaModalIdActivo').value;
            const titulo = document.getElementById('notaModalInputTitulo').value.trim();
            const fecha = document.getElementById('notaModalInputFecha').value.trim();
            const nota = document.getElementById('notaModalInputContenido').value.trim();

            if (!titulo || !nota) {
                alert('Por favor complete el título y la descripción de la nota.');
                return;
            }

            const promptMsg = mode === 'edit' 
                ? 'Ingrese su matrícula para autorizar la modificación de la nota:' 
                : 'Ingrese su matrícula para registrar la nueva nota:';

            const matricula = await solicitarMatricula(promptMsg);
            if (!matricula) return;

            try {
                const formData = new FormData();
                formData.append('action', mode === 'edit' ? 'update' : 'add');
                if (mode === 'edit') formData.append('id_nota', idNota);
                formData.append('categoria', cat);
                formData.append('id_activo', idActivo);
                formData.append('titulo', titulo);
                formData.append('fecha', fecha);
                formData.append('nota', nota);
                formData.append('matricula', matricula);

                const res = await fetch('api/notas.php', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    cerrarNotaModal();
                    await fetchAndRenderNotesTags(cat, idActivo);
                } else {
                    alert(result.error || 'Error al procesar la nota.');
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión con el servidor.');
            }
        });
    }

    // ---- Búsqueda Local (Filtrado de Tabla) ----
    const localSearchInput = document.getElementById('localSearch');
    if (localSearchInput) {
        localSearchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.table-container table tbody tr');
            
            rows.forEach(row => {
                const searchable = row.getAttribute('data-search') || row.textContent.toLowerCase();
                row.style.display = searchable.includes(term) ? '' : 'none';
            });
        });
    }

    if (formFichaTecnica) {
        formFichaTecnica.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Solicitar matrícula antes de guardar
            const matricula = await solicitarMatricula('Ingrese su matrícula para autorizar y guardar los cambios:');
            if (!matricula) return;

            const formData = new FormData(formFichaTecnica);
            formData.append('matricula_responsable', matricula);

            try {
                const response = await fetch('api/save_activo.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al guardar');
                }
            } catch (error) {
                console.error('Error saving:', error);
                alert('Error de conexión con la API.');
            }
        });
    }

    // ---- Drag and Drop logic ----
    const dropZone = document.getElementById('dropZone');
    const fileImport = document.getElementById('fileImport');
    
    if (dropZone && fileImport) {
        dropZone.addEventListener('click', () => fileImport.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileImport.files = e.dataTransfer.files;
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
        
        fileImport.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFileUpload(e.target.files[0]);
            }
        });
    }

    // Fetch units for dropdowns
    fetch('api/get_unidades.php')
        .then(res => res.json())
        .then(data => {
            window.globalUnidades = data;
        });

    // ---- Administrative Management Search ----
    const handleAdminSearch = (inputId, bodyId) => {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll(`#${bodyId} tr`);
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    };

    handleAdminSearch('searchUnidades', 'bodyUnidades');
    handleAdminSearch('searchEmpleados', 'bodyEmpleados');

    // ---- Borrado / Baja de Registros ----
    const btnCloseBajaModal = document.getElementById('btnCloseBajaModal');
    const btnCancelBajaModal = document.getElementById('btnCancelBajaModal');
    const formBajaModal = document.getElementById('formBajaModal');

    if (btnCloseBajaModal) btnCloseBajaModal.addEventListener('click', cerrarBajaModal);
    if (btnCancelBajaModal) btnCancelBajaModal.addEventListener('click', cerrarBajaModal);

    if (formBajaModal) {
        formBajaModal.addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = document.getElementById('bajaItemId').value;
            const cat = document.getElementById('bajaItemCat').value;
            const fecha = document.getElementById('bajaInputFecha').value;
            const estatus = document.getElementById('bajaInputEstatus').value;
            const observaciones = document.getElementById('bajaInputObservaciones').value.trim();
            const matricula = document.getElementById('bajaInputMatricula').value.trim();

            if (!id || !cat) return;
            if (!fecha) {
                alert('Por favor seleccione la fecha de retiro o baja.');
                return;
            }
            if (!observaciones) {
                alert('Por favor especifique el motivo u observación del movimiento.');
                return;
            }
            if (!matricula) {
                alert('Por favor ingrese su matrícula de autorización.');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('entity_cat', cat);
                formData.append('categoria', cat);
                formData.append('fecha_retiro', fecha);
                formData.append('estatus', estatus);
                formData.append('observaciones', observaciones);
                formData.append('matricula_responsable', matricula);

                const response = await fetch('api/delete_item.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    cerrarBajaModal();
                    if (typeof closeModal === 'function') closeModal();
                    location.reload();
                } else {
                    alert(result.error || 'Error al procesar la baja.');
                }
            } catch (error) {
                console.error('Error procesando baja:', error);
                alert('Error de conexión con la API.');
            }
        });
    }

    const btnDeleteModal = document.getElementById('btnDeleteModal');
    if (btnDeleteModal) {
        btnDeleteModal.addEventListener('click', () => {
            const id = document.getElementById('modalItemId').value;
            const cat = document.getElementById('modalItemCat').value;
            
            if (!id || !cat) return;
            abrirModalBaja(cat, id);
        });
    }
});

// ---- Solicitud de Matrícula (Modal) ----
function solicitarMatricula(promptMsg = 'Ingrese su matrícula para confirmar la acción:') {
    return new Promise((resolve) => {
        const authModal = document.getElementById('authModal');
        const promptEl = document.getElementById('authModalPrompt');
        const inputEl = document.getElementById('inputAuthMatricula');
        const btnCancel = document.getElementById('btnCancelAuth');
        const formAuth = document.getElementById('formAuthModal');

        if (!authModal || !inputEl) {
            const mat = prompt(promptMsg);
            return resolve(mat ? mat.trim() : null);
        }

        promptEl.textContent = promptMsg;
        inputEl.value = '';
        authModal.style.display = 'flex';
        inputEl.focus();

        const cleanup = () => {
            authModal.style.display = 'none';
            btnCancel.onclick = null;
            formAuth.onsubmit = null;
        };

        btnCancel.onclick = () => {
            cleanup();
            resolve(null);
        };

        formAuth.onsubmit = (e) => {
            e.preventDefault();
            const val = inputEl.value.trim();
            if (!val) {
                alert('Debe ingresar una matrícula válida.');
                return;
            }
            cleanup();
            resolve(val);
        };
    });
}

// ---- Funciones Globales ----

async function performGlobalSearch(query) {
    try {
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        displaySearchResults(results);
    } catch (error) {
        console.error('Error searching:', error);
    }
}

function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    if (!container) return;
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<div class="search-result-item" style="justify-content: center; color: var(--text-secondary);">No se encontraron resultados.</div>';
        container.style.display = 'block';
        return;
    }

    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'search-result-item';
        const icon = getIconForCategory(item.categoria);
        div.innerHTML = `
            <i class="ph ${icon}"></i>
            <div class="details">
                <span class="title">${item.fabricante || ''} ${item.modelo || ''} - Serie: ${item.serie}</span>
                <span class="subtitle">Unidad: ${item.unidad || 'N/A'}</span>
            </div>
        `;
        div.addEventListener('click', () => {
            openFichaTecnica(item.id, item.categoria);
            container.style.display = 'none';
        });
        container.appendChild(div);
    });
    container.style.display = 'block';
}

function getIconForCategory(cat) {
    switch(cat) {
        case 'computo': return 'ph-laptop';
        case 'impresoras': return 'ph-printer';
        case 'televisiones': return 'ph-monitor';
        case 'telefonia': return 'ph-phone';
        case 'red': 
        case 'redes': return 'ph-hard-drives';
        case 'consumibles': return 'ph-battery-full';
        case 'unidades': return 'ph-buildings';
        case 'empleados': return 'ph-user';
        default: return 'ph-cube';
    }
}

function hideSearchResults() {
    const c = document.getElementById('searchResults');
    if (c) c.style.display = 'none';
}

function openFichaTecnica(id, categoria) {
    const modal = document.getElementById('fichaModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('formFichaTecnica');
    if (form) form.classList.remove('edit-mode');

    modalTitle.textContent = `Ficha Técnica - ${categoria.toUpperCase()}`;
    modalBody.innerHTML = '<div style="text-align:center; padding:20px;"><i class="ph ph-spinner ph-spin"></i> Cargando...</div>';
    const btnDelete = document.getElementById('btnDeleteModal');
    if (btnDelete) btnDelete.style.display = 'flex';
    modal.style.display = 'flex';

    document.getElementById('modalItemId').value = id;
    document.getElementById('modalItemCat').value = categoria;

    fetch(`api/get_activo.php?id=${id}&cat=${categoria}`)
        .then(res => {
            if(!res.ok) throw new Error('Error en el servidor');
            return res.json();
        })
        .then(data => {
            renderModalContent(data, categoria, modalBody);
            // Cargar y mostrar bitácora de notas como botones / etiquetas
            if (id > 0) {
                loadAndRenderNotas(categoria, id, modalBody);
                loadAndRenderHistorial(categoria, id, modalBody);
            }
        })
        .catch(err => {
            console.error('Error cargando ficha:', err);
            modalBody.innerHTML = `<div style="color:red; text-align:center; padding:20px;"><i class="ph ph-warning"></i> Error al cargar datos: ${err.message}</div>`;
        });
}

function renderModalContent(data, categoria, container) {
    if(data.error) {
        container.innerHTML = `<div style="color:red;">${data.error}</div>`;
        return;
    }
    
    let sections = {
        'Datos Generales': {
            icon: 'ph-info',
            fields: ['serie', 'tipo', 'nombre_equipo', 'fabricante', 'modelo', 'monitor']
        },
        'Datos Técnicos': {
            icon: 'ph-cpu',
            fields: ['tipo_alm', 'capacidad', 'ram']
        },
        'Datos de Red': {
            icon: 'ph-wifi-high',
            fields: ['ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router']
        },
        'Ubicación y Asignación': {
            icon: 'ph-map-pin',
            fields: (categoria === 'computo') ? ['matricula', 'nombre', 'cuenta_dominio', 'categoria_usuario', 'unidad', 'area', 'departamento', 'extension'] : ['usuario', 'unidad', 'area', 'departamento', 'nombre', 'extension', 'categoria']
        },
        'Movimientos': {
            icon: 'ph-arrows-left-right',
            fields: ['estatus', 'proyecto', 'fecha_instalacion', 'fecha_retiro', 'observaciones']
        }
    };

    if (categoria === 'computo') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['serie', 'tipo', 'nombre_equipo', 'fabricante', 'modelo', 'monitor']
            },
            'Datos Técnicos': {
                icon: 'ph-cpu',
                fields: ['tipo_alm', 'capacidad', 'ram']
            },
            'Datos de Red': {
                icon: 'ph-wifi-high',
                fields: ['ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router']
            },
            'Ubicación y Asignación': {
                icon: 'ph-map-pin',
                fields: ['matricula', 'nombre', 'cuenta_dominio', 'categoria_usuario', 'unidad', 'area', 'departamento', 'extension']
            },
            'Movimientos': {
                icon: 'ph-arrows-left-right',
                fields: ['estatus', 'proyecto', 'fecha_instalacion', 'fecha_retiro', 'observaciones']
            }
        };
    } else if (categoria === 'impresoras') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['serie', 'tipo', 'fabricante', 'modelo']
            },
            'Datos de Red': {
                icon: 'ph-wifi-high',
                fields: ['ip']
            },
            'Ubicación y Asignación': {
                icon: 'ph-map-pin',
                fields: ['unidad', 'area', 'departamento']
            },
            'Movimientos': {
                icon: 'ph-arrows-left-right',
                fields: ['estatus', 'fecha_instalacion', 'fecha_retiro', 'observaciones']
            }
        };
    } else if (categoria === 'televisiones') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['serie', 'fabricante', 'modelo']
            },
            'Ubicación y Asignación': {
                icon: 'ph-map-pin',
                fields: ['unidad', 'area', 'departamento', 'uso']
            },
            'Movimientos': {
                icon: 'ph-arrows-left-right',
                fields: ['estatus', 'fecha_instalacion', 'fecha_retiro', 'observaciones']
            }
        };
    } else if (categoria === 'telefonia') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['serie', 'tipo', 'fabricante', 'modelo']
            },
            'Datos de Red': {
                icon: 'ph-wifi-high',
                fields: ['ip', 'nodo', 'p_router']
            },
            'Ubicación y Asignación': {
                icon: 'ph-map-pin',
                fields: ['unidad', 'area', 'departamento', 'nombre', 'extension']
            },
            'Movimientos': {
                icon: 'ph-arrows-left-right',
                fields: ['estatus', 'fecha_instalacion', 'fecha_retiro', 'observaciones']
            }
        };
    } else if (categoria === 'redes') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['serie', 'tipo', 'fabricante', 'modelo']
            },
            'Datos de Red': {
                icon: 'ph-wifi-high',
                fields: ['ip_gestion', 'mac', 'num_puertos', 'nodo_uplink']
            },
            'Ubicación y Asignación': {
                icon: 'ph-map-pin',
                fields: ['unidad', 'area']
            },
            'Movimientos': {
                icon: 'ph-arrows-left-right',
                fields: ['estatus', 'observaciones']
            }
        };
    } else if (categoria === 'consumibles') {
        sections = {
            'Datos Generales': {
                icon: 'ph-info',
                fields: ['tipo', 'categoria', 'longitud', 'cantidad_stock', 'unidad', 'estatus', 'observaciones']
            }
        };
    } else if (categoria === 'empleados') {
        sections = {
            'Datos Generales': {
                icon: 'ph-user',
                fields: ['matricula', 'nombre', 'usuario', 'categoria', 'unidad', 'password']
            }
        };
    } else if (categoria === 'unidades') {
        sections = {
            'Datos Generales': {
                icon: 'ph-buildings',
                fields: ['clave', 'unidad', 'zona']
            }
        };
    }

    let html = '';
    let unassignedKeys = Object.keys(data).filter(k => k !== 'id' && k !== 'modificado_por');

    const labelMap = {
        'cuenta_dominio': 'CUENTA',
        'categoria_usuario': 'CATEGORIA',
        'tipo_alm': 'TIPO DE ALMACENAMIENTO',
        'p_router': 'PUERTO ROUTER',
        'num_puertos': '# DE PUERTOS',
        'ip_gestion': 'IP',
        'nombre_equipo': 'NOMBRE DEL EQUIPO',
        'cantidad_stock': 'STOCK',
        'estatus': (categoria === 'consumibles') ? 'STATUS' : 'ESTATUS',
        'fecha_instalacion': 'FECHA DE INSTALACION',
        'fecha_retiro': 'FECHA DE RETIRO'
    };

    for (const [sectionName, sectionInfo] of Object.entries(sections)) {
        let sectionHtml = '';
        sectionInfo.fields.forEach(field => {
            if (unassignedKeys.includes(field)) {
                const val = data[field] || 'N/A';
                const label = labelMap[field] || field.replace(/_/g, ' ').toUpperCase();
                const isFullWidth = (field === 'observaciones');
                sectionHtml += `
                    <div class="form-group field-${field} ${isFullWidth ? 'full-width' : ''}">
                        <label>${label}</label>
                        <div class="form-value">${val}</div>
                        ${field === 'unidad' && categoria !== 'unidades' ? `
                            <select name="${field}" data-field="${field}">
                                <option value="">Seleccione Unidad...</option>
                                ${window.globalUnidades ? window.globalUnidades.map(u => `
                                    <option value="${u.unidad}" ${u.unidad === val ? 'selected' : ''}>${u.unidad}</option>
                                `).join('') : `<option value="${val}">${val}</option>`}
                            </select>
                        ` : `
                            <input type="text" name="${field}" data-field="${field}" value="${val !== 'N/A' ? val : ''}">
                        `}
                    </div>`;
                unassignedKeys = unassignedKeys.filter(k => k !== field);
            }
        });

        if (sectionHtml !== '') {
            html += `
                <div class="section-group">
                    <div class="section-title"><i class="ph ${sectionInfo.icon}"></i> ${sectionName}</div>
                    <div class="section-grid">
                        ${sectionHtml}
                    </div>
                </div>`;
        }
    }

    if (unassignedKeys.length > 0) {
        let sectionHtml = '';
        unassignedKeys.forEach(field => {
            const val = data[field] || 'N/A';
            const label = labelMap[field] || field.replace(/_/g, ' ').toUpperCase();
            sectionHtml += `
                <div class="form-group">
                    <label>${label}</label>
                    <div class="form-value">${val}</div>
                    <input type="text" name="${field}" value="${val !== 'N/A' ? val : ''}">
                </div>`;
        });
        html += `
            <div class="section-group">
                <div class="section-title"><i class="ph ph-dots-three-circle"></i> Otros Datos</div>
                <div class="section-grid">
                    ${sectionHtml}
                </div>
            </div>`;
    }

    container.innerHTML = html;

    // Logic for conditional visibility of dates
    const statusInput = container.querySelector('input[name="estatus"]');
    const fInstalacion = container.querySelector('.field-fecha_instalacion');
    const fRetiro = container.querySelector('.field-fecha_retiro');

    const updateDateVisibility = (status) => {
        if (!status) return;
        const s = status.toUpperCase();
        const isActive = (s === 'ACTIVO' || s === 'ACTTIVO');
        if (fInstalacion) fInstalacion.style.display = isActive ? 'flex' : 'none';
        if (fRetiro) fRetiro.style.display = (!isActive && s !== 'N/A' && s !== '') ? 'flex' : 'none';
    };

    if (statusInput) {
        statusInput.addEventListener('input', (e) => updateDateVisibility(e.target.value));
        updateDateVisibility(statusInput.value);
    }

    // --- AUTO-FILL EMPLOYEE DATA ---
    const matriculaInput = container.querySelector('input[name="matricula"]');
    if (matriculaInput && categoria === 'computo') {
        matriculaInput.addEventListener('blur', async (e) => {
            const matricula = e.target.value.trim();
            if (matricula.length >= 4) {
                try {
                    const response = await fetch(`api/get_empleado.php?matricula=${encodeURIComponent(matricula)}`);
                    const emp = await response.json();
                    if (emp && !emp.not_found && !emp.error) {
                        const nameInput = container.querySelector('input[name="nombre"]');
                        const accountInput = container.querySelector('input[name="cuenta"]');
                        const catInput = container.querySelector('input[name="categoria_usuario"]');
                        const domInput = container.querySelector('input[name="cuenta_dominio"]');
                        
                        if (nameInput) nameInput.value = emp.nombre || '';
                        if (accountInput) accountInput.value = emp.cuenta || '';
                        if (catInput) catInput.value = emp.categoria || '';
                        if (domInput && emp.cuenta) domInput.value = emp.cuenta;
                        
                        matriculaInput.style.borderColor = 'var(--primary-color)';
                    } else if (emp.not_found) {
                        console.log('Empleado no encontrado, proceda a agregar manualmente.');
                        matriculaInput.style.borderColor = 'var(--warning-color)';
                    }
                } catch (err) {
                    console.error('Error fetching employee:', err);
                }
            }
        });
    }
}

// ---- Bitácora de Notas (Visualización con Botones / Etiquetas y Popup) ----

async function loadAndRenderNotas(categoria, id, container) {
    let notesSection = container.querySelector('.section-notes');
    if (!notesSection) {
        notesSection = document.createElement('div');
        notesSection.className = 'section-notes';
        container.appendChild(notesSection);
    }

    notesSection.innerHTML = `
        <div class="notes-header">
            <div class="section-title"><i class="ph ph-note"></i> Bitácora de Notas</div>
            <button type="button" class="btn-add-note-pill" id="btnOpenNewNotaModal">+ NOTA</button>
        </div>
        <div class="notes-tags-container" id="notesTagsContainer">
            <div style="color:var(--text-secondary); font-size:0.85rem;"><i class="ph ph-spinner ph-spin"></i> Cargando notas...</div>
        </div>
    `;

    const btnAdd = notesSection.querySelector('#btnOpenNewNotaModal');
    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            abrirModalNuevaNota(categoria, id);
        });
    }

    await fetchAndRenderNotesTags(categoria, id);
}

async function fetchAndRenderNotesTags(categoria, id) {
    const container = document.getElementById('notesTagsContainer');
    if (!container) return;

    try {
        const res = await fetch(`api/notas.php?action=get&cat=${encodeURIComponent(categoria)}&id_activo=${id}`);
        const notas = await res.json();

        if (!notas || notas.length === 0) {
            container.innerHTML = '<span class="note-empty-text">No hay notas registradas para este activo.</span>';
            return;
        }

        window.currentLoadedNotas = notas;
        container.innerHTML = '';

        notas.forEach((n, idx) => {
            let fechaStr = '';
            if (n.fecha) {
                const parts = n.fecha.substring(0, 10).split('-');
                if (parts.length === 3) {
                    fechaStr = `${parts[2]}/${parts[1]}/${parts[0]}`;
                } else {
                    fechaStr = n.fecha.substring(0, 10);
                }
            }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'note-tag-btn';
            btn.textContent = `${n.titulo} (${fechaStr})`;
            btn.addEventListener('click', () => {
                abrirModalDetalleNota(n, categoria, id);
            });
            container.appendChild(btn);
        });
    } catch (err) {
        console.error(err);
        container.innerHTML = '<span style="color:red; font-size:0.85rem;">Error al cargar notas.</span>';
    }
}

function abrirModalNuevaNota(categoria, idActivo) {
    const modal = document.getElementById('notaModal');
    const titleEl = document.getElementById('notaModalTitle');
    const modeEl = document.getElementById('notaModalMode');
    const idNotaEl = document.getElementById('notaModalIdNota');
    const catEl = document.getElementById('notaModalCat');
    const idActivoEl = document.getElementById('notaModalIdActivo');

    const viewTit = document.getElementById('notaModalViewTitulo');
    const viewFec = document.getElementById('notaModalViewFecha');
    const viewCont = document.getElementById('notaModalViewContenido');
    const viewAuthBox = document.getElementById('notaModalViewAuthorBox');

    const inpTit = document.getElementById('notaModalInputTitulo');
    const inpFec = document.getElementById('notaModalInputFecha');
    const inpCont = document.getElementById('notaModalInputContenido');

    const btnEdit = document.getElementById('btnEditNotaModal');
    const btnDel = document.getElementById('btnDeleteNotaModal');
    const btnSave = document.getElementById('btnSaveNotaModal');
    const btnCancel = document.getElementById('btnCancelNotaModal');

    modeEl.value = 'add';
    idNotaEl.value = '';
    catEl.value = categoria;
    idActivoEl.value = idActivo;

    titleEl.innerHTML = '<i class="ph ph-note-pencil" style="color: var(--accent-green);"></i> Nueva Nota';

    // Ocultar vistas de texto
    viewTit.style.display = 'none';
    viewFec.style.display = 'none';
    viewCont.style.display = 'none';
    viewAuthBox.style.display = 'none';

    // Mostrar inputs
    inpTit.style.display = 'block';
    inpFec.style.display = 'block';
    inpCont.style.display = 'block';

    inpTit.value = '';
    inpFec.value = new Date().toISOString().split('T')[0]; // Fecha de hoy por defecto
    inpCont.value = '';

    btnEdit.style.display = 'none';
    btnDel.style.display = 'none';
    btnSave.style.display = 'flex';
    btnSave.textContent = 'Guardar';
    btnCancel.textContent = 'Cancelar';

    modal.style.display = 'flex';
    inpFec.focus();
}

function abrirModalDetalleNota(nota, categoria, idActivo) {
    const modal = document.getElementById('notaModal');
    const titleEl = document.getElementById('notaModalTitle');
    const modeEl = document.getElementById('notaModalMode');
    const idNotaEl = document.getElementById('notaModalIdNota');
    const catEl = document.getElementById('notaModalCat');
    const idActivoEl = document.getElementById('notaModalIdActivo');

    const viewTit = document.getElementById('notaModalViewTitulo');
    const viewFec = document.getElementById('notaModalViewFecha');
    const viewCont = document.getElementById('notaModalViewContenido');
    const viewAuthBox = document.getElementById('notaModalViewAuthorBox');
    const viewAuth = document.getElementById('notaModalViewAuthor');

    const inpTit = document.getElementById('notaModalInputTitulo');
    const inpFec = document.getElementById('notaModalInputFecha');
    const inpCont = document.getElementById('notaModalInputContenido');

    const btnEdit = document.getElementById('btnEditNotaModal');
    const btnDel = document.getElementById('btnDeleteNotaModal');
    const btnSave = document.getElementById('btnSaveNotaModal');
    const btnCancel = document.getElementById('btnCancelNotaModal');

    modeEl.value = 'view';
    idNotaEl.value = nota.id_nota;
    catEl.value = categoria;
    idActivoEl.value = idActivo;

    titleEl.innerHTML = '<i class="ph ph-note" style="color: var(--accent-green);"></i> Detalle de Nota';

    // Llenar datos en texto
    viewTit.textContent = nota.titulo;
    viewFec.textContent = nota.fecha ? nota.fecha.substring(0, 16) : '';
    viewCont.textContent = nota.nota;
    viewAuth.textContent = `${nota.personal || 'Personal'} (Matrícula: ${nota.matricula || 'N/A'})`;

    // Llenar datos en inputs (por si entra a modo edición)
    inpTit.value = nota.titulo;
    inpFec.value = nota.fecha ? nota.fecha.substring(0, 10) : '';
    inpCont.value = nota.nota;

    // Mostrar vistas de solo lectura
    viewTit.style.display = 'block';
    viewFec.style.display = 'block';
    viewCont.style.display = 'block';
    viewAuthBox.style.display = 'block';

    // Ocultar inputs
    inpTit.style.display = 'none';
    inpFec.style.display = 'none';
    inpCont.style.display = 'none';

    // Botones
    btnEdit.style.display = 'flex';
    btnDel.style.display = 'flex';
    btnSave.style.display = 'none';
    btnCancel.textContent = 'Cerrar';

    modal.style.display = 'flex';
}

function activarEdicionNota() {
    const titleEl = document.getElementById('notaModalTitle');
    const modeEl = document.getElementById('notaModalMode');

    const viewTit = document.getElementById('notaModalViewTitulo');
    const viewFec = document.getElementById('notaModalViewFecha');
    const viewCont = document.getElementById('notaModalViewContenido');

    const inpTit = document.getElementById('notaModalInputTitulo');
    const inpFec = document.getElementById('notaModalInputFecha');
    const inpCont = document.getElementById('notaModalInputContenido');

    const btnEdit = document.getElementById('btnEditNotaModal');
    const btnSave = document.getElementById('btnSaveNotaModal');
    const btnCancel = document.getElementById('btnCancelNotaModal');

    modeEl.value = 'edit';
    titleEl.innerHTML = '<i class="ph ph-pencil-simple" style="color: var(--accent-green);"></i> Editar Nota';

    // Ocultar vistas
    viewTit.style.display = 'none';
    viewFec.style.display = 'none';
    viewCont.style.display = 'none';

    // Mostrar inputs
    inpTit.style.display = 'block';
    inpFec.style.display = 'block';
    inpCont.style.display = 'block';

    btnEdit.style.display = 'none';
    btnSave.style.display = 'flex';
    btnSave.textContent = 'Guardar Cambios';
    btnCancel.textContent = 'Cancelar';

    inpTit.focus();
}

function cerrarNotaModal() {
    const modal = document.getElementById('notaModal');
    if (modal) modal.style.display = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ---- Historial de Movimientos y Auditoría en Ficha Técnica ----

async function loadAndRenderHistorial(categoria, id, container) {
    let histSection = container.querySelector('.section-historial');
    if (!histSection) {
        histSection = document.createElement('div');
        histSection.className = 'section-historial';
        container.appendChild(histSection);
    }

    histSection.innerHTML = `
        <div class="section-title"><i class="ph ph-clock-counter-clockwise"></i> Historial de Movimientos y Cambios</div>
        <div class="historial-timeline-container" id="historialTimelineContainer">
            <div style="color:var(--text-secondary); font-size:0.85rem;"><i class="ph ph-spinner ph-spin"></i> Cargando historial...</div>
        </div>
    `;

    const timelineContainer = histSection.querySelector('#historialTimelineContainer');
    if (!timelineContainer) return;

    try {
        const res = await fetch(`api/historial.php?action=get_by_activo&cat=${encodeURIComponent(categoria)}&id_activo=${id}`);
        const historial = await res.json();

        if (!historial || historial.length === 0) {
            timelineContainer.innerHTML = '<span class="historial-empty-text">Sin movimientos o cambios registrados para este activo.</span>';
            return;
        }

        timelineContainer.innerHTML = '';

        historial.forEach((item) => {
            const card = document.createElement('div');
            let badgeClass = 'badge-modificacion';
            let cardModClass = 'card-modificacion';
            if (item.accion === 'ALTA') {
                badgeClass = 'badge-alta';
                cardModClass = 'card-alta';
            } else if (item.accion === 'ELIMINACION' || item.accion === 'BAJA') {
                badgeClass = 'badge-eliminacion';
                cardModClass = 'card-eliminacion';
            } else if (item.accion === 'NOTA') {
                badgeClass = 'badge-nota';
                cardModClass = 'card-nota';
            }

            card.className = `timeline-card ${cardModClass}`;

            let fechaStr = item.fecha || '';
            if (item.fecha && item.fecha.length >= 16) {
                const fParts = item.fecha.substring(0, 10).split('-');
                const timePart = item.fecha.substring(11, 16);
                if (fParts.length === 3) {
                    fechaStr = `${fParts[2]}/${fParts[1]}/${fParts[0]} ${timePart}`;
                }
            }

            let formattedDetails = '';
            if (item.detalles && item.detalles.includes(' | ')) {
                const detailParts = item.detalles.split(' | ');
                formattedDetails = detailParts.map(p => `<span class="timeline-change-item">${escapeHtml(p)}</span>`).join(' ');
            } else {
                formattedDetails = escapeHtml(item.detalles || 'Sin detalles');
            }

            card.innerHTML = `
                <div class="timeline-card-header">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="hist-badge ${badgeClass}">${escapeHtml(item.accion)}</span>
                        <span class="timeline-author"><i class="ph ph-user"></i> ${escapeHtml(item.nombre_responsable || 'Personal')} (Matrícula: <strong>${escapeHtml(item.matricula_responsable || 'N/A')}</strong>)</span>
                    </div>
                    <span class="timeline-date"><i class="ph ph-calendar"></i> ${escapeHtml(fechaStr)}</span>
                </div>
                <div class="timeline-details">${formattedDetails}</div>
            `;

            timelineContainer.appendChild(card);
        });

    } catch (err) {
        console.error('Error cargando historial:', err);
        timelineContainer.innerHTML = '<span class="historial-empty-text" style="color:var(--danger-color);">Error al cargar el historial.</span>';
    }
}

function closeModal() {
    const modal = document.getElementById('fichaModal');
    if (modal) modal.style.display = 'none';
}

function openAddModal(categoria) {
    const modal = document.getElementById('fichaModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('formFichaTecnica');
    
    modalTitle.textContent = `Agregar Nuevo - ${categoria.toUpperCase()}`;
    const btnDelete = document.getElementById('btnDeleteModal');
    if (btnDelete) btnDelete.style.display = 'none';

    document.getElementById('modalItemId').value = '';
    document.getElementById('modalItemCat').value = categoria;
    if (form) form.classList.add('edit-mode');

    let fields = [];
    switch(categoria) {
        case 'computo': fields = ['serie', 'tipo', 'nombre_equipo', 'fabricante', 'modelo', 'monitor', 'tipo_alm', 'capacidad', 'ram', 'ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router', 'matricula', 'nombre', 'cuenta_dominio', 'categoria_usuario', 'unidad', 'area', 'departamento', 'extension', 'estatus', 'proyecto', 'fecha_instalacion', 'fecha_retiro', 'observaciones']; break;
        case 'impresoras': fields = ['tipo', 'serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'ip', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
        case 'televisiones': fields = ['serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'uso', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
        case 'telefonia': fields = ['serie', 'fabricante', 'modelo', 'tipo', 'ip', 'nombre', 'extension', 'nodo', 'p_router', 'unidad', 'area', 'departamento', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
        case 'redes': fields = ['tipo', 'serie', 'fabricante', 'modelo', 'num_puertos', 'ip_gestion', 'mac', 'nodo_uplink', 'unidad', 'area', 'estatus', 'observaciones']; break;
        case 'consumibles': fields = ['tipo', 'categoria', 'longitud', 'cantidad_stock', 'unidad', 'estatus', 'observaciones']; break;
        case 'unidades': fields = ['clave', 'unidad', 'zona']; break;
        case 'empleados': fields = ['matricula', 'nombre', 'usuario', 'password', 'categoria', 'unidad']; break;
    }

    // Build empty data object
    let emptyData = {};
    fields.forEach(f => emptyData[f] = '');
    
    // Use the exact same render logic!
    renderModalContent(emptyData, categoria, modalBody);
    
    modal.style.display = 'flex';
}

let currentImportCategory = '';
function openImportModal(categoria) {
    currentImportCategory = categoria;
    const modal = document.getElementById('importModal');
    const uploadStatus = document.getElementById('uploadStatus');
    
    uploadStatus.textContent = '';
    modal.style.display = 'flex';

    // Link directly to the server-side API for the most reliable download
    const linkDescargar = document.getElementById('linkDescargarPlantilla');
    linkDescargar.href = `api/template.php?cat=${categoria}`;
    linkDescargar.onclick = null;
}

function downloadTemplate(categoria) {
    let headers = [];
    switch(categoria) {
        case 'computo': headers = ['serie', 'tipo', 'fabricante', 'modelo', 'monitor', 'tipo_alm', 'capacidad', 'ram', 'ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router', 'area', 'departamento', 'extension', 'proyecto', 'fecha_instalacion', 'estatus', 'observaciones']; break;
        case 'impresoras': headers = ['tipo', 'serie', 'fabricante', 'modelo', 'area', 'departamento', 'ip', 'fecha_instalacion', 'estatus', 'observaciones']; break;
        case 'televisiones': headers = ['serie', 'fabricante', 'modelo', 'area', 'departamento', 'uso', 'fecha_instalacion', 'estatus', 'observaciones']; break;
        case 'telefonia': headers = ['serie', 'fabricante', 'modelo', 'tipo', 'ip', 'nombre', 'extension', 'nodo', 'p_router', 'area', 'departamento', 'fecha_instalacion', 'estatus', 'observaciones']; break;
        case 'redes': headers = ['tipo', 'serie', 'fabricante', 'modelo', 'num_puertos', 'ip_gestion', 'mac', 'nodo_uplink', 'area', 'estatus', 'observaciones']; break;
        case 'consumibles': headers = ['tipo', 'categoria', 'longitud', 'cantidad_stock', 'estatus', 'observaciones']; break;
    }
    const ws = XLSX.utils.aoa_to_sheet([headers]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, "Plantilla");
    XLSX.writeFile(wb, `plantilla_${categoria}.xlsx`);
}

async function handleFileUpload(file) {
    if (!file) return;
    const uploadStatus = document.getElementById('uploadStatus');
    uploadStatus.textContent = 'Subiendo archivo...';
    
    const formData = new FormData();
    formData.append('fileExcel', file);
    formData.append('entity_cat', currentImportCategory);

    try {
        const response = await fetch('api/import.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            uploadStatus.textContent = result.message;
            uploadStatus.style.color = '#4CAF50';
            setTimeout(() => location.reload(), 1500);
        } else {
            uploadStatus.textContent = result.error || result.message || 'Error desconocido';
            uploadStatus.style.color = '#f44336';
        }
    } catch (error) {
        console.error('Error uploading:', error);
        uploadStatus.textContent = 'Error de conexión con el servidor.';
        uploadStatus.style.color = '#f44336';
    }
}

function exportData(cat) {
    window.location.href = `api/export.php?cat=${cat}`;
}

function filterEmployeesByUnit(unitName) {
    const rows = document.querySelectorAll('#bodyEmpleados tr');
    const searchInput = document.getElementById('searchEmpleados');
    
    if (searchInput) searchInput.value = '';

    rows.forEach(row => {
        const rowUnit = row.getAttribute('data-unidad-name');
        if (!unitName || rowUnit === unitName) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    const unitRows = document.querySelectorAll('#bodyUnidades tr');
    unitRows.forEach(r => {
        r.style.backgroundColor = '';
        if (r.textContent.includes(unitName)) {
             r.style.backgroundColor = 'rgba(76, 175, 80, 0.15)';
        }
    });
}

function filterByStat(term) {
    const localSearch = document.getElementById('localSearch');
    if (localSearch) {
        localSearch.value = term;
        localSearch.dispatchEvent(new Event('input'));
    }
}

function filterByStat(category, term) {
    const localSearch = document.getElementById('localSearch');
    if (localSearch) {
        if (term === 'Sin Especificar') {
            let fieldKey = (category || '').toLowerCase()
                .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                .trim();
            if (fieldKey.includes('fabricante') || fieldKey.includes('modelo')) fieldKey = 'fabricante';
            if (fieldKey.includes('area')) fieldKey = 'area';
            if (fieldKey.includes('departamento')) fieldKey = 'departamento';
            if (fieldKey.includes('estatus')) fieldKey = 'estatus';
            if (fieldKey.includes('proyecto')) fieldKey = 'proyecto';
            if (fieldKey.includes('tipo')) fieldKey = 'tipo';
            if (fieldKey.includes('uso')) fieldKey = 'uso';
            if (fieldKey.includes('categoria')) fieldKey = 'categoria';
            
            localSearch.value = 'sin_' + fieldKey;
        } else {
            localSearch.value = term;
        }
        localSearch.dispatchEvent(new Event('input'));
    }
}

// ---- Funciones de Baja / Retiro de Equipo ----
function abrirModalBaja(cat, id) {
    const bajaModal = document.getElementById('bajaModal');
    if (!bajaModal) return;

    document.getElementById('bajaItemId').value = id;
    document.getElementById('bajaItemCat').value = cat;
    
    // Set default date to today's date (YYYY-MM-DD)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bajaInputFecha').value = today;
    document.getElementById('bajaInputEstatus').value = 'RETIRADO';
    document.getElementById('bajaInputObservaciones').value = '';
    document.getElementById('bajaInputMatricula').value = '';

    bajaModal.style.display = 'flex';
    document.getElementById('bajaInputObservaciones').focus();
}

function cerrarBajaModal() {
    const bajaModal = document.getElementById('bajaModal');
    if (bajaModal) bajaModal.style.display = 'none';
}
