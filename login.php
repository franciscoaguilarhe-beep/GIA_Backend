<?php include 'views/layout/header.php'; ?>

<div class="dashboard-container">
    <div class="search-wrapper" style="max-width: 400px; background-color: var(--bg-surface); padding: 40px; border-radius: 15px; border: 1px solid var(--border-color);">
        <h2 style="margin-bottom: 10px;">G.I.A<span>.</span></h2>
        <p class="search-subtitle">Iniciar Sesión</p>

        <?php if (isset($_GET['error'])): ?>
            <p style="color: #f44336; text-align: center; margin-bottom: 15px; font-size: 0.9rem;">
                <?= htmlspecialchars($_GET['error']) ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <p style="color: #4CAF50; text-align: center; margin-bottom: 15px; font-size: 0.9rem;">
                <?= htmlspecialchars($_GET['success']) ?>
            </p>
        <?php endif; ?>

        <form action="api/auth_handler.php?action=login" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Ingrese su usuario" required style="display: block; width: 100%;">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Ingrese su contraseña" required style="display: block; width: 100%;">
            </div>
            <button type="submit" class="btn-save" style="width: 100%; padding: 12px; margin-top: 10px;">Entrar</button>
        </form>

        <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-secondary);">
            ¿No tienes cuenta? <a href="register.php" style="color: #4CAF50; text-decoration: none; font-weight: 500;">Regístrate aquí</a>
        </p>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
