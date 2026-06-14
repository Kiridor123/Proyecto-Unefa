<?php
// Frontend interactivo con carga dinámica (Vanilla JS Fetch API)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Web de Gestión Médica</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Para Alertas elegantes -->

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">

    <aside class="w-64 bg-brand-900 text-white flex flex-col hidden md:flex fixed h-full shadow-2xl z-10">
        <div class="p-6 flex items-center gap-3 border-b border-brand-700/50">
            <div class="bg-white/10 p-2 rounded-lg"><i class="ph ph-heartbeat text-2xl text-blue-300"></i></div>
            <div>
                <h1 class="text-lg font-bold leading-tight">Salud UNEFA</h1>
                <p class="text-xs text-brand-100/70">Gestión Médica</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-brand-700/50 rounded-xl text-sm font-medium transition hover:bg-brand-700">
                <i class="ph ph-squares-four text-lg"></i> Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-brand-100 hover:bg-white/5 rounded-xl text-sm font-medium transition">
                <i class="ph ph-users text-lg"></i> Pacientes
            </a>
        </nav>
    </aside>

    <main class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen">
        <header class="h-16 glass-panel sticky top-0 z-20 flex justify-between items-center px-8 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold text-slate-800">Panel de Control</h2>
            </div>
        </header>

        <div class="p-8 flex-1 flex flex-col gap-8 max-w-7xl mx-auto w-full">
            
            <!-- Tarjetas Dinámicas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-panel p-6 rounded-2xl border-l-4 border-brand-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Total Pacientes</p>
                        <h3 class="text-3xl font-bold text-slate-800" id="statPacientes">-</h3>
                    </div>
                    <div class="w-12 h-12 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center text-2xl"><i class="ph ph-users"></i></div>
                </div>
                
                <div class="glass-panel p-6 rounded-2xl border-l-4 border-amber-400 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Citas Pendientes</p>
                        <h3 class="text-3xl font-bold text-slate-800" id="statCitas">-</h3>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-2xl"><i class="ph ph-calendar-check"></i></div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Consultas del Mes</p>
                        <h3 class="text-3xl font-bold text-slate-800" id="statConsultas">-</h3>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-2xl"><i class="ph ph-stethoscope"></i></div>
                </div>
            </div>

            <!-- Sección de Tabla -->
            <div class="glass-panel rounded-2xl flex flex-col flex-1 border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4 bg-white/50">
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="filterCedula" placeholder="Buscar por Cédula..." 
                                class="w-full sm:w-64 pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-brand-500 transition">
                        </div>
                        <div class="relative min-w-[160px]">
                            <select id="filterCategoria" class="w-full pl-4 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 appearance-none shadow-sm transition">
                                <option value="">Todas las Categorías</option>
                                <!-- JS llenará esto -->
                            </select>
                            <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <button onclick="openModal()" class="w-full lg:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-xl transition">
                        <i class="ph ph-plus-circle text-lg"></i> Registrar Consulta
                    </button>
                </div>

                <div class="overflow-x-auto relative">
                    <!-- Loader Overlay -->
                    <div id="tableLoader" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center text-brand-600">
                        <i class="ph ph-spinner-gap animate-spin text-4xl mb-2"></i>
                        <span class="text-sm font-medium">Cargando pacientes...</span>
                    </div>

                    <table class="w-full text-left border-collapse" id="patientsTable">
                        <thead>
                            <tr class="bg-slate-50/80 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                                <th class="px-6 py-4">Cédula</th>
                                <th class="px-6 py-4">Nombres y Apellidos</th>
                                <th class="px-6 py-4">Categoría</th>
                                <th class="px-6 py-4">Última Consulta</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100 bg-white/30" id="tableBody">
                            <!-- Datos inyectados vía JS -->
                        </tbody>
                    </table>
                    
                    <div id="emptyState" class="hidden flex flex-col items-center justify-center py-12 text-slate-400">
                        <i class="ph ph-magnifying-glass text-4xl mb-3 text-slate-300"></i>
                        <p class="text-sm">No se encontraron registros.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Modal de Registro -->
    <div id="patientModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 sm:p-0">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div id="modalContent" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh] modal-enter">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ph ph-user-plus text-brand-600"></i> Registrar Consulta
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form id="registroForm" class="space-y-6">
                    <div>
                        <h4 class="text-sm font-semibold text-brand-700 uppercase tracking-wider mb-4 border-b border-brand-100 pb-2">Datos del Paciente</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Cédula de Identidad *</label>
                                <input type="text" name="cedula" id="formCedula" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition" placeholder="Ej. V-12345678">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Categoría Institucional *</label>
                                <select name="categoria" id="formCategoria" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                                    <option value="">Seleccione...</option>
                                    <!-- Opciones cargadas por JS -->
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Nombres *</label>
                                <input type="text" name="nombres" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Apellidos *</label>
                                <input type="text" name="apellidos" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-brand-700 uppercase tracking-wider mb-4 border-b border-brand-100 pb-2">Detalle de Consulta</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Fecha de Circunstancia *</label>
                                <input type="date" name="fecha_circunstancia" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Resumen del Caso Médico *</label>
                                <textarea name="resumen" required rows="3" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Inicio de Reposo</label>
                                    <input type="date" name="inicio_reposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Fin de Reposo</label>
                                    <input type="date" name="fin_reposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-brand-700 uppercase tracking-wider mb-4 border-b border-brand-100 pb-2">Archivo Adjunto (Opcional)</h4>
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 transition relative">
                            <input type="file" name="archivo" id="archivoAdjunto" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="validarArchivo(this)">
                            <i class="ph ph-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                            <p class="text-sm font-medium text-slate-600">Haz clic o arrastra un archivo aquí</p>
                            <p class="text-xs text-slate-400 mt-1">Formatos soportados: JPG, PNG, PDF (Máx. 10MB)</p>
                            <div id="fileError" class="mt-2 text-xs text-red-500 font-medium hidden"></div>
                            <div id="fileName" class="mt-2 text-xs text-brand-600 font-medium hidden"></div>
                        </div>
                    </div>

                </form>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end gap-3">
                <button onclick="closeModal()" class="px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button type="submit" form="registroForm" id="btnGuardar" class="px-5 py-2 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-md flex items-center gap-2">
                    <i class="ph ph-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Funciones de Carga Inicial
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadCategorias();
            loadPacientes();
        });

        async function loadStats() {
            try {
                let res = await fetch('api/get_stats.php');
                let json = await res.json();
                if(json.success) {
                    document.getElementById('statPacientes').textContent = json.data.total_pacientes;
                    document.getElementById('statCitas').textContent = json.data.citas_proximas;
                    document.getElementById('statConsultas').textContent = json.data.consultas_mes;
                }
            } catch(e) { console.error('Error stats:', e); }
        }

        async function loadCategorias() {
            try {
                let res = await fetch('api/get_categorias.php');
                let json = await res.json();
                if(json.success) {
                    const filterSelect = document.getElementById('filterCategoria');
                    const formSelect = document.getElementById('formCategoria');
                    
                    let options = '';
                    json.data.forEach(cat => {
                        options += `<option value="${cat.id}">${cat.nombre}</option>`;
                    });

                    filterSelect.innerHTML += options;
                    formSelect.innerHTML += options;
                }
            } catch(e) { console.error('Error cats:', e); }
        }

        // Variable global para almacenar pacientes y filtrar rápido en frontend
        let pacientesList = [];

        async function loadPacientes() {
            document.getElementById('tableLoader').classList.remove('hidden');
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
                document.getElementById('tableLoader').classList.add('hidden');
            }
        }

        function renderTable() {
            const tbody = document.getElementById('tableBody');
            const emptyState = document.getElementById('emptyState');
            
            const qCedula = document.getElementById('filterCedula').value.toLowerCase();
            const qCat = document.getElementById('filterCategoria').options[document.getElementById('filterCategoria').selectedIndex].text.toLowerCase();
            const isFilterCatActive = document.getElementById('filterCategoria').value !== "";

            tbody.innerHTML = '';
            let visibleCount = 0;

            pacientesList.forEach(p => {
                const cedulaMatch = p.cedula.toLowerCase().includes(qCedula);
                const catMatch = !isFilterCatActive || p.categoria.toLowerCase() === qCat;

                if (cedulaMatch && catMatch) {
                    visibleCount++;
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50 transition';
                    row.innerHTML = `
                        <td class="px-6 py-4 font-medium text-slate-700">${p.cedula}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                    ${p.nombres.charAt(0)}${p.apellidos.charAt(0)}
                                </div>
                                <span>${p.nombres} ${p.apellidos}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">${p.categoria}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">${p.ultima_consulta ? p.ultima_consulta.split(' ')[0] : 'Sin consultas'}</td>
                    `;
                    tbody.appendChild(row);
                }
            });

            if (visibleCount === 0) emptyState.classList.remove('hidden');
            else emptyState.classList.add('hidden');
        }

        // Filtros eventos
        document.getElementById('filterCedula').addEventListener('input', renderTable);
        document.getElementById('filterCategoria').addEventListener('change', renderTable);

        // Envío de Formulario
        document.getElementById('registroForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardar');
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
                        title: 'Oops...',
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

        // Modal Lógica
        const modal = document.getElementById('patientModal');
        const modalContent = document.getElementById('modalContent');

        function openModal() {
            modal.classList.remove('hidden');
            void modalContent.offsetWidth; // reflow
            modalContent.classList.remove('modal-enter');
            modalContent.classList.add('modal-enter-active');
        }

        function closeModal() {
            modalContent.classList.remove('modal-enter-active');
            modalContent.classList.add('modal-leave-active');
            setTimeout(() => {
                modal.classList.add('hidden');
                modalContent.classList.remove('modal-leave-active');
                modalContent.classList.add('modal-enter');
                document.getElementById('registroForm').reset();
                document.getElementById('fileError').classList.add('hidden');
                document.getElementById('fileName').classList.add('hidden');
            }, 200);
        }

        // Validación de Archivo en Frontend
        function validarArchivo(input) {
            const fileError = document.getElementById('fileError');
            const fileName = document.getElementById('fileName');
            const maxSize = 10 * 1024 * 1024;
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            fileError.classList.add('hidden');
            fileName.classList.add('hidden');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > maxSize) {
                    fileError.textContent = "El archivo excede el tamaño máximo permitido de 10MB.";
                    fileError.classList.remove('hidden');
                    input.value = "";
                    return;
                }
                if (!validTypes.includes(file.type)) {
                    fileError.textContent = "Formato no válido. Solo se permiten archivos JPG, PNG o PDF.";
                    fileError.classList.remove('hidden');
                    input.value = "";
                    return;
                }
                fileName.textContent = "Archivo seleccionado: " + file.name;
                fileName.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
