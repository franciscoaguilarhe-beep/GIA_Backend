<?php include 'views/layout/header.php'; ?>

<div class="dashboard-container">
    <div class="search-wrapper">
        <h2>G.I.A<span>.</span></h2>
        <p class="search-subtitle">(Gestor de Inventario ASTI)</p>
        
        <div class="search-input-container">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" class="search-input" id="globalSearch" placeholder="Buscar por Serie, IP, Modelo, Unidad o Usuario..." autocomplete="off">
            
            <div class="search-results" id="searchResults">
                <!-- Los resultados se inyectarán aquí -->
                <!-- Ejemplo de estructura de un resultado:
                <div class="search-result-item">
                    <i class="ph ph-laptop"></i>
                    <div class="details">
                        <span class="title">HP ProDesk 400 - Serie: MXL123...</span>
                        <span class="subtitle">UMF 40 (G.IMSS Green)</span>
                    </div>
                </div>
                -->
            </div>
        </div>
        
        <p style="text-align: center; color: var(--text-secondary); margin-top: 20px; font-size: 0.9rem;">
            Ingrese término de búsqueda para comenzar.
        </p>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
