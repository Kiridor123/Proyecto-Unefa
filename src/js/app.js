// Variables globales
let pacientesList = [];
let citasList = [];
let currentTab = 'dashboard';
let categoryChartInstance = null;
let historyChartInstance = null;

// Sistema de notificaciones Toast auto-cerrables (SweetAlert2)
function showNotification(type, message, duration = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type, // 'success', 'error', 'warning', 'info'
        title: message
    });
}

// Alerta consolidada (Pop-up) para errores de validación múltiples
function showValidationErrors(errors) {
    let htmlList = '<ul class="text-left text-sm text-slate-600 list-disc list-inside mt-2 space-y-1">';
    
    // Soportar tanto array normal (antiguo) como objeto (nuevo)
    const errorMessages = Array.isArray(errors) ? errors : Object.values(errors);
    
    errorMessages.forEach(err => {
        htmlList += `<li>${escapeHTML(err)}</li>`;
    });
    htmlList += '</ul>';

    Swal.fire({
        icon: 'warning',
        title: 'Existen errores de validación',
        html: htmlList,
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'Entendido'
    });
}

// Mostrar errores en línea bajo cada input
function handleInlineErrors(errorsObj, formId) {
    clearInlineErrors(formId);
    if (!errorsObj || typeof errorsObj !== 'object' || Array.isArray(errorsObj)) return;

    const form = document.getElementById(formId);
    if (!form) return;

    for (const [fieldName, message] of Object.entries(errorsObj)) {
        const input = form.querySelector(`[name="${fieldName}"]`);
        if (input) {
            // Pintar borde rojo
            input.classList.add('border-red-500', 'bg-red-50');
            input.classList.remove('border-slate-200', 'bg-slate-50', 'focus:border-brand-500');
            
            // Crear o buscar el texto de ayuda
            let errorText = input.nextElementSibling;
            if (!errorText || !errorText.classList.contains('inline-error-text')) {
                errorText = document.createElement('p');
                errorText.className = 'inline-error-text text-red-500 text-[10px] mt-1 font-medium';
                input.parentNode.insertBefore(errorText, input.nextSibling);
            }
            errorText.textContent = message;

            // Limpiar error al cambiar
            const cleanError = function() {
                input.classList.remove('border-red-500', 'bg-red-50');
                input.classList.add('border-slate-200', 'bg-slate-50', 'focus:border-brand-500');
                if (errorText) errorText.remove();
                input.removeEventListener('input', cleanError);
            };
            input.addEventListener('input', cleanError);
        }
    }
}

// Limpiar todos los errores en línea de un form
function clearInlineErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const errorTexts = form.querySelectorAll('.inline-error-text');
    errorTexts.forEach(el => el.remove());
    const invalidInputs = form.querySelectorAll('.border-red-500');
    invalidInputs.forEach(input => {
        input.classList.remove('border-red-500', 'bg-red-50');
        input.classList.add('border-slate-200', 'bg-slate-50', 'focus:border-brand-500');
    });
}

// Autoformateo de Cédula (Ej. "v12" -> "V-12")
document.addEventListener('input', function(e) {
    if (e.target && e.target.name === 'cedula') {
        let val = e.target.value.toUpperCase().replace(/\s/g, '');
        if (val.length > 0) {
            // Si empieza con letra válida, asegurar el guion
            if (/^[VEJGCPN]/.test(val)) {
                if (val.length > 1 && val[1] !== '-') {
                    val = val.substring(0, 1) + '-' + val.substring(1);
                }
            } else if (val.length === 1 && /[0-9]/.test(val)) {
                // Si alguien mete un número primero, por defecto poner V-
                val = 'V-' + val;
            }
        }
        e.target.value = val;
    }
    
    // Autoformateo Tensión Arterial (Ej. 12080 -> 120/80)
    if (e.target && e.target.name === 'vital_ta') {
        let val = e.target.value.replace(/[^\d/]/g, ''); // solo números y barra
        if (val.length === 5 && !val.includes('/')) {
            val = val.substring(0, 3) + '/' + val.substring(3);
        }
        e.target.value = val;
    }
});

