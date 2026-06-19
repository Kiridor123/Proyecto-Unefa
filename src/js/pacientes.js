// Carga de Pacientes (API -> Cache global)
async function loadPacientes() {
    const tableLoader = document.getElementById('tableLoader');
    const tableWrap = document.getElementById('pacientesTableWrap');
    const cardsWrap = document.getElementById('tableBodyCards');
    if (tableLoader) tableLoader.style.display = 'flex';
    if (tableWrap) tableWrap.style.display = 'none';
    if (cardsWrap) cardsWrap.style.display = 'none';
    try {
        let res = await fetch('api/get_pacientes.php');
        let json = await res.json();
        if(json.success) {
            pacientesList = json.data;
            renderTable();
        }
    } catch(e) { 
        console.error('Error pacientes:', e); 
    } finally {
        if (tableLoader) tableLoader.style.display = 'none';
        if (tableWrap) tableWrap.style.display = '';
        if (cardsWrap) cardsWrap.style.display = '';
    }
}

// Renderizar Tabla de Pacientes (XSS Protegido e Inclusivo de break-points)
function renderTable() {
    const tbody = document.getElementById('tableBody');
    const emptyState = document.getElementById('emptyState');
    if (!tbody) return;
    
    const filterCedulaEl = document.getElementById('filterCedula');
    const filterCategoriaEl = document.getElementById('filterCategoria');

    const qCedula = filterCedulaEl ? filterCedulaEl.value.toLowerCase() : '';
    const qCat = filterCategoriaEl && filterCategoriaEl.selectedIndex >= 0 ? filterCategoriaEl.options[filterCategoriaEl.selectedIndex].text.toLowerCase() : '';
    const isFilterCatActive = filterCategoriaEl ? filterCategoriaEl.value !== "" : false;

    tbody.innerHTML = '';
    let visibleCount = 0;

    const cardsContainer = document.getElementById('tableBodyCards');
    if (cardsContainer) cardsContainer.innerHTML = '';

    pacientesList.forEach(p => {
        const cedulaMatch = p.cedula.toLowerCase().includes(qCedula);
        const catMatch = !isFilterCatActive || p.categoria.toLowerCase() === qCat;

        if (cedulaMatch && catMatch) {
            visibleCount++;
            const initials = `${escapeHTML(p.nombres.charAt(0))}${escapeHTML(p.apellidos.charAt(0))}`;
            const fullName = `${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}`;
            const lastConsult = p.ultima_consulta
                ? p.ultima_consulta.split(' ')[0]
                : '<span class="text-slate-400 italic">Sin consultas</span>';

            // ── FILA DE TABLA (visible solo en md+) ───────────────────
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/60 cursor-pointer transition';
            row.onclick = () => viewPatientDetails(p.id);
            row.innerHTML = `
                <td class="px-6 py-4 font-semibold text-slate-700 text-sm">${escapeHTML(p.cedula)}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-xs shrink-0">${initials}</div>
                        <span class="font-medium text-slate-800 text-sm">${fullName}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/50">${escapeHTML(p.categoria)}</span>
                </td>
                <td class="px-6 py-4 text-slate-500 text-sm">${lastConsult}</td>
                <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                    <div class="flex justify-end gap-2">
                        <button onclick="openModal('${escapeHTML(p.cedula)}', ${p.categoria_id}, '${escapeHTML(p.nombres)}', '${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-brand-50 text-slate-400 hover:text-brand-600 rounded-lg transition" title="Registrar Consulta">
                            <i class="ph ph-stethoscope text-lg"></i>
                        </button>
                        <button onclick="openCitaModal(${p.id}, '${escapeHTML(p.cedula)}', '${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-amber-50 text-slate-400 hover:text-amber-500 rounded-lg transition" title="Programar Cita">
                            <i class="ph ph-calendar-plus text-lg"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);

            // ── TARJETA MÓVIL (visible solo en < md) ─────────────────
            if (cardsContainer) {
                const card = document.createElement('div');
                card.className = 'glass-panel p-5 rounded-2xl border border-slate-100 bg-white/85 hover:bg-white transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-md gap-4 relative';
                card.onclick = () => viewPatientDetails(p.id);
                card.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100 shadow-sm">${initials}</div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-800 text-sm sm:text-base truncate leading-tight">${fullName}</h4>
                                <p class="text-xs text-slate-500 font-semibold mt-1 flex items-center gap-1">
                                    <i class="ph ph-identification-card text-brand-500 text-sm"></i> ${escapeHTML(p.cedula)}
                                </p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/50 shrink-0">${escapeHTML(p.categoria)}</span>
                    </div>
                    
                    <div class="border-t border-slate-100/70 pt-3 flex flex-col gap-1 text-[11px] text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <i class="ph ph-calendar text-xs text-slate-400"></i>
                            <span>Última consulta:</span>
                            <span class="font-medium text-slate-700">${p.ultima_consulta ? p.ultima_consulta.split(' ')[0] : '<span class="text-slate-400 italic">Sin consultas</span>'}</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 mt-1 pt-1" onclick="event.stopPropagation()">
                        <button onclick="openModal('${escapeHTML(p.cedula)}', ${p.categoria_id}, '${escapeHTML(p.nombres)}', '${escapeHTML(p.apellidos)}')" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-brand-50 hover:bg-brand-100 text-brand-600 text-xs font-bold rounded-xl border border-brand-100 transition shadow-sm min-h-[44px]">
                            <i class="ph ph-stethoscope text-base"></i> Registrar Consulta
                        </button>
                        <button onclick="openCitaModal(${p.id}, '${escapeHTML(p.cedula)}', '${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}')" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-amber-50 hover:bg-amber-100 text-amber-600 text-xs font-bold rounded-xl border border-amber-100 transition shadow-sm min-h-[44px]">
                            <i class="ph ph-calendar-plus text-base"></i> Agendar Cita
                        </button>
                    </div>
                `;
                cardsContainer.appendChild(card);
            }
        }
    });


    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
            emptyState.style.display = 'flex';
        } else {
            emptyState.classList.add('hidden');
            emptyState.style.display = 'none';
        }
    }
}

