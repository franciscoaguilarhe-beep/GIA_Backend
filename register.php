<?php 
include 'views/layout/header.php'; 
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch units for the select dropdown
$query = "SELECT id, unidad FROM unidades ORDER BY unidad ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$unidades = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <div class="search-wrapper" style="max-width: 500px; background-color: var(--bg-surface); padding: 40px; border-radius: 15px; border: 1px solid var(--border-color);">
        <h2 style="margin-bottom: 10px;">G.I.A<span>.</span></h2>
        <p class="search-subtitle">Registro de Nuevo Usuario</p>

        <?php if (isset($_GET['error'])): ?>
            <p style="color: #f44336; text-align: center; margin-bottom: 15px; font-size: 0.9rem;">
                <?= htmlspecialchars($_GET['error']) ?>
            </p>
        <?php endif; ?>

        <form action="api/auth_handler.php?action=register" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" placeholder="Ej. Juan PÃ©rez" required style="display: block; width: 100%;">
            </div>
            
            <div class="form-group">
                <label>Matrícula</label>
                <input type="text" name="matricula" placeholder="00000000" required style="display: block; width: 100%;">
            </div>

            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="jperez" required style="display: block; width: 100%;">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Unidad de Adscripción</label>
                <select name="id_unidad" required style="display: block; width: 100%; background-color: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 10px; border-radius: 5px;">
                    <option value="">Seleccione una unidad...</option>
                    <?php foreach ($unidades as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unidad']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Mínimo 6 caracteres" required style="display: block; width: 100%;">
            </div>

            <button type="submit" class="btn-save" style="grid-column: 1 / -1; padding: 12px; margin-top: 10px;">Registrarse</button>
        </form>

        <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-secondary);">
            Â¿Ya tienes cuenta? <a href="login.php" style="color: #4CAF50; text-decoration: none; font-weight: 500;">Inicia sesión</a>
        </p>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