// Escapar HTML para evitar XSS
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

// Toggles para Sidebar móvil con Transición Suave
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    const isOpen = sidebar.classList.contains('translate-x-0');
    
    if (isOpen) {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.add('translate-x-0');
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
}

// Navegación de secciones (TABS)
function switchTab(tabName) {
    currentTab = tabName;
    
    // Cerrar sidebar en móvil al cambiar de sección
    if(document.getElementById('sidebar').classList.contains('translate-x-0') && window.innerWidth < 768) {
        toggleMobileSidebar();
    }

    // Ocultar todas las secciones
    document.querySelectorAll('.tab-section').forEach(sec => sec.classList.add('hidden'));
    
    // Desactivar todos los links de navegación
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('bg-brand-700/50', 'text-white');
        link.classList.add('text-brand-100', 'hover:bg-white/5');
    });
    
    // Mostrar sección seleccionada
    document.getElementById('section-' + tabName).classList.remove('hidden');
    
    // Activar link seleccionado
    const activeLink = document.getElementById('link-' + tabName);
    if (activeLink) {
        activeLink.classList.add('bg-brand-700/50', 'text-white');
        activeLink.classList.remove('text-brand-100', 'hover:bg-white/5');
    }
    
    // Títulos de Header
    const titles = {
        'dashboard': 'Panel de Control',
        'pacientes': 'Gestión de Pacientes',
        'citas': 'Control de Citas Médicas',
        'reportes': 'Reportes de Morbilidad'
    };
    document.getElementById('headerTitle').textContent = titles[tabName] || 'Salud UNEFA';
    
    // Recargar sección correspondiente
    if (tabName === 'pacientes') {
        loadPacientes();
    } else if (tabName === 'citas') {
        loadCitas();
    } else if (tabName === 'dashboard') {
        loadStats();
    }
}

// Control de secciones colapsables (Acordeón)
function toggleAccordion(id) {
    document.querySelectorAll('[id^="acc-"]').forEach(item => {
        item.classList.add('hidden');
        const itemIcon = document.getElementById('icon-' + item.id);
        if (itemIcon) itemIcon.classList.remove('rotate-180');
    });
    const el = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    if (el) {
        el.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
    }
}

// Carga de Categorías
async function loadCategorias() {
    try {
        let res = await fetch('api/get_categorias.php');
        let json = await res.json();
        if(json.success) {
            const filterSelect = document.getElementById('filterCategoria');
            const formSelect = document.getElementById('formCategoria');
            const formCitaSelect = document.getElementById('citaCategoria');
            
            let options = '';
            json.data.forEach(cat => {
                options += `<option value="${cat.id}">${escapeHTML(cat.nombre)}</option>`;
            });

            if (filterSelect) filterSelect.innerHTML = '<option value="">Todas las Categorías</option>' + options;
            if (formSelect) formSelect.innerHTML = '<option value="">Seleccione...</option>' + options;
            if (formCitaSelect) formCitaSelect.innerHTML = '<option value="">Seleccione...</option>' + options;
        }
    } catch(e) { console.error('Error cats:', e); }
}

// Modal Lógica - Consulta
const modal = document.getElementById('patientModal');
const modalContent = document.getElementById('modalContent');

function openModal(cedula = '', categoriaId = '', nombres = '', apellidos = '') {
    const modalEl = document.getElementById('patientModal');
    const modalContentEl = document.getElementById('modalContent');
    if (!modalEl || !modalContentEl) return;
    
    modalEl.classList.remove('hidden');
    void modalContentEl.offsetWidth; // reflow
    modalContentEl.classList.remove('modal-enter');
    modalContentEl.classList.add('modal-enter-active');
    
    // Asegurar que el flag solo_paciente inicie en 0
    const formSoloPaciente = document.getElementById('formSoloPaciente');
    if (formSoloPaciente) {
        formSoloPaciente.value = '0';
    }

    // Si viene pre-rellenado (desde el drawer o botón de fila)
    if (cedula) {
        document.getElementById('formCedula').value = cedula;
        document.getElementById('formCategoria').value = categoriaId;
        document.getElementById('formNombres').value = nombres;
        document.getElementById('formApellidos').value = apellidos;
        
        // Cargar el historial completo del paciente para rellenar campos clínicos
        buscarPacientePorCedula(cedula);
    }
    
    // Colocar por defecto la fecha de hoy en fecha de circunstancia
    const today = new Date().toISOString().split('T')[0];
    const fechaCircunstanciaEl = document.getElementById('formFechaCircunstancia');
    if (fechaCircunstanciaEl) {
        fechaCircunstanciaEl.value = today;
    }

    // Por defecto, abrir la primera pestaña (identificación) del acordeón
    toggleAccordion('acc-paciente');
    const formCompromisoTatuajes = document.getElementById('formCompromisoTatuajes');
    if (formCompromisoTatuajes) {
        formCompromisoTatuajes.value = 'Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.';
    }
}

