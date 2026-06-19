<!-- Modal de Registro de Consulta (Responsive) -->
<div id="patientModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div id="modalContent" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col max-h-[90vh] modal-enter">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
            <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="ph ph-user-plus text-brand-600"></i> Registrar Consulta e Historia Clínica
            </h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition shrink-0">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>
        
        <div class="p-4 sm:p-5 overflow-y-auto">
            <form id="registroForm" class="space-y-4">
                <input type="hidden" name="solo_paciente" id="formSoloPaciente" value="0">
                <input type="hidden" name="fecha_circunstancia" id="formFechaCircunstancia" value="">
                <div class="space-y-3">
                    
                    <!-- Accordion Item 1: Datos Personales -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button type="button" onclick="toggleAccordion('acc-paciente')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                            <span class="flex items-center gap-2"><i class="ph ph-user-circle text-brand-600 text-base sm:text-lg"></i> 1. Identificación del Paciente</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-paciente"></i>
                        </button>
                        <div id="acc-paciente" class="p-4 sm:p-5 border-t border-slate-200 bg-white space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cédula de Identidad <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="cedula" id="formCedula" required pattern="^[VEJGCPNvejgcpn]-[0-9]+$" title="Formato válido: V-12345678" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. V-12345678" onblur="buscarPacientePorCedula(this.value)">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Categoría Institucional <span class="text-brand-600 font-bold">*</span></label>
                                    <select name="categoria" id="formCategoria" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                        <option value="">Seleccione...</option>
                                        <!-- Opciones cargadas por JS -->
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sexo <span class="text-brand-600 font-bold">*</span></label>
                                    <select name="sexo" id="formSexo" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                        <option value="">Seleccione...</option>
                                        <option value="Femenino">Femenino</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Otro">Otro / No especificado</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nombres <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="nombres" id="formNombres" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Apellidos <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="apellidos" id="formApellidos" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
                                    <input type="text" name="telefono" id="formTelefono" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 0412-1234567">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Lugar de Nacimiento</label>
                                    <input type="text" name="lugar_nacimiento" id="formLugarNacimiento" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Acarigua">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" id="formFechaNacimiento" max="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nivel Educativo</label>
                                    <input type="text" name="nivel_educativo" id="formNivelEducativo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Bachiller / Universitario">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Carrera (Solo Estudiantes)</label>
                                    <input type="text" name="carrera" id="formCarrera" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Ing. de Sistemas">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Semestre (Solo Estudiantes)</label>
                                    <input type="text" name="semestre" id="formSemestre" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 1er Semestre">
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Dirección de Habitación</label>
                                    <textarea name="direccion" id="formDireccion" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Dirección completa del paciente..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 2: Antecedentes Médicos -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button type="button" onclick="toggleAccordion('acc-antecedentes')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                            <span class="flex items-center gap-2"><i class="ph ph-heart text-brand-600 text-base sm:text-lg"></i> 2. Antecedentes Médicos, Ginecológicos y Tatuajes</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-antecedentes"></i>
                        </button>
                        <div id="acc-antecedentes" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-5">
                            <!-- Antecedentes Personales Patológicos -->
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-clipboard-text"></i> Personales Patológicos (Indicar condición o 'Negado')</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cardiovascular</label>
                                        <input type="text" name="antecedente_cardiovascular" id="formAntecedenteCardiovascular" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Óseo / Articular</label>
                                        <input type="text" name="antecedente_oseo" id="formAntecedenteOseo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Respiratorio</label>
                                        <input type="text" name="antecedente_respiratorio" id="formAntecedenteRespiratorio" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Asma leve">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Digestivo</label>
                                        <input type="text" name="antecedente_digestivo" id="formAntecedenteDigestivo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Endocrino / Metabólico</label>
                                        <input type="text" name="antecedente_endocrino" id="formAntecedenteEndocrino" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Resistencia insulina">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Otros antecedentes</label>
                                        <input type="text" name="antecedente_otros" id="formAntecedenteOtros" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Alergias, etc.">
                                    </div>
                                </div>
                            </div>

                            <!-- Quirurgicos -->
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-bandaids"></i> Quirúrgicos / Traumatológicos</h5>
                                <input type="text" name="antecedente_quirurgico" id="formAntecedenteQuirurgico" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Cirugías previas, fracturas, prótesis...">
                            </div>

                            <!-- Antecedentes Ginecobstétricos -->
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-gender-female"></i> Ginecoobstétricos (Si aplica)</h5>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Menarquia</label>
                                        <input type="text" name="gineco_menarquia" id="formGinecoMenarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Sexarquia</label>
                                        <input type="text" name="gineco_sexarquia" id="formGinecoSexarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">ACO</label>
                                        <input type="text" name="gineco_aco" id="formGinecoAco" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ninguno / Marca">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Gestas</label>
                                        <input type="text" name="gineco_gestas" id="formGinecoGestas" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="N° Embarazos">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Última Citología</label>
                                        <input type="text" name="gineco_citologia" id="formGinecoCitologia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Fecha / Resultado">
                                    </div>
                                </div>
                            </div>

                            <!-- Tatuajes y Compromiso -->
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-palette"></i> Control de Tatuajes</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">¿Tiene Tatuajes?</label>
                                        <select name="tiene_tatuajes" id="formTieneTatuajes" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                            <option value="No">No</option>
                                            <option value="Sí">Sí</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Compromiso Institucional</label>
                                        <input type="text" name="compromiso_tatuajes" id="formCompromisoTatuajes" value="Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera." class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Antecedentes Familiares -->
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-users-three"></i> Familiares Patológicos (Indicar condición o 'Sano')</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Padre</label>
                                        <input type="text" name="antecedente_padre" id="formAntecedentePadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sano / Hipertensión">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Madre</label>
                                        <input type="text" name="antecedente_madre" id="formAntecedenteMadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sana / Diabetes">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Hermanos</label>
                                        <input type="text" name="antecedente_hermanos" id="formAntecedenteHermanos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Hijos</label>
                                        <input type="text" name="antecedente_hijos" id="formAntecedenteHijos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 3: Motivo y Signos Vitales -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button type="button" onclick="toggleAccordion('acc-vitales')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                            <span class="flex items-center gap-2"><i class="ph ph-heartbeat text-brand-600 text-base sm:text-lg"></i> 3. Motivo Clínico y Signos Vitales</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-vitales"></i>
                        </button>
                        <div id="acc-vitales" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Motivo de Consulta <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="motivo_consulta" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Dolor de cabeza, fiebre..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Enfermedad Actual / Resumen <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="enfermedad_actual" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Paciente refiere cefalea de 3 días de evolución..."></textarea>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-3">
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-waveform"></i> Toma de Signos Vitales</h5>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">T.A. (mmHg)</label>
                                        <input type="text" name="vital_ta" id="formVitalTA" pattern="^\d{2,3}\/\d{2,3}$" title="Ej. 120/80" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 120/80">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">F.C. (lpm)</label>
                                        <input type="number" name="vital_fc" id="formVitalFC" min="0" max="300" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 72">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">F.R. (rpm)</label>
                                        <input type="number" name="vital_fr" id="formVitalFR" min="0" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 16">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">SpO2 (%)</label>
                                        <input type="number" name="vital_spo2" id="formVitalSpo2" min="0" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 98">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Peso/Talla</label>
                                        <input type="text" name="vital_peso_talla" id="formVitalPesoTalla" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 65 kg / 1.70 m">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 4: Examen Físico -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button type="button" onclick="toggleAccordion('acc-fisico')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                            <span class="flex items-center gap-2"><i class="ph ph-stethoscope text-brand-600 text-base sm:text-lg"></i> 4. Examen Físico General</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-fisico"></i>
                        </button>
                        <div id="acc-fisico" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                            <p class="text-[10px] text-slate-400 italic mb-2">Describa cualquier hallazgo patológico. Deje vacío o coloque 'Sin alteraciones' si está normal.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Piel y Mucosa</label>
                                    <input type="text" name="fisico_piel" id="formFisicoPiel" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cabeza</label>
                                    <input type="text" name="fisico_cabeza" id="formFisicoCabeza" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cuello</label>
                                    <input type="text" name="fisico_cuello" id="formFisicoCuello" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tórax</label>
                                    <input type="text" name="fisico_torax" id="formFisicoTorax" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Abdomen</label>
                                    <input type="text" name="fisico_abdomen" id="formFisicoAbdomen" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Extremidades</label>
                                    <input type="text" name="fisico_extremidades" id="formFisicoExtremidades" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Neurológico</label>
                                    <input type="text" name="fisico_neurologico" id="formFisicoNeurologico" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Activo, consciente y orientado">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 5: Diagnóstico, Tratamiento, Reposo y Adjunto -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button type="button" onclick="toggleAccordion('acc-diagnostico')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                            <span class="flex items-center gap-2"><i class="ph ph-prescription text-brand-600 text-base sm:text-lg"></i> 5. Diagnóstico, Tratamiento, Reposo y Anexos</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-diagnostico"></i>
                        </button>
                        <div id="acc-diagnostico" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Impresión Diagnóstica (DX) <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="diagnostico" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Faringitis aguda, etc."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Plan de Tratamiento / Indicaciones <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="plan_tratamiento" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Analgésicos cada 8 horas, abundante líquido..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Laboratorios u Exámenes Complementarios</label>
                                    <textarea name="laboratorios" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Examen de orina, química sanguínea..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pendiente / Observaciones</label>
                                    <textarea name="pendiente" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Traer resultados en la próxima cita..."></textarea>
                                </div>
                            </div>

                            <!-- --- Reposo Médico --- -->
                            <div class="border-t border-slate-100 pt-3">
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-calendar"></i> Configurar Reposo Médico (Opcional)</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Inicio de Reposo</label>
                                        <input type="date" name="inicio_reposo" id="formInicioReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fin de Reposo</label>
                                        <input type="date" name="fin_reposo" id="formFinReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Archivo Adjunto -->
                            <div class="border-t border-slate-100 pt-3">
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-paperclip"></i> Anexar Informe Externo o Exámenes (Opcional)</h5>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 transition relative">
                                    <input type="file" name="archivo" id="archivoAdjunto" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="validarArchivo(this)">
                                    <i class="ph ph-cloud-arrow-up text-xl text-slate-400 mb-1"></i>
                                    <p class="text-xs font-medium text-slate-600 text-center">Haz clic o arrastra un archivo aquí</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 text-center">Formatos soportados: JPG, PNG, PDF (Máx. 10MB)</p>
                                    <div id="fileError" class="mt-2 text-[10px] text-red-500 font-medium hidden"></div>
                                    <div id="fileName" class="mt-2 text-[10px] text-brand-600 font-medium hidden"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
        
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex flex-col sm:flex-row justify-end gap-3 shrink-0">
            <div class="flex flex-col sm:flex-row w-full justify-between gap-3">
                <button type="button" onclick="guardarSoloPaciente()" id="btnGuardarSoloPaciente" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-xl transition shadow-md flex items-center justify-center gap-1.5 min-h-[44px]">
                    <i class="ph ph-user-gear text-base"></i> Actualizar sólo Paciente
                </button>
                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                    <button type="button" onclick="closeModal()" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition min-h-[44px]">Cancelar</button>
                    <button type="submit" form="registroForm" id="btnGuardar" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-md flex items-center justify-center gap-1.5 min-h-[44px]">
                        <i class="ph ph-floppy-disk text-base"></i> Guardar Consulta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
