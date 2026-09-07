<?php 
include 'views/layout/header.php'; 
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener registros de historial
$query = "SELECT * FROM historial ORDER BY fecha DESC, id DESC LIMIT 500";
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas para la barra lateral
$stat_acciones_stmt = $db->query("SELECT accion as label, COUNT(*) as count FROM historial GROUP BY accion ORDER BY count DESC");
$stat_acciones = $stat_acciones_stmt->fetchAll(PDO::FETCH_ASSOC);

$stat_cat_stmt = $db->query("SELECT categoria as label, COUNT(*) as count FROM historial GROUP BY categoria ORDER BY count DESC");
$stat_categorias = $stat_cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$stat_resp_stmt = $db->query("SELECT CONCAT(matricula_responsable, ' - ', nombre_responsable) as label, COUNT(*) as count FROM historial GROUP BY matricula_responsable, nombre_responsable ORDER BY count DESC LIMIT 10");
$stat_responsables = $stat_resp_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_global = count($records);
?>

<div class="layout-container">
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <h2 class="sidebar-title"><i class="ph ph-clock-counter-clockwise"></i> Historial y Auditoría</h2>
        
        <div class="local-search">
            <div class="search-input-container">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="historialSearch" class="search-input" placeholder="Buscar en historial...">
            </div>
        </div>

        <div class="sidebar-stats">
            <div class="stat-block">
                <div class="stat-title">Total Movimientos</div>
                <div class="stat-item">
                    <span class="label">Registros</span>
                    <div class="stat-bar"><div class="stat-fill" style="width: 100%;"></div></div>
                    <span class="stat-count"><?= $total_global ?></span>
                </div>
            </div>

            <!-- Por Acción -->
            <div class="stat-block">
                <div class="stat-title">Por Tipo de Acción</div>
                <div class="stat-items">
                    <?php 
                    $max_acc = 0;
                    foreach($stat_acciones as $a) if($a['count'] > $max_acc) $max_acc = $a['count'];
                    ?>
                    <?php foreach($stat_acciones as $item): ?>
                        <?php 
                            $label = $item['label'] ?: 'Sin Especificar';
                            $percent = $max_acc > 0 ? ($item['count'] / $max_acc) * 100 : 0;
                        ?>
                        <div class="stat-item" onclick="filterHistorialByStat('<?= addslashes($label) ?>')" style="cursor: pointer;">
                            <span class="label"><?= htmlspecialchars($label) ?></span>
                            <div class="stat-bar">
                                <div class="stat-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="stat-count"><?= $item['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Por Categoría -->
            <div class="stat-block">
                <div class="stat-title">Por Categoría</div>
                <div class="stat-items">
                    <?php 
                    $max_cat = 0;
                    foreach($stat_categorias as $c) if($c['count'] > $max_cat) $max_cat = $c['count'];
                    ?>
                    <?php foreach($stat_categorias as $item): ?>
                        <?php 
                            $label = strtoupper($item['label']) ?: 'Sin Especificar';
                            $percent = $max_cat > 0 ? ($item['count'] / $max_cat) * 100 : 0;
                        ?>
                        <div class="stat-item" onclick="filterHistorialByStat('<?= addslashes($item['label']) ?>')" style="cursor: pointer;">
                            <span class="label"><?= htmlspecialchars($label) ?></span>
                            <div class="stat-bar">
                                <div class="stat-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="stat-count"><?= $item['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Por Responsable -->
            <div class="stat-block">
                <div class="stat-title">Principales Responsables</div>
                <div class="stat-items">
                    <?php 
                    $max_resp = 0;
                    foreach($stat_responsables as $r) if($r['count'] > $max_resp) $max_resp = $r['count'];
                    ?>
                    <?php foreach($stat_responsables as $item): ?>
                        <?php 
                            $label = $item['label'] ?: 'Sin Especificar';
                            $display_label = strlen($label) > 22 ? substr($label, 0, 19) . '...' : $label;
                            $percent = $max_resp > 0 ? ($item['count'] / $max_resp) * 100 : 0;
                        ?>
                        <div class="stat-item" onclick="filterHistorialByStat('<?= addslashes($label) ?>')" style="cursor: pointer;" title="<?= htmlspecialchars($label) ?>">
                            <span class="label"><?= htmlspecialchars($display_label) ?></span>
                            <div class="stat-bar">
                                <div class="stat-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="stat-count"><?= $item['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </aside>

    <!-- SECCIÓN PRINCIPAL -->
    <section class="data-section">
        <div class="action-menu" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="color: var(--text-secondary); font-size: 0.85rem;">Desde:</label>
                <input type="date" id="histFechaDesde" class="date-input" style="background: #1e1e1e; border: 1px solid #333; color: #fff; padding: 6px 10px; border-radius: 5px; font-size: 0.85rem;">
                <label style="color: var(--text-secondary); font-size: 0.85rem;">Hasta:</label>
                <input type="date" id="histFechaHasta" class="date-input" style="background: #1e1e1e; border: 1px solid #333; color: #fff; padding: 6px 10px; border-radius: 5px; font-size: 0.85rem;">
                <button class="btn-action" id="btnFiltrarFechas" onclick="applyHistorialDateFilter()"><i class="ph ph-funnel"></i> Filtrar</button>
                <button class="btn-action btn-secondary" onclick="resetHistorialFilters()"><i class="ph ph-arrow-counter-clockwise"></i> Limpiar</button>
            </div>
            
            <div class="action-buttons">
                <button class="btn-action" onclick="exportHistorialData()">
                    <i class="ph ph-download-simple"></i> Exportar a Excel
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="table-historial" id="tableHistorial">
                <thead>
                    <tr>
                        <th style="width: 155px;">FECHA Y HORA</th>
                        <th style="width: 120px;">ACCIÓN</th>
                        <th style="width: 120px;">CATEGORÍA</th>
                        <th style="width: 140px;">IDENTIFICADOR / SERIE</th>
                        <th>DETALLES DEL MOVIMIENTO</th>
                        <th style="width: 110px;">MATRÍCULA</th>
                        <th style="width: 220px;">RESPONSABLE</th>
                    </tr>
                </thead>
                <tbody id="bodyHistorial">
                    <?php if(count($records) > 0): ?>
                        <?php foreach($records as $row): ?>
                            <?php 
                                $fecha_fmt = date('d/m/Y h:i A', strtotime($row['fecha']));
                                $search_str = strtolower($row['fecha'] . ' ' . $fecha_fmt . ' ' . $row['accion'] . ' ' . $row['categoria'] . ' ' . $row['identificador'] . ' ' . $row['detalles'] . ' ' . $row['matricula_responsable'] . ' ' . $row['nombre_responsable']);
                                
                                $badge_class = 'badge-modificacion';
                                if ($row['accion'] === 'ALTA') $badge_class = 'badge-alta';
                                elseif ($row['accion'] === 'ELIMINACION') $badge_class = 'badge-eliminacion';
                                elseif ($row['accion'] === 'NOTA') $badge_class = 'badge-nota';
                            ?>
                            <tr data-search="<?= htmlspecialchars($search_str) ?>" data-date="<?= substr($row['fecha'], 0, 10) ?>">
                                <td style="color: #bbb; font-size: 0.88rem;"><?= $fecha_fmt ?></td>
                                <td><span class="hist-badge <?= $badge_class ?>"><?= htmlspecialchars($row['accion']) ?></span></td>
                                <td><span style="font-weight: 600; text-transform: uppercase; color: #ddd; font-size: 0.85rem;"><?= htmlspecialchars($row['categoria']) ?></span></td>
                                <td>
                                    <?php if($row['id_activo'] && $row['accion'] !== 'ELIMINACION' && in_array($row['categoria'], ['computo', 'impresoras', 'televisiones', 'telefonia', 'redes', 'consumibles', 'unidades', 'empleados'])): ?>
                                        <strong style="color: #4CAF50; cursor: pointer; text-decoration: underline;" onclick="openFichaTecnica(<?= $row['id_activo'] ?>, '<?= $row['categoria'] ?>')" title="Ver Ficha Técnica">
                                            <?= htmlspecialchars($row['identificador']) ?>
                                        </strong>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($row['identificador']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="col-detalles" title="<?= htmlspecialchars($row['detalles']) ?>"><?= htmlspecialchars($row['detalles']) ?></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['matricula_responsable']) ?></td>
                                <td class="col-responsable" title="<?= htmlspecialchars($row['nombre_responsable']) ?>"><?= htmlspecialchars($row['nombre_responsable']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-secondary);">No hay registros de historial en el sistema.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
