document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('informesTableBody');
    const filterFecha = document.getElementById('filterFecha');
    const filterArea = document.getElementById('filterArea');
    const filterRegistrado = document.getElementById('filterRegistrado');
    const btnAgregar = document.getElementById('btnAgregar');
    const btnImportar = document.getElementById('btnImportar');
    const btnExportar = document.getElementById('btnExportar');
    
    // Add/Edit Modal
    const modalAgregar = document.getElementById('modalAgregar');
    const closeModal = document.getElementById('closeModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const formAgregar = document.getElementById('formAgregarInforme');
    const matriculaFeedback = document.getElementById('matriculaFeedback');
    
    // Detail Modal
    const detalleModal = document.getElementById('detalleModal');
    const btnCloseDetalle = document.getElementById('btnCloseDetalle');
    const btnEditDetalle = document.getElementById('btnEditDetalle');
    
    let informesData = [];
    let fpFilter = null;
    let selectedDateRange = [];

    // Initialize Flatpickr for filter (Range mode)
    fpFilter = flatpickr(filterFecha, {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "es",
        onChange: function(selectedDates, dateStr, instance) {
            selectedDateRange = selectedDates;
            renderTable();
        }
    });

    // Load data
    function loadInformes() {
        fetch('api/informes.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    informesData = data.data;
                    renderTable();
                } else {
                    alert('Error al cargar informes: ' + data.message);
                }
            })
            .catch(err => console.error(err));
    }

    function renderTable() {
        const areaVal = filterArea.value.toLowerCase().trim();
        const registradoVal = filterRegistrado.value.toLowerCase().trim();

        tableBody.innerHTML = '';

        const filtered = informesData.filter(item => {
            let matchFecha = true;
            if (selectedDateRange.length === 2) {
                const itemDate = new Date(item.fecha + "T00:00:00");
                const startDate = selectedDateRange[0];
                const endDate = selectedDateRange[1];
                startDate.setHours(0,0,0,0);
                endDate.setHours(23,59,59,999);
                if (itemDate < startDate || itemDate > endDate) {
                    matchFecha = false;
                }
            } else if (selectedDateRange.length === 1) {
                const itemDate = new Date(item.fecha + "T00:00:00");
                const startDate = selectedDateRange[0];
                startDate.setHours(0,0,0,0);
                if (itemDate.getTime() !== startDate.getTime()) {
                    matchFecha = false;
                }
            }

            let searchStr = (item.area + ' ' + item.acciones).toLowerCase();
            let matchArea = !areaVal || searchStr.includes(areaVal);
            
            let matchRegistrado = !registradoVal || (item.nombre_empleado || '').toLowerCase().includes(registradoVal);

            return matchFecha && matchArea && matchRegistrado;
        });

        filtered.forEach(item => {
            const tr = document.createElement('tr');
            tr.style.cursor = "pointer";
            tr.addEventListener('click', () => showDetalle(item));
            tr.innerHTML = `
                <td>${item.fecha}</td>
                <td><strong>${item.area}</strong></td>
                <td>${item.acciones.substring(0, 50)}${item.acciones.length > 50 ? '...' : ''}</td>
                <td>${item.hora_inicio}</td>
                <td>${item.duracion} min</td>
                <td title="${item.matricula_empleado}">${item.nombre_empleado}</td>
            `;
            tableBody.appendChild(tr);
        });
        
        if(filtered.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px;">No se encontraron registros.</td></tr>`;
        }
    }

    filterArea.addEventListener('input', renderTable);
    if (filterRegistrado) {
        filterRegistrado.addEventListener('input', renderTable);
    }

    let currentItem = null;

    // Show Detail Modal
    function showDetalle(item) {
        currentItem = item;
        document.getElementById('detFecha').textContent = item.fecha;
        document.getElementById('detArea').textContent = item.area;
        document.getElementById('detAcciones').textContent = item.acciones;
        document.getElementById('detHora').textContent = item.hora_inicio;
        document.getElementById('detDuracion').textContent = item.duracion + " min";
        document.getElementById('detRegistradoTexto').textContent = item.nombre_empleado + " (" + item.matricula_empleado + ")";
        
        detalleModal.style.display = 'flex';
    }

    btnCloseDetalle.addEventListener('click', () => {
        detalleModal.style.display = 'none';
    });

    btnEditDetalle.addEventListener('click', () => {
        detalleModal.style.display = 'none';
        openAddEditModal(currentItem);
    });

    // Add / Edit Modal logic
    function openAddEditModal(item = null) {
        formAgregar.reset();
        matriculaFeedback.style.display = 'none';
        document.getElementById('informeId').value = item ? item.id : '';
        document.getElementById('modalTitle').innerHTML = `<i class="ph ph-file-text" style="color: var(--accent-green);"></i> ${item ? 'Editar Informe' : 'Agregar Informe'}`;
        
        if (item) {
            document.getElementById('addFecha').value = item.fecha;
            document.getElementById('addArea').value = item.area;
            document.getElementById('addAcciones').value = item.acciones;
            document.getElementById('addHoraInicio').value = item.hora_inicio;
            document.getElementById('addDuracion').value = item.duracion;
            
            // If editing, maybe we don't require re-signing or we hide it.
            // Let's hide matricula to avoid reassignment unless they change it.
            document.getElementById('matriculaGroup').style.display = 'none';
            document.getElementById('addMatricula').removeAttribute('required');
        } else {
            document.getElementById('matriculaGroup').style.display = 'block';
            document.getElementById('addMatricula').setAttribute('required', 'required');
        }
        
        modalAgregar.style.display = 'flex';
    }

    btnAgregar.addEventListener('click', () => openAddEditModal(null));

    const closeModalFunc = () => {
        modalAgregar.style.display = 'none';
    };

    closeModal.addEventListener('click', closeModalFunc);
    btnCancelModal.addEventListener('click', closeModalFunc);

    window.addEventListener('click', (e) => {
        if (e.target == modalAgregar) modalAgregar.style.display = 'none';
        if (e.target == detalleModal) detalleModal.style.display = 'none';
    });

    formAgregar.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const id = document.getElementById('informeId').value;
        const data = {
            id: id,
            fecha: document.getElementById('addFecha').value,
            area: document.getElementById('addArea').value,
            acciones: document.getElementById('addAcciones').value,
            hora_inicio: document.getElementById('addHoraInicio').value,
            duracion: document.getElementById('addDuracion').value,
            matricula: document.getElementById('addMatricula').value
        };

        const method = id ? 'PUT' : 'POST';

        fetch('api/informes.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.success) {
                // alert('Informe guardado exitosamente.');
                modalAgregar.style.display = 'none';
                loadInformes();
            } else {
                if (resData.error_type === 'matricula') {
                    matriculaFeedback.style.display = 'block';
                    matriculaFeedback.textContent = resData.message;
                } else {
                    alert('Error: ' + resData.message);
                }
            }
        })
        .catch(err => console.error(err));
    });

    // Importar (Multiple files)
    btnImportar.addEventListener('change', async (e) => {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        let mat = '';
        if (typeof solicitarMatricula === 'function') {
            mat = await solicitarMatricula('Ingrese su matrícula para autorizar la importación:');
            if (!mat) {
                btnImportar.value = ''; // Reset
                return; // Cancelado
            }
        } else {
            mat = prompt('Ingrese su matrícula para autorizar la importación:');
            if (!mat) {
                btnImportar.value = '';
                return;
            }
        }

        let successCount = 0;
        let totalInserted = 0;
        let errors = [];

        // Upload sequentially to avoid overloading
        for (let i = 0; i < files.length; i++) {
            const formData = new FormData();
            formData.append('file', files[i]);
            formData.append('matricula', mat);

            try {
                const res = await fetch('api/informes_import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    successCount++;
                    totalInserted += data.inserted;
                } else {
                    errors.push(files[i].name + ": " + data.message);
                }
            } catch (err) {
                errors.push(files[i].name + ": Error de conexión");
            }
        }
        
        let msg = `Importación finalizada.\nArchivos procesados: ${successCount} / ${files.length}\nRegistros añadidos: ${totalInserted}`;
        if (errors.length > 0) {
            msg += "\nErrores:\n" + errors.join("\n");
        }
        
        alert(msg);
        loadInformes();
        btnImportar.value = ''; // Reset
    });

    // Exportar
    btnExportar.addEventListener('click', () => {
        let url = 'api/informes_export.php?';
        const params = new URLSearchParams();
        
        const areaVal = filterArea.value.toLowerCase().trim();
        if (areaVal) {
            params.append('area', areaVal);
        }
        
        if (selectedDateRange.length === 2) {
            params.append('start', flatpickr.formatDate(selectedDateRange[0], "Y-m-d"));
            params.append('end', flatpickr.formatDate(selectedDateRange[1], "Y-m-d"));
        } else if (selectedDateRange.length === 1) {
            params.append('start', flatpickr.formatDate(selectedDateRange[0], "Y-m-d"));
            params.append('end', flatpickr.formatDate(selectedDateRange[0], "Y-m-d"));
        }
        
        window.location.href = url + params.toString();
    });

    // Initial load
    loadInformes();
});
