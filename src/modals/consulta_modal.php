<!-- Registro y edición de consultas. Los modos (consulta, solo paciente, edición) se aplican desde js/app.js. -->
<div id="patientModal" class="modal-root fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true">
    <!-- El fondo no cierra el formulario: un clic accidental no debe descartar una consulta. -->
    <div class="modal-backdrop" aria-hidden="true"></div>
    <div id="modalContent" class="modal-panel modal-panel-wide modal-enter" role="dialog" aria-modal="true" aria-labelledby="patientModalTitle" aria-describedby="patientModalSubtitle" tabindex="-1">
        <div class="modal-heading">
            <div><h2 id="patientModalTitle">Consulta e historia clínica</h2><p id="patientModalSubtitle">Revise la identificación y complete la atención. Los campos con * son obligatorios.</p></div>
            <button type="button" onclick="closeModal()" class="icon-button" aria-label="Cerrar registro de consulta"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>

        <div class="modal-body">
            <form id="registroForm" class="clinical-form" novalidate>
                <input type="hidden" name="solo_paciente" id="formSoloPaciente" value="0">
                <input type="hidden" name="fecha_circunstancia" id="formFechaCircunstancia" value="">
                <input type="hidden" name="solicitud_id" id="formSolicitudId" value="">
                <input type="hidden" name="cita_id" id="formCitaId" value="" disabled>
                <input type="hidden" name="confirmar_identidad" id="formConfirmarIdentidad" value="1" disabled>
                <input type="hidden" name="consulta_id" id="formConsultaId" value="" disabled>
                <input type="hidden" name="version_esperada" id="formVersionEsperada" value="" disabled>
                <!-- Solo en edición: conserva un resumen distinto de la enfermedad actual registrado antes. -->
                <input type="hidden" name="resumen" id="formResumen" value="" disabled>

                <!-- Solo en modo edición: identidad fija y motivo obligatorio de la corrección. -->
                <fieldset id="editConsultaBlock" class="edit-notice" hidden disabled>
                    <legend class="sr-only">Corrección de una consulta registrada</legend>
                    <div class="edit-notice-identity">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        <div><p id="editConsultaPaciente" class="edit-notice-title"></p><p id="editConsultaMeta" class="field-help"></p></div>
                    </div>
                    <div class="field">
                        <label for="formMotivoEdicion">Motivo de la corrección <span class="required-mark" aria-hidden="true">*</span></label>
                        <textarea name="motivo_edicion" id="formMotivoEdicion" rows="2" required minlength="5" maxlength="500" aria-describedby="motivoEdicionHelp" placeholder="Ej. Se corrige la tensión arterial transcrita."></textarea>
                        <p id="motivoEdicionHelp" class="field-help">Entre 5 y 500 caracteres. La versión anterior se conserva en el historial de la consulta.</p>
                    </div>
                </fieldset>

                <!-- 1. Identificación -->
                <fieldset class="clinical-section" data-section="paciente">
                    <legend class="sr-only">Identificación del paciente</legend>
                    <button type="button" id="toggle-acc-paciente" onclick="toggleAccordion('acc-paciente')" class="clinical-section-toggle" aria-controls="acc-paciente" aria-expanded="true">
                        <span class="clinical-section-title"><i class="ph ph-user-circle" aria-hidden="true"></i><span>Identificación del paciente<small id="summary-acc-paciente" class="section-summary"></small></span></span>
                        <span class="clinical-section-state"><span id="status-acc-paciente" class="section-status"></span><i class="ph ph-caret-down section-caret" aria-hidden="true"></i></span>
                    </button>
                    <div id="acc-paciente" class="clinical-section-body">
                        <div class="form-grid form-grid-3">
                            <div class="field">
                                <label for="formCedula">Cédula de identidad <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text" name="cedula" id="formCedula" data-autofocus required pattern="^[VEJGCPNvejgcpn]-[0-9]{1,18}$" maxlength="20" autocomplete="off" inputmode="text" aria-describedby="formCedulaHelp" placeholder="V-12345678" onblur="buscarPacientePorCedula(this.value)">
                                <p id="formCedulaHelp" class="field-help">Letra, guion y números. Ej. V-12345678.</p>
                            </div>
                            <div class="field">
                                <label for="formCategoria">Categoría institucional <span class="required-mark" aria-hidden="true">*</span></label>
                                <select name="categoria" id="formCategoria" required>
                                    <option value="">Seleccione una categoría</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="formSexo">Sexo <span class="required-mark" aria-hidden="true">*</span></label>
                                <select name="sexo" id="formSexo" required>
                                    <option value="">Seleccione…</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Otro">Otro / No especificado</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="formNombres">Nombres <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text" name="nombres" id="formNombres" required maxlength="100" autocomplete="off">
                            </div>
                            <div class="field">
                                <label for="formApellidos">Apellidos <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text" name="apellidos" id="formApellidos" required maxlength="100" autocomplete="off">
                            </div>
                            <div class="field">
                                <label for="formTelefono">Teléfono</label>
                                <input type="tel" name="telefono" id="formTelefono" maxlength="50" autocomplete="off" placeholder="0412-1234567">
                            </div>
                            <div class="field">
                                <label for="formLugarNacimiento">Lugar de nacimiento</label>
                                <input type="text" name="lugar_nacimiento" id="formLugarNacimiento" maxlength="255" placeholder="Ej. Acarigua">
                            </div>
                            <div class="field">
                                <label for="formFechaNacimiento">Fecha de nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="formFechaNacimiento" min="<?= date('Y-m-d', strtotime('-120 years')) ?>" max="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="field">
                                <label for="formNivelEducativo">Nivel educativo</label>
                                <input type="text" name="nivel_educativo" id="formNivelEducativo" maxlength="100" placeholder="Ej. Bachiller / Universitario">
                            </div>
                            <div class="field" data-student-only>
                                <label for="formCarrera">Carrera</label>
                                <input type="text" name="carrera" id="formCarrera" maxlength="150" placeholder="Ej. Ing. de Sistemas">
                            </div>
                            <div class="field" data-student-only>
                                <label for="formSemestre">Semestre</label>
                                <input type="text" name="semestre" id="formSemestre" maxlength="50" placeholder="Ej. 1er semestre">
                            </div>
                            <div class="field form-span-full">
                                <label for="formDireccion">Dirección de habitación</label>
                                <textarea name="direccion" id="formDireccion" rows="2" class="textarea-compact" placeholder="Dirección completa del paciente"></textarea>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 2. Antecedentes -->
                <fieldset class="clinical-section" data-section="antecedentes" data-clinical>
                    <legend class="sr-only">Antecedentes médicos, ginecoobstétricos y tatuajes</legend>
                    <button type="button" id="toggle-acc-antecedentes" onclick="toggleAccordion('acc-antecedentes')" class="clinical-section-toggle" aria-controls="acc-antecedentes" aria-expanded="false">
                        <span class="clinical-section-title"><i class="ph ph-heart" aria-hidden="true"></i><span>Antecedentes médicos y tatuajes</span></span>
                        <span class="clinical-section-state"><span id="status-acc-antecedentes" class="section-status"></span><i class="ph ph-caret-down section-caret" aria-hidden="true"></i></span>
                    </button>
                    <div id="acc-antecedentes" class="clinical-section-body hidden">
                        <div class="clinical-group">
                            <h3 class="clinical-subheading"><i class="ph ph-clipboard-text" aria-hidden="true"></i>Personales patológicos <span class="field-help">Indique la condición o «Negado»</span></h3>
                            <div class="form-grid form-grid-3">
                                <div class="field"><label for="formAntecedenteCardiovascular">Cardiovascular</label><input type="text" name="antecedente_cardiovascular" id="formAntecedenteCardiovascular" placeholder="Ej. Negado"></div>
                                <div class="field"><label for="formAntecedenteOseo">Óseo / articular</label><input type="text" name="antecedente_oseo" id="formAntecedenteOseo" placeholder="Ej. Negado"></div>
                                <div class="field"><label for="formAntecedenteRespiratorio">Respiratorio</label><input type="text" name="antecedente_respiratorio" id="formAntecedenteRespiratorio" placeholder="Ej. Asma leve"></div>
                                <div class="field"><label for="formAntecedenteDigestivo">Digestivo</label><input type="text" name="antecedente_digestivo" id="formAntecedenteDigestivo" placeholder="Ej. Negado"></div>
                                <div class="field"><label for="formAntecedenteEndocrino">Endocrino / metabólico</label><input type="text" name="antecedente_endocrino" id="formAntecedenteEndocrino" placeholder="Ej. Resistencia a la insulina"></div>
                                <div class="field"><label for="formAntecedenteOtros">Otros antecedentes</label><input type="text" name="antecedente_otros" id="formAntecedenteOtros" placeholder="Alergias, etc."></div>
                                <div class="field form-span-full"><label for="formAntecedenteQuirurgico">Quirúrgicos / traumatológicos</label><input type="text" name="antecedente_quirurgico" id="formAntecedenteQuirurgico" placeholder="Cirugías previas, fracturas, prótesis…"></div>
                            </div>
                        </div>

                        <div class="clinical-group" id="ginecoGroup">
                            <h3 class="clinical-subheading"><i class="ph ph-gender-female" aria-hidden="true"></i>Ginecoobstétricos</h3>
                            <div class="form-grid form-grid-5">
                                <div class="field"><label for="formGinecoMenarquia">Menarquia</label><input type="text" name="gineco_menarquia" id="formGinecoMenarquia" maxlength="50" placeholder="Edad"></div>
                                <div class="field"><label for="formGinecoSexarquia">Sexarquia</label><input type="text" name="gineco_sexarquia" id="formGinecoSexarquia" maxlength="50" placeholder="Edad"></div>
                                <div class="field"><label for="formGinecoAco">ACO</label><input type="text" name="gineco_aco" id="formGinecoAco" maxlength="100" placeholder="Ninguno / marca"></div>
                                <div class="field"><label for="formGinecoGestas">Gestas</label><input type="text" name="gineco_gestas" id="formGinecoGestas" maxlength="50" placeholder="N.º de embarazos"></div>
                                <div class="field"><label for="formGinecoCitologia">Última citología</label><input type="text" name="gineco_citologia" id="formGinecoCitologia" maxlength="255" placeholder="Fecha / resultado"></div>
                            </div>
                        </div>

                        <div class="clinical-group">
                            <h3 class="clinical-subheading"><i class="ph ph-palette" aria-hidden="true"></i>Control de tatuajes</h3>
                            <div class="form-grid form-grid-3">
                                <div class="field">
                                    <label for="formTieneTatuajes">¿Tiene tatuajes?</label>
                                    <select name="tiene_tatuajes" id="formTieneTatuajes">
                                        <option value="No">No</option>
                                        <option value="Sí">Sí</option>
                                    </select>
                                </div>
                                <div class="field form-span-2 commitment-field" id="compromisoGroup">
                                    <p class="commitment-text" id="compromisoTexto">Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.</p>
                                    <!-- El campo oculto envía vacío cuando la casilla no está marcada. -->
                                    <input type="hidden" name="compromiso_tatuajes" id="formCompromisoTatuajesVacio" value="">
                                    <label class="check-row" for="formCompromisoTatuajes">
                                        <input type="checkbox" name="compromiso_tatuajes" id="formCompromisoTatuajes" value="Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera." aria-describedby="compromisoTexto">
                                        <span>Firmó el compromiso de no realizarse tatuajes</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="clinical-group">
                            <h3 class="clinical-subheading"><i class="ph ph-users-three" aria-hidden="true"></i>Familiares patológicos <span class="field-help">Indique la condición o «Sano»</span></h3>
                            <div class="form-grid form-grid-4">
                                <div class="field"><label for="formAntecedentePadre">Padre</label><input type="text" name="antecedente_padre" id="formAntecedentePadre" placeholder="Sano / hipertensión"></div>
                                <div class="field"><label for="formAntecedenteMadre">Madre</label><input type="text" name="antecedente_madre" id="formAntecedenteMadre" placeholder="Sana / diabetes"></div>
                                <div class="field"><label for="formAntecedenteHermanos">Hermanos</label><input type="text" name="antecedente_hermanos" id="formAntecedenteHermanos" placeholder="Sanos"></div>
                                <div class="field"><label for="formAntecedenteHijos">Hijos</label><input type="text" name="antecedente_hijos" id="formAntecedenteHijos" placeholder="Sanos"></div>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 3. Motivo y signos vitales -->
                <fieldset class="clinical-section" data-section="vitales" data-clinical data-consulta>
                    <legend class="sr-only">Motivo clínico y signos vitales</legend>
                    <button type="button" id="toggle-acc-vitales" onclick="toggleAccordion('acc-vitales')" class="clinical-section-toggle" aria-controls="acc-vitales" aria-expanded="false">
                        <span class="clinical-section-title"><i class="ph ph-heartbeat" aria-hidden="true"></i><span>Motivo clínico y signos vitales</span></span>
                        <span class="clinical-section-state"><span id="status-acc-vitales" class="section-status"></span><i class="ph ph-caret-down section-caret" aria-hidden="true"></i></span>
                    </button>
                    <div id="acc-vitales" class="clinical-section-body hidden">
                        <div class="form-grid form-grid-2">
                            <div class="field">
                                <label for="formMotivoConsulta">Motivo de consulta <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea name="motivo_consulta" id="formMotivoConsulta" required rows="2" placeholder="Ej. Dolor de cabeza, fiebre…"></textarea>
                            </div>
                            <div class="field">
                                <label for="formEnfermedadActual">Enfermedad actual / resumen <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea name="enfermedad_actual" id="formEnfermedadActual" required rows="2" placeholder="Ej. Refiere cefalea de 3 días de evolución…"></textarea>
                            </div>
                        </div>
                        <div class="clinical-group">
                            <h3 class="clinical-subheading"><i class="ph ph-waveform" aria-hidden="true"></i>Signos vitales</h3>
                            <div class="form-grid form-grid-5">
                                <div class="field">
                                    <label for="formVitalTA">T.A. (mmHg)</label>
                                    <input type="text" name="vital_ta" id="formVitalTA" inputmode="numeric" pattern="^\d{2,3}\/\d{2,3}$" title="Sistólica/diastólica, por ejemplo 120/80." aria-describedby="formVitalTAHelp" placeholder="120/80">
                                    <p id="formVitalTAHelp" class="field-help">Ej. 120/80</p>
                                </div>
                                <div class="field"><label for="formVitalFC">F.C. (lpm)</label><input type="number" name="vital_fc" id="formVitalFC" min="0" max="300" inputmode="numeric" placeholder="72"></div>
                                <div class="field"><label for="formVitalFR">F.R. (rpm)</label><input type="number" name="vital_fr" id="formVitalFR" min="0" max="100" inputmode="numeric" placeholder="16"></div>
                                <div class="field"><label for="formVitalSpo2">SpO₂ (%)</label><input type="number" name="vital_spo2" id="formVitalSpo2" min="0" max="100" inputmode="numeric" placeholder="98"></div>
                                <div class="field"><label for="formVitalPesoTalla">Peso / talla</label><input type="text" name="vital_peso_talla" id="formVitalPesoTalla" maxlength="100" placeholder="65 kg / 1,70 m"></div>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 4. Examen físico -->
                <fieldset class="clinical-section" data-section="fisico" data-clinical data-consulta>
                    <legend class="sr-only">Examen físico general</legend>
                    <button type="button" id="toggle-acc-fisico" onclick="toggleAccordion('acc-fisico')" class="clinical-section-toggle" aria-controls="acc-fisico" aria-expanded="false">
                        <span class="clinical-section-title"><i class="ph ph-stethoscope" aria-hidden="true"></i><span>Examen físico general</span></span>
                        <span class="clinical-section-state"><span id="status-acc-fisico" class="section-status"></span><i class="ph ph-caret-down section-caret" aria-hidden="true"></i></span>
                    </button>
                    <div id="acc-fisico" class="clinical-section-body hidden">
                        <p class="field-help section-help">Describa los hallazgos patológicos. Deje vacío o escriba «Sin alteraciones» si es normal.</p>
                        <div class="form-grid form-grid-3">
                            <div class="field"><label for="formFisicoPiel">Piel y mucosas</label><input type="text" name="fisico_piel" id="formFisicoPiel" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoCabeza">Cabeza</label><input type="text" name="fisico_cabeza" id="formFisicoCabeza" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoCuello">Cuello</label><input type="text" name="fisico_cuello" id="formFisicoCuello" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoTorax">Tórax</label><input type="text" name="fisico_torax" id="formFisicoTorax" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoAbdomen">Abdomen</label><input type="text" name="fisico_abdomen" id="formFisicoAbdomen" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoExtremidades">Extremidades</label><input type="text" name="fisico_extremidades" id="formFisicoExtremidades" placeholder="Sin alteraciones"></div>
                            <div class="field"><label for="formFisicoNeurologico">Neurológico</label><input type="text" name="fisico_neurologico" id="formFisicoNeurologico" placeholder="Activo, consciente y orientado"></div>
                        </div>
                    </div>
                </fieldset>

                <!-- 5. Diagnóstico, tratamiento, reposo y anexos -->
                <fieldset class="clinical-section" data-section="diagnostico" data-clinical data-consulta>
                    <legend class="sr-only">Diagnóstico, tratamiento, reposo y anexos</legend>
                    <button type="button" id="toggle-acc-diagnostico" onclick="toggleAccordion('acc-diagnostico')" class="clinical-section-toggle" aria-controls="acc-diagnostico" aria-expanded="false">
                        <span class="clinical-section-title"><i class="ph ph-prescription" aria-hidden="true"></i><span>Diagnóstico, tratamiento, reposo y anexos</span></span>
                        <span class="clinical-section-state"><span id="status-acc-diagnostico" class="section-status"></span><i class="ph ph-caret-down section-caret" aria-hidden="true"></i></span>
                    </button>
                    <div id="acc-diagnostico" class="clinical-section-body hidden">
                        <div class="form-grid form-grid-2">
                            <div class="field">
                                <label for="formDiagnostico">Impresión diagnóstica <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea name="diagnostico" id="formDiagnostico" required rows="2" placeholder="Ej. Faringitis aguda"></textarea>
                            </div>
                            <div class="field">
                                <label for="formPlanTratamiento">Plan de tratamiento / indicaciones <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea name="plan_tratamiento" id="formPlanTratamiento" required rows="2" placeholder="Ej. Analgésico cada 8 horas, abundante líquido…"></textarea>
                            </div>
                            <div class="field">
                                <label for="formLaboratorios">Laboratorios o exámenes complementarios</label>
                                <textarea name="laboratorios" id="formLaboratorios" rows="2" class="textarea-compact" placeholder="Ej. Examen de orina, química sanguínea…"></textarea>
                            </div>
                            <div class="field">
                                <label for="formPendiente">Pendiente / observaciones</label>
                                <textarea name="pendiente" id="formPendiente" rows="2" class="textarea-compact" placeholder="Ej. Traer resultados en la próxima cita…"></textarea>
                            </div>
                        </div>

                        <div class="clinical-group">
                            <h3 class="clinical-subheading"><i class="ph ph-calendar" aria-hidden="true"></i>Reposo médico <span class="field-help">Opcional</span></h3>
                            <div class="form-grid form-grid-2">
                                <div class="field"><label for="formInicioReposo">Inicio del reposo</label><input type="date" name="inicio_reposo" id="formInicioReposo" min="1900-01-01" aria-describedby="reposoHelp"></div>
                                <div class="field"><label for="formFinReposo">Fin del reposo</label><input type="date" name="fin_reposo" id="formFinReposo" min="1900-01-01" aria-describedby="reposoHelp"></div>
                            </div>
                            <p id="reposoHelp" class="field-help">Indique ambas fechas. El inicio puede ser hasta 7 días antes o 30 días después de la consulta; máximo 365 días.</p>
                        </div>

                        <div class="field clinical-attachment" id="attachmentField">
                            <label for="archivoAdjunto">Informe externo o exámenes <span class="field-help">(opcional)</span></label>
                            <input type="file" name="archivo" id="archivoAdjunto" accept=".jpg,.jpeg,.png,.pdf" aria-describedby="fileHelp" onchange="validarArchivo(this)">
                            <p id="fileHelp" class="field-help">JPG, PNG o PDF. Tamaño máximo: 10 MB.</p>
                            <p id="fileError" class="file-error-text hidden" role="alert"></p>
                            <p id="fileName" class="field-help hidden" role="status"></p>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>

        <div class="modal-footer clinical-footer">
            <button type="button" onclick="guardarSoloPaciente()" id="btnGuardarSoloPaciente" class="btn btn-secondary"><i class="ph ph-user-gear" aria-hidden="true"></i> Guardar solo paciente</button>
            <button type="button" onclick="closeModal()" class="btn btn-text">Cancelar</button>
            <button type="submit" form="registroForm" id="btnGuardar" class="btn btn-primary"><i class="ph ph-floppy-disk" aria-hidden="true"></i> Guardar consulta</button>
        </div>
    </div>
</div>
