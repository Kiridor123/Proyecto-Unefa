<div id="citaModal" class="modal-root fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true" onclick="closeCitaModal()"></div>
    <div id="citaModalContent" class="modal-panel modal-panel-compact modal-enter" role="dialog" aria-modal="true" aria-labelledby="citaModalTitle" tabindex="-1">
        <div class="modal-heading">
            <div><h2 id="citaModalTitle">Programar cita</h2><p>Busque al paciente por su cédula.</p></div>
            <button type="button" onclick="closeCitaModal()" class="icon-button" aria-label="Cerrar programación de cita"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>
        <div class="modal-body">
            <form id="citaForm" class="space-y-4" novalidate>
                <input type="hidden" name="paciente_id" id="citaPacienteId" value="0">
                <div class="field">
                    <label for="citaCedula">Cédula del paciente <span aria-hidden="true">*</span></label>
                    <input type="text" name="cedula" id="citaCedula" required pattern="^[VEJGCPNvejgcpn]-[0-9]{1,18}$" maxlength="20" title="Formato válido: V-12345678" placeholder="V-12345678" autocomplete="off" onblur="buscarPacienteParaCita(this.value)">
                </div>
                <div id="citaPacienteInfo" class="patient-match hidden" role="status">
                    <p class="field-help">Paciente registrado</p><p id="citaPacienteNombreText"></p>
                </div>
                <div id="citaNuevoPacienteCampos" class="new-patient-fields hidden space-y-4">
                    <p class="field-help">No encontramos esta cédula. Complete los datos para registrar al paciente y su cita.</p>
                    <div class="field"><label for="citaNombres">Nombres <span aria-hidden="true">*</span></label><input type="text" name="nombres" id="citaNombres" maxlength="100" autocomplete="given-name"></div>
                    <div class="field"><label for="citaApellidos">Apellidos <span aria-hidden="true">*</span></label><input type="text" name="apellidos" id="citaApellidos" maxlength="100" autocomplete="family-name"></div>
                    <div class="field"><label for="citaCategoria">Categoría institucional <span aria-hidden="true">*</span></label><select name="categoria" id="citaCategoria"><option value="">Seleccione una categoría</option></select></div>
                </div>
                <div class="field">
                    <label for="citaFecha">Fecha y hora <span aria-hidden="true">*</span></label>
                    <input type="datetime-local" name="fecha_cita" id="citaFecha" required>
                </div>
                <div class="field">
                    <label for="citaMotivo">Motivo de la cita</label>
                    <textarea name="motivo" id="citaMotivo" maxlength="500" rows="3" placeholder="Ej.: control de tensión arterial, revisión de resultados" aria-describedby="citaMotivoAyuda"></textarea>
                    <p class="field-help" id="citaMotivoAyuda">Opcional. No incluya diagnósticos; máximo 500 caracteres. Los campos con * son obligatorios.</p>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeCitaModal()" class="btn btn-secondary">Cancelar</button>
            <button type="submit" form="citaForm" id="btnGuardarCita" class="btn btn-primary"><i class="ph ph-calendar-check" aria-hidden="true"></i> Programar cita</button>
        </div>
    </div>
</div>
