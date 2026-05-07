<?php 
include 'views/layout/header.php'; 
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$cat = isset($_GET['cat']) ? $_GET['cat'] : 'computo';
$title = "";
$table = "";
$columns = [];

switch($cat) {
    case 'computo': 
        $title = "Equipos de Cómputo"; 
        $table = "equipos_computo"; 
        $columns = ['IP', 'TIPO', 'SERIE', 'USUARIO ASIGNADO', 'CUENTA', 'UNIDAD', 'AREA/DEPTO'];
        break;
    case 'impresoras': 
        $title = "Impresoras"; 
        $table = "impresoras"; 
        $columns = ['SERIE', 'MODELO', 'UNIDAD', 'AREA/DEPTO', 'TIPO', 'IP'];
        break;
    case 'televisiones': 
        $title = "Televisiones"; 
        $table = "televisiones"; 
        $columns = ['SERIE', 'FABRICANTE', 'MODELO', 'AREA/DEPTO'];
        break;
    case 'telefonia': 
        $title = "Telefonía"; 
        $table = "telefonos"; 
        $columns = ['UNIDAD', 'NOMBRE', 'EXTENSIÓN'];
        break;
    case 'redes': 
        $title = "Infraestructura de Red"; 
        $table = "redes"; 
        $columns = ['SERIE', 'TIPO', 'UNIDAD', 'IP GESTIÓN'];
        break;
    case 'consumibles': 
        $title = "Consumibles"; 
        $table = "consumibles"; 
        $columns = ['TIPO', 'CATEGORÍA', 'CANTIDAD STOCK', 'UNIDAD', 'ESTATUS'];
        break;
    default:
        $title = "Equipos de Cómputo"; 
        $table = "equipos_computo"; 
        $columns = ['IP', 'TIPO', 'SERIE', 'USUARIO ASIGNADO', 'CUENTA', 'UNIDAD', 'AREA/DEPTO'];
        $cat = 'computo';
}

// Lógica de filtrado por unidad (si aplica)
$filtro_unidad = isset($_SESSION['id_unidad']) ? $_SESSION['id_unidad'] : null;

// Obtener todas las unidades para el selector
$un_stmt = $db->prepare("SELECT id, unidad FROM unidades ORDER BY unidad ASC");
$un_stmt->execute();
$todas_unidades = $un_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener registros de la tabla actual
$query = "SELECT t.*, u.unidad as nombre_unidad ";
if ($cat === 'computo') {
    $query .= ", e.nombre as nombre_usuario, e.matricula as matricula_usuario, COALESCE(NULLIF(e.usuario, ''), t.cuenta_dominio) as cuenta ";
}
$query .= " FROM $table t LEFT JOIN unidades u ON t.id_unidad = u.id ";
if ($cat === 'computo') {
    $query .= " LEFT JOIN empleados e ON t.id_usuario = e.id ";
}
if ($filtro_unidad) {
    $query .= " WHERE t.id_unidad = :id_u";
}
if ($cat === 'telefonia') {
    $query .= " ORDER BY CAST(t.extension AS UNSIGNED) ASC, t.extension ASC";
} else {
    $query .= " ORDER BY t.id DESC";
}

