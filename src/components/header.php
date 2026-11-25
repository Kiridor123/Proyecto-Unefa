<header class="page-header">
    <div class="page-title-group">
        <button id="sidebarToggle" type="button" class="icon-button mobile-menu" onclick="toggleMobileSidebar()" aria-label="Abrir menú de navegación" aria-controls="sidebar" aria-expanded="false"><i class="ph ph-list" aria-hidden="true"></i></button>
        <a href="#dashboard" onclick="switchTab('dashboard'); return false;" class="mobile-brand-mark" aria-label="Salud UNEFA, ir al resumen"><img src="<?= asset_url('assets/logo-unefa.png') ?>" width="26" height="30" alt="Escudo de la UNEFA" decoding="async"></a>
        <h1 id="headerTitle" tabindex="-1">Resumen de atención</h1>
    </div>
    <div class="user-menu-wrap">
        <button type="button" id="userMenuButton" class="user-menu-button" onclick="toggleUserMenu()" aria-haspopup="menu" aria-expanded="false" aria-controls="userMenu">
            <span class="user-avatar" data-user-initials aria-hidden="true"></span>
            <span class="user-menu-identity"><span class="user-menu-name" data-user-name>Usuario</span><small data-user-role></small></span>
            <i class="ph ph-caret-down" aria-hidden="true"></i>
            <span class="sr-only">Abrir menú de usuario</span>
        </button>
        <div id="userMenu" class="user-menu" role="menu" aria-labelledby="userMenuButton" hidden>
            <div class="user-menu-header" role="presentation">
                <p data-user-name>Usuario</p>
                <small data-user-role></small>
            </div>
            <button type="button" role="menuitem" class="user-menu-item" onclick="cambiarPassword()" tabindex="-1"><i class="ph ph-key" aria-hidden="true"></i>Cambiar contraseña</button>
            <button type="button" role="menuitem" class="user-menu-item" onclick="cerrarSesion()" tabindex="-1"><i class="ph ph-sign-out" aria-hidden="true"></i>Cerrar sesión</button>
        </div>
    </div>
</header>
