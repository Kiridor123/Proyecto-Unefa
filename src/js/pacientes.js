// Carga de Pacientes (API -> Cache global)
async function loadPacientes() {
    const tableLoader = document.getElementById('tableLoader');
    if (tableLoader) tableLoader.classList.remove('hidden');
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
        if (tableLoader) tableLoader.classList.add('hidden');
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

    pacientesList.forEach(p => {
        const cedulaMatch = p.cedula.toLowerCase().includes(qCedula);
        const catMatch = !isFilterCatActive || p.categoria.toLowerCase() === qCat;

        if (cedulaMatch && catMatch) {
            visibleCount++;
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/60 cursor-pointer transition';
            
            // Al hacer clic en la fila, se abre el drawer con el historial clínico
            row.onclick = () => viewPatientDetails(p.id);

            row.innerHTML = `
                <td class="px-4 sm:px-6 py-4 font-semibold text-slate-700 text-xs sm:text-sm">${escapeHTML(p.cedula)}</td>
                <td class="px-4 sm:px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-xs shrink-0">
                            ${escapeHTML(p.nombres.charAt(0))}${escapeHTML(p.apellidos.charAt(0))}
                        </div>
                        <span class="font-medium text-slate-800 text-xs sm:text-sm truncate max-w-[120px] sm:max-w-none">${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}</span>
                    </div>
                </td>
                <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/50">${escapeHTML(p.categoria)}</span>
                </td>
                <td class="px-4 sm:px-6 py-4 hidden sm:table-cell text-slate-500 text-xs sm:text-sm">${p.ultima_consulta ? p.ultima_consulta.split(' ')[0] : '<span class="text-slate-400 italic">Sin consultas</span>'}</td>
                <td class="px-4 sm:px-6 py-4 text-right" onclick="event.stopPropagation()">
                    <div class="flex justify-end gap-1.5 sm:gap-2">
                        <button onclick="openModal('${escapeHTML(p.cedula)}', ${p.categoria_id}, '${escapeHTML(p.nombres)}', '${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-brand-50 text-slate-400 hover:text-brand-600 rounded-lg transition" title="Registrar Consulta">
                            <i class="ph ph-stethoscope text-base sm:text-lg"></i>
                        </button>
                        <button onclick="openCitaModal(${p.id}, '${escapeHTML(p.cedula)}', '${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-amber-50 text-slate-400 hover:text-amber-500 rounded-lg transition" title="Programar Cita">
                            <i class="ph ph-calendar-plus text-base sm:text-lg"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        }
    });

    if (emptyState) {
        if (visibleCount === 0) emptyState.classList.remove('hidden');
        else emptyState.classList.add('hidden');
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

// Envío de Formulario Registro Consulta
const registroForm = document.getElementById('registroForm');
if (registroForm) {
    registroForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardar');
        if (!btn) return;
        
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Guardando...';
        btn.disabled = true;

        const formData = new FormData(this);

        try {
            let res = await fetch('api/save_registro.php', {
                method: 'POST',
                body: formData
            });
            let json = await res.json();
            
            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Registrado!',
                    text: json.message,
                    confirmButtonColor: '#3b82f6'
                });
                closeModal();
                // Recargar tablas y stats
                loadStats();
                loadPacientes();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: json.message,
                    confirmButtonColor: '#3b82f6'
                });
            }
        } catch (error) {
            Swal.fire('Error', 'Hubo un error de conexión con el servidor', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
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