// Búsqueda Inteligente para la Consulta
async function buscarPacientePorCedula(cedula) {
    if (!cedula) return;
    const cedulaLimpia = cedula.replace(/\s+/g, '').toUpperCase();
    
    // Buscar en nuestra lista cacheada local
    const paciente = pacientesList.find(p => p.cedula.replace(/\s+/g, '').toUpperCase() === cedulaLimpia);
    if (paciente) {
        try {
            let res = await fetch(`api/get_paciente_detalle.php?id=${paciente.id}`);
            let json = await res.json();
            if (json.success) {
                const p = json.data.paciente;
                document.getElementById('formCategoria').value = p.categoria_id;
                document.getElementById('formNombres').value = p.nombres;
                document.getElementById('formApellidos').value = p.apellidos;
                
                // Rellenar campos adicionales de historia clínica
                document.getElementById('formSexo').value = p.sexo || '';
                document.getElementById('formTelefono').value = p.telefono || '';
                document.getElementById('formLugarNacimiento').value = p.lugar_nacimiento || '';
                document.getElementById('formFechaNacimiento').value = p.fecha_nacimiento || '';
                document.getElementById('formNivelEducativo').value = p.nivel_educativo || '';
                document.getElementById('formCarrera').value = p.carrera || '';
                document.getElementById('formSemestre').value = p.semestre || '';
                document.getElementById('formDireccion').value = p.direccion || '';
                
                document.getElementById('formAntecedenteCardiovascular').value = p.antecedente_cardiovascular || '';
                document.getElementById('formAntecedenteOseo').value = p.antecedente_oseo || '';
                document.getElementById('formAntecedenteRespiratorio').value = p.antecedente_respiratorio || '';
                document.getElementById('formAntecedenteDigestivo').value = p.antecedente_digestivo || '';
                document.getElementById('formAntecedenteEndocrino').value = p.antecedente_endocrino || '';
                document.getElementById('formAntecedenteOtros').value = p.antecedente_otros || '';
                document.getElementById('formAntecedenteQuirurgico').value = p.antecedente_quirurgico || '';
                
                document.getElementById('formGinecoMenarquia').value = p.gineco_menarquia || '';
                document.getElementById('formGinecoSexarquia').value = p.gineco_sexarquia || '';
                document.getElementById('formGinecoAco').value = p.gineco_aco || '';
                document.getElementById('formGinecoGestas').value = p.gineco_gestas || '';
                document.getElementById('formGinecoCitologia').value = p.gineco_citologia || '';
                
                document.getElementById('formTieneTatuajes').value = p.tiene_tatuajes || 'No';
                document.getElementById('formCompromisoTatuajes').value = p.compromiso_tatuajes || 'Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.';
                
                document.getElementById('formAntecedentePadre').value = p.antecedente_padre || '';
                document.getElementById('formAntecedenteMadre').value = p.antecedente_madre || '';
                document.getElementById('formAntecedenteHermanos').value = p.antecedente_hermanos || '';
                document.getElementById('formAntecedenteHijos').value = p.antecedente_hijos || '';

                // Efecto visual de autocompletado en el primer acordeón
                const fields = ['formCategoria', 'formSexo', 'formNombres', 'formApellidos', 'formTelefono'];
                fields.forEach(fid => {
                    const el = document.getElementById(fid);
                    if(el) {
                        el.classList.add('ring-2', 'ring-emerald-500/20', 'border-emerald-500');
                        setTimeout(() => {
                            el.classList.remove('ring-2', 'ring-emerald-500/20', 'border-emerald-500');
                        }, 1500);
                    }
                });
            }
        } catch(e) {
            console.error("Fallo al autocompletar paciente clínico", e);
        }
    }
}

