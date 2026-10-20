<div id="patientDrawer" class="drawer-root fixed inset-0 z-40 hidden" aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true" onclick="closeDrawer()"></div>
    <div id="drawerContent" class="patient-drawer translate-x-full" role="dialog" aria-modal="true" aria-labelledby="detNombre" tabindex="-1">
        <div class="modal-heading">
            <div class="drawer-patient-heading">
                <div class="patient-initials" id="detIniciales" aria-hidden="true"></div>
                <div><h2 id="detNombre">Historia clínica</h2><p><span id="detCedula"></span> <span id="detCategoria"></span></p></div>
            </div>
            <button type="button" onclick="closeDrawer()" class="icon-button" aria-label="Cerrar historia clínica"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>
        <div class="modal-body drawer-body">
            <div id="drawerLoader" class="state-panel" role="status"><i class="ph ph-spinner-gap animate-spin" aria-hidden="true"></i><p>Cargando historia clínica…</p></div>
            <div id="drawerData" class="space-y-6 hidden">
                <div class="record-actions">
                    <button type="button" id="btnConsultaAlVuelo" class="btn btn-primary"><i class="ph ph-stethoscope" aria-hidden="true"></i> Nueva consulta</button>
                    <button type="button" id="btnCitaAlVuelo" class="btn btn-secondary"><i class="ph ph-calendar-plus" aria-hidden="true"></i> Agendar cita</button>
                    <button type="button" id="btnPrintHistoria" class="btn btn-secondary"><i class="ph ph-printer" aria-hidden="true"></i> Imprimir historia de ingreso</button>
                </div>
                <section class="drawer-section" aria-labelledby="clinicalHistoryTitle"><h3 id="clinicalHistoryTitle">Historial de consultas</h3><div id="detConsultas" class="space-y-3"></div></section>
                <section class="drawer-section" aria-labelledby="appointmentHistoryTitle"><h3 id="appointmentHistoryTitle">Citas registradas</h3><div id="detCitas" class="space-y-3"></div></section>
            </div>
        </div>
    </div>
</div>
