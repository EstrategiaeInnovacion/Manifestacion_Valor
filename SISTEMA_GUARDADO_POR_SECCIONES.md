# Sistema de Guardado por Secciones - MVE

## 📋 DESCRIPCIÓN GENERAL

El sistema de Manifestación de Valor Electrónica (MVE) ahora utiliza un enfoque modular donde cada sección del formulario se guarda de manera independiente en su propia tabla de base de datos. Esto evita mezclar datos y permite un control granular del progreso de cada sección.

## 🗂️ ESTRUCTURA DE TABLAS

### 1. **mv_datos_manifestacion**
Almacena los datos generales de la manifestación:
- `rfc_importador`
- `metodo_valoracion`
- `existe_vinculacion`
- `pedimento`
- `patente`
- `aduana`
- `persona_consulta` (JSON encriptado)

**Modelo:** `MvDatosManifestacion`

### 2. **mv_informacion_cove**
Almacena la información de los Comprobantes de Valor Electrónico:
- `informacion_cove` (JSON encriptado - array de COVEs)
- `pedimentos` (JSON encriptado)
- `incrementables` (JSON encriptado)
- `decrementables` (JSON encriptado)
- `precio_pagado` (JSON encriptado)
- `precio_por_pagar` (JSON encriptado)
- `compenso_pago` (JSON encriptado)

**Modelo:** `MvInformacionCove`

### 3. **mv_valor_aduana**
Almacena los valores y montos aduanales:
- `valor_en_aduana` (JSON encriptado - totales)

**Modelo:** `MvValorAduana`

### 4. **mv_documentos**
Almacena los documentos adjuntos:
- `documentos` (JSON encriptado - array de documentos)

**Modelo:** `MvDocumentos`

## 🔐 SEGURIDAD

Todas las tablas incluyen:
- Encriptación automática de todos los campos usando `Crypt::encrypt/decrypt`
- Campo `status` para identificar si es 'borrador' o 'completado'
- Relación `belongsTo` con `MvClientApplicant`
- Índices para optimizar búsquedas por `applicant_id` y `status`

## 🎯 BOTONES DE GUARDADO

Cada sección del formulario tiene su propio botón de guardado:

### Sección 1: Datos de Manifestación
**Botón:** "Guardar Datos de Manifestación"
**Función JS:** `saveDatosManifestacion()`
**Endpoint:** `POST /mve/save-datos-manifestacion/{applicant}`
**Controlador:** `MveController@saveDatosManifestacion`

### Sección 2: Información COVE
**Botón:** "Guardar Información COVE"
**Función JS:** `saveInformacionCove()`
**Endpoint:** `POST /mve/save-informacion-cove/{applicant}`
**Controlador:** `MveController@saveInformacionCove`

### Sección 3: Valor en Aduana
**Botón:** "Guardar Valor en Aduana"
**Función JS:** `saveValorAduana()`
**Endpoint:** `POST /mve/save-valor-aduana/{applicant}`
**Controlador:** `MveController@saveValorAduana`

### Sección 4: Documentos
**Botón:** "Guardar Documentos"
**Función JS:** `saveDocumentos()`
**Endpoint:** `POST /mve/save-documentos/{applicant}`
**Controlador:** `MveController@saveDocumentos`

## 🔄 FLUJO DE GUARDADO

1. Usuario llena datos en una sección específica
2. Hace clic en el botón "Guardar [Nombre de la Sección]"
3. JavaScript recolecta los datos de esa sección
4. Envía petición POST al endpoint correspondiente
5. Controlador valida y guarda/actualiza en la tabla específica
6. Retorna mensaje de éxito o error
7. Usuario puede continuar con otra sección

## 📊 VENTAJAS DEL SISTEMA MODULAR

✅ **Separación de datos:** Cada sección tiene su propia tabla
✅ **Control granular:** Se puede guardar cada sección independientemente
✅ **Mejor organización:** Código más limpio y mantenible
✅ **Escalabilidad:** Fácil agregar nuevas secciones
✅ **Rendimiento:** Consultas más eficientes al buscar datos específicos
✅ **Seguridad:** Encriptación a nivel de campo en cada modelo
✅ **Flexibilidad:** Usuario puede guardar secciones en cualquier orden

## 🛠️ MÉTODOS DEL CONTROLADOR

### saveDatosManifestacion(Request $request, $applicantId)
- Verifica permisos del usuario
- Guarda/actualiza RFC importador, método valoración, vinculación, pedimento, patente, aduana, y personas de consulta
- Retorna JSON con `success`, `message`, y `section_id`

### saveInformacionCove(Request $request, $applicantId)
- Verifica permisos del usuario
- Guarda/actualiza COVEs, pedimentos, incrementables, decrementables, precio pagado, precio por pagar y compenso pago
- Retorna JSON con `success`, `message`, y `section_id`

### saveValorAduana(Request $request, $applicantId)
- Verifica permisos del usuario
- Guarda/actualiza totales de valor en aduana
- Retorna JSON con `success`, `message`, y `section_id`

### saveDocumentos(Request $request, $applicantId)
- Verifica permisos del usuario
- Guarda/actualiza array de documentos adjuntos
- Retorna JSON con `success`, `message`, y `section_id`

## 📝 EJEMPLO DE GUARDADO

```javascript
// Usuario hace clic en "Guardar Datos de Manifestación"
async function saveDatosManifestacion() {
    const data = {
        rfc_importador: 'ABC123456XYZ',
        metodo_valoracion: 'VALADU.VTM',
        existe_vinculacion: '0',
        pedimento: '26 3124 0001234',
        patente: '1234',
        aduana: '02 Tijuana',
        persona_consulta: [...]
    };
    
    // Envía solo los datos de esta sección
    await saveSection('datos-manifestacion', data, 'Datos de Manifestación');
}
```

## 🔮 PRÓXIMOS PASOS

1. Implementar vista de "MVE Pendientes" para mostrar progreso por sección
2. Agregar indicadores visuales del estado de cada sección
3. Implementar validaciones específicas por sección
4. Crear sistema de autoguardado cada X minutos
5. Agregar histórico de cambios por sección

## 📌 NOTAS IMPORTANTES

- Los datos se guardan como "borrador" hasta que el usuario complete y envíe toda la MVE
- Cada tabla mantiene solo UN registro en borrador por `applicant_id`
- Al guardar una sección, se usa `updateOrCreate` para actualizar si ya existe
- Todos los datos JSON se encriptan automáticamente antes de guardarse
- Las funciones JavaScript utilizan async/await para mejor manejo de errores
