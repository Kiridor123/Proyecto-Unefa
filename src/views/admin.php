<section id="section-admin" class="tab-section hidden" aria-label="Administración del sistema">
    <div class="section-intro"><p>Gestione las cuentas del personal y revise el registro de actividad del sistema.</p></div>
    <div class="admin-tabs" role="tablist" aria-label="Áreas de administración">
        <button type="button" role="tab" id="adminTabUsuarios" class="admin-tab" aria-controls="adminPanelUsuarios" aria-selected="true"><i class="ph ph-users" aria-hidden="true"></i>Usuarios</button>
        <button type="button" role="tab" id="adminTabAuditoria" class="admin-tab" aria-controls="adminPanelAuditoria" aria-selected="false" tabindex="-1"><i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i>Registro de actividad</button>
    </div>

    <section id="adminPanelUsuarios" class="panel list-panel" role="tabpanel" aria-labelledby="adminTabUsuarios">
        <div class="panel-heading">
            <div><h2 id="adminUsuariosTitle">Usuarios del sistema <span id="adminUsuariosCount" class="result-count"></span></h2><p>Cuentas, roles y acceso del personal del servicio</p></div>
            <div class="action-group">
                <button type="button" class="icon-button" onclick="loadAdminUsuarios()" aria-label="Actualizar usuarios"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-primary" onclick="openUsuarioModal()"><i class="ph ph-user-plus" aria-hidden="true"></i>Nuevo usuario</button>
            </div>
        </div>
        <div class="toolbar">
            <div class="field search-field"><label for="adminFiltroUsuario">Buscar usuario</label><div class="input-icon"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="adminFiltroUsuario" placeholder="Usuario o nombre" autocomplete="off"></div></div>
            <div class="field filter-field"><label for="adminFiltroRol">Rol</label><select id="adminFiltroRol"><option value="">Todos los roles</option><option value="administrador">Administrador</option><option value="medico">Médico</option><option value="asistente">Asistente</option></select></div>
            <div class="field filter-field"><label for="adminFiltroEstado">Estado</label><select id="adminFiltroEstado"><option value="">Todos</option><option value="activo">Activos</option><option value="inactivo">Inactivos</option><option value="bloqueado">Bloqueados temporalmente</option></select></div>
        </div>
        <div id="adminUsuariosLoader" class="state-panel loading-state" role="status"><span class="loading-bar"></span><p>Cargando usuarios…</p></div>
        <div id="adminUsuariosTableWrap" class="hidden md:block table-frame">
            <table class="data-table"><caption class="sr-only">Usuarios del sistema, su rol y su estado</caption><thead><tr><th scope="col">Usuario</th><th scope="col">Nombre y registro</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Último acceso</th><th scope="col" class="text-right">Acciones</th></tr></thead><tbody id="adminUsuariosBody"></tbody></table>
        </div>
        <div class="record-list md:hidden" id="adminUsuariosCards"></div>
        <div id="adminUsuariosEmpty" class="state-panel hidden" role="status"><i class="ph ph-users" aria-hidden="true"></i><h3>Sin usuarios en esta selección</h3><p>Cambie los filtros o cree un usuario nuevo.</p></div>
    </section>

    <section id="adminPanelAuditoria" class="panel list-panel hidden" role="tabpanel" aria-labelledby="adminTabAuditoria">
        <div class="panel-heading">
            <div><h2 id="adminAuditoriaTitle">Registro de actividad <span id="adminAuditoriaCount" class="result-count"></span></h2><p>Accesos, consultas, ediciones e impresiones, del más reciente al más antiguo</p></div>
            <button type="button" class="icon-button" onclick="loadAuditoria()" aria-label="Actualizar registro de actividad"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></button>
        </div>
        <form id="adminAuditoriaForm" class="toolbar audit-toolbar" novalidate>
            <div class="field"><label for="auditUsuario">Usuario</label><select id="auditUsuario" name="usuario_id"><option value="">Todos los usuarios</option></select></div>
            <div class="field"><label for="auditAccion">Acción</label><select id="auditAccion" name="accion"><option value="">Todas las acciones</option></select></div>
            <div class="field"><label for="auditDesde">Desde</label><input type="date" id="auditDesde" name="desde"></div>
            <div class="field"><label for="auditHasta">Hasta</label><input type="date" id="auditHasta" name="hasta"></div>
            <div class="action-group audit-actions"><button type="submit" class="btn btn-primary"><i class="ph ph-funnel-simple" aria-hidden="true"></i>Filtrar</button><button type="button" class="btn btn-text" id="auditLimpiar">Limpiar</button></div>
        </form>
        <div id="adminAuditoriaLoader" class="state-panel loading-state hidden" role="status"><span class="loading-bar"></span><p>Cargando el registro de actividad…</p></div>
        <div id="adminAuditoriaTableWrap" class="hidden md:block table-frame">
            <table class="data-table audit-table"><caption class="sr-only">Registro de actividad del sistema</caption><thead><tr><th scope="col">Fecha y hora</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Registro</th><th scope="col">Detalle</th><th scope="col">IP</th></tr></thead><tbody id="adminAuditoriaBody"></tbody></table>
        </div>
        <div class="record-list md:hidden" id="adminAuditoriaCards"></div>
        <div id="adminAuditoriaEmpty" class="state-panel hidden" role="status"><i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i><h3>Sin actividad registrada</h3><p>No hay eventos que coincidan con los filtros.</p></div>
        <nav id="adminAuditoriaPager" class="list-pager hidden" aria-label="Páginas del registro de actividad"></nav>
    </section>