$stmt = $db->prepare($query);
if ($filtro_unidad) {
    $stmt->bindParam(':id_u', $filtro_unidad);
}
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para obtener estadísticas dinámicas
function getStats($db, $table, $field, $limit = 5, $id_unidad = null) {
    $sql = "SELECT $field as label, COUNT(*) as count FROM $table";
    if ($id_unidad) {
        $sql .= " WHERE id_unidad = :id_u";
    }
    $sql .= " GROUP BY $field ORDER BY label ASC";
    if ($limit > 0) $sql .= " LIMIT $limit";
    
    $stmt = $db->prepare($sql);
    if ($id_unidad) $stmt->bindParam(':id_u', $id_unidad);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estadísticas básicas
$stats_blocks = [];
switch($cat) {
    case 'computo':
        $stats_blocks['Área'] = getStats($db, $table, "area", 0, $filtro_unidad);
        $stats_blocks['Estatus'] = getStats($db, $table, "estatus", 0, $filtro_unidad);
        $stats_blocks['Fabricante/Modelo'] = getStats($db, $table, "CONCAT(fabricante, ' ', modelo)", 0, $filtro_unidad);
        $stats_blocks['Proyecto'] = getStats($db, $table, "proyecto", 0, $filtro_unidad);
        $stats_blocks['Tipo'] = getStats($db, $table, "tipo", 0, $filtro_unidad);
        break;
    case 'impresoras':
        $stats_blocks['Área'] = getStats($db, $table, "area", 0, $filtro_unidad);
        $stats_blocks['Fabricante/Modelo'] = getStats($db, $table, "CONCAT(fabricante, ' ', modelo)", 0, $filtro_unidad);
        $stats_blocks['Tipo'] = getStats($db, $table, "tipo", 0, $filtro_unidad);
        break;
    case 'televisiones':
        $stats_blocks['Fabricante/Modelo'] = getStats($db, $table, "CONCAT(fabricante, ' ', modelo)", 0, $filtro_unidad);
        $stats_blocks['Uso'] = getStats($db, $table, "uso", 0, $filtro_unidad);
        break;
    case 'telefonia':
        $stats_blocks['Fabricante/Modelo'] = getStats($db, $table, "CONCAT(fabricante, ' ', modelo)", 0, $filtro_unidad);
        $stats_blocks['Tipo'] = getStats($db, $table, "tipo", 0, $filtro_unidad);
        break;
    case 'redes':
        $stats_blocks['Área'] = getStats($db, $table, "area", 0, $filtro_unidad);
        $stats_blocks['Tipo'] = getStats($db, $table, "tipo", 0, $filtro_unidad);
        break;
    case 'consumibles':
        $stats_blocks['Tipo'] = getStats($db, $table, "tipo", 0, $filtro_unidad);
        $stats_blocks['Categoría'] = getStats($db, $table, "categoria", 0, $filtro_unidad);
        break;
}

$total_global = count($records);
?>

<div class="layout-container">
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <h2 class="sidebar-title"><i class="ph ph-chart-bar"></i> Analítica - <?= $title ?></h2>
        
        <div class="local-search">
            <div class="search-input-container">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="localSearch" class="search-input" placeholder="Buscador Local...">
            </div>
        </div>

        <div class="sidebar-stats">
            <div class="stat-block">
                <div class="stat-title">Total Global</div>
                <div class="stat-item">
                    <span class="label">Registros</span>
                    <div class="stat-bar"><div class="stat-fill" style="width: 100%;"></div></div>
                    <span class="stat-count"><?= $total_global ?></span>
                </div>
            </div>

            <?php foreach($stats_blocks as $block_title => $items): ?>
                <div class="stat-block">
                    <div class="stat-title"><?= $block_title ?></div>
                    <div class="stat-items">
                        <?php 
                        // Find max in this block for scale
                        $max_in_block = 0;
                        foreach($items as $i) if($i['count'] > $max_in_block) $max_in_block = $i['count'];
                        ?>
                        <?php foreach($items as $item): ?>
                            <?php 
                                $label = $item['label'] ?: 'Sin Especificar';
                                $display_label = $label;
                                if(strlen($display_label) > 22) $display_label = substr($display_label, 0, 19) . '...';
                                $percent = $max_in_block > 0 ? ($item['count'] / $max_in_block) * 100 : 0;
                            ?>
                            <div class="stat-item" onclick="filterByStat('<?= addslashes($block_title) ?>', '<?= addslashes($label) ?>')">
                                <span class="label" title="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($display_label) ?></span>
                                <div class="stat-bar">
                                    <div class="stat-fill" style="width: <?= $percent ?>%;"></div>
                                </div>
                                <span class="stat-count"><?= $item['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- SECCIÓN PRINCIPAL -->
    <section class="data-section">
        <div class="action-menu" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="action-buttons">
                <button class="btn-action" onclick="openAddModal('<?= $cat ?>')">
                    <i class="ph ph-plus-circle"></i> Agregar
                </button>
                <button class="btn-action" onclick="exportData('<?= $cat ?>')">
                    <i class="ph ph-download-simple"></i> Exportar
                </button>
                <button class="btn-action" onclick="openImportModal('<?= $cat ?>')">
                    <i class="ph ph-upload-simple"></i> Importar
                </button>
            </div>
            
            <div class="unit-badge">
                <i class="ph ph-buildings"></i>
                <select onchange="location.href='api/auth_handler.php?action=change_unit&id='+this.value">
                    <option value="0" <?= !$filtro_unidad ? 'selected' : '' ?>>Ver Todas las Unidades</option>
                    <?php foreach($todas_unidades as $un): ?>
                        <option value="<?= $un['id'] ?>" <?= $filtro_unidad == $un['id'] ? 'selected' : '' ?>>
                            Unidad <?= $un['unidad'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php foreach($columns as $col): ?>
                            <th><?= $col ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($records) > 0): ?>
                        <?php foreach($records as $row): ?>
                            <?php 
                                $search_data = [
                                    'serie' => $row['serie'],
                                    'ip' => $row['ip'] ?? '',
                                    'tipo' => $row['tipo'] ?? '',
                                    'fabricante' => $row['fabricante'] ?? '',
                                    'modelo' => $row['modelo'] ?? '',
                                    'area' => $row['area'] ?? '',
                                    'departamento' => $row['departamento'] ?? '',
                                    'usuario' => $row['nombre_usuario'] ?? '',
                                    'matricula' => $row['matricula_usuario'] ?? '',
                                    'unidad' => $row['nombre_unidad'] ?? '',
                                    'proyecto' => $row['proyecto'] ?? '',
                                    'estatus' => $row['estatus'] ?? '',
                                    'uso' => $row['uso'] ?? '',
                                    'categoria' => $row['categoria'] ?? '',
                                    'nombre' => $row['nombre'] ?? '',
                                    'extension' => $row['extension'] ?? ''
                                ];

                                $search_tags = [];
                                foreach($search_data as $key => $val) {
                                    if(empty($val) || $val == 'N/A') {
                                        $search_tags[] = "sin_" . $key;
                                    } else {
                                        $search_tags[] = $val;
                                    }
                                }
                                $search_str = strtolower(implode(' ', $search_tags));
                            ?>
                            <tr onclick="openFichaTecnica(<?= $row['id'] ?>, '<?= $cat ?>')" data-search="<?= htmlspecialchars($search_str) ?>">
                                <?php if($cat == 'computo'): ?>
                                    <td><?= $row['ip'] ?></td>
                                    <td><?= $row['tipo'] ?></td>
                                    <td><strong><?= $row['serie'] ?></strong></td>
                                    <td><?= $row['nombre_usuario'] ?: 'N/A' ?></td>
                                    <td><?= $row['cuenta'] ?: 'N/A' ?></td>
                                    <td><?= $row['nombre_unidad'] ?></td>
                                    <td><?= $row['area'] ?></td>
                                <?php elseif($cat == 'impresoras'): ?>
                                    <td><strong><?= $row['serie'] ?></strong></td>
                                    <td><?= $row['modelo'] ?></td>
                                    <td><?= $row['nombre_unidad'] ?></td>
                                    <td><?= $row['area'] ?></td>
                                    <td><?= $row['tipo'] ?></td>
                                    <td><?= $row['ip'] ?></td>
                                <?php elseif($cat == 'televisiones'): ?>
                                    <td><strong><?= $row['serie'] ?></strong></td>
                                    <td><?= $row['fabricante'] ?></td>
                                    <td><?= $row['modelo'] ?></td>
                                    <td><?= $row['area'] ?></td>
                                <?php elseif($cat == 'telefonia'): ?>
                                    <td><?= $row['nombre_unidad'] ?></td>
                                    <td><?= $row['nombre'] ?></td>
                                    <td><?= $row['extension'] ?></td>
                                <?php elseif($cat == 'redes'): ?>
                                    <td><strong><?= $row['serie'] ?></strong></td>
                                    <td><?= $row['tipo'] ?></td>
                                    <td><?= $row['nombre_unidad'] ?></td>
                                    <td><?= $row['ip_gestion'] ?></td>
                                <?php elseif($cat == 'consumibles'): ?>
                                    <td><?= $row['tipo'] ?></td>
                                    <td><?= $row['categoria'] ?></td>
                                    <td><?= $row['cantidad_stock'] ?></td>
                                    <td><?= $row['nombre_unidad'] ?></td>
                                    <td><?= $row['estatus'] ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($columns) ?>" style="text-align: center;">No hay registros en esta categoría.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include 'views/layout/footer.php'; ?>