function filterHistorialByStat(term) {
    const input = document.getElementById('historialSearch');
    if (input) {
        input.value = term;
        input.dispatchEvent(new Event('input'));
    }
}

function applyHistorialDateFilter() {
    const desde = document.getElementById('histFechaDesde').value;
    const hasta = document.getElementById('histFechaHasta').value;
    const rows = document.querySelectorAll('#bodyHistorial tr');

    rows.forEach(r => {
        const rowDate = r.getAttribute('data-date');
        if (!rowDate) return;
        let show = true;
        if (desde && rowDate < desde) show = false;
        if (hasta && rowDate > hasta) show = false;
        r.style.display = show ? '' : 'none';
    });
}

function resetHistorialFilters() {
    document.getElementById('histFechaDesde').value = '';
    document.getElementById('histFechaHasta').value = '';
    const input = document.getElementById('historialSearch');
    if (input) input.value = '';
    const rows = document.querySelectorAll('#bodyHistorial tr');
    rows.forEach(r => r.style.display = '');
}

function exportHistorialData() {
    const desde = document.getElementById('histFechaDesde').value;
    const hasta = document.getElementById('histFechaHasta').value;
    window.location.href = `api/historial.php?action=export&fecha_desde=${encodeURIComponent(desde)}&fecha_hasta=${encodeURIComponent(hasta)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('historialSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#bodyHistorial tr');
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || row.textContent.toLowerCase();
                row.style.display = searchData.includes(term) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include 'views/layout/footer.php'; ?>
