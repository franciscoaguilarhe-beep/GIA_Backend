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

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });

    /* 
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    */

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

    if(formFichaTecnica) {
        formFichaTecnica.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formFichaTecnica);
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

    // ---- Borrado de Registros ----
    const btnDeleteModal = document.getElementById('btnDeleteModal');
    if (btnDeleteModal) {
        btnDeleteModal.addEventListener('click', async () => {
            const id = document.getElementById('modalItemId').value;
            const cat = document.getElementById('modalItemCat').value;
            
            if (!id || !cat) return;
            
            if (confirm(`¿Estás seguro de que deseas eliminar este registro de ${cat.toUpperCase()}? Esta acción no se puede deshacer.`)) {
                try {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('categoria', cat);
                    
                    const response = await fetch('api/delete_item.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.error || 'Error al eliminar');
                    }
                } catch (error) {
                    console.error('Error deleting:', error);
                    alert('Error de conexión con la API.');
                }
            }
        });
    }
});

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
        case 'red': return 'ph-hard-drives';
        case 'consumibles': return 'ph-battery-full';
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
    document.getElementById('formFichaTecnica').classList.remove('edit-mode');
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
            console.log('Datos recibidos:', data);
            renderModalContent(data, categoria, modalBody);
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
                        
                        // Notify user or visual feedback
                        matriculaInput.style.borderColor = 'var(--primary-color)';
                    } else if (emp.not_found) {
                        // User can add new employee manually
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
    linkDescargar.onclick = null; // Remove the JS click handler
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
    XLSX.utils.book_append_sheet(wb, ws, "Plantilla");
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
    
    // Clear search input to avoid conflict
    if (searchInput) searchInput.value = '';

    rows.forEach(row => {
        const rowUnit = row.getAttribute('data-unidad-name');
        if (!unitName || rowUnit === unitName) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Highlight selected unit row
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
            let fieldKey = category;
            // Mapeo de nombres de bloques a claves de búsqueda
            if (category === 'fabricante_modelo') fieldKey = 'fabricante';
            if (category === 'area') fieldKey = 'area';
            if (category === 'estatus') fieldKey = 'estatus';
            if (category === 'proyecto') fieldKey = 'proyecto';
            if (category === 'tipo') fieldKey = 'tipo';
            if (category === 'uso') fieldKey = 'uso';
            
            localSearch.value = 'sin_' + fieldKey;
        } else {
            localSearch.value = term;
        }
        localSearch.dispatchEvent(new Event('input'));
    }
}