</section>

<div id="usuarioModal" class="modal-root fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true" onclick="closeUsuarioModal()"></div>
    <div id="usuarioModalContent" class="modal-panel modal-panel-compact modal-enter" role="dialog" aria-modal="true" aria-labelledby="usuarioModalTitle" tabindex="-1">
        <div class="modal-heading">
            <div><h2 id="usuarioModalTitle">Nuevo usuario</h2><p id="usuarioModalSubtitle">Cree la cuenta y asigne el rol del personal.</p></div>
            <button type="button" onclick="closeUsuarioModal()" class="icon-button" aria-label="Cerrar formulario de usuario"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>
        <div class="modal-body">
            <form id="usuarioForm" class="admin-form" novalidate>
                <input type="hidden" name="id" id="usuarioId" value="">
                <div class="field">
                    <label for="usuarioUsuario">Nombre de usuario <span aria-hidden="true">*</span></label>
                    <input type="text" name="usuario" id="usuarioUsuario" required minlength="3" maxlength="50" pattern="^[a-z0-9._\-]{3,50}$" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="p. ej. mgonzalez" aria-describedby="usuarioUsuarioAyuda">
                    <p class="field-help" id="usuarioUsuarioAyuda">De 3 a 50 caracteres: minúsculas, números, punto, guion o guion bajo.</p>
                </div>
                <div class="field">
                    <label for="usuarioNombre">Nombre completo <span aria-hidden="true">*</span></label>
                    <input type="text" name="nombre_completo" id="usuarioNombre" required minlength="3" maxlength="150" autocomplete="off">
                </div>
                <div class="field">
                    <label for="usuarioRol">Rol <span aria-hidden="true">*</span></label>
                    <select name="rol" id="usuarioRol" required aria-describedby="usuarioRolAyuda">
                        <option value="">Seleccione un rol</option>
                        <option value="medico">Médico</option>
                        <option value="asistente">Asistente</option>
                        <option value="administrador">Administrador</option>
                    </select>
                    <p class="field-help" id="usuarioRolAyuda">Seleccione el rol para ver sus permisos.</p>
                </div>
                <div class="field" id="usuarioRegistroCampo">
                    <label for="usuarioRegistro">Registro profesional</label>
                    <input type="text" name="registro_profesional" id="usuarioRegistro" maxlength="50" autocomplete="off" placeholder="MPPS o colegio" aria-describedby="usuarioRegistroAyuda">
                    <p class="field-help" id="usuarioRegistroAyuda">Aparece junto a la firma en informes y reposos. Recomendado para médicos.</p>
                </div>
                <div class="field checkbox-field">
                    <label class="checkbox-label" for="usuarioActivo"><input type="checkbox" name="activo" id="usuarioActivo" value="1" checked> Cuenta activa</label>
                    <p class="field-help" id="usuarioActivoAyuda">Una cuenta inactiva no puede iniciar sesión; su historial se conserva.</p>
                </div>
                <fieldset class="admin-fieldset" id="usuarioPasswordGrupo">
                    <legend id="usuarioPasswordLegend">Contraseña</legend>
                    <p class="field-help" id="usuarioPasswordAyuda">Mínimo 10 caracteres. Entréguela a la persona por un medio seguro.</p>
                    <div class="field">
                        <label for="usuarioPassword" id="usuarioPasswordLabel">Contraseña <span aria-hidden="true">*</span></label>
                        <input type="password" name="password" id="usuarioPassword" minlength="10" maxlength="200" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="usuarioPasswordConfirm">Repita la contraseña</label>
                        <input type="password" name="password_confirmacion" id="usuarioPasswordConfirm" minlength="10" maxlength="200" autocomplete="new-password">
                    </div>
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeUsuarioModal()" class="btn btn-secondary">Cancelar</button>
            <button type="submit" form="usuarioForm" id="btnGuardarUsuario" class="btn btn-primary"><i class="ph ph-check" aria-hidden="true"></i>Guardar usuario</button>
        </div>
    </div>
</div>