// Historial Clínico del Paciente (Drawer)
async function viewPatientDetails(id) {
    const drawer = document.getElementById('patientDrawer');
    const drawerContent = document.getElementById('drawerContent');
    const drawerLoader = document.getElementById('drawerLoader');
    const drawerData = document.getElementById('drawerData');
    
    if (!drawer || !drawerContent) return;

    drawer.classList.remove('hidden');
    void drawer.offsetWidth; // Reflow
    drawerContent.classList.remove('translate-x-full');
    
    if (drawerLoader) drawerLoader.classList.remove('hidden');
    if (drawerData) drawerData.classList.add('hidden');
    
    try {
        let res = await fetch(`api/get_paciente_detalle.php?id=${id}`);
        let json = await res.json();
        
        if (json.success) {
            const data = json.data;
            document.getElementById('detNombre').textContent = `${data.paciente.nombres} ${data.paciente.apellidos}`;
            document.getElementById('detCedula').textContent = data.paciente.cedula;
            document.getElementById('detCategoria').textContent = data.paciente.categoria;
            document.getElementById('detIniciales').textContent = `${data.paciente.nombres.charAt(0)}${data.paciente.apellidos.charAt(0)}`;
            
            // Configurar botones rápidos en el Drawer
            document.getElementById('btnConsultaAlVuelo').onclick = () => {
                closeDrawer();
                openModal(data.paciente.cedula, data.paciente.categoria_id, data.paciente.nombres, data.paciente.apellidos);
            };
            document.getElementById('btnCitaAlVuelo').onclick = () => {
                closeDrawer();
                openCitaModal(data.paciente.id, data.paciente.cedula, `${data.paciente.nombres} ${data.paciente.apellidos}`);
            };
            document.getElementById('btnPrintHistoria').onclick = () => {
                window.open(`api/print_historia.php?paciente_id=${data.paciente.id}`, '_blank');
            };

            // Renderizar Consultas
            const detConsultas = document.getElementById('detConsultas');
            detConsultas.innerHTML = '';
            
            if (data.consultas.length === 0) {
                detConsultas.innerHTML = `
                    <div class="text-center py-8 text-slate-400 text-xs">
                        <i class="ph ph-stethoscope text-2xl mb-2 text-slate-300"></i>
                        <p>Este paciente no tiene consultas registradas.</p>
                    </div>
                `;
            } else {
                data.consultas.forEach(c => {
                    let reposoHtml = '';
                    if (c.fecha_inicio_reposo && c.fecha_fin_reposo) {
                        const start = new Date(c.fecha_inicio_reposo);
                        const end = new Date(c.fecha_fin_reposo);
                        const days = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                        reposoHtml = `
                            <div class="mt-2 flex items-center gap-1.5 text-[10px] sm:text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-lg px-2.5 py-1.5 w-fit">
                                <i class="ph ph-calendar-blank"></i>
                                <span>Reposo: ${c.fecha_inicio_reposo} al ${c.fecha_fin_reposo} (${days} días)</span>
                            </div>
                        `;
                    }

                    let archivosHtml = '';
                    if (c.archivos && c.archivos.length > 0) {
                        c.archivos.forEach(file => {
                            const isPdf = file.tipo_archivo.includes('pdf');
                            const iconClass = isPdf ? 'ph-file-pdf text-red-500' : 'ph-image text-blue-500';
                            archivosHtml += `
                                <a href="${file.ruta_archivo}" target="_blank" class="flex items-center gap-2 p-2 bg-slate-50 hover:bg-slate-100 border border-slate-200/60 rounded-xl text-[10px] font-medium text-slate-700 transition">
                                    <i class="ph ${iconClass} text-base shrink-0"></i>
                                    <span class="truncate max-w-[120px]">${file.ruta_archivo.split('/').pop()}</span>
                                    <i class="ph ph-download ml-auto text-slate-400"></i>
                                </a>
                            `;
                        });
                    }

                    const div = document.createElement('div');
                    div.className = 'p-4 sm:p-5 bg-white border border-slate-200/80 shadow-sm rounded-2xl space-y-3';
                    div.innerHTML = `
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-[10px] font-semibold text-brand-600 bg-brand-50 border border-brand-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                <i class="ph ph-calendar"></i> ${c.fecha_circunstancia}
                            </span>
                            <span class="text-[9px] font-medium text-slate-400">Reg: ${c.fecha_registro.split(' ')[0]}</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">${escapeHTML(c.resumen)}</p>
                        ${reposoHtml}
                        ${archivosHtml ? `<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">${archivosHtml}</div>` : ''}
                        
                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-2.5 mt-2">
                            <button onclick="window.open('api/print_informe.php?consulta_id=${c.id}', '_blank')" class="flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-[10px] font-semibold text-slate-700 rounded-lg transition">
                                <i class="ph ph-printer"></i> Imprimir Informe
                            </button>
                            ${c.fecha_inicio_reposo && c.fecha_fin_reposo ? `
                                <button onclick="window.open('api/print_reposo.php?consulta_id=${c.id}', '_blank')" class="flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-[10px] font-semibold text-rose-700 rounded-lg border border-rose-100 transition">
                                    <i class="ph ph-prescription"></i> Imprimir Reposo
                                </button>
                            ` : ''}
                        </div>
                    `;
                    detConsultas.appendChild(div);
                });
            }

            // Renderizar Citas
            const detCitas = document.getElementById('detCitas');
            detCitas.innerHTML = '';
            
            if (data.citas.length === 0) {
                detCitas.innerHTML = `<p class="text-center py-4 text-slate-400 text-xs">Sin citas programadas.</p>`;
            } else {
                data.citas.forEach(cita => {
                    let badge = '';
                    if (cita.estado === 'Pendiente') badge = '<span class="text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 px-2 py-0.5 rounded-full">Pendiente</span>';
                    else if (cita.estado === 'Completada') badge = '<span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-0.5 rounded-full">Completada</span>';
                    else badge = '<span class="text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full">Cancelada</span>';
                    
                    // Formato fecha amigable
                    const fechaAmigable = cita.fecha_cita.replace('T', ' ');

                    const div = document.createElement('div');
                    div.className = 'flex justify-between items-center py-2';
                    div.innerHTML = `
                        <span class="text-xs font-medium text-slate-600 flex items-center gap-1">
                            <i class="ph ph-clock"></i> ${fechaAmigable}
                        </span>
                        ${badge}
                    `;
                    detCitas.appendChild(div);
                });
            }

            if (drawerLoader) drawerLoader.classList.add('hidden');
            if (drawerData) drawerData.classList.remove('hidden');
        } else {
            Swal.fire('Error', json.message, 'error');
            closeDrawer();
        }
    } catch(e) {
        console.error(e);
        Swal.fire('Error', 'Hubo un fallo al recuperar el expediente.', 'error');
        closeDrawer();
    }
}

// Actualizar únicamente los datos del paciente (sin consulta)
async function guardarSoloPaciente() {
    const form = document.getElementById('registroForm');
    if (!form) return;

    // Validar campos obligatorios del paciente manualmente
    const requiredPatientFields = [
        { id: 'formCedula', name: 'Cédula de Identidad' },
        { id: 'formCategoria', name: 'Categoría Institucional' },
        { id: 'formSexo', name: 'Sexo' },
        { id: 'formNombres', name: 'Nombres' },
        { id: 'formApellidos', name: 'Apellidos' }
    ];

    for (const field of requiredPatientFields) {
        const el = document.getElementById(field.id);
        if (!el || !el.value.trim()) {
            showNotification('warning', `El campo "${field.name}" es obligatorio.`);
            toggleAccordion('acc-paciente');
            if (el) el.focus();
            return;
        }
    }

    // Set solo_paciente flag
    const formSoloPaciente = document.getElementById('formSoloPaciente');
    if (formSoloPaciente) {
        formSoloPaciente.value = '1';
    }

    // Temporalmente habilitar novalidate para evitar validación de campos obligatorios clínicos
    form.setAttribute('novalidate', '');

    // Disparar envío
    form.requestSubmit();
}

// Envío de Formulario Registro Consulta / Paciente
const registroForm = document.getElementById('registroForm');
if (registroForm) {
    registroForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const isSoloPaciente = document.getElementById('formSoloPaciente').value === '1';
        const btnId = isSoloPaciente ? 'btnGuardarSoloPaciente' : 'btnGuardar';
        const btn = document.getElementById(btnId);
        
        let originalText = '';
        if (btn) {
            originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Guardando...';
            btn.disabled = true;
        }

        const formData = new FormData(this);

        try {
            let res = await fetch('api/save_registro.php', {
                method: 'POST',
                body: formData
            });
            let json = await res.json();
            
            if (json.success) {
                showNotification('success', json.message);
                closeModal();
                // Recargar tablas y stats
                loadStats();
                loadPacientes();
            } else {
                if (json.errors && Object.keys(json.errors).length > 0) {
                    if (typeof handleInlineErrors === 'function') {
                        handleInlineErrors(json.errors, 'registroForm');
                    }
                    if (typeof showValidationErrors === 'function') {
                        showValidationErrors(json.errors);
                    } else {
                        showNotification('error', json.message);
                    }
                } else {
                    showNotification('error', json.message);
                }
            }
        } catch (error) {
            console.error(error);
            showNotification('error', 'Hubo un error de conexión con el servidor.');
        } finally {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
            this.removeAttribute('novalidate');
            const formSoloPaciente = document.getElementById('formSoloPaciente');
            if (formSoloPaciente) {
                formSoloPaciente.value = '0';
            }
        }
    });
}

// Filtros de eventos para Pacientes
const filterCedula = document.getElementById('filterCedula');
if (filterCedula) {
    filterCedula.addEventListener('input', renderTable);
}

const filterCategoria = document.getElementById('filterCategoria');
if (filterCategoria) {
    filterCategoria.addEventListener('change', renderTable);
}
