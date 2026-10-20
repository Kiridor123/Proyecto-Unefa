<!-- Modal de Registro de Consulta (Responsive) -->
<div id="patientModal" class="modal-root fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true" onclick="closeModal()"></div>
    <div id="modalContent" class="modal-panel modal-panel-wide modal-enter" role="dialog" aria-modal="true" aria-labelledby="patientModalTitle" tabindex="-1">
        <div class="modal-heading">
            <div><h2 id="patientModalTitle">Consulta e historia clínica</h2><p>Revise la identificación y complete la atención. Los campos con * son obligatorios.</p></div>
            <button type="button" onclick="closeModal()" class="icon-button" aria-label="Cerrar registro de consulta"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>
        
        <div class="modal-body">
            <form id="registroForm" class="space-y-4" novalidate>
                <input type="hidden" name="solo_paciente" id="formSoloPaciente" value="0">
                <input type="hidden" name="fecha_circunstancia" id="formFechaCircunstancia" value="">
                <div class="space-y-3">
                    
                    <!-- Accordion Item 1: Datos Personales -->
                    <div class="clinical-section">
                        <button type="button" onclick="toggleAccordion('acc-paciente')" class="clinical-section-toggle">
                            <span class="flex items-center gap-2"><i class="ph ph-user-circle text-brand-600 text-base sm:text-lg"></i> Identificación del Paciente</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-paciente"></i>
                        </button>
                        <div id="acc-paciente" class="clinical-section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="field ">
                                    <label>Cédula de Identidad <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="cedula" id="formCedula" required pattern="^[VEJGCPNvejgcpn]-[0-9]{1,18}$" maxlength="20" autocomplete="off" title="Formato válido: V-12345678" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. V-12345678" onblur="buscarPacientePorCedula(this.value)">
                                </div>
                                <div class="field ">
                                    <label>Categoría Institucional <span class="text-brand-600 font-bold">*</span></label>
                                    <select name="categoria" id="formCategoria" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                        <option value="">Seleccione...</option>
                                        <!-- Opciones cargadas por JS -->
                                    </select>
                                </div>
                                <div class="field ">
                                    <label>Sexo <span class="text-brand-600 font-bold">*</span></label>
                                    <select name="sexo" id="formSexo" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                        <option value="">Seleccione...</option>
                                        <option value="Femenino">Femenino</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Otro">Otro / No especificado</option>
                                    </select>
                                </div>
                                <div class="field ">
                                    <label>Nombres <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="nombres" id="formNombres" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div class="field ">
                                    <label>Apellidos <span class="text-brand-600 font-bold">*</span></label>
                                    <input type="text" name="apellidos" id="formApellidos" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div class="field ">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" id="formTelefono" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 0412-1234567">
                                </div>
                                <div class="field ">
                                    <label>Lugar de Nacimiento</label>
                                    <input type="text" name="lugar_nacimiento" id="formLugarNacimiento" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Acarigua">
                                </div>
                                <div class="field ">
                                    <label>Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" id="formFechaNacimiento" max="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                </div>
                                <div class="field ">
                                    <label>Nivel Educativo</label>
                                    <input type="text" name="nivel_educativo" id="formNivelEducativo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Bachiller / Universitario">
                                </div>
                                <div class="field ">
                                    <label>Carrera (Solo Estudiantes)</label>
                                    <input type="text" name="carrera" id="formCarrera" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Ing. de Sistemas">
                                </div>
                                <div class="field ">
                                    <label>Semestre (Solo Estudiantes)</label>
                                    <input type="text" name="semestre" id="formSemestre" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 1er Semestre">
                                </div>
                                <div class="field sm:col-span-2 lg:col-span-3">
                                    <label>Dirección de Habitación</label>
                                    <textarea name="direccion" id="formDireccion" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Dirección completa del paciente..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 2: Antecedentes Médicos -->
                    <div class="clinical-section">
                        <button type="button" onclick="toggleAccordion('acc-antecedentes')" class="clinical-section-toggle">
                            <span class="flex items-center gap-2"><i class="ph ph-heart text-brand-600 text-base sm:text-lg"></i> Antecedentes Médicos, Ginecológicos y Tatuajes</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-antecedentes"></i>
                        </button>
                        <div id="acc-antecedentes" class="clinical-section-body hidden">
                            <!-- Antecedentes Personales Patológicos -->
                            <div>
                                <h5 class="clinical-subheading"><i class="ph ph-clipboard-text"></i> Personales Patológicos (Indicar condición o 'Negado')</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div class="field ">
                                        <label>Cardiovascular</label>
                                        <input type="text" name="antecedente_cardiovascular" id="formAntecedenteCardiovascular" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div class="field ">
                                        <label>Óseo / Articular</label>
                                        <input type="text" name="antecedente_oseo" id="formAntecedenteOseo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div class="field ">
                                        <label>Respiratorio</label>
                                        <input type="text" name="antecedente_respiratorio" id="formAntecedenteRespiratorio" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Asma leve">
                                    </div>
                                    <div class="field ">
                                        <label>Digestivo</label>
                                        <input type="text" name="antecedente_digestivo" id="formAntecedenteDigestivo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                    </div>
                                    <div class="field ">
                                        <label>Endocrino / Metabólico</label>
                                        <input type="text" name="antecedente_endocrino" id="formAntecedenteEndocrino" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Resistencia insulina">
                                    </div>
                                    <div class="field ">
                                        <label>Otros antecedentes</label>
                                        <input type="text" name="antecedente_otros" id="formAntecedenteOtros" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Alergias, etc.">
                                    </div>
                                </div>
                            </div>

                            <!-- Quirurgicos -->
                            <div>
                                <h5 class="clinical-subheading"><i class="ph ph-bandaids"></i> Quirúrgicos / Traumatológicos</h5>
                                <input type="text" name="antecedente_quirurgico" id="formAntecedenteQuirurgico" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Cirugías previas, fracturas, prótesis...">
                            </div>

                            <!-- Antecedentes Ginecobstétricos -->
                            <div>
                                <h5 class="clinical-subheading"><i class="ph ph-gender-female"></i> Ginecoobstétricos (Si aplica)</h5>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    <div class="field ">
                                        <label>Menarquia</label>
                                        <input type="text" name="gineco_menarquia" id="formGinecoMenarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                    </div>
                                    <div class="field ">
                                        <label>Sexarquia</label>
                                        <input type="text" name="gineco_sexarquia" id="formGinecoSexarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                    </div>
                                    <div class="field ">
                                        <label>ACO</label>
                                        <input type="text" name="gineco_aco" id="formGinecoAco" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ninguno / Marca">
                                    </div>
                                    <div class="field ">
                                        <label>Gestas</label>
                                        <input type="text" name="gineco_gestas" id="formGinecoGestas" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="N° Embarazos">
                                    </div>
                                    <div class="field col-span-2 sm:col-span-1">
                                        <label>Última Citología</label>
                                        <input type="text" name="gineco_citologia" id="formGinecoCitologia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Fecha / Resultado">
                                    </div>
                                </div>
                            </div>

                            <!-- Tatuajes y Compromiso -->
                            <div>
                                <h5 class="clinical-subheading"><i class="ph ph-palette"></i> Control de Tatuajes</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="field ">
                                        <label>¿Tiene Tatuajes?</label>
                                        <select name="tiene_tatuajes" id="formTieneTatuajes" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                            <option value="No">No</option>
                                            <option value="Sí">Sí</option>
                                        </select>
                                    </div>
                                    <div class="field sm:col-span-2">
                                        <label>Compromiso Institucional</label>
                                        <input type="text" name="compromiso_tatuajes" id="formCompromisoTatuajes" value="Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera." class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Antecedentes Familiares -->
                            <div>
                                <h5 class="clinical-subheading"><i class="ph ph-users-three"></i> Familiares Patológicos (Indicar condición o 'Sano')</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div class="field ">
                                        <label>Padre</label>
                                        <input type="text" name="antecedente_padre" id="formAntecedentePadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sano / Hipertensión">
                                    </div>
                                    <div class="field ">
                                        <label>Madre</label>
                                        <input type="text" name="antecedente_madre" id="formAntecedenteMadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sana / Diabetes">
                                    </div>
                                    <div class="field ">
                                        <label>Hermanos</label>
                                        <input type="text" name="antecedente_hermanos" id="formAntecedenteHermanos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                    </div>
                                    <div class="field ">
                                        <label>Hijos</label>
                                        <input type="text" name="antecedente_hijos" id="formAntecedenteHijos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 3: Motivo y Signos Vitales -->
                    <div class="clinical-section">
                        <button type="button" onclick="toggleAccordion('acc-vitales')" class="clinical-section-toggle">
                            <span class="flex items-center gap-2"><i class="ph ph-heartbeat text-brand-600 text-base sm:text-lg"></i> Motivo Clínico y Signos Vitales</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-vitales"></i>
                        </button>
                        <div id="acc-vitales" class="clinical-section-body hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="field ">
                                    <label>Motivo de Consulta <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="motivo_consulta" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Dolor de cabeza, fiebre..."></textarea>
                                </div>
                                <div class="field ">
                                    <label>Enfermedad Actual / Resumen <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="enfermedad_actual" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Paciente refiere cefalea de 3 días de evolución..."></textarea>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-3">
                                <h5 class="clinical-subheading"><i class="ph ph-waveform"></i> Toma de Signos Vitales</h5>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    <div class="field ">
                                        <label>T.A. (mmHg)</label>
                                        <input type="text" name="vital_ta" id="formVitalTA" pattern="^\d{2,3}\/\d{2,3}$" title="Ej. 120/80" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 120/80">
                                    </div>
                                    <div class="field ">
                                        <label>F.C. (lpm)</label>
                                        <input type="number" name="vital_fc" id="formVitalFC" min="0" max="300" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 72">
                                    </div>
                                    <div class="field ">
                                        <label>F.R. (rpm)</label>
                                        <input type="number" name="vital_fr" id="formVitalFR" min="0" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 16">
                                    </div>
                                    <div class="field ">
                                        <label>SpO2 (%)</label>
                                        <input type="number" name="vital_spo2" id="formVitalSpo2" min="0" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 98">
                                    </div>
                                    <div class="field col-span-2 sm:col-span-1">
                                        <label>Peso/Talla</label>
                                        <input type="text" name="vital_peso_talla" id="formVitalPesoTalla" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 65 kg / 1.70 m">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 4: Examen Físico -->
                    <div class="clinical-section">
                        <button type="button" onclick="toggleAccordion('acc-fisico')" class="clinical-section-toggle">
                            <span class="flex items-center gap-2"><i class="ph ph-stethoscope text-brand-600 text-base sm:text-lg"></i> Examen Físico General</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-fisico"></i>
                        </button>
                        <div id="acc-fisico" class="clinical-section-body hidden">
                            <p class="text-xs text-slate-400 italic mb-2">Describa cualquier hallazgo patológico. Deje vacío o coloque 'Sin alteraciones' si está normal.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="field ">
                                    <label>Piel y Mucosa</label>
                                    <input type="text" name="fisico_piel" id="formFisicoPiel" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Cabeza</label>
                                    <input type="text" name="fisico_cabeza" id="formFisicoCabeza" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Cuello</label>
                                    <input type="text" name="fisico_cuello" id="formFisicoCuello" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Tórax</label>
                                    <input type="text" name="fisico_torax" id="formFisicoTorax" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Abdomen</label>
                                    <input type="text" name="fisico_abdomen" id="formFisicoAbdomen" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Extremidades</label>
                                    <input type="text" name="fisico_extremidades" id="formFisicoExtremidades" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                </div>
                                <div class="field ">
                                    <label>Neurológico</label>
                                    <input type="text" name="fisico_neurologico" id="formFisicoNeurologico" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Activo, consciente y orientado">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 5: Diagnóstico, Tratamiento, Reposo y Adjunto -->
                    <div class="clinical-section">
                        <button type="button" onclick="toggleAccordion('acc-diagnostico')" class="clinical-section-toggle">
                            <span class="flex items-center gap-2"><i class="ph ph-prescription text-brand-600 text-base sm:text-lg"></i> Diagnóstico, Tratamiento, Reposo y Anexos</span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-diagnostico"></i>
                        </button>
                        <div id="acc-diagnostico" class="clinical-section-body hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="field ">
                                    <label>Impresión Diagnóstica (DX) <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="diagnostico" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Faringitis aguda, etc."></textarea>
                                </div>
                                <div class="field ">
                                    <label>Plan de Tratamiento / Indicaciones <span class="text-brand-600 font-bold">*</span></label>
                                    <textarea name="plan_tratamiento" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Analgésicos cada 8 horas, abundante líquido..."></textarea>
                                </div>
                                <div class="field ">
                                    <label>Laboratorios u Exámenes Complementarios</label>
                                    <textarea name="laboratorios" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Examen de orina, química sanguínea..."></textarea>
                                </div>
                                <div class="field ">
                                    <label>Pendiente / Observaciones</label>
                                    <textarea name="pendiente" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Traer resultados en la próxima cita..."></textarea>
                                </div>
                            </div>

                            <!-- --- Reposo Médico --- -->
                            <div class="border-t border-slate-100 pt-3">
                                <h5 class="clinical-subheading"><i class="ph ph-calendar"></i> Configurar Reposo Médico (Opcional)</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="field ">
                                        <label>Inicio de Reposo</label>
                                        <input type="date" name="inicio_reposo" id="formInicioReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                    <div class="field ">
                                        <label>Fin de Reposo</label>
                                        <input type="date" name="fin_reposo" id="formFinReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-base sm:text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Archivo Adjunto -->
                            <div class="field field clinical-attachment">
                                <label for="archivoAdjunto">Informe externo o exámenes <span class="field-help">(opcional)</span></label>
                                <input type="file" name="archivo" id="archivoAdjunto" accept=".jpg,.jpeg,.png,.pdf" aria-describedby="fileHelp" onchange="validarArchivo(this)">
                                <p id="fileHelp" class="field-help">JPG, PNG o PDF. Tamaño máximo: 10 MB.</p>
                                <p id="fileError" class="inline-error-text hidden" role="status"></p>
                                <p id="fileName" class="field-help hidden" role="status"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
        
        <div class="modal-footer clinical-footer">
            <button type="button" onclick="guardarSoloPaciente()" id="btnGuardarSoloPaciente" class="btn btn-secondary"><i class="ph ph-user-gear" aria-hidden="true"></i> Guardar solo paciente</button>
            <button type="button" onclick="closeModal()" class="btn btn-text">Cancelar</button>
            <button type="submit" form="registroForm" id="btnGuardar" class="btn btn-primary"><i class="ph ph-floppy-disk" aria-hidden="true"></i> Guardar consulta</button>
        </div>
    </div>
</div>
