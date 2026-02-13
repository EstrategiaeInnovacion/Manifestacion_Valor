// Importar axios si es necesario
import axios from 'axios';
import ExchangeRateManager from './exchange-rates';

// Variables globales
let currentSearchedRfc = null;
let selectedCoveRow = null;
let pedimentosData = [];
let incrementablesData = [];
let decrementablesData = [];
let precioPagadoData = [];
let precioPorPagarData = [];
let compensoPagoData = [];

// ============================================
// HELPERS PARA OBTENER TEXTOS DE CATALOGOS
// ============================================
/**
 * Obtiene el texto descriptivo de un select dado su ID y el valor de la clave
 */
window.getSelectTextByValue = function(selectId, value) {
    if (!value) return '';
    const select = document.getElementById(selectId);
    if (!select) return value;
    const option = select.querySelector(`option[value="${value}"]`);
    return option ? option.textContent : value;
};

/**
 * Mapeo de valores antiguos (texto) a claves VUCEM correctas
 * Esto permite migrar datos guardados con formato incorrecto
 */
const LEGACY_KEY_MAP = {
    // Tipos de figura
    'Representante Legal': 'TIPFIG.REP',
    'Agente Aduanal': 'TIPFIG.AGE',
    'Agencia Aduanal': 'TIPFIG.AAD',
    'Otro': 'TIPFIG.OTR',
    // Formas de pago (antiguas)
    'TRANSFERENCIA': 'FORPAG.TE',
    'EFECTIVO': 'FORPAG.EF',
    'CHEQUE': 'FORPAG.CH',
    'CARTA_CREDITO': 'FORPAG.CC',
    'LETRA_CAMBIO': 'FORPAG.LC',
    'OTRO': 'FORPAG.OT',
    // Incrementables (valores antiguos)
    'GASTOS_TRANSPORTE': 'INCRE.GS',
    'GASTOS_CARGA': 'INCRE.GS',
    'GASTOS_SEGURO': 'INCRE.GS',
    'COMISIONES': 'INCRE.CG',
    'ENVASES_EMBALAJES': 'INCRE.CE',
    'GASTOS_CONTENEDOR': 'INCRE.GT',
    'MATERIALES_CONSUMIBLES': 'INCRE.MP',
    'HERRAMIENTAS_UTILES': 'INCRE.HM',
    'MATERIALES_CONSUMIDOS': 'INCRE.MC',
    'TRABAJOS_INGENIERIA': 'INCRE.TI',
    'TRABAJOS_ARTE': 'INCRE.TI',
    'PLANOS_CROQUIS': 'INCRE.TI',
    'CANONES_DERECHOS': 'INCRE.RD',
    'OTROS': 'INCRE.VC',
    // Decrementables (valores antiguos)
    'GASTOS_DESCARGA': 'DECRE.GT',
    'GASTOS_MANIPULACION': 'DECRE.GT',
    'INTERESES': 'DECRE.PI',
    'OTROS_DECREMENTABLES': 'DECRE.GR',
};

/**
 * Normaliza una clave antigua al formato VUCEM correcto
 */
window.normalizeLegacyKey = function(value) {
    if (!value) return '';
    // Si ya es una clave VUCEM válida, devolverla tal cual
    if (value.includes('.')) return value;
    // Buscar en el mapa de conversión
    return LEGACY_KEY_MAP[value] || value;
};

// ============================================
// FUNCIONES DE LOCALSTORAGE PARA MVE
// ============================================
const MVE_STORAGE_KEY = 'mve_manifestacion_data';

/**
 * Obtener el ID del applicant actual para usar como clave única
 */
function getApplicantStorageKey() {
    const container = document.getElementById('mveManualData');
    const applicantId = container ? container.dataset.applicantId : 'default';
    return `${MVE_STORAGE_KEY}_${applicantId}`;
}

/**
 * Guardar datos de MVE en localStorage
 */
window.saveMveToLocalStorage = function(data) {
    try {
        const storageKey = getApplicantStorageKey();
        const existingData = getMveFromLocalStorage() || {};
        const mergedData = { ...existingData, ...data, lastUpdated: new Date().toISOString() };
        localStorage.setItem(storageKey, JSON.stringify(mergedData));
        console.log('Datos MVE guardados en localStorage:', mergedData);
        return true;
    } catch (error) {
        console.error('Error guardando en localStorage:', error);
        return false;
    }
};

/**
 * Obtener datos de MVE desde localStorage
 */
window.getMveFromLocalStorage = function() {
    try {
        const storageKey = getApplicantStorageKey();
        const data = localStorage.getItem(storageKey);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('Error leyendo de localStorage:', error);
        return null;
    }
};

/**
 * Limpiar datos de MVE del localStorage
 */
window.clearMveLocalStorage = function() {
    try {
        const storageKey = getApplicantStorageKey();
        localStorage.removeItem(storageKey);
        console.log('Datos MVE eliminados de localStorage');
        return true;
    } catch (error) {
        console.error('Error limpiando localStorage:', error);
        return false;
    }
};

/**
 * Guardar información de pedimentos en localStorage
 */
window.savePedimentosToLocalStorage = function() {
    saveMveToLocalStorage({ pedimentos: pedimentosData });
};

/**
 * Guardar información de incoterm en localStorage
 */
window.saveIncotermToLocalStorage = function(incoterm) {
    saveMveToLocalStorage({ incoterm: incoterm });
};

/**
 * Guardar información de vinculación (importador-vendedor) en localStorage
 */
window.saveVinculacionToLocalStorage = function(valor) {
    saveMveToLocalStorage({ vinculacion: valor });
};

/**
 * Guardar información de pedimento (número, patente, aduana) en localStorage
 */
window.savePedimentoInfoToLocalStorage = function(pedimento, patente, aduana) {
    saveMveToLocalStorage({ 
        pedimentoInfo: {
            numero: pedimento,
            patente: patente,
            aduana: aduana
        }
    });
};

/**
 * Cargar datos guardados de localStorage al cargar la página
 */
window.loadMveFromLocalStorage = function() {
    const savedData = getMveFromLocalStorage();
    if (!savedData) return;
    
    console.log('Cargando datos de localStorage:', savedData);
    
    // Cargar pedimentos si existen
    if (savedData.pedimentos && Array.isArray(savedData.pedimentos) && savedData.pedimentos.length > 0) {
        pedimentosData = savedData.pedimentos;
        actualizarTablaPedimentos();
    }
    
    // Cargar incoterm si existe
    if (savedData.incoterm) {
        const incotermSelect = document.getElementById('modalIncoterm');
        if (incotermSelect) {
            incotermSelect.value = savedData.incoterm;
        }
    }
    
    // Cargar vinculación si existe
    if (savedData.vinculacion !== undefined && savedData.vinculacion !== '') {
        const radioButton = document.querySelector(`input[name="modalExisteVinculacion"][value="${savedData.vinculacion}"]`);
        if (radioButton) {
            radioButton.checked = true;
        }
    }
};

const getMveManualData = () => {
    const container = document.getElementById('mveManualData');

    if (!container) {
        return null;
    }

    const parseJson = (value, fallback) => {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
    };

    return {
        personaConsulta: parseJson(container.dataset.personaConsulta, []),
        metodoValoracion: container.dataset.metodoValoracion || '',
        existeVinculacion: container.dataset.existeVinculacion || '',
        pedimento: container.dataset.pedimento || '',
        patente: container.dataset.patente || '',
        aduana: container.dataset.aduana || '',
        informacionCove: parseJson(container.dataset.informacionCove, []),
        pedimentosGuardados: parseJson(container.dataset.pedimentos, []),
        incrementables: parseJson(container.dataset.incrementables, []),
        decrementables: parseJson(container.dataset.decrementables, []),
        precioPagado: parseJson(container.dataset.precioPagado, []),
        precioPorPagar: parseJson(container.dataset.precioPorPagar, []),
        compensoPago: parseJson(container.dataset.compensoPago, []),
        valorAduana: parseJson(container.dataset.valorAduana, null),
        documentos: parseJson(container.dataset.documentos, []),
        desdeArchivoM: container.dataset.desdeArchivoM === 'true', // Indica si los datos vienen del archivo M
        vinculacionArchivoM: container.dataset.vinculacionArchivoM || '', // Vinculación extraída del registro 551
    };
};

const getOptionText = (selectId, value) => {
    const select = document.getElementById(selectId);

    if (!select) {
        return value;
    }

    const option = Array.from(select.options).find((opt) => opt.value === value);

    return option ? option.textContent : value;
};

window.loadSavedDataCallback = function() {
    const data = getMveManualData();

    if (!data) {
        return;
    }

    // Debug: Log para ver los datos de COVE
    console.log('Datos COVE cargados:', data.informacionCove);

    if (Array.isArray(data.personaConsulta) && data.personaConsulta.length > 0) {
        data.personaConsulta.forEach((persona) => {
            if (!persona) {
                return;
            }

            // Normalizar clave antigua a formato VUCEM
            const tipoFiguraClave = normalizeLegacyKey(persona.tipo_figura);
            const tipoFiguraDesc = getOptionText('tipoFiguraConsulta', tipoFiguraClave);
            window.addRfcToTable(persona.rfc, persona.razon_social, tipoFiguraDesc, tipoFiguraClave);
        });
    }

    if (data.metodoValoracion && document.getElementById('metodoValoracion')) {
        document.getElementById('metodoValoracion').value = data.metodoValoracion;
    }
    if (data.existeVinculacion && document.getElementById('existeVinculacion')) {
        document.getElementById('existeVinculacion').value = data.existeVinculacion;
    }
    if (data.pedimento && document.getElementById('pedimento')) {
        document.getElementById('pedimento').value = data.pedimento;
    }
    if (data.patente && document.getElementById('patente')) {
        document.getElementById('patente').value = data.patente;
    }
    if (data.aduana && document.getElementById('aduana')) {
        document.getElementById('aduana').value = data.aduana;
    }

    if (Array.isArray(data.informacionCove) && data.informacionCove.length > 0) {
        // Si los datos vienen del archivo M (primera carga), cargar en los campos del formulario
        // Si los datos ya están guardados en BD, cargarlos en la tabla
        if (data.desdeArchivoM) {
            // Primera carga desde archivo M: cargar en campos para que el usuario revise y agregue
            const primerCove = data.informacionCove[0];
            if (primerCove && primerCove.numero_cove) {
                window.loadCoveToFields(
                    primerCove.numero_cove,
                    primerCove.metodo_valoracion || '',
                    primerCove.numero_factura || '',
                    primerCove.fecha_expedicion || '',
                    primerCove.emisor_original || '',
                    primerCove.destinatario || ''
                );
                
                // Si hay incoterm, guardarlo en localStorage
                if (primerCove.incoterm) {
                    saveIncotermToLocalStorage(primerCove.incoterm);
                }
                
                // Si hay vinculación en el COVE, guardarlo en localStorage
                if (primerCove.vinculacion) {
                    saveVinculacionToLocalStorage(primerCove.vinculacion);
                }
            }
            
            // Guardar vinculación del archivo M (del registro 551) en localStorage
            if (data.vinculacionArchivoM) {
                saveVinculacionToLocalStorage(data.vinculacionArchivoM);
                console.log('Vinculación del archivo M guardada:', data.vinculacionArchivoM);
            }
            
            // Guardar pedimento, patente y aduana en localStorage desde el archivo M
            if (data.pedimento || data.patente || data.aduana) {
                savePedimentoInfoToLocalStorage(data.pedimento || '', data.patente || '', data.aduana || '');
            }
        } else {
            // Datos ya guardados en BD: cargar directamente en la tabla
            data.informacionCove.forEach((cove) => {
                if (cove && cove.numero_cove) {
                    window.addCoveToTableFromData(
                        cove.numero_cove,
                        cove.metodo_valoracion || '',
                        cove.numero_factura || '',
                        cove.fecha_expedicion || '',
                        cove.emisor_original || '',
                        cove.destinatario || '',
                        cove.incoterm || '',
                        cove.vinculacion || ''
                    );
                }
            });
        }
    }

    if (Array.isArray(data.pedimentosGuardados) && data.pedimentosGuardados.length > 0) {
        pedimentosData = data.pedimentosGuardados;
        actualizarTablaPedimentos();
    }

    if (data.valorAduana) {
        if (data.valorAduana.total_precio_pagado && document.getElementById('totalPrecioPagado')) {
            document.getElementById('totalPrecioPagado').value = data.valorAduana.total_precio_pagado;
        }
        if (data.valorAduana.total_precio_por_pagar && document.getElementById('totalPrecioPorPagar')) {
            document.getElementById('totalPrecioPorPagar').value = data.valorAduana.total_precio_por_pagar;
        }
        if (data.valorAduana.total_incrementables && document.getElementById('totalIncrementables')) {
            document.getElementById('totalIncrementables').value = data.valorAduana.total_incrementables;
        }
        if (data.valorAduana.total_decrementables && document.getElementById('totalDecrementables')) {
            document.getElementById('totalDecrementables').value = data.valorAduana.total_decrementables;
        }
        if (data.valorAduana.total_valor_aduana && document.getElementById('totalValorAduana')) {
            document.getElementById('totalValorAduana').value = data.valorAduana.total_valor_aduana;
        }
    }

    if (Array.isArray(data.incrementables) && data.incrementables.length > 0) {
        incrementablesData = data.incrementables;
        actualizarTablaIncrementables();
    }

    if (Array.isArray(data.decrementables) && data.decrementables.length > 0) {
        decrementablesData = data.decrementables;
        actualizarTablaDecrementables();
    }

    if (Array.isArray(data.precioPagado) && data.precioPagado.length > 0) {
        precioPagadoData = data.precioPagado;
        actualizarTablaPrecioPagado();
    }

    if (Array.isArray(data.precioPorPagar) && data.precioPorPagar.length > 0) {
        precioPorPagarData = data.precioPorPagar;
        actualizarTablaPrecioPorPagar();
    }

    if (Array.isArray(data.compensoPago) && data.compensoPago.length > 0) {
        compensoPagoData = data.compensoPago;
        actualizarTablaCompensoPago();
    }

    // Cargar datos adicionales desde localStorage (pedimentos, incoterm, a cargo del importador)
    loadMveFromLocalStorage();

    if (Array.isArray(data.documentos) && data.documentos.length > 0) {
        data.documentos.forEach((documento) => {
            if (!documento) {
                return;
            }
            addEdocumentToTable(documento);
        });
    }
};

