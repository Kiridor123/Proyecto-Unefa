<!-- Modal de Agendamiento de Citas (Responsive) -->
<div id="citaModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeCitaModal()"></div>
    <div id="citaModalContent" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh] modal-enter">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="ph ph-calendar-plus text-amber-500"></i> Programar Cita
            </h3>
            <button onclick="closeCitaModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition shrink-0">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>
        
        <div class="p-5 overflow-y-auto space-y-4">
            <form id="citaForm" class="space-y-4">
                <input type="hidden" name="paciente_id" id="citaPacienteId" value="0">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cédula del Paciente <span class="text-brand-600 font-bold">*</span></label>
                    <input type="text" name="cedula" id="citaCedula" required pattern="^[VEJGCPNvejgcpn]-[0-9]+$" title="Formato válido: V-12345678" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 transition" placeholder="Ej. V-12345678" onblur="buscarPacienteParaCita(this.value)">
                </div>

                <!-- Campos informativos si el paciente existe o se creará al vuelo -->
                <div id="citaPacienteInfo" class="hidden p-3 bg-slate-50 border border-slate-100 rounded-xl space-y-1">
                    <p class="text-[10px] font-semibold text-brand-600">Paciente Encontrado:</p>
                    <p class="text-xs font-medium text-slate-700" id="citaPacienteNombreText">-</p>
                </div>

                <div id="citaNuevoPacienteCampos" class="hidden border border-amber-100 bg-amber-50/30 p-3.5 rounded-xl space-y-3">
                    <p class="text-[10px] font-semibold text-amber-600 flex items-center gap-1">
                        <i class="ph ph-warning-circle"></i> Paciente no registrado. Complete los datos:
                    </p>
                    <div>
                        <label class="block text-[9px] font-medium text-slate-500 mb-1">Nombres *</label>
                        <input type="text" name="nombres" id="citaNombres" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 transition">
                    </div>
                    <div>
                        <label class="block text-[9px] font-medium text-slate-500 mb-1">Apellidos *</label>
                        <input type="text" name="apellidos" id="citaApellidos" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 transition">
                    </div>
                    <div>
                        <label class="block text-[9px] font-medium text-slate-500 mb-1">Categoría Institucional *</label>
                        <select name="categoria" id="citaCategoria" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 transition">
                            <option value="">Seleccione...</option>
                            <!-- Cargado por JS -->
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Fecha y Hora de la Cita *</label>
                    <input type="datetime-local" name="fecha_cita" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 transition">
                </div>
            </form>
        </div>
        
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 shrink-0">
            <button onclick="closeCitaModal()" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-xl transition min-h-[44px]">Cancelar</button>
            <button type="submit" form="citaForm" id="btnGuardarCita" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-md flex items-center justify-center gap-1.5 min-h-[44px]">
                <i class="ph ph-calendar-check text-base"></i> Programar
            </button>
        </div>
    </div>
</div>