function closeModal() {
    const modalEl = document.getElementById('patientModal');
    const modalContentEl = document.getElementById('modalContent');
    if (!modalEl || !modalContentEl) return;

    modalContentEl.classList.remove('modal-enter-active');
    modalContentEl.classList.add('modal-leave-active');
    setTimeout(() => {
        modalEl.classList.add('hidden');
        modalContentEl.classList.remove('modal-leave-active');
        modalContentEl.classList.add('modal-enter');
        
        const form = document.getElementById('registroForm');
        if (form) {
            form.removeAttribute('novalidate');
            form.reset();
        }
        
        document.getElementById('fileError').classList.add('hidden');
        document.getElementById('fileName').classList.add('hidden');
    }, 200);
}

// Modal Lógica - Cita
const citaModal = document.getElementById('citaModal');
const citaModalContent = document.getElementById('citaModalContent');

function openCitaModal(pacienteId = 0, cedula = '', nombreCompleto = '') {
    const citaModalEl = document.getElementById('citaModal');
    const citaModalContentEl = document.getElementById('citaModalContent');
    if (!citaModalEl || !citaModalContentEl) return;

    citaModalEl.classList.remove('hidden');
    void citaModalContentEl.offsetWidth; // reflow
    citaModalContentEl.classList.remove('modal-enter');
    citaModalContentEl.classList.add('modal-enter-active');
    
    if (pacienteId > 0) {
        document.getElementById('citaPacienteId').value = pacienteId;
        document.getElementById('citaCedula').value = cedula;
        document.getElementById('citaPacienteNombreText').textContent = nombreCompleto;
        document.getElementById('citaPacienteInfo').classList.remove('hidden');
        
        // Desactivar requeridos del vuelo
        document.getElementById('citaNombres').required = false;
        document.getElementById('citaApellidos').required = false;
        document.getElementById('citaCategoria').required = false;
    }
}

function closeCitaModal() {
    const citaModalEl = document.getElementById('citaModal');
    const citaModalContentEl = document.getElementById('citaModalContent');
    if (!citaModalEl || !citaModalContentEl) return;

    citaModalContentEl.classList.remove('modal-enter-active');
    citaModalContentEl.classList.add('modal-leave-active');
    setTimeout(() => {
        citaModalEl.classList.add('hidden');
        citaModalContentEl.classList.remove('modal-leave-active');
        citaModalContentEl.classList.add('modal-enter');
        document.getElementById('citaForm').reset();
        document.getElementById('citaPacienteInfo').classList.add('hidden');
        document.getElementById('citaNuevoPacienteCampos').classList.add('hidden');
        document.getElementById('citaPacienteId').value = 0;
    }, 200);
}

function closeDrawer() {
    const drawer = document.getElementById('patientDrawer');
    const content = document.getElementById('drawerContent');
    if (!drawer || !content) return;

    content.classList.add('translate-x-full');
    setTimeout(() => {
        drawer.classList.add('hidden');
    }, 300);
}

// Validación de Archivos (MIME/size)
function validarArchivo(input) {
    const fileError = document.getElementById('fileError');
    const fileName = document.getElementById('fileName');
    const maxSize = 10 * 1024 * 1024;
    const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!fileError || !fileName) return;

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

// Carga inicial al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadCategorias();
    loadPacientes(); // Cargar pacientes en cache global
});