// ============================================
// MODAL DE NOTIFICACIONES
// ============================================
window.showNotification = function(message, type = 'info', title = '') {
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const titleElement = document.getElementById('notificationTitle');
    const messageElement = document.getElementById('notificationMessage');
    const closeBtn = document.getElementById('notificationCloseBtn');
    
    if (!modal || !icon || !titleElement || !messageElement || !closeBtn) {
        alert(message);
        return;
    }
    
    // Limpiar contenedor de iconos y crear uno nuevo
    icon.innerHTML = '<i data-lucide="info" class="w-6 h-6"></i>';
    const iconElement = icon.querySelector('i');
    
    // Configurar según el tipo
    if (type === 'success') {
        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-green-100';
        iconElement.setAttribute('data-lucide', 'check-circle');
        iconElement.className = 'w-6 h-6 text-green-600';
        titleElement.textContent = title || 'Éxito';
        titleElement.className = 'text-lg font-semibold text-green-900';
        closeBtn.className = 'px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors';
    } else if (type === 'error') {
        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-red-100';
        iconElement.setAttribute('data-lucide', 'alert-circle');
        iconElement.className = 'w-6 h-6 text-red-600';
        titleElement.textContent = title || 'Error';
        titleElement.className = 'text-lg font-semibold text-red-900';
        closeBtn.className = 'px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors';
    } else if (type === 'warning') {
        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-amber-100';
        iconElement.setAttribute('data-lucide', 'alert-triangle');
        iconElement.className = 'w-6 h-6 text-amber-600';
        titleElement.textContent = title || 'Advertencia';
        titleElement.className = 'text-lg font-semibold text-amber-900';
        closeBtn.className = 'px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors';
    } else {
        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-blue-100';
        iconElement.setAttribute('data-lucide', 'info');
        iconElement.className = 'w-6 h-6 text-blue-600';
        titleElement.textContent = title || 'Información';
        titleElement.className = 'text-lg font-semibold text-blue-900';
        closeBtn.className = 'px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors';
    }
    
    messageElement.textContent = message;
    modal.style.display = 'flex';
    
    // Recrear iconos de Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
};

window.closeNotificationModal = function() {
    const modal = document.getElementById('notificationModal');
    modal.style.display = 'none';
};

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    const avatarButton = document.getElementById('avatarButton');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (avatarButton && dropdownMenu) {
        avatarButton.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('active');
            }
        });
    }

    window.exchangeRateManager = new ExchangeRateManager({
        apiEndpoint: '/api/exchange-rate',
        debounceMs: 400,
        decimalPlaces: 4,
        loadingText: 'Consultando Banxico...',
        errorText: 'No disponible'
    });

    window.exchangeRateManager.initialize('body', {
        dateField: '[data-exchange-date]',
        currencyField: '[data-exchange-currency]',
        rateField: '[data-exchange-rate]',
        autoButton: '[data-exchange-auto]',
        statusIndicator: '[data-exchange-status]'
    });

    window.clearExchangeRateCache = function() {
        window.exchangeRateManager.destroy();
        window.exchangeRateManager = new ExchangeRateManager({
            apiEndpoint: '/api/exchange-rate',
            debounceMs: 400,
            decimalPlaces: 4,
            cacheExpirationHours: 24,
            loadingText: 'Consultando Banxico...',
            errorText: 'No disponible'
        });
        window.exchangeRateManager.initialize('body', {
            dateField: '[data-exchange-date]',
            currencyField: '[data-exchange-currency]',
            rateField: '[data-exchange-rate]',
            autoButton: '[data-exchange-auto]',
            statusIndicator: '[data-exchange-status]'
        });
        showNotification('Cache de tipos de cambio limpiado', 'success', 'Cache');
    };

    window.showExchangeRateCacheInfo = function() {
        const cacheSize = window.exchangeRateManager.rateCache.size;
        const storageInfo = localStorage.getItem('exchange_rate_cache');
        const storageSize = storageInfo ? JSON.parse(storageInfo) : {};

        console.log('=== INFORMACIÓN DE CACHE DE TIPOS DE CAMBIO ===');
        console.log(`Entradas en memoria: ${cacheSize}`);
        console.log('Entradas en localStorage:', Object.keys(storageSize).length);
        console.log('Datos en memoria:', Array.from(window.exchangeRateManager.rateCache.keys()));
        console.log('Para limpiar cache: clearExchangeRateCache()');

        showNotification(`Cache: ${cacheSize} en memoria, ${Object.keys(storageSize).length} en storage`, 'info', 'Info');
    };

    // Cargar datos guardados
    window.loadSavedDataCallback();
});

// ============================================
// MANEJO DE FOLIOS eDocument
// ============================================
const EDOCUMENT_STATUS = {
    PENDING: 'PENDING',
    VALID: 'VALID',
    INVALID: 'INVALID'
};

// Variable para almacenar el PDF validado pendiente de digitalización
let pdfValidado = null;

function normalizeEdocumentFolio(folio) {
    if (!folio) {
        return '';
    }
    return folio.replace(/\s+/g, '').toUpperCase().trim();
}

function validateEdocumentFormat(folio) {
    if (!folio) {
        return { valid: false, message: 'El folio eDocument es obligatorio.' };
    }

    const normalized = normalizeEdocumentFolio(folio);
    if (normalized.length < 8 || normalized.length > 30) {
        return { valid: false, message: 'El folio eDocument debe tener entre 8 y 30 caracteres.' };
    }

    if (!/^[A-Z0-9]+$/.test(normalized)) {
        return { valid: false, message: 'El folio eDocument solo puede contener caracteres alfanuméricos.' };
    }

    return { valid: true, message: 'Formato válido.' };
}

function getEdocumentStatusMeta(status) {
    switch (status) {
        case EDOCUMENT_STATUS.VALID:
            return { label: 'VALID', className: 'text-green-600' };
        case EDOCUMENT_STATUS.INVALID:
            return { label: 'INVALID', className: 'text-red-600' };
        default:
            return { label: 'PENDING', className: 'text-amber-600' };
    }
}

function updateEdocumentValidationStatus(status, message) {
    const statusElement = document.getElementById('edocumentValidationStatus');
    if (!statusElement) {
        return;
    }

    const meta = getEdocumentStatusMeta(status);
    statusElement.textContent = `Estado: ${meta.label}${message ? ` (${message})` : ''}`;
    statusElement.className = `text-xs ${meta.className}`;
}

async function validateEdocumentWithServer(folio) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const response = await fetch('/mve/validate-edocument', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ folio })
    });

    return response.json();
}

window.validateEdocumentInput = async function() {
    const folioInput = document.getElementById('edocumentFolio');
    const folio = normalizeEdocumentFolio(folioInput?.value || '');
    const validation = validateEdocumentFormat(folio);

    if (!validation.valid) {
        showNotification(validation.message, 'warning');
        updateEdocumentValidationStatus(EDOCUMENT_STATUS.INVALID, 'Formato inválido');
        return;
    }

    try {
        const result = await validateEdocumentWithServer(folio);

        if (!result.configured) {
            showNotification(result.message || 'Servicio no configurado', 'warning');
            updateEdocumentValidationStatus(EDOCUMENT_STATUS.PENDING, 'No configurado');
            return;
        }

        const status = result.valid ? EDOCUMENT_STATUS.VALID : EDOCUMENT_STATUS.INVALID;
        updateEdocumentValidationStatus(status, result.message);
        showNotification(result.message, result.valid ? 'success' : 'warning');
    } catch (error) {
        console.error('Error validando eDocument:', error);
        showNotification('Error al validar eDocument', 'error');
        updateEdocumentValidationStatus(EDOCUMENT_STATUS.INVALID, 'Error WS');
    }
};

window.addEdocument = function() {
    const documentTypeInput = document.getElementById('documentType');
    const folioInput = document.getElementById('edocumentFolio');

    const documentType = documentTypeInput?.value?.trim() || '';
    const folioRaw = folioInput?.value || '';
    const folio = normalizeEdocumentFolio(folioRaw);

    if (!documentType) {
        showNotification('Ingrese el tipo de documento.', 'warning');
        return;
    }

    if (!folio) {
        showNotification('Ingrese el folio del eDocument.', 'warning');
        return;
    }

    const existingFolios = Array.from(document.querySelectorAll('#edocumentsTableBody tr[data-edocument-row]'))
        .map(row => row.dataset.edocumentFolio);

    if (existingFolios.includes(folio)) {
        showNotification('Este folio eDocument ya fue agregado.', 'warning');
        return;
    }

    addEdocumentToTable({
        tipo_documento: documentType,
        nombre_documento: '',
        folio_edocument: folio,
        estado_vucem: EDOCUMENT_STATUS.VALID,
        created_at: new Date().toISOString()
    });

    documentTypeInput.value = '';
    folioInput.value = '';
    showNotification('Documento agregado correctamente', 'success');
};

function addEdocumentToTable(documentData) {
    const tbody = document.getElementById('edocumentsTableBody');
    if (!tbody) {
        return;
    }

    const emptyMessage = tbody.querySelector('.table-empty');
    if (emptyMessage) {
        emptyMessage.parentElement.remove();
    }

    const createdAt = documentData.created_at
        ? new Date(documentData.created_at).toLocaleString('es-MX')
        : new Date().toLocaleString('es-MX');

    // Obtener nombre legible del tipo de documento
    const tiposDocMap = JSON.parse(document.getElementById('mveManualData')?.getAttribute('data-tipos-documento') || '{}');
    const tipoNombre = tiposDocMap[documentData.tipo_documento] || documentData.tipo_documento || '';

    const row = document.createElement('tr');
    row.className = 'table-row';
    row.dataset.edocumentRow = 'true';
    row.dataset.edocumentFolio = documentData.folio_edocument || '';
    row.dataset.edocumentType = documentData.tipo_documento || '';
    row.dataset.edocumentName = documentData.nombre_documento || '';
    row.dataset.edocumentCreatedAt = documentData.created_at || '';

    row.innerHTML = `
        <td class="table-cell">${tipoNombre}</td>
        <td class="table-cell font-mono text-xs">${documentData.folio_edocument || ''}</td>
        <td class="table-cell">${documentData.nombre_documento || ''}</td>
        <td class="table-cell">${createdAt}</td>
        <td class="table-cell text-center">
            <div class="flex items-center justify-center gap-2">
                <button type="button" onclick="removeEdocumentRow(this)" class="btn-icon-danger" title="Eliminar">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </td>
    `;

    tbody.appendChild(row);
    setTimeout(() => lucide.createIcons(), 50);
}

window.validateEdocumentRow = async function(button) {
    const row = button.closest('tr');
    if (!row) {
        return;
    }

    const folio = row.dataset.edocumentFolio || '';
    const validation = validateEdocumentFormat(folio);

    if (!validation.valid) {
        showNotification(validation.message, 'warning');
        return;
    }

    try {
        const result = await validateEdocumentWithServer(folio);

        if (!result.configured) {
            showNotification(result.message || 'Servicio no configurado', 'warning');
            return;
        }

        const status = result.valid ? EDOCUMENT_STATUS.VALID : EDOCUMENT_STATUS.INVALID;
        row.dataset.edocumentStatus = status;
        const statusCell = row.querySelector('td:nth-child(3) span');
        const meta = getEdocumentStatusMeta(status);
        if (statusCell) {
            statusCell.textContent = meta.label;
            statusCell.className = `text-sm ${meta.className}`;
        }

        showNotification(result.message, result.valid ? 'success' : 'warning');
    } catch (error) {
        console.error('Error validando eDocument:', error);
        showNotification('Error al validar eDocument', 'error');
    }
};

window.removeEdocumentRow = function(button) {
    const row = button.closest('tr');
    if (!row) {
        return;
    }

    row.remove();

    const tbody = document.getElementById('edocumentsTableBody');
    if (tbody && tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">NO HAY DOCUMENTOS ASOCIADOS</p>
                </td>
            </tr>
        `;
        setTimeout(() => lucide.createIcons(), 50);
    }
};

// ============================================
// VALIDACIÓN Y DIGITALIZACIÓN DE PDF
// ============================================

// Listener para validar PDF al seleccionar archivo
document.addEventListener('DOMContentLoaded', function() {
    const pdfInput = document.getElementById('pdfFileInput');
    if (pdfInput) {
        pdfInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            const statusDiv = document.getElementById('pdfValidationStatus');
            pdfValidado = null; // Reset

            if (!file) {
                statusDiv.classList.add('hidden');
                return;
            }

            // Validar que es PDF
            if (!file.name.toLowerCase().endsWith('.pdf')) {
                statusDiv.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                    <span class="text-sm text-red-700">Solo se aceptan archivos PDF.</span>
                </div>`;
                statusDiv.classList.remove('hidden');
                lucide.createIcons();
                return;
            }

            // Validar tamaño (20MB max)
            if (file.size > 20 * 1024 * 1024) {
                statusDiv.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                    <span class="text-sm text-red-700">El archivo excede 20MB. Máximo permitido: 20MB.</span>
                </div>`;
                statusDiv.classList.remove('hidden');
                lucide.createIcons();
                return;
            }

            // Mostrar estado de carga
            statusDiv.innerHTML = `<div class="rounded-lg border border-blue-200 bg-blue-50 p-3 flex items-center gap-2">
                <span class="inline-block w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
                <span class="text-sm text-blue-700">Validando formato VUCEM...</span>
            </div>`;
            statusDiv.classList.remove('hidden');

            // Enviar a validar
            try {
                const formData = new FormData();
                formData.append('archivo', file);
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/mve/validar-pdf', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    pdfValidado = {
                        file_content: result.file_content,
                        original_name: result.original_name,
                        was_converted: result.was_converted,
                        final_size: result.final_size
                    };

                    const sizeKB = (result.final_size / 1024).toFixed(1);
                    const convertMsg = result.was_converted 
                        ? `<span class="text-amber-600 text-xs ml-2">(Convertido a formato VUCEM)</span>` 
                        : `<span class="text-green-600 text-xs ml-2">(Formato VUCEM nativo)</span>`;

                    statusDiv.innerHTML = `<div class="rounded-lg border border-green-200 bg-green-50 p-3 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        <span class="text-sm text-green-700">PDF válido para VUCEM (${sizeKB} KB) ${convertMsg}</span>
                    </div>`;
                } else {
                    statusDiv.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                        <span class="text-sm text-red-700">${result.message || 'Error al procesar PDF'}</span>
                    </div>`;
                }
            } catch (err) {
                statusDiv.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                    <span class="text-sm text-red-700">Error de conexión al validar PDF.</span>
                </div>`;
            }
            lucide.createIcons();
        });
    }
});

/**
 * Digitaliza el documento: firma y envía a VUCEM via AJAX.
 * Recibe el folio eDocument y lo agrega a la tabla.
 */
window.digitalizarDocumento = async function() {
    const nombreDoc = document.getElementById('documentName')?.value?.trim();
    const tipoDocSelect = document.getElementById('documentTypeSelect');
    const tipoDocId = tipoDocSelect?.value;
    const rfcConsulta = document.getElementById('rfcConsultaDigit')?.value?.trim() || '';

    // Validaciones
    if (!nombreDoc) {
        showNotification('Ingrese el nombre del documento.', 'warning');
        return;
    }
    if (!tipoDocId) {
        showNotification('Seleccione el tipo de documento.', 'warning');
        return;
    }
    if (!pdfValidado || !pdfValidado.file_content) {
        showNotification('Cargue y espere a que se valide el archivo PDF.', 'warning');
        return;
    }

    const applicantId = document.querySelector('[data-applicant-id]').getAttribute('data-applicant-id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const dataEl = document.getElementById('mveManualData');
    const hasStoredCreds = dataEl?.getAttribute('data-has-vucem-credentials') === 'true';
    const hasStoredWs = dataEl?.getAttribute('data-has-webservice-key') === 'true';

    // Construir FormData
    const formData = new FormData();
    formData.append('tipo_documento', tipoDocId);
    formData.append('nombre_documento', nombreDoc);
    formData.append('file_content', pdfValidado.file_content);
    formData.append('rfc_consulta', rfcConsulta);

    // Credenciales manuales (solo si no están almacenadas)
    if (!hasStoredWs) {
        const claveWS = document.getElementById('digitClaveWS')?.value;
        if (!claveWS) {
            showNotification('Ingrese la Contraseña del Web Service VUCEM.', 'warning');
            return;
        }
        formData.append('clave_webservice', claveWS);
    }

    if (!hasStoredCreds) {
        const certFile = document.getElementById('digitCertFile')?.files[0];
        const keyFile = document.getElementById('digitKeyFile')?.files[0];
        const keyPass = document.getElementById('digitKeyPassword')?.value;

        if (!certFile || !keyFile || !keyPass) {
            showNotification('Ingrese los archivos de firma electrónica (.cer, .key) y la contraseña.', 'warning');
            return;
        }
        formData.append('certificado', certFile);
        formData.append('llave_privada', keyFile);
        formData.append('contrasena_llave', keyPass);
    }

    // Deshabilitar botón y mostrar loading
    const btnDigit = document.getElementById('btnDigitalizar');
    const originalHTML = btnDigit.innerHTML;
    btnDigit.disabled = true;
    btnDigit.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Enviando a VUCEM...';

    try {
        const response = await fetch(`/mve/digitalizar-documento/${applicantId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Agregar a la tabla
            const tipoNombre = tipoDocSelect.options[tipoDocSelect.selectedIndex]?.text || tipoDocId;
            addEdocumentToTable({
                tipo_documento: tipoDocId,
                nombre_documento: nombreDoc,
                folio_edocument: result.eDocument,
                created_at: new Date().toISOString()
            });

            showNotification(`¡Éxito! Folio eDocument: ${result.eDocument}`, 'success');

            // Limpiar campos
            document.getElementById('documentName').value = '';
            document.getElementById('documentTypeSelect').value = '';
            document.getElementById('pdfFileInput').value = '';
            document.getElementById('rfcConsultaDigit').value = '';
            document.getElementById('pdfValidationStatus').classList.add('hidden');
            pdfValidado = null;

            // Auto-guardar documentos
            await saveDocumentos();
        } else {
            showNotification(result.message || 'Error al digitalizar documento.', 'error');
        }
    } catch (error) {
        console.error('[DIGITALIZAR] Error:', error);
        showNotification('Error de conexión al enviar a VUCEM.', 'error');
    } finally {
        btnDigit.disabled = false;
        btnDigit.innerHTML = originalHTML;
        lucide.createIcons();
    }
};

