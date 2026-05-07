<?php 
include 'views/layout/header.php'; 
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch Unidades
$u_stmt = $db->prepare("SELECT * FROM unidades ORDER BY unidad ASC");
$u_stmt->execute();
$unidades = $u_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Empleados
$e_stmt = $db->prepare("SELECT e.*, u.unidad as nombre_unidad FROM empleados e LEFT JOIN unidades u ON e.id_unidad = u.id ORDER BY e.nombre ASC");
$e_stmt->execute();
$empleados = $e_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-layout">
    <!-- COLUMNA UNIDADES -->
    <div class="admin-column">
        <div class="admin-header">
            <h2><i class="ph ph-buildings"></i> Unidades</h2>
            <div class="admin-actions">
                <div class="search-input-container admin-search">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" class="search-input" id="searchUnidades" placeholder="Buscar unidad...">
                </div>
                <button class="btn-action" onclick="openAddModal('unidades')"><i class="ph ph-plus-circle"></i> Agregar</button>
                <button class="btn-action" onclick="openImportModal('unidades')"><i class="ph ph-upload-simple"></i> Importar</button>
                <button class="btn-action btn-secondary" onclick="exportData('unidades')"><i class="ph ph-download-simple"></i> Exportar</button>
            </div>
        </div>

        <div class="table-container">
            <table class="table-unidades">
                <thead>
                    <tr>
                        <th>UNIDAD</th>
                        <th>ZONA</th>
                        <th style="width: 50px;">VER</th>
                    </tr>
                </thead>
                <tbody id="bodyUnidades">
                    <?php foreach($unidades as $u): ?>
                        <tr onclick="filterEmployeesByUnit('<?= htmlspecialchars($u['unidad']) ?>')" style="cursor: pointer;">
                            <td><?= htmlspecialchars($u['unidad']) ?></td>
                            <td><?= htmlspecialchars($u['zona']) ?></td>
                            <td style="text-align: center;">
                                <button class="btn-icon" onclick="event.stopPropagation(); openFichaTecnica(<?= $u['id'] ?>, 'unidades')" title="Ver Ficha Técnica">
                                    <i class="ph ph-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- COLUMNA EMPLEADOS -->
    <div class="admin-column">
        <div class="admin-header">
            <h2><i class="ph ph-users"></i> Empleados</h2>
            <div class="admin-actions">
                <div class="search-input-container admin-search">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" class="search-input" id="searchEmpleados" placeholder="Buscar empleado...">
                </div>
                <button class="btn-action" onclick="openAddModal('empleados')"><i class="ph ph-plus-circle"></i> Agregar</button>
                <button class="btn-action" onclick="openImportModal('empleados')"><i class="ph ph-upload-simple"></i> Importar</button>
                <button class="btn-action btn-secondary" onclick="exportData('empleados')"><i class="ph ph-download-simple"></i> Exportar</button>
            </div>
        </div>

        <div class="table-container">
            <table class="table-empleados">
                <thead>
                    <tr>
                        <th>MATRÍCULA</th>
                        <th>NOMBRE</th>
                        <th>USUARIO</th>
                        <th>CATEGORÍA</th>
                    </tr>
                </thead>
                <tbody id="bodyEmpleados">
                    <?php foreach($empleados as $e): ?>
                        <tr onclick="openFichaTecnica(<?= $e['id'] ?>, 'empleados')" data-unidad-name="<?= htmlspecialchars($e['nombre_unidad'] ?? '') ?>">
                            <td><?= htmlspecialchars($e['matricula']) ?></td>
                            <td><?= htmlspecialchars($e['nombre']) ?></td>
                            <td><?= htmlspecialchars($e['usuario']) ?></td>
                            <td><?= htmlspecialchars($e['categoria']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