// ============================================
// RFC DE CONSULTA
// ============================================
window.searchRfcConsulta = async function() {
    const rfcInput = document.getElementById('rfcConsultaInput');
    const rfcValue = rfcInput.value.trim().toUpperCase();
    const applicantRfc = document.querySelector('[data-applicant-rfc]').getAttribute('data-applicant-rfc');

    if (!rfcValue) {
        showNotification('Por favor ingrese un RFC para buscar', 'warning');
        return;
    }

    if (rfcValue.length < 12 || rfcValue.length > 13) {
        showNotification('El RFC debe tener entre 12 y 13 caracteres', 'warning');
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const searchUrl = document.querySelector('[data-search-url]').getAttribute('data-search-url');
        
        const response = await fetch(searchUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                applicant_rfc: applicantRfc,
                rfc_consulta: rfcValue
            })
        });

        const data = await response.json();

        if (data.found) {
            document.getElementById('razonSocialConsulta').value = data.data.razon_social;
            document.getElementById('tipoFiguraConsulta').value = data.data.tipo_figura;
            currentSearchedRfc = data.data;
            showRfcFoundModal();
        } else {
            showRfcNotFoundModal();
        }
    } catch (error) {
        showNotification('Error al buscar el RFC. Por favor intente nuevamente.', 'error');
    }
};

window.addRfcConsulta = async function() {
    const rfcValue = document.getElementById('rfcConsultaInput').value.trim().toUpperCase();
    const razonSocial = document.getElementById('razonSocialConsulta').value.trim().toUpperCase();
    const tipoFiguraSelect = document.getElementById('tipoFiguraConsulta');
    const tipoFigura = tipoFiguraSelect.value;
    const tipoFiguraTexto = tipoFiguraSelect.options[tipoFiguraSelect.selectedIndex].text;

    if (!rfcValue || !razonSocial || !tipoFigura) {
        showNotification('Por favor complete todos los campos', 'warning');
        return;
    }

    if (rfcValue.length < 12 || rfcValue.length > 13) {
        showNotification('El RFC debe tener entre 12 y 13 caracteres', 'warning');
        return;
    }

    await saveAndAddRfcConsulta(rfcValue, razonSocial, tipoFigura, tipoFiguraTexto);
};

window.showRfcNotFoundModal = function() {
    document.getElementById('rfcNotFoundModal').classList.remove('hidden');
};

window.closeRfcNotFoundModal = function() {
    document.getElementById('rfcNotFoundModal').classList.add('hidden');
};

window.showRfcFoundModal = function() {
    document.getElementById('rfcFoundModal').classList.remove('hidden');
};

window.closeRfcFoundModal = function() {
    document.getElementById('rfcFoundModal').classList.add('hidden');
};

window.confirmAddRfcConsulta = async function() {
    const rfcValue = document.getElementById('rfcConsultaInput').value.trim().toUpperCase();
    const razonSocial = document.getElementById('razonSocialConsulta').value.trim().toUpperCase();
    const tipoFiguraSelect = document.getElementById('tipoFiguraConsulta');
    const tipoFigura = tipoFiguraSelect.value;
    const tipoFiguraTexto = tipoFiguraSelect.options[tipoFiguraSelect.selectedIndex].text;

    if (!rfcValue || !razonSocial || !tipoFigura) {
        showNotification('Por favor complete todos los campos', 'warning');
        return;
    }

    closeRfcFoundModal();

    if (currentSearchedRfc) {
        addRfcToTable(rfcValue, razonSocial, tipoFiguraTexto, tipoFigura);
        clearRfcConsultaFields();
        currentSearchedRfc = null;
        return;
    }

    await saveAndAddRfcConsulta(rfcValue, razonSocial, tipoFigura, tipoFiguraTexto);
};

async function saveAndAddRfcConsulta(rfc, razonSocial, tipoFigura, tipoFiguraTexto) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const storeUrl = document.querySelector('[data-store-url]').getAttribute('data-store-url');
        const applicantRfc = document.querySelector('[data-applicant-rfc]').getAttribute('data-applicant-rfc');
        
        const response = await fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                applicant_rfc: applicantRfc,
                rfc_consulta: rfc,
                razon_social: razonSocial,
                tipo_figura: tipoFigura
            })
        });

        const data = await response.json();

        if (data.success || (data.message && data.message.includes('ya está registrado'))) {
            addRfcToTable(rfc, razonSocial, tipoFiguraTexto, tipoFigura);
            clearRfcConsultaFields();
        } else if (data.errors) {
            let errorMessages = [];
            for (let field in data.errors) {
                errorMessages.push(data.errors[field].join(', '));
            }
            showNotification('Errores de validación:\n' + errorMessages.join('\n'), 'error');
        } else {
            showNotification(data.message || 'Error al guardar el RFC', 'error');
        }
    } catch (error) {
        showNotification('Error al guardar el RFC. Por favor intente nuevamente.', 'error');
    }
}

window.addRfcToTable = function(rfc, razonSocial, tipoFigura, tipoFiguraClave = null) {
    const tbody = document.getElementById('rfcConsultaTableBody');
    
    if (!tbody) return;
    
    const existingRow = tbody.querySelector(`tr[data-rfc="${rfc}"]`);
    if (existingRow) return;
    
    const emptyRow = tbody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    // Si no se proporciona la clave, intentar obtenerla del texto o usar el valor directamente
    const clave = tipoFiguraClave || tipoFigura;

    const newRow = document.createElement('tr');
    newRow.setAttribute('data-rfc', rfc);
    newRow.setAttribute('data-razon-social', razonSocial);
    newRow.setAttribute('data-tipo-figura', clave);
    newRow.innerHTML = `
        <td class="table-checkbox">
            <input type="checkbox" class="table-checkbox-input rfc-checkbox" onchange="toggleDeleteButton()">
        </td>
        <td class="font-semibold text-[#003399]">${rfc}</td>
        <td>${razonSocial}</td>
        <td>${tipoFigura}</td>
    `;
    
    tbody.appendChild(newRow);
    setTimeout(() => lucide.createIcons(), 50);
};

window.toggleAllRfcConsulta = function(checkbox) {
    const checkboxes = document.querySelectorAll('.rfc-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    toggleDeleteButton();
};

window.toggleDeleteButton = function() {
    const checkedBoxes = document.querySelectorAll('.rfc-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeleteRfcConsulta');
    
    if (checkedBoxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }
};

window.deleteSelectedRfcConsulta = async function() {
    const checkedBoxes = document.querySelectorAll('.rfc-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        showNotification('Por favor seleccione al menos un RFC para eliminar', 'warning');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkedBoxes.length} RFC(s) de consulta?`,
        'ELIMINAR RFCS'
    );
    
    if (!confirmResult) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const deleteUrl = document.querySelector('[data-delete-url]').getAttribute('data-delete-url');
    const applicantRfc = document.querySelector('[data-applicant-rfc]').getAttribute('data-applicant-rfc');

    const deletePromises = [];
    const rowsToDelete = [];

    checkedBoxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const rfc = row.getAttribute('data-rfc');
        rowsToDelete.push(row);

        const deletePromise = fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                applicant_rfc: applicantRfc,
                rfc_consulta: rfc
            })
        });

        deletePromises.push(deletePromise);
    });

    try {
        await Promise.all(deletePromises);
        
        rowsToDelete.forEach(row => row.remove());
        
        const tbody = document.getElementById('rfcConsultaTableBody');
        if (tbody.children.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="4" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay RFC's agregados</p>
                </td>
            `;
            tbody.appendChild(emptyRow);
            setTimeout(() => lucide.createIcons(), 50);
        }

        document.getElementById('selectAllRfcConsulta').checked = false;
        toggleDeleteButton();

        showNotification('RFC(s) eliminado(s) exitosamente', 'success');
    } catch (error) {
        showNotification('Error al eliminar los RFC(s). Por favor intente nuevamente.', 'error');
    }
};

window.clearRfcConsultaFields = function() {
    document.getElementById('rfcConsultaInput').value = '';
    document.getElementById('razonSocialConsulta').value = '';
    document.getElementById('tipoFiguraConsulta').value = '';
    currentSearchedRfc = null;
};

// ============================================
// INFORMACIÓN COVE
// ============================================

/**
 * Convertir fecha de formato DDMMYYYY a YYYY-MM-DD (formato para input date)
 */
function convertDateToInputFormat(dateStr) {
    if (!dateStr) return '';
    
    // Si ya está en formato YYYY-MM-DD, devolverla tal cual
    if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateStr;
    }
    
    // Si está en formato DDMMYYYY (8 caracteres)
    if (dateStr.length === 8 && /^\d{8}$/.test(dateStr)) {
        const day = dateStr.substring(0, 2);
        const month = dateStr.substring(2, 4);
        const year = dateStr.substring(4, 8);
        return `${year}-${month}-${day}`;
    }
    
    // Si está en formato DD/MM/YYYY o DD-MM-YYYY
    const match = dateStr.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    
    console.warn('Formato de fecha no reconocido:', dateStr);
    return dateStr;
}

/**
 * Cargar datos del COVE en los campos del formulario (no en la tabla)
 * El usuario debe darle "AGREGAR" para añadirlo a la tabla
 */
window.loadCoveToFields = function(cove, metodoValor, factura, fechaExpedicion, emisorOriginal, destinatario) {
    const coveInput = document.getElementById('coveInput');
    const metodoSelect = document.getElementById('metodoValoracionCove');
    const facturaInput = document.getElementById('facturaInput');
    const fechaInput = document.getElementById('fechaExpedicionInput');
    const emisorInput = document.getElementById('emisorOriginalInput');
    const destinatarioInput = document.getElementById('destinatarioInput');
    
    // Convertir fecha al formato que espera el input date (YYYY-MM-DD)
    const fechaFormateada = convertDateToInputFormat(fechaExpedicion);
    
    if (coveInput) coveInput.value = cove || '';
    if (metodoSelect) metodoSelect.value = metodoValor || '';
    if (facturaInput) facturaInput.value = factura || '';
    if (fechaInput) fechaInput.value = fechaFormateada || '';
    if (emisorInput) emisorInput.value = emisorOriginal || '';
    if (destinatarioInput) destinatarioInput.value = destinatario || '';
    
    console.log('Datos COVE cargados en campos del formulario:', { cove, metodoValor, factura, fechaExpedicion, fechaFormateada, emisorOriginal, destinatario });
};

window.addCoveToTableFromData = function(cove, metodoValor, factura, fechaExpedicion, emisorOriginal, destinatario, incoterm = '', vinculacion = '') {
    const tableBody = document.getElementById('informacionCoveTableBody');
    if (!tableBody) return;

    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const metodoTexto = getOptionText('metodoValoracionCove', metodoValor);

    const row = document.createElement('tr');
    row.setAttribute('data-cove', cove);
    row.setAttribute('data-metodo', metodoValor);
    row.setAttribute('data-factura', factura);
    row.setAttribute('data-fecha', fechaExpedicion);
    row.setAttribute('data-emisor', emisorOriginal);
    row.setAttribute('data-destinatario', destinatario);
    row.setAttribute('data-incoterm', incoterm);
    row.setAttribute('data-vinculacion', vinculacion);
    row.className = 'hover:bg-slate-50 transition-colors';
    
    row.innerHTML = `
        <td class="table-checkbox">
            <input type="checkbox" class="table-checkbox-input cove-checkbox" onchange="updateDeleteCoveButton()">
        </td>
        <td class="font-mono text-sm text-purple-600 font-semibold">${cove}</td>
        <td class="text-sm text-slate-700">${metodoTexto}</td>
        <td class="text-sm text-slate-700">${factura}</td>
        <td class="text-sm text-slate-700">${fechaExpedicion}</td>
        <td class="text-sm text-slate-700">${emisorOriginal}</td>
        <td class="text-sm text-slate-700">${destinatario}</td>
    `;

    tableBody.appendChild(row);
    lucide.createIcons();
};

window.addCoveToTable = function() {
    const cove = document.getElementById('coveInput').value.trim().toUpperCase();
    const metodoSelect = document.getElementById('metodoValoracionCove');
    const metodoValor = metodoSelect.value;
    const metodoTexto = metodoSelect.options[metodoSelect.selectedIndex].text;
    const factura = document.getElementById('facturaInput').value.trim();
    const fechaExpedicion = document.getElementById('fechaExpedicionInput').value;
    const emisorOriginal = document.getElementById('emisorOriginalInput').value.trim().toUpperCase();
    const destinatario = document.getElementById('destinatarioInput').value.trim().toUpperCase();
    const incotermSelect = document.getElementById('incotermCove');
    const incotermValor = incotermSelect ? incotermSelect.value : '';

    // Validar COVE
    const coveValidation = validateCove(cove);
    if (!coveValidation.valid) {
        showNotification(coveValidation.message, 'error', 'Validación');
        return;
    }

    // Validar método de valoración (requerido, max:20 según Request)
    if (!metodoValor) {
        showNotification('El Método de Valoración es obligatorio', 'error', 'Campo Requerido');
        return;
    }
    
    if (metodoValor.length > 20) {
        showNotification('El Método de Valoración no debe exceder los 20 caracteres', 'error', 'Validación');
        return;
    }

    // Validar otros campos obligatorios
    if (!factura || !fechaExpedicion || !emisorOriginal || !destinatario) {
        showNotification('Por favor complete todos los campos obligatorios', 'warning');
        return;
    }

    const tableBody = document.getElementById('informacionCoveTableBody');
    if (!tableBody) return;

    const existingRows = tableBody.querySelectorAll('tr[data-cove]');
    for (let row of existingRows) {
        if (row.getAttribute('data-cove') === cove) {
            showNotification('Este COVE ya ha sido agregado', 'warning');
            return;
        }
    }

    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const row = document.createElement('tr');
    row.setAttribute('data-cove', cove);
    row.setAttribute('data-metodo', metodoValor);
    row.setAttribute('data-factura', factura);
    row.setAttribute('data-fecha', fechaExpedicion);
    row.setAttribute('data-emisor', emisorOriginal);
    row.setAttribute('data-destinatario', destinatario);
    row.setAttribute('data-incoterm', incotermValor);
    row.setAttribute('data-vinculacion', '');
    row.className = 'hover:bg-slate-50 transition-colors';
    
    row.innerHTML = `
        <td class="table-checkbox">
            <input type="checkbox" class="table-checkbox-input cove-checkbox" onchange="updateDeleteCoveButton()">
        </td>
        <td class="font-mono text-sm text-purple-600 font-semibold">${cove}</td>
        <td class="text-sm text-slate-700">${metodoTexto}</td>
        <td class="text-sm text-slate-700">${factura}</td>
        <td class="text-sm text-slate-700">${fechaExpedicion}</td>
        <td class="text-sm text-slate-700">${emisorOriginal}</td>
        <td class="text-sm text-slate-700">${destinatario}</td>
    `;

    tableBody.appendChild(row);
    clearCoveFields();
    lucide.createIcons();
};

window.clearCoveFields = function() {
    document.getElementById('coveInput').value = '';
    document.getElementById('metodoValoracionCove').value = '';
    document.getElementById('facturaInput').value = '';
    document.getElementById('fechaExpedicionInput').value = '';
    document.getElementById('emisorOriginalInput').value = '';
    document.getElementById('destinatarioInput').value = '';
};

window.toggleAllCove = function(checkbox) {
    const coveCheckboxes = document.querySelectorAll('.cove-checkbox');
    coveCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateDeleteCoveButton();
};

window.updateDeleteCoveButton = function() {
    const checkedBoxes = document.querySelectorAll('.cove-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeleteCove');
    const manifestacionBtn = document.getElementById('btnAddManifestacion');
    
    if (checkedBoxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    if (checkedBoxes.length === 1) {
        manifestacionBtn.classList.remove('hidden');
    } else {
        manifestacionBtn.classList.add('hidden');
    }
};

window.deleteSelectedCove = async function() {
    const checkedBoxes = document.querySelectorAll('.cove-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        showNotification('Por favor seleccione al menos un COVE para eliminar', 'warning');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkedBoxes.length} COVE(s)?`,
        'ELIMINAR COVES'
    );
    
    if (!confirmResult) {
        return;
    }

    checkedBoxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        row.remove();
    });

    const tableBody = document.getElementById('informacionCoveTableBody');
    if (tableBody.querySelectorAll('tr').length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay COVE agregados</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
    }

    document.getElementById('btnDeleteCove').classList.add('hidden');
    
    const selectAllCheckbox = document.getElementById('selectAllCove');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
};

// ============================================
// MODIFICACIÓN DE COVE
// ============================================
window.openManifestacionModal = function() {
    const checkedBox = document.querySelector('.cove-checkbox:checked');
    if (!checkedBox) {
        showNotification('Por favor seleccione un COVE', 'warning');
        return;
    }

    selectedCoveRow = checkedBox.closest('tr');
    const cove = selectedCoveRow.getAttribute('data-cove');
    const metodo = selectedCoveRow.getAttribute('data-metodo');
    let incoterm = selectedCoveRow.getAttribute('data-incoterm') || '';
    const vinculacion = selectedCoveRow.getAttribute('data-vinculacion') || '';

    document.getElementById('modalCoveDisplay').value = cove;
    document.getElementById('modalMetodoValoracion').value = metodo;
    
    // Si no hay incoterm en el COVE, intentar cargar desde localStorage
    if (!incoterm) {
        const savedData = getMveFromLocalStorage();
        if (savedData && savedData.incoterm) {
            incoterm = savedData.incoterm;
        }
    }
    
    // Asegurar que el select de incoterm se establezca correctamente
    const incotermSelect = document.getElementById('modalIncoterm');
    if (incotermSelect && incoterm) {
        incotermSelect.value = incoterm;
        // Si no se selecciona, intentar seleccionar con un pequeño retraso
        setTimeout(() => {
            if (incotermSelect.value !== incoterm) {
                incotermSelect.value = incoterm;
            }
        }, 50);
    }
    
    // Si no hay vinculación en el COVE, intentar cargar desde localStorage
    let vinculacionToSet = vinculacion;
    if (vinculacionToSet === '') {
        const savedData = getMveFromLocalStorage();
        if (savedData && savedData.vinculacion !== undefined && savedData.vinculacion !== '') {
            vinculacionToSet = savedData.vinculacion;
        }
    }
    
    if (vinculacionToSet !== '') {
        const radioButton = document.querySelector(`input[name="modalExisteVinculacion"][value="${vinculacionToSet}"]`);
        if (radioButton) {
            radioButton.checked = true;
        }
    } else {
        document.querySelectorAll('input[name="modalExisteVinculacion"]').forEach(radio => {
            radio.checked = false;
        });
    }

    // Pre-llenar pedimento desde datos del Archivo M o localStorage si están disponibles y no hay pedimentos agregados
    const mveData = getMveManualData();
    const savedLocalData = getMveFromLocalStorage();
    
    if (pedimentosData.length === 0) {
        const pedimentoInput = document.getElementById('numeroPedimento');
        const patenteInput = document.getElementById('patentePedimento');
        const aduanaSelect = document.getElementById('aduanaPedimento');
        
        // Primero intentar cargar desde localStorage (tiene prioridad porque es más reciente)
        if (savedLocalData && savedLocalData.pedimentoInfo) {
            if (pedimentoInput && savedLocalData.pedimentoInfo.numero) {
                pedimentoInput.value = formatPedimentoDisplay(savedLocalData.pedimentoInfo.numero);
            }
            if (patenteInput && savedLocalData.pedimentoInfo.patente) {
                patenteInput.value = savedLocalData.pedimentoInfo.patente;
            }
            if (aduanaSelect && savedLocalData.pedimentoInfo.aduana) {
                const aduanaValue = savedLocalData.pedimentoInfo.aduana;
                const options = aduanaSelect.options;
                for (let i = 0; i < options.length; i++) {
                    const optionValue = options[i].value;
                    if (optionValue.replace(/[-\s]/g, '') === aduanaValue.replace(/[-\s]/g, '')) {
                        aduanaSelect.value = optionValue;
                        break;
                    }
                }
            }
        }
        // Si no hay en localStorage, intentar desde datos del servidor (Archivo M)
        else if (mveData && mveData.pedimento) {
            if (pedimentoInput && mveData.pedimento) {
                pedimentoInput.value = formatPedimentoDisplay(mveData.pedimento);
            }
            if (patenteInput && mveData.patente) {
                patenteInput.value = mveData.patente;
            }
            if (aduanaSelect && mveData.aduana) {
                const aduanaValue = mveData.aduana;
                const options = aduanaSelect.options;
                for (let i = 0; i < options.length; i++) {
                    const optionValue = options[i].value;
                    if (optionValue.replace(/[-\s]/g, '') === aduanaValue.replace(/[-\s]/g, '')) {
                        aduanaSelect.value = optionValue;
                        break;
                    }
                }
            }
            
            // Guardar en localStorage para futuras cargas
            savePedimentoInfoToLocalStorage(mveData.pedimento, mveData.patente, mveData.aduana);
        }
    }

    const modificacionForm = document.getElementById('modificacionCoveForm');
    modificacionForm.classList.remove('hidden');
    
    setTimeout(() => {
        modificacionForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);

    lucide.createIcons();
};

window.ocultarModificacionForm = function() {
    const modificacionForm = document.getElementById('modificacionCoveForm');
    modificacionForm.classList.add('hidden');
    selectedCoveRow = null;
};

window.guardarModificacionesCove = function() {
    const metodoSelect = document.getElementById('modalMetodoValoracion');
    const metodoValor = metodoSelect.value;
    const metodoTexto = metodoSelect.options[metodoSelect.selectedIndex].text;
    
    const incotermSelect = document.getElementById('modalIncoterm');
    const incotermValor = incotermSelect.value;
    
    const vinculacionRadio = document.querySelector('input[name="modalExisteVinculacion"]:checked');
    const vinculacion = vinculacionRadio ? vinculacionRadio.value : '';

    if (!metodoValor) {
        showNotification('Por favor seleccione un método de valoración', 'warning');
        return;
    }

    if (!incotermValor) {
        showNotification('Por favor seleccione un INCOTERM', 'warning');
        return;
    }

    if (vinculacion === '') {
        showNotification('Por favor indique si existe vinculación', 'warning');
        return;
    }

    if (!selectedCoveRow) {
        showNotification('No hay un COVE seleccionado', 'error');
        return;
    }

    selectedCoveRow.setAttribute('data-metodo', metodoValor);
    selectedCoveRow.setAttribute('data-incoterm', incotermValor);
    selectedCoveRow.setAttribute('data-vinculacion', vinculacion);
    
    // Guardar incoterm y vinculación en localStorage
    saveIncotermToLocalStorage(incotermValor);
    saveVinculacionToLocalStorage(vinculacion);

    const cells = selectedCoveRow.querySelectorAll('td');
    if (cells[2]) {
        cells[2].textContent = metodoTexto;
    }

    ocultarModificacionForm();
    
    if (selectedCoveRow) {
        const checkbox = selectedCoveRow.querySelector('.cove-checkbox');
        if (checkbox) {
            checkbox.checked = false;
        }
    }
    updateDeleteCoveButton();

    showNotification('Modificaciones aplicadas correctamente. Recuerde guardar la sección.', 'success');
};

// ============================================
// FUNCIONES DE VALIDACIÓN Y FORMATEO
// ============================================

// Formatear pedimento: mostrar con espacios, guardar sin espacios
function formatPedimentoDisplay(value) {
    // Remover espacios y caracteres no numéricos
    const numbers = value.replace(/\D/g, '');
    
    // Formatear como: XX XXX XXXX XXXXXXX (máximo 20 caracteres según validación)
    if (numbers.length <= 2) return numbers;
    if (numbers.length <= 5) return numbers.slice(0, 2) + ' ' + numbers.slice(2);
    if (numbers.length <= 9) return numbers.slice(0, 2) + ' ' + numbers.slice(2, 5) + ' ' + numbers.slice(5);
    if (numbers.length <= 16) return numbers.slice(0, 2) + ' ' + numbers.slice(2, 5) + ' ' + numbers.slice(5, 9) + ' ' + numbers.slice(9);
    
    // Truncar si excede 20 caracteres
    return numbers.slice(0, 2) + ' ' + numbers.slice(2, 5) + ' ' + numbers.slice(5, 9) + ' ' + numbers.slice(9, 16);
}

// Obtener pedimento sin espacios para guardar
function getPedimentoForStorage(value) {
    const numbers = value.replace(/\D/g, '');
    // Validar máximo 20 caracteres según StoreManifestacionValorRequest
    return numbers.slice(0, 20);
}

// Procesar clave de aduana: remover guión (48-0 -> 480)
function processAduanaClave(aduanaValue) {
    // Remover guión y mantener solo números
    return aduanaValue.replace(/-/g, '');
}

// Validar formato de RFC
window.validateRfcInput = function(input) {
    let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    
    // Validar longitud según StoreManifestacionValorRequest (min:12, max:13)
    if (value.length > 13) {
        value = value.slice(0, 13);
    }
    
    input.value = value;
    
    // Validar formato básico de RFC
    if (value.length >= 12) {
        const rfcPattern = /^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/;
        if (!rfcPattern.test(value)) {
            input.setCustomValidity('Formato de RFC inválido');
        } else {
            input.setCustomValidity('');
        }
    } else if (value.length > 0) {
        input.setCustomValidity('El RFC debe tener entre 12 y 13 caracteres');
    } else {
        input.setCustomValidity('');
    }
};

// Validar campos monetarios según StoreManifestacionValorRequest (between:0,999999999999999.999)
window.validateMonetaryInput = function(input) {
    const value = parseFloat(input.value);
    const maxValue = 999999999999999.999;
    
    if (isNaN(value)) {
        input.setCustomValidity('El valor debe ser numérico');
        return;
    }
    
    if (value < 0) {
        input.setCustomValidity('El valor no puede ser negativo');
        return;
    }
    
    if (value > maxValue) {
        input.setCustomValidity(`El valor no debe exceder ${maxValue.toLocaleString()}`);
        return;
    }
    
    // Validar hasta 3 decimales según las reglas
    const decimalParts = input.value.split('.');
    if (decimalParts.length > 1 && decimalParts[1].length > 3) {
        input.value = parseFloat(input.value).toFixed(3);
    }
    
    input.setCustomValidity('');
};

// Validar COVE en tiempo real
window.validateCoveInput = function(input) {
    const value = input.value.trim();
    
    if (value.length > 20) {
        input.value = value.slice(0, 20);
    }
    
    if (value.length === 0) {
        input.setCustomValidity('El número de COVE es obligatorio para identificar la operación');
    } else {
        input.setCustomValidity('');
    }
};

// Validar COVE según reglas del Request (max:20)
function validateCove(value) {
    if (!value || value.trim().length === 0) {
        return { valid: false, message: 'El número de COVE es obligatorio para identificar la operación' };
    }
    
    if (value.length > 20) {
        return { valid: false, message: 'El COVE no debe exceder los 20 caracteres' };
    }
    
    return { valid: true };
}

// Formatear pedimento en tiempo real
window.formatPedimentoInput = function(input) {
    const cursorPosition = input.selectionStart;
    const oldValue = input.value;
    const newValue = formatPedimentoDisplay(oldValue);
    
    if (oldValue !== newValue) {
        input.value = newValue;
        
        // Ajustar posición del cursor
        let newCursorPosition = cursorPosition;
        if (newValue.length > oldValue.length) {
            newCursorPosition++;
        }
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }
};

// Validar formato de pedimento
function validatePedimento(value) {
    const numbers = value.replace(/\D/g, '');
    
    if (numbers.length === 0) {
        return { valid: false, message: 'El número de pedimento es obligatorio' };
    }
    
    if (numbers.length > 20) {
        return { valid: false, message: 'El número de pedimento no debe exceder los 20 caracteres' };
    }
    
    if (numbers.length < 10) {
        return { valid: false, message: 'El número de pedimento debe tener al menos 10 dígitos' };
    }
    
    return { valid: true };
}

// ============================================
// PEDIMENTOS
// ============================================
window.agregarPedimento = function() {
    const pedimentoInput = document.getElementById('numeroPedimento');
    const numeroPedimentoDisplay = pedimentoInput.value.trim();
    const numeroPedimento = getPedimentoForStorage(numeroPedimentoDisplay);
    const patente = document.getElementById('patentePedimento').value.trim();
    const aduanaSelect = document.getElementById('aduanaPedimento');
    const aduana = aduanaSelect.value;
    const aduanaText = aduanaSelect.selectedIndex > 0 ? aduanaSelect.options[aduanaSelect.selectedIndex].text : '';
    
    // Validar pedimento
    const pedimentoValidation = validatePedimento(numeroPedimentoDisplay);
    if (!pedimentoValidation.valid) {
        showNotification(pedimentoValidation.message, 'error', 'Campo Requerido');
        return;
    }

    // Validar patente
    if (!patente) {
        showNotification('Por favor ingrese la patente', 'error', 'Campo Requerido');
        return;
    }
    
    if (patente.length > 20) {
        showNotification('La patente no debe exceder los 20 caracteres', 'error', 'Validación');
        return;
    }

    // Validar aduana
    if (!aduana) {
        showNotification('Por favor seleccione una aduana', 'error', 'Campo Requerido');
        return;
    }
    
    // Procesar clave de aduana
    const aduanaClave = processAduanaClave(aduana);
    if (aduanaClave.length > 20) {
        showNotification('La clave de aduana no debe exceder los 20 caracteres', 'error', 'Validación');
        return;
    }

    // Verificar duplicado
    if (pedimentosData.some(p => p.numero === numeroPedimento)) {
        showNotification('Este número de pedimento ya fue agregado', 'warning', 'Pedimento Duplicado');
        return;
    }

    const pedimento = {
        numero: numeroPedimento, // Sin espacios para guardar
        numeroDisplay: numeroPedimentoDisplay, // Con espacios para mostrar
        patente: patente,
        aduana: aduanaClave, // Clave procesada sin guión
        aduanaText: aduanaText // Texto completo para mostrar
    };
    
    pedimentosData.push(pedimento);

    actualizarTablaPedimentos();
    
    // Guardar pedimentos en localStorage
    savePedimentosToLocalStorage();

    document.getElementById('numeroPedimento').value = '';
    document.getElementById('patentePedimento').value = '';
    document.getElementById('aduanaPedimento').value = '';

    showNotification('Pedimento agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaPedimentos = function() {
    const tbody = document.getElementById('pedimentosTableBody');
    
    if (pedimentosData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay pedimentos agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeletePedimentos');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = pedimentosData.map((pedimento, index) => `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="pedimento-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonPedimentos()">
            </td>
            <td class="table-cell">${pedimento.numeroDisplay || pedimento.numero}</td>
            <td class="table-cell">${pedimento.patente}</td>
            <td class="table-cell">${pedimento.aduanaText}</td>
        </tr>
    `).join('');
    
    lucide.createIcons();
};

window.toggleAllPedimentos = function(checkbox) {
    const checkboxes = document.querySelectorAll('.pedimento-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonPedimentos();
};

window.toggleDeleteButtonPedimentos = function() {
    const checkboxes = document.querySelectorAll('.pedimento-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeletePedimentos');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllPedimentos');
    const allCheckboxes = document.querySelectorAll('.pedimento-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
};

window.eliminarPedimentosSeleccionados = async function() {
    const checkboxes = document.querySelectorAll('.pedimento-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un pedimento para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} pedimento(s)?`,
        'ELIMINAR PEDIMENTOS'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        pedimentosData.splice(index, 1);
    });

    actualizarTablaPedimentos();
    
    // Actualizar localStorage después de eliminar
    savePedimentosToLocalStorage();
    
    document.getElementById('selectAllPedimentos').checked = false;
    showNotification('Pedimento(s) eliminado(s) correctamente', 'success', 'Éxito');
};

// ============================================
// GUARDAR POR SECCIÓN
// ============================================
// Validar tipo de cambio según StoreManifestacionValorRequest (between:0,9999999999999.9999)
window.validateExchangeRateInput = function(input) {
    const value = parseFloat(input.value);
    const maxValue = 9999999999999.9999;
    
    if (isNaN(value)) {
        input.setCustomValidity('El tipo de cambio debe ser numérico');
        return;
    }
    
    if (value < 0) {
        input.setCustomValidity('El tipo de cambio no puede ser negativo');
        return;
    }
    
    if (value > maxValue) {
        input.setCustomValidity(`El tipo de cambio no debe exceder ${maxValue.toLocaleString()}`);
        return;
    }
    
    // Validar hasta 3 decimales
    const decimalParts = input.value.split('.');
    if (decimalParts.length > 1 && decimalParts[1].length > 3) {
        input.value = parseFloat(input.value).toFixed(3);
    }
    
    input.setCustomValidity('');
};

// ============================================
// INCREMENTABLES
// ============================================
window.addIncrementableToTable = function() {
    const incrementableSelect = document.getElementById('incrementableSelect');
    const incrementable = incrementableSelect.value;
    const incrementableText = incrementableSelect.options[incrementableSelect.selectedIndex].text;
    const fechaErogacion = document.getElementById('fechaErogacionInput').value;
    const importe = document.getElementById('importeIncrementableInput').value;
    const tipoMonedaSelect = document.getElementById('tipoMonedaIncrementableSelect');
    const tipoMoneda = tipoMonedaSelect.value;
    const tipoMonedaText = tipoMonedaSelect.options[tipoMonedaSelect.selectedIndex].text;
    const tipoCambio = document.getElementById('tipoCambioIncrementableInput').value;
    const aCargoImportadorRadio = document.querySelector('input[name="aCargoImportador"]:checked');
    const aCargoImportador = aCargoImportadorRadio ? aCargoImportadorRadio.value : '';

    // Validaciones
    if (!incrementable) {
        showNotification('Por favor seleccione un incrementable', 'error', 'Campo Requerido');
        return;
    }

    if (!fechaErogacion) {
        showNotification('Por favor ingrese la fecha de erogación', 'error', 'Campo Requerido');
        return;
    }

    if (!importe || parseFloat(importe) <= 0) {
        showNotification('Por favor ingrese un importe válido', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoMoneda) {
        showNotification('Por favor seleccione el tipo de moneda', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoCambio || parseFloat(tipoCambio) <= 0) {
        showNotification('Por favor ingrese un tipo de cambio válido', 'error', 'Campo Requerido');
        return;
    }

    if (!aCargoImportador) {
        showNotification('Por favor indique si está a cargo del importador', 'error', 'Campo Requerido');
        return;
    }

    const tableBody = document.getElementById('incrementablesTableBody');
    if (!tableBody) return;

    // Eliminar fila vacía si existe
    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const incrementableData = {
        incrementable: incrementable,
        incrementableText: incrementableText,
        fechaErogacion: fechaErogacion,
        importe: parseFloat(importe),
        tipoMoneda: tipoMoneda,
        tipoMonedaText: tipoMonedaText,
        tipoCambio: parseFloat(tipoCambio),
        aCargoImportador: parseInt(aCargoImportador)
    };

    incrementablesData.push(incrementableData);
    actualizarTablaIncrementables();

    // Limpiar campos
    clearIncrementableFields();
    showNotification('Incrementable agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaIncrementables = function() {
    const tbody = document.getElementById('incrementablesTableBody');
    
    if (incrementablesData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay incrementables agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeleteIncrementables');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = incrementablesData.map((incrementable, index) => {
        // Normalizar clave antigua y obtener textos descriptivos
        const incrementableClave = normalizeLegacyKey(incrementable.incrementable);
        // Actualizar el objeto con la clave normalizada para guardado posterior
        incrementable.incrementable = incrementableClave;
        
        const incrementableText = incrementable.incrementableText || 
            getSelectTextByValue('incrementableSelect', incrementableClave) || 
            incrementableClave || '';
        const tipoMonedaText = incrementable.tipoMonedaText || 
            getSelectTextByValue('tipoMonedaIncrementableSelect', incrementable.tipoMoneda) || 
            incrementable.tipoMoneda || '';
        const importe = parseFloat(incrementable.importe) || 0;
        const tipoCambio = parseFloat(incrementable.tipoCambio) || 0;
        
        return `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="incrementable-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonIncrementables()">
            </td>
            <td class="table-cell">${incrementableText}</td>
            <td class="table-cell">${incrementable.fechaErogacion || ''}</td>
            <td class="table-cell">$${importe.toFixed(3)}</td>
            <td class="table-cell">${tipoMonedaText}</td>
            <td class="table-cell">${tipoCambio.toFixed(4)}</td>
            <td class="table-cell">${incrementable.aCargoImportador ? 'Sí' : 'No'}</td>
        </tr>
    `}).join('');

    lucide.createIcons();
};

window.toggleAllIncrementables = function(checkbox) {
    const checkboxes = document.querySelectorAll('.incrementable-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonIncrementables();
};

window.toggleDeleteButtonIncrementables = function() {
    const checkboxes = document.querySelectorAll('.incrementable-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeleteIncrementables');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllIncrementables');
    const allCheckboxes = document.querySelectorAll('.incrementable-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    }
};

window.deleteSelectedIncrementables = async function() {
    const checkboxes = document.querySelectorAll('.incrementable-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un incrementable para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} incrementable(s)?`,
        'ELIMINAR INCREMENTABLES'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        incrementablesData.splice(index, 1);
    });

    actualizarTablaIncrementables();
    const selectAllCheckbox = document.getElementById('selectAllIncrementables');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    showNotification('Incrementable(s) eliminado(s) correctamente', 'success', 'Éxito');
};

function clearIncrementableFields() {
    document.getElementById('incrementableSelect').value = '';
    document.getElementById('fechaErogacionInput').value = '';
    document.getElementById('importeIncrementableInput').value = '';
    document.getElementById('tipoMonedaIncrementableSelect').value = '';
    document.getElementById('tipoCambioIncrementableInput').value = '';
    document.querySelectorAll('input[name="aCargoImportador"]').forEach(radio => {
        radio.checked = false;
    });
}

// ============================================
// DECREMENTABLES FUNCTIONS
// ============================================
window.addDecrementableToTable = function() {
    const decrementableSelect = document.getElementById('decrementableSelect');
    const decrementable = decrementableSelect.value;
    const decrementableText = decrementableSelect.options[decrementableSelect.selectedIndex].text;
    const fechaErogacion = document.getElementById('fechaErogacionDecrementableInput').value;
    const importe = document.getElementById('importeDecrementableInput').value;
    const tipoMonedaSelect = document.getElementById('tipoMonedaDecrementableSelect');
    const tipoMoneda = tipoMonedaSelect.value;
    const tipoMonedaText = tipoMonedaSelect.options[tipoMonedaSelect.selectedIndex].text;
    const tipoCambio = document.getElementById('tipoCambioDecrementableInput').value;

    // Validaciones
    if (!decrementable) {
        showNotification('Por favor seleccione un decrementable', 'error', 'Campo Requerido');
        return;
    }

    if (!fechaErogacion) {
        showNotification('Por favor ingrese la fecha de su erogación', 'error', 'Campo Requerido');
        return;
    }

    if (!importe || parseFloat(importe) <= 0) {
        showNotification('Por favor ingrese un importe válido', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoMoneda) {
        showNotification('Por favor seleccione el tipo de moneda', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoCambio || parseFloat(tipoCambio) <= 0) {
        showNotification('Por favor ingrese un tipo de cambio válido', 'error', 'Campo Requerido');
        return;
    }

    const tableBody = document.getElementById('decrementablesTableBody');
    if (!tableBody) return;

    // Eliminar fila vacía si existe
    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const decrementableData = {
        decrementable: decrementable,
        decrementableText: decrementableText,
        fechaErogacion: fechaErogacion,
        importe: parseFloat(importe),
        tipoMoneda: tipoMoneda,
        tipoMonedaText: tipoMonedaText,
        tipoCambio: parseFloat(tipoCambio)
    };

    decrementablesData.push(decrementableData);
    actualizarTablaDecrementables();

    // Limpiar campos
    clearDecrementableFields();
    showNotification('Decrementable agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaDecrementables = function() {
    const tbody = document.getElementById('decrementablesTableBody');
    
    if (decrementablesData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay decrementables agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeleteDecrementables');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = decrementablesData.map((decrementable, index) => {
        // Normalizar clave antigua y obtener textos descriptivos
        const decrementableClave = normalizeLegacyKey(decrementable.decrementable);
        // Actualizar el objeto con la clave normalizada para guardado posterior
        decrementable.decrementable = decrementableClave;
        
        const decrementableText = decrementable.decrementableText || 
            getSelectTextByValue('decrementableSelect', decrementableClave) || 
            decrementableClave || '';
        const tipoMonedaText = decrementable.tipoMonedaText || 
            getSelectTextByValue('tipoMonedaDecrementableSelect', decrementable.tipoMoneda) || 
            decrementable.tipoMoneda || '';
        const importe = parseFloat(decrementable.importe) || 0;
        
        return `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="decrementable-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonDecrementables()">
            </td>
            <td class="table-cell">${decrementableText}</td>
            <td class="table-cell">${decrementable.fechaErogacion || ''}</td>
            <td class="table-cell">$${importe.toFixed(3)}</td>
            <td class="table-cell">${tipoMonedaText}</td>
        </tr>
    `}).join('');

    lucide.createIcons();
};

window.toggleAllDecrementables = function(checkbox) {
    const checkboxes = document.querySelectorAll('.decrementable-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonDecrementables();
};

window.toggleDeleteButtonDecrementables = function() {
    const checkboxes = document.querySelectorAll('.decrementable-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeleteDecrementables');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllDecrementables');
    const allCheckboxes = document.querySelectorAll('.decrementable-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    }
};

window.deleteSelectedDecrementables = async function() {
    const checkboxes = document.querySelectorAll('.decrementable-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un decrementable para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} decrementable(s)?`,
        'ELIMINAR DECREMENTABLES'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        decrementablesData.splice(index, 1);
    });

    actualizarTablaDecrementables();
    const selectAllCheckbox = document.getElementById('selectAllDecrementables');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    showNotification('Decrementable(s) eliminado(s) correctamente', 'success', 'Éxito');
};

function clearDecrementableFields() {
    document.getElementById('decrementableSelect').value = '';
    document.getElementById('fechaErogacionDecrementableInput').value = '';
    document.getElementById('importeDecrementableInput').value = '';
    document.getElementById('tipoMonedaDecrementableSelect').value = '';
    document.getElementById('tipoCambioDecrementableInput').value = '';
}

// ============================================
// PRECIO PAGADO FUNCTIONS
// ============================================
window.addPrecioPagadoToTable = function() {
    const fecha = document.getElementById('fechaPrecioPagadoInput').value;
    const importe = document.getElementById('importePrecioPagadoInput').value;
    const formaPagoSelect = document.getElementById('formaPagoPrecioPagadoSelect');
    const formaPago = formaPagoSelect.value;
    const formaPagoText = formaPagoSelect.options[formaPagoSelect.selectedIndex].text;
    const tipoMonedaSelect = document.getElementById('tipoMonedaPrecioPagadoSelect');
    const tipoMoneda = tipoMonedaSelect.value;
    const tipoMonedaText = tipoMonedaSelect.options[tipoMonedaSelect.selectedIndex].text;
    const tipoCambio = document.getElementById('tipoCambioPrecioPagadoInput').value;

    // Validaciones
    if (!fecha) {
        showNotification('Por favor ingrese la fecha', 'error', 'Campo Requerido');
        return;
    }

    if (!importe || parseFloat(importe) <= 0) {
        showNotification('Por favor ingrese un importe válido', 'error', 'Campo Requerido');
        return;
    }

    if (!formaPago) {
        showNotification('Por favor seleccione la forma de pago', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoMoneda) {
        showNotification('Por favor seleccione el tipo de moneda', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoCambio || parseFloat(tipoCambio) <= 0) {
        showNotification('Por favor ingrese un tipo de cambio válido', 'error', 'Campo Requerido');
        return;
    }

    const tableBody = document.getElementById('precioPagadoTableBody');
    if (!tableBody) return;

    // Eliminar fila vacía si existe
    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const precioPagadoItem = {
        fecha: fecha,
        importe: parseFloat(importe),
        formaPago: formaPago,
        formaPagoText: formaPagoText,
        especifique: formaPago === 'OTROS' ? 'Especificar otros' : '',
        tipoMoneda: tipoMoneda,
        tipoMonedaText: tipoMonedaText,
        tipoCambio: parseFloat(tipoCambio)
    };

    precioPagadoData.push(precioPagadoItem);
    actualizarTablaPrecioPagado();

    // Limpiar campos
    clearPrecioPagadoFields();
    showNotification('Concepto de precio pagado agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaPrecioPagado = function() {
    const tbody = document.getElementById('precioPagadoTableBody');
    
    if (precioPagadoData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay conceptos de precio pagado agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeletePrecioPagado');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = precioPagadoData.map((item, index) => {
        // Normalizar clave antigua
        const formaPagoClave = normalizeLegacyKey(item.formaPago);
        item.formaPago = formaPagoClave;
        
        const formaPagoText = item.formaPagoText || 
            getSelectTextByValue('formaPagoPrecioPagadoSelect', formaPagoClave) || 
            formaPagoClave || '';
        const tipoMonedaText = item.tipoMonedaText || item.tipoMoneda || '';
        const importe = parseFloat(item.importe) || 0;
        const tipoCambio = parseFloat(item.tipoCambio) || 0;
        
        return `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="precio-pagado-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonPrecioPagado()">
            </td>
            <td class="table-cell">${item.fecha || ''}</td>
            <td class="table-cell">$${importe.toFixed(3)}</td>
            <td class="table-cell">${formaPagoText}</td>
            <td class="table-cell">${tipoMonedaText}</td>
            <td class="table-cell">${tipoCambio.toFixed(4)}</td>
        </tr>
    `}).join('');

    lucide.createIcons();
};

window.toggleAllPrecioPagado = function(checkbox) {
    const checkboxes = document.querySelectorAll('.precio-pagado-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonPrecioPagado();
};

window.toggleDeleteButtonPrecioPagado = function() {
    const checkboxes = document.querySelectorAll('.precio-pagado-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeletePrecioPagado');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllPrecioPagado');
    const allCheckboxes = document.querySelectorAll('.precio-pagado-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    }
};

window.deleteSelectedPrecioPagado = async function() {
    const checkboxes = document.querySelectorAll('.precio-pagado-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un concepto de precio pagado para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} concepto(s) de precio pagado?`,
        'ELIMINAR CONCEPTOS'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        precioPagadoData.splice(index, 1);
    });

    actualizarTablaPrecioPagado();
    const selectAllCheckbox = document.getElementById('selectAllPrecioPagado');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    showNotification('Concepto(s) de precio pagado eliminado(s) correctamente', 'success', 'Éxito');
};

function clearPrecioPagadoFields() {
    document.getElementById('fechaPrecioPagadoInput').value = '';
    document.getElementById('importePrecioPagadoInput').value = '';
    document.getElementById('formaPagoPrecioPagadoSelect').value = '';
    document.getElementById('tipoMonedaPrecioPagadoSelect').value = '';
    document.getElementById('tipoCambioPrecioPagadoInput').value = '';
}

// ============================================
// PRECIO POR PAGAR FUNCTIONS
// ============================================
window.addPrecioPorPagarToTable = function() {
    const fecha = document.getElementById('fechaPrecioPorPagarInput').value;
    const importe = document.getElementById('importePrecioPorPagarInput').value;
    const formaPagoSelect = document.getElementById('formaPagoPrecioPorPagarSelect');
    const formaPago = formaPagoSelect.value;
    const formaPagoText = formaPagoSelect.options[formaPagoSelect.selectedIndex].text;
    const tipoMonedaSelect = document.getElementById('tipoMonedaPrecioPorPagarSelect');
    const tipoMoneda = tipoMonedaSelect.value;
    const tipoMonedaText = tipoMonedaSelect.options[tipoMonedaSelect.selectedIndex].text;
    const tipoCambio = document.getElementById('tipoCambioPrecioPorPagarInput').value;
    const momentoSituacion = document.getElementById('momentoSituacionInput').value;

    // Validaciones
    if (!fecha) {
        showNotification('Por favor ingrese la fecha', 'error', 'Campo Requerido');
        return;
    }

    if (!importe || parseFloat(importe) <= 0) {
        showNotification('Por favor ingrese un importe válido', 'error', 'Campo Requerido');
        return;
    }

    if (!formaPago) {
        showNotification('Por favor seleccione la forma de pago', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoMoneda) {
        showNotification('Por favor seleccione el tipo de moneda', 'error', 'Campo Requerido');
        return;
    }

    if (!tipoCambio || parseFloat(tipoCambio) <= 0) {
        showNotification('Por favor ingrese un tipo de cambio válido', 'error', 'Campo Requerido');
        return;
    }

    if (!momentoSituacion.trim()) {
        showNotification('Por favor describa el momento o situación cuando se realizará el pago', 'error', 'Campo Requerido');
        return;
    }

    const tableBody = document.getElementById('precioPorPagarTableBody');
    if (!tableBody) return;

    // Eliminar fila vacía si existe
    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const precioPorPagarItem = {
        fecha: fecha,
        importe: parseFloat(importe),
        formaPago: formaPago,
        formaPagoText: formaPagoText,
        momentoSituacion: momentoSituacion,
        especifique: formaPago === 'OTROS' ? 'Especificar otros' : '',
        tipoMoneda: tipoMoneda,
        tipoMonedaText: tipoMonedaText,
        tipoCambio: parseFloat(tipoCambio)
    };

    precioPorPagarData.push(precioPorPagarItem);
    actualizarTablaPrecioPorPagar();

    // Limpiar campos
    clearPrecioPorPagarFields();
    showNotification('Concepto de precio por pagar agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaPrecioPorPagar = function() {
    const tbody = document.getElementById('precioPorPagarTableBody');
    
    if (precioPorPagarData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay conceptos de precio por pagar agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeletePrecioPorPagar');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = precioPorPagarData.map((item, index) => {
        // Normalizar clave antigua
        const formaPagoClave = normalizeLegacyKey(item.formaPago);
        item.formaPago = formaPagoClave;
        
        const formaPagoText = item.formaPagoText || 
            getSelectTextByValue('formaPagoPrecioPorPagarSelect', formaPagoClave) || 
            formaPagoClave || '';
        const tipoMonedaText = item.tipoMonedaText || item.tipoMoneda || '';
        const importe = parseFloat(item.importe) || 0;
        
        return `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="precio-por-pagar-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonPrecioPorPagar()">
            </td>
            <td class="table-cell">${item.fecha || ''}</td>
            <td class="table-cell">$${importe.toFixed(3)}</td>
            <td class="table-cell">${formaPagoText}</td>
            <td class="table-cell">${item.momentoSituacion || ''}</td>
            <td class="table-cell">${tipoMonedaText}</td>
        </tr>
    `}).join('');

    lucide.createIcons();
};

window.toggleAllPrecioPorPagar = function(checkbox) {
    const checkboxes = document.querySelectorAll('.precio-por-pagar-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonPrecioPorPagar();
};

window.toggleDeleteButtonPrecioPorPagar = function() {
    const checkboxes = document.querySelectorAll('.precio-por-pagar-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeletePrecioPorPagar');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllPrecioPorPagar');
    const allCheckboxes = document.querySelectorAll('.precio-por-pagar-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    }
};

window.deleteSelectedPrecioPorPagar = async function() {
    const checkboxes = document.querySelectorAll('.precio-por-pagar-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un concepto de precio por pagar para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} concepto(s) de precio por pagar?`,
        'ELIMINAR CONCEPTOS'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        precioPorPagarData.splice(index, 1);
    });

    actualizarTablaPrecioPorPagar();
    const selectAllCheckbox = document.getElementById('selectAllPrecioPorPagar');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    showNotification('Concepto(s) de precio por pagar eliminado(s) correctamente', 'success', 'Éxito');
};

function clearPrecioPorPagarFields() {
    document.getElementById('fechaPrecioPorPagarInput').value = '';
    document.getElementById('importePrecioPorPagarInput').value = '';
    document.getElementById('formaPagoPrecioPorPagarSelect').value = '';
    document.getElementById('tipoMonedaPrecioPorPagarSelect').value = '';
    document.getElementById('tipoCambioPrecioPorPagarInput').value = '';
    document.getElementById('momentoSituacionInput').value = '';
}

// ============================================
// COMPENSO PAGO FUNCTIONS
// ============================================
window.addCompensoPagoToTable = function() {
    const fecha = document.getElementById('fechaCompensoPagoInput').value;
    const formaPagoSelect = document.getElementById('formaPagoCompensoPagoSelect');
    const formaPago = formaPagoSelect.value;
    const formaPagoText = formaPagoSelect.options[formaPagoSelect.selectedIndex].text;
    const motivo = document.getElementById('motivoCompensoPagoInput').value;
    const prestacionMercancia = document.getElementById('prestacionMercanciaInput').value;

    // Validaciones
    if (!fecha) {
        showNotification('Por favor ingrese la fecha', 'error', 'Campo Requerido');
        return;
    }

    if (!formaPago) {
        showNotification('Por favor seleccione la forma de pago', 'error', 'Campo Requerido');
        return;
    }

    if (!motivo.trim()) {
        showNotification('Por favor describa el motivo por lo que se realizó', 'error', 'Campo Requerido');
        return;
    }

    if (!prestacionMercancia.trim()) {
        showNotification('Por favor describa la prestación de la mercancía', 'error', 'Campo Requerido');
        return;
    }

    const tableBody = document.getElementById('compensoPagoTableBody');
    if (!tableBody) return;

    // Eliminar fila vacía si existe
    const emptyRow = tableBody.querySelector('.table-empty');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }

    const compensoPagoItem = {
        fecha: fecha,
        formaPago: formaPago,
        formaPagoText: formaPagoText,
        motivo: motivo,
        prestacionMercancia: prestacionMercancia,
        especifique: formaPago === 'OTROS' ? 'Especificar otros' : ''
    };

    compensoPagoData.push(compensoPagoItem);
    actualizarTablaCompensoPago();

    // Limpiar campos
    clearCompensoPagoFields();
    showNotification('Concepto de compenso pago agregado correctamente', 'success', 'Éxito');
};

window.actualizarTablaCompensoPago = function() {
    const tbody = document.getElementById('compensoPagoTableBody');
    
    if (compensoPagoData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="table-empty">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    <p class="text-sm text-slate-400 mt-2">No hay conceptos de compenso pago agregados</p>
                </td>
            </tr>
        `;
        const deleteBtn = document.getElementById('btnDeleteCompensoPago');
        if (deleteBtn) deleteBtn.classList.add('hidden');
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = compensoPagoData.map((item, index) => {
        // Normalizar clave antigua
        const formaPagoClave = normalizeLegacyKey(item.formaPago);
        item.formaPago = formaPagoClave;
        
        const formaPagoText = item.formaPagoText || 
            getSelectTextByValue('formaPagoCompensoPagoSelect', formaPagoClave) || 
            formaPagoClave || '';
        
        return `
        <tr class="table-row">
            <td class="table-cell text-center">
                <input type="checkbox" class="compenso-pago-checkbox table-checkbox-input" data-index="${index}" onchange="toggleDeleteButtonCompensoPago()">
            </td>
            <td class="table-cell">${item.fecha || ''}</td>
            <td class="table-cell">${item.motivo || ''}</td>
            <td class="table-cell">${item.prestacionMercancia || ''}</td>
            <td class="table-cell">${formaPagoText}</td>
        </tr>
    `}).join('');

    lucide.createIcons();
};

window.toggleAllCompensoPago = function(checkbox) {
    const checkboxes = document.querySelectorAll('.compenso-pago-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    toggleDeleteButtonCompensoPago();
};

window.toggleDeleteButtonCompensoPago = function() {
    const checkboxes = document.querySelectorAll('.compenso-pago-checkbox:checked');
    const deleteBtn = document.getElementById('btnDeleteCompensoPago');
    
    if (checkboxes.length > 0) {
        deleteBtn.classList.remove('hidden');
    } else {
        deleteBtn.classList.add('hidden');
    }

    const selectAllCheckbox = document.getElementById('selectAllCompensoPago');
    const allCheckboxes = document.querySelectorAll('.compenso-pago-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    }
};

window.deleteSelectedCompensoPago = async function() {
    const checkboxes = document.querySelectorAll('.compenso-pago-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Seleccione al menos un concepto de compenso pago para eliminar', 'warning', 'Atención');
        return;
    }

    const confirmResult = await showCustomConfirm(
        `¿Está seguro de eliminar ${checkboxes.length} concepto(s) de compenso pago?`,
        'ELIMINAR CONCEPTOS'
    );
    
    if (!confirmResult) {
        return;
    }

    const indicesToRemove = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index)).sort((a, b) => b - a);
    indicesToRemove.forEach(index => {
        compensoPagoData.splice(index, 1);
    });

    actualizarTablaCompensoPago();
    const selectAllCheckbox = document.getElementById('selectAllCompensoPago');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    showNotification('Concepto(s) de compenso pago eliminado(s) correctamente', 'success', 'Éxito');
};

function clearCompensoPagoFields() {
    document.getElementById('fechaCompensoPagoInput').value = '';
    document.getElementById('formaPagoCompensoPagoSelect').value = '';
    document.getElementById('motivoCompensoPagoInput').value = '';
    document.getElementById('prestacionMercanciaInput').value = '';
}

// ============================================
// SAVE FUNCTIONS
// ============================================
window.saveDatosManifestacion = async function() {
    const personaConsulta = [];
    const rfcRows = document.querySelectorAll('#rfcConsultaTableBody tr');
    rfcRows.forEach(row => {
        const rfc = row.getAttribute('data-rfc');
        if (rfc) {
            // Leer las claves de los atributos data en lugar del texto de las celdas
            const razonSocial = row.getAttribute('data-razon-social') || row.querySelectorAll('td')[2]?.textContent || '';
            const tipoFigura = row.getAttribute('data-tipo-figura') || ''; // CLAVE, no descripción
            personaConsulta.push({
                rfc: rfc,
                razon_social: razonSocial,
                tipo_figura: tipoFigura
            });
        }
    });
    
    const applicantRfc = document.querySelector('[data-applicant-rfc]').getAttribute('data-applicant-rfc');
    const data = {
        rfc_importador: applicantRfc,
        metodo_valoracion: document.getElementById('metodoValoracion')?.value || '',
        existe_vinculacion: document.getElementById('existeVinculacion')?.value || '',
        pedimento: document.getElementById('pedimento')?.value || '',
        patente: document.getElementById('patente')?.value || '',
        aduana: document.getElementById('aduana')?.value || '',
        persona_consulta: personaConsulta
    };

    await saveSection('datos-manifestacion', data, 'Datos de Manifestación');
};

window.saveInformacionCove = async function() {
    const informacionCove = [];
    const coveRows = document.querySelectorAll('#informacionCoveTableBody tr[data-cove]');
    
    console.log('Filas COVE encontradas:', coveRows.length);
    
    coveRows.forEach(row => {
        const cove = row.getAttribute('data-cove');
        if (cove) {
            informacionCove.push({
                numero_cove: cove,
                metodo_valoracion: row.getAttribute('data-metodo') || '',
                numero_factura: row.getAttribute('data-factura') || '',
                fecha_expedicion: row.getAttribute('data-fecha') || '',
                emisor_original: row.getAttribute('data-emisor') || '',
                destinatario: row.getAttribute('data-destinatario') || '',
                incoterm: row.getAttribute('data-incoterm') || '',
                vinculacion: row.getAttribute('data-vinculacion') || ''
            });
        }
    });
    
    console.log('Información COVE a guardar:', informacionCove);
    
    const data = {
        informacion_cove: informacionCove,
        pedimentos: pedimentosData,
        incrementables: incrementablesData,
        decrementables: decrementablesData,
        precio_pagado: precioPagadoData,
        precio_por_pagar: precioPorPagarData,
        compenso_pago: compensoPagoData
    };

    // Debug log para verificar los datos
    console.log('Datos a enviar:', {
        informacion_cove: informacionCove.length,
        incrementables: incrementablesData.length,
        decrementables: decrementablesData.length,
        precio_pagado: precioPagadoData.length,
        precio_por_pagar: precioPorPagarData.length,
        compenso_pago: compensoPagoData.length
    });

    await saveSection('informacion-cove', data, 'Información COVE');
};

window.saveValorAduana = async function() {
    const data = {
        valor_en_aduana: {
            total_precio_pagado: document.getElementById('totalPrecioPagado')?.value || 0,
            total_precio_por_pagar: document.getElementById('totalPrecioPorPagar')?.value || 0,
            total_incrementables: document.getElementById('totalIncrementables')?.value || 0,
            total_decrementables: document.getElementById('totalDecrementables')?.value || 0,
            total_valor_aduana: document.getElementById('totalValorAduana')?.value || 0
        }
    };

    await saveSection('valor-aduana', data, 'Valor en Aduana');
};

window.saveDocumentos = async function() {
    const documentos = [];
    const docRows = document.querySelectorAll('#edocumentsTableBody tr[data-edocument-row]');
    docRows.forEach(row => {
        documentos.push({
            tipo_documento: row.dataset.edocumentType || '',
            nombre_documento: row.dataset.edocumentName || '',
            folio_edocument: row.dataset.edocumentFolio || '',
            created_at: row.dataset.edocumentCreatedAt || ''
        });
    });
    
    const data = {
        documentos: documentos
    };

    await saveSection('documentos', data, 'Documentos');
};

async function saveSection(sectionName, data, sectionLabel) {
    try {
        const applicantId = document.querySelector('[data-applicant-id]').getAttribute('data-applicant-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch(`/mve/save-${sectionName}/${applicantId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(`${sectionLabel} guardado exitosamente`, 'success');
        } else {
            showNotification(`Error al guardar ${sectionLabel}: ` + (result.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        showNotification(`Error al guardar ${sectionLabel}. Por favor intente nuevamente.`, 'error');
    }
}

window.saveIncrementables = async function() {
    const incrementablesTableData = [];
    const incrementablesRows = document.querySelectorAll('#incrementablesTableBody tr');
    
    incrementablesRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6) {
            incrementablesTableData.push({
                incrementable: cells[1]?.textContent?.trim() || '',
                fecha_erogacion: cells[2]?.textContent?.trim() || '',
                importe: cells[3]?.textContent?.trim() || '',
                tipo_moneda: cells[4]?.textContent?.trim() || '',
                tipo_cambio: cells[5]?.textContent?.trim() || '',
                a_cargo_importador: cells[6]?.textContent?.trim() || ''
            });
        }
    });
    
    const data = {
        incrementables: incrementablesTableData
    };
    
    await saveSection(data, 'incrementables', 'Incrementables');
};

window.saveDecrementables = async function() {
    const decrementablesTableData = [];
    const decrementablesRows = document.querySelectorAll('#decrementablesTableBody tr');
    
    decrementablesRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 5) {
            decrementablesTableData.push({
                decrementable: cells[1]?.textContent?.trim() || '',
                fecha_erogacion: cells[2]?.textContent?.trim() || '',
                importe: cells[3]?.textContent?.trim() || '',
                tipo_moneda: cells[4]?.textContent?.trim() || ''
            });
        }
    });
    
    const data = {
        decrementables: decrementablesTableData
    };
    
    await saveSection(data, 'decrementables', 'Decrementables');
};

window.savePrecioPagado = async function() {
    const precioPagadoTableData = [];
    const precioPagadoRows = document.querySelectorAll('#precioPagadoTableBody tr');
    
    precioPagadoRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            precioPagadoTableData.push({
                fecha: cells[1]?.textContent?.trim() || '',
                importe: cells[2]?.textContent?.trim() || '',
                forma_pago: cells[3]?.textContent?.trim() || '',
                especifique: cells[4]?.textContent?.trim() || '',
                tipo_moneda: cells[5]?.textContent?.trim() || '',
                tipo_cambio: cells[6]?.textContent?.trim() || ''
            });
        }
    });
    
    const data = {
        precio_pagado: precioPagadoTableData
    };
    
    await saveSection(data, 'precio-pagado', 'Precio Pagado');
};

window.savePrecioPorPagar = async function() {
    const precioPorPagarTableData = [];
    const precioPorPagarRows = document.querySelectorAll('#precioPorPagarTableBody tr');
    
    precioPorPagarRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            precioPorPagarTableData.push({
                fecha: cells[1]?.textContent?.trim() || '',
                importe: cells[2]?.textContent?.trim() || '',
                forma_pago: cells[3]?.textContent?.trim() || '',
                momento_situacion: cells[4]?.textContent?.trim() || '',
                especifique: cells[5]?.textContent?.trim() || '',
                tipo_moneda: cells[6]?.textContent?.trim() || ''
            });
        }
    });
    
    const data = {
        precio_por_pagar: precioPorPagarTableData
    };
    
    await saveSection(data, 'precio-por-pagar', 'Precio por Pagar');
};

window.saveCompensoPago = async function() {
    const compensoPagoTableData = [];
    const compensoPagoRows = document.querySelectorAll('#compensoPagoTableBody tr');
    
    compensoPagoRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6) {
            compensoPagoTableData.push({
                fecha: cells[1]?.textContent?.trim() || '',
                motivo: cells[2]?.textContent?.trim() || '',
                prestacion_mercancia: cells[3]?.textContent?.trim() || '',
                forma_pago: cells[4]?.textContent?.trim() || '',
                especifique: cells[5]?.textContent?.trim() || ''
            });
        }
    });
    
    const data = {
        compenso_pago: compensoPagoTableData
    };
    
    await saveSection(data, 'compenso-pago', 'Compenso Pago');
};

// Verificar completitud al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        checkAllSectionsCompletion();
    }, 1000);
});

// ============================================
// VALIDACIÓN Y GUARDADO COMPLETO
// ============================================

// Verificar si todas las secciones están completas
async function checkAllSectionsCompletion() {
    try {
        const applicantId = document.querySelector('[data-applicant-id]').getAttribute('data-applicant-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch(`/mve/check-completion/${applicantId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (response.ok) {
            const result = await response.json();
            const btnGuardar = document.getElementById('btnGuardarManifestacion');
            
            if (result.all_sections_complete) {
                btnGuardar.disabled = false;
                btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnGuardar.disabled = true;
                btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    } catch (error) {
        console.warn('Error verificando completitud de secciones:', error);
    }
}

// Función para guardar la manifestación completa
window.guardarManifestacionCompleta = async function() {
    const btnGuardar = document.getElementById('btnGuardarManifestacion');
    
    if (btnGuardar.disabled) {
        showNotification('Complete todas las secciones antes de guardar la manifestación', 'warning', 'Secciones Incompletas');
        return;
    }

    // Mostrar vista previa en lugar de guardar directamente
    await mostrarVistaPrevia();
};

// Hook para verificar completitud después de cada guardado de sección
const originalSaveSection = saveSection;
saveSection = async function(sectionName, data, sectionLabel) {
    const result = await originalSaveSection.call(this, sectionName, data, sectionLabel);
    
    // Verificar completitud después del guardado
    setTimeout(() => {
        checkAllSectionsCompletion();
    }, 500);
    
    return result;
};

// ============================================
// FUNCIONES PARA VISTA PREVIA
// ============================================

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

// Mostrar vista previa de todos los datos
async function mostrarVistaPrevia() {
    try {
        const applicantId = document.querySelector('[data-applicant-id]').getAttribute('data-applicant-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Obtener datos de vista previa
        const response = await fetch(`/mve/preview-data/${applicantId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error('Error al obtener datos de vista previa');
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error desconocido');
        }

        // Generar contenido de vista previa
        const previewContent = generarContenidoVistaPrevia(data);
        document.getElementById('previewContent').innerHTML = previewContent;
        
        // Mostrar modal
        const modal = document.getElementById('previewModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Inicializar iconos de Lucide
        setTimeout(() => {
            lucide.createIcons();
        }, 100);
        
    } catch (error) {
        console.error('Error mostrando vista previa:', error);
        showNotification('Error al mostrar la vista previa: ' + error.message, 'error', 'Error');
    }
}

// Generar HTML del contenido de vista previa - Formato Oficial gob.mx
function generarContenidoVistaPrevia(data) {
    let html = '';
    
    // Obtener datos
    const coves = data.informacion_cove?.informacion_cove || [];
    const pedimentos = data.informacion_cove?.pedimentos || [];
    const incrementables = data.informacion_cove?.incrementables || [];
    const decrementables = data.informacion_cove?.decrementables || [];
    const precioPagado = data.informacion_cove?.precio_pagado || [];
    const precioPorPagar = data.informacion_cove?.precio_por_pagar || [];
    const compensoPago = data.informacion_cove?.compenso_pago || [];
    const valorAduana = data.valor_aduana?.valor_en_aduana_data || {};
    const personasConsulta = data.datos_manifestacion?.persona_consulta || [];
    const documentos = data.documentos || [];
    
    // Primer COVE para datos generales
    const primerCove = coves[0] || {};
    
    html += `
    <div class="bg-white border border-slate-300 shadow-lg">
        <!-- Header gob.mx -->
        <div class="bg-gradient-to-r from-[#6d1a36] to-[#9a2a4d] text-white p-4">
            <div class="text-2xl font-bold italic">gob.mx</div>
            <div class="text-center mt-2">
                <div class="text-sm font-bold tracking-wide">MANIFESTACIÓN DE VALOR</div>
                <div class="text-xs">Ventanilla Digital Mexicana de Comercio Exterior</div>
                <div class="text-xs">Promoción o solicitud en materia de comercio exterior</div>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div class="p-6 space-y-6">
            
            <!-- Datos de la Manifestación de valor -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Datos de la Manifestación de valor</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold w-1/3">RFC del importador</td>
                        <td class="border border-slate-300 p-2 w-2/3">Nombre o Razón social</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2 font-medium">${data.datos_manifestacion?.rfc_importador || data.applicant?.rfc || ''}</td>
                        <td class="border border-slate-300 p-2">${data.applicant?.razon_social || ''}</td>
                    </tr>
                </table>
            </div>
            
            <!-- RFC's de consulta -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">RFC's de consulta</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold w-1/4">RFC</td>
                        <td class="border border-slate-300 p-2 w-2/4">Nombre o Razón social</td>
                    </tr>
                    ${personasConsulta.length > 0 ? personasConsulta.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2 font-medium">${p.rfc || ''}</td>
                            <td class="border border-slate-300 p-2">${p.razon_social || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">Tipo de figura</td>
                    </tr>
                    ${personasConsulta.length > 0 ? personasConsulta.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="2">${p.tipo_figura || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="2">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Información Acuse de Valor -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Información Acuse de Valor</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">Método de valoración aduanera</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2" colspan="2">${primerCove.metodo_valoracion || ''}</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">INCOTERM</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2" colspan="2">${primerCove.incoterm || ''}</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">¿Existe vinculación entre importador y vendedor/proveedor?</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2" colspan="2">${primerCove.vinculacion === '1' || primerCove.vinculacion === 1 ? 'Sí' : (primerCove.vinculacion === '0' || primerCove.vinculacion === 0 ? 'No' : '')}</td>
                    </tr>
                </table>
            </div>
            
            <!-- Pedimentos -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Pedimentos</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold w-1/3">Pedimento</td>
                        <td class="border border-slate-300 p-2 font-semibold w-1/3">Patente</td>
                        <td class="border border-slate-300 p-2 font-semibold w-1/3">Aduana</td>
                    </tr>
                    ${pedimentos.length > 0 ? pedimentos.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2">${p.numeroDisplay || p.numero || ''}</td>
                            <td class="border border-slate-300 p-2">${p.patente || ''}</td>
                            <td class="border border-slate-300 p-2">${p.aduanaText || p.aduana || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Incrementables conforme al artículo 65 de la ley -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Incrementables conforme al artículo 65 de la ley</h3>
                <p class="text-xs text-slate-600 mb-3">Las comisiones y los gastos de corretaje, salvo las comisiones de compra.</p>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de erogación</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                    </tr>
                    ${incrementables.length > 0 ? incrementables.map(inc => `
                        <tr>
                            <td class="border border-slate-300 p-2">${inc.fechaErogacion || ''}</td>
                            <td class="border border-slate-300 p-2">${inc.importe ? '$' + parseFloat(inc.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                            <td class="border border-slate-300 p-2">${inc.tipoMonedaText || inc.tipoMoneda || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de cambio</td>
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">¿Está a cargo del importador?</td>
                    </tr>
                    ${incrementables.length > 0 ? incrementables.map(inc => `
                        <tr>
                            <td class="border border-slate-300 p-2">${inc.tipoCambio || ''}</td>
                            <td class="border border-slate-300 p-2" colspan="2">${inc.aCargoImportador !== undefined ? (inc.aCargoImportador ? 'Sí' : 'No') : ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2" colspan="2">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Decrementables (Información que no integra el valor de transacción) -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Información que no integra el valor de transacción conforme el artículo 66 de la ley aduanera (DECREMENTABLES)</h3>
                <p class="text-xs text-slate-600 mb-3">(Se considera que se distinguen del precio pagado las cantidades que se mencionan, se detallan o especifican separadamente del precio pagado en el comprobante fiscal digital o en el documento equivalente)</p>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de erogación</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                    </tr>
                    ${decrementables.length > 0 ? decrementables.map(dec => `
                        <tr>
                            <td class="border border-slate-300 p-2">${dec.fechaErogacion || ''}</td>
                            <td class="border border-slate-300 p-2">${dec.importe ? '$' + parseFloat(dec.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                            <td class="border border-slate-300 p-2">${dec.tipoMonedaText || dec.tipoMoneda || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                    </tr>
                    ${decrementables.length > 0 ? decrementables.map(dec => `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">${dec.tipoCambio || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Los gastos que por cuenta propia realice el importador -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Los gastos que por cuenta propia realice el importador, aun cuando se pueda estimar que benefician al vendedor, salvo aquellos respecto de los cuales deba efectuarse un ajuste conforme a lo dispuesto por el artículo 65 de esta Ley.</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de erogación</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2">&nbsp;</td>
                        <td class="border border-slate-300 p-2">&nbsp;</td>
                        <td class="border border-slate-300 p-2">&nbsp;</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                    </tr>
                </table>
            </div>
            
            <!-- Precio pagado -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Precio pagado</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                        <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                    </tr>
                    ${precioPagado.length > 0 ? precioPagado.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2">${p.fecha || ''}</td>
                            <td class="border border-slate-300 p-2">${p.importe ? '$' + parseFloat(p.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                            <td class="border border-slate-300 p-2">${p.formaPagoText || p.formaPago || ''}</td>
                            <td class="border border-slate-300 p-2">${p.especifique || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                    </tr>
                    ${precioPagado.length > 0 ? precioPagado.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2">${p.tipoMonedaText || p.tipoMoneda || ''}</td>
                            <td class="border border-slate-300 p-2" colspan="3">${p.tipoCambio || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Precio por pagar -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Precio por pagar</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                        <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                    </tr>
                    ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2">${p.fecha || ''}</td>
                            <td class="border border-slate-300 p-2">${p.importe ? '$' + parseFloat(p.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                            <td class="border border-slate-300 p-2">${p.formaPagoText || p.formaPago || ''}</td>
                            <td class="border border-slate-300 p-2">${p.especifique || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                    </tr>
                    ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2">${p.tipoMonedaText || p.tipoMoneda || ''}</td>
                            <td class="border border-slate-300 p-2" colspan="3">${p.tipoCambio || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="4">Momento(s) o situación(es) cuando se realizará el pago</td>
                    </tr>
                    ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="4">${p.momentoSituacion || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="4">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Compenso pago -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Compenso pago</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                        <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                    </tr>
                    ${compensoPago.length > 0 ? compensoPago.map(c => `
                        <tr>
                            <td class="border border-slate-300 p-2">${c.fecha || ''}</td>
                            <td class="border border-slate-300 p-2">${c.formaPagoText || c.formaPago || ''}</td>
                            <td class="border border-slate-300 p-2">${c.especifique || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Motivo por lo que se realizó</td>
                    </tr>
                    ${compensoPago.length > 0 ? compensoPago.map(c => `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">${c.motivo || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                        </tr>
                    `}
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="3">Prestación de la mercancía</td>
                    </tr>
                    ${compensoPago.length > 0 ? compensoPago.map(c => `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">${c.prestacionMercancia || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2" colspan="3">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Valor en aduana -->
            <div class="border-b-2 border-slate-300 pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Valor en aduana</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Importe total del precio pagado (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe total del precio por pagar (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_precio_pagado || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_precio_por_pagar || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">Importe total de incrementables (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                        <td class="border border-slate-300 p-2 font-semibold">Importe total de decrementables (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_incrementables || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_decrementables || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold" colspan="2">Total del valor en aduana (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2 text-lg font-bold text-green-700" colspan="2">$${parseFloat(valorAduana.total_valor_aduana || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                    </tr>
                </table>
            </div>
            
            <!-- eDocuments -->
            <div class="pb-4">
                <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">eDocuments</h3>
                <table class="w-full text-xs">
                    <tr class="bg-slate-100">
                        <td class="border border-slate-300 p-2 font-semibold">eDocument</td>
                    </tr>
                    ${documentos.length > 0 ? documentos.map(doc => `
                        <tr>
                            <td class="border border-slate-300 p-2">${doc.folio_edocument || ''}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td class="border border-slate-300 p-2">&nbsp;</td>
                        </tr>
                    `}
                </table>
            </div>
            
            <!-- Cadena Original -->
            <div class="pt-4 border-t-2 border-slate-400">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-700">Cadena Original (VUCEM)</h3>
                    <button type="button" id="toggleCadenaBtn" onclick="toggleCadenaOriginalPreview()" 
                            class="px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 border border-indigo-200 rounded hover:bg-indigo-50 transition-colors">
                        Mostrar cadena
                    </button>
                </div>
                <div id="cadenaOriginalPreview" class="hidden">
                    <div class="bg-slate-900 text-green-400 text-xs font-mono p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-all max-h-64 overflow-y-auto">
                        ${data.cadena_original || '||Sin datos||'}
                    </div>
                    <p class="text-xs text-slate-500 mt-2 italic">
                        * Los campos vacíos se representan con || (doble pipe). El orden de los campos sigue el XSD de VUCEM.
                    </p>
                </div>
            </div>
            
        </div>
    </div>
    `;
    
    return html;
}

// Función para mostrar/ocultar cadena original en vista previa
window.toggleCadenaOriginalPreview = function() {
    const content = document.getElementById('cadenaOriginalPreview');
    const button = document.getElementById('toggleCadenaBtn');
    if (!content || !button) return;
    
    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden', !isHidden);
    button.textContent = isHidden ? 'Ocultar cadena' : 'Mostrar cadena';
};

window.toggleCadenaOriginal = function() {
    const content = document.getElementById('cadenaOriginalContent');
    const button = document.getElementById('toggleCadenaOriginalBtn');
    if (!content || !button) {
        return;
    }

    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden', !isHidden);
    content.classList.toggle('block', isHidden);
    button.textContent = isHidden ? 'Ocultar cadena original' : 'Mostrar cadena original';
};

// Cerrar modal de vista previa
window.closePreviewModal = function() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Confirmar guardado final
window.confirmarGuardadoFinal = async function() {
    // Primero cerrar el modal de vista previa para que la confirmación sea visible
    closePreviewModal();
    
    const confirmResult = await showCustomConfirm(
        '¿Está seguro de que desea guardar la manifestación? Una vez guardada, podrá firmarla y enviarla a VUCEM desde la lista de pendientes.',
        'GUARDAR MANIFESTACIÓN'
    );
    
    if (!confirmResult) {
        // Si cancela, volver a abrir la vista previa
        mostrarVistaPrevia();
        return;
    }

    try {
        const btnGuardar = document.getElementById('btnGuardarManifestacion');
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 mr-2 animate-spin"></i>Guardando...';
        
        const applicantId = document.querySelector('[data-applicant-id]').getAttribute('data-applicant-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch(`/mve/save-final-manifestacion/${applicantId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Manifestación de Valor guardada exitosamente. Ahora puede firmarla y enviarla a VUCEM.', 'success', '¡Éxito!');
            
            // Redirigir a la lista de pendientes para firmar
            setTimeout(() => {
                window.location.href = '/mve/pendientes';
            }, 2000);
        } else {
            throw new Error(result.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error guardando manifestación:', error);
        showNotification('Error al guardar la manifestación: ' + error.message, 'error', 'Error');
        
        const btnGuardar = document.getElementById('btnGuardarManifestacion');
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i data-lucide="save" class="w-5 h-5 mr-2"></i>Guardar Manifestación';
    }
};

// Función para mostrar/ocultar detalles de secciones en vista previa
window.togglePreviewSection = function(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        if (section.classList.contains('hidden')) {
            section.classList.remove('hidden');
            section.classList.add('block');
            // Actualizar el ícono a chevron-up
            const button = section.previousElementSibling;
            const icon = button.querySelector('i[data-lucide="chevron-down"]');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-up');
                // Reinicializar lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        } else {
            section.classList.add('hidden');
            section.classList.remove('block');
            // Actualizar el ícono a chevron-down
            const button = section.previousElementSibling;
            const icon = button.querySelector('i[data-lucide="chevron-up"]');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-down');
                // Reinicializar lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }
    }
};

// Función para visualizar documento
window.viewDocument = async function(documentId) {
    // Mostrar modal de carga
    showDocumentLoadingModal();
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch(`/mve/view-document/${documentId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error('Error al cargar el documento');
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        
        // Ocultar modal de carga
        hideDocumentLoadingModal();
        
        // Abrir en nueva ventana
        window.open(url, '_blank');
        
    } catch (error) {
        console.error('Error al visualizar documento:', error);
        // Ocultar modal de carga en caso de error
        hideDocumentLoadingModal();
        showNotification('Error al visualizar el documento: ' + error.message, 'error', 'Error');
    }
};

// Función para descargar documento
window.downloadDocument = async function(documentId, filename) {
    // Mostrar modal de carga
    showDocumentLoadingModal();
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch(`/mve/download-document/${documentId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error('Error al descargar el documento');
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        
        // Ocultar modal de carga
        hideDocumentLoadingModal();
        
        // Crear enlace de descarga
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'documento';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error('Error al descargar documento:', error);
        // Ocultar modal de carga en caso de error
        hideDocumentLoadingModal();
        showNotification('Error al descargar el documento: ' + error.message, 'error', 'Error');
    }
};

// ============================================
// MODAL DE CARGA PARA DOCUMENTOS
// ============================================

// Función para mostrar modal de carga
function showDocumentLoadingModal() {
    // Crear modal si no existe
    let modal = document.getElementById('documentLoadingModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'documentLoadingModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80]';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-8 shadow-xl max-w-sm mx-4">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">PROCESANDO DOCUMENTO</h3>
                    <p class="text-sm text-gray-600">Verificando y convirtiendo el documento...</p>
                    <p class="text-xs text-gray-500 mt-2">Por favor espere un momento</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
}

// Función para ocultar modal de carga
function hideDocumentLoadingModal() {
    const modal = document.getElementById('documentLoadingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ============================================
// MODAL DE CONFIRMACIÓN PERSONALIZADO
// ============================================

// Variable global para manejar promesas de confirmación
let confirmResolve = null;

// Función para mostrar modal de confirmación personalizado
function showCustomConfirm(message, title = 'CONFIRMACIÓN') {
    return new Promise((resolve) => {
        confirmResolve = resolve;
        
        // Crear modal si no existe
        let modal = document.getElementById('customConfirmModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'customConfirmModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80]';
            modal.style.display = 'none';
            
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md mx-4 w-full">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-amber-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 id="confirmTitle" class="text-lg font-semibold text-gray-900"></h3>
                            </div>
                        </div>
                        <div class="mt-2">
                            <p id="confirmMessage" class="text-sm text-gray-600"></p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                        <button id="confirmCancel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            CANCELAR
                        </button>
                        <button id="confirmAccept" type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            ACEPTAR
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Event listeners
            document.getElementById('confirmCancel').addEventListener('click', () => {
                hideCustomConfirm();
                if (confirmResolve) confirmResolve(false);
            });
            
            document.getElementById('confirmAccept').addEventListener('click', () => {
                hideCustomConfirm();
                if (confirmResolve) confirmResolve(true);
            });
            
            // Cerrar al hacer clic fuera del modal
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hideCustomConfirm();
                    if (confirmResolve) confirmResolve(false);
                }
            });
        }
        
        // Actualizar contenido
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        // Crear iconos de Lucide
        setTimeout(() => lucide.createIcons(), 50);
    });
}

// Función para ocultar modal de confirmación
function hideCustomConfirm() {
    const modal = document.getElementById('customConfirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
