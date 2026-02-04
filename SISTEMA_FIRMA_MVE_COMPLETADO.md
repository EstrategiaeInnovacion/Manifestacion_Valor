# Sistema de Firma Electrónica y Envío a VUCEM para Manifestación de Valor

## Resumen de Implementación

Se ha implementado un sistema completo de firma electrónica y envío a VUCEM para Manifestaciones de Valor (MV), similar al sistema existente de ConsultarEdocument pero adaptado específicamente para el proceso de MVE.

## 1. Estructura de Base de Datos

### Tabla: `mv_acuses`
Creada mediante migración `2026_01_29_184927_create_mv_acuses_table.php`

**Campos:**
- `id`: Identificador único
- `applicant_id`: FK a `mv_client_applicants`
- `datos_manifestacion_id`: FK a `mv_datos_manifestacion`
- `folio_manifestacion`: Folio único retornado por VUCEM
- `numero_pedimento`: Número de pedimento asociado
- `numero_cove`: Número de COVE asociado
- `xml_enviado`: XML enviado a VUCEM (TEXT)
- `xml_respuesta`: XML de respuesta de VUCEM (TEXT)
- `acuse_pdf`: PDF del acuse en base64 (LONGTEXT)
- `status`: Estado del acuse (ENVIADO/ACEPTADO/RECHAZADO)
- `mensaje_vucem`: Mensaje descriptivo de VUCEM
- `fecha_envio`: Fecha y hora del envío
- `fecha_respuesta`: Fecha y hora de la respuesta
- `timestamps`: created_at, updated_at

## 2. Modelo de Datos

### `app/Models/MvAcuse.php`
- **Relaciones:**
  - `applicant()`: belongsTo MvClientApplicant
  - `datosManifestacion()`: belongsTo MvDatosManifestacion
  
- **Campos fillable:** applicant_id, datos_manifestacion_id, folio_manifestacion, numero_pedimento, numero_cove, xml_enviado, xml_respuesta, acuse_pdf, status, mensaje_vucem, fecha_envio, fecha_respuesta

- **Casts:** 
  - fecha_envio: datetime
  - fecha_respuesta: datetime

## 3. Servicio de Firma

### `app/Services/MveSignService.php`

#### Métodos Principales:

**1. `firmarYEnviarManifestacion()`**
- Orquesta todo el proceso de firma y envío
- Valida los certificados
- Construye el XML
- Genera el sello digital
- Envía a VUCEM (o simula en modo prueba)
- Guarda el resultado en la base de datos

**2. `generarSelloDigital()`**
- Usa OpenSSL para firmar la cadena original
- Algoritmo: SHA256withRSA
- Formato de salida: Base64
- Maneja contraseñas de llave privada

**3. `getCertificadoBase64()`**
- Convierte el certificado .cer a base64
- Remueve headers PEM si existen
- Validación de formato

**4. `buildXmlManifestacion()`**
- Construye el XML conforme al schema de VUCEM
- Estructura: ManifestacionValor > DatosGenerales > InformacionCove > Documentos
- Incluye sello digital y certificado

**5. `enviarAVucem()`**
- Cliente SOAP para comunicación con VUCEM
- Endpoint: config('vucem.mv_endpoint')
- WSDL: config('vucem.mv_wsdl')
- Manejo de errores de conexión y respuesta
- Timeout configurable

**6. `guardarManifestacionPrueba()`**
- Modo de prueba sin envío real
- Simula respuesta exitosa de VUCEM
- Genera folio simulado
- Útil para testing

**7. `procesarRespuestaVucem()`**
- Procesa el XML de respuesta
- Extrae folio, status, mensaje
- Decodifica PDF del acuse (si existe)
- Guarda todo en tabla mv_acuses

## 4. Controlador

### `app/Http/Controllers/MveController.php`

#### Métodos Agregados:

**1. `showSign($manifestacionId)`**
- GET `/mve/firmar/{manifestacion}`
- Muestra formulario de firma
- Valida permisos del usuario
- Verifica estado de la manifestación (debe estar 'completa')
- Muestra información del acuse si ya existe

**2. `processSign(Request $request, $manifestacionId)`**
- POST `/mve/firmar/{manifestacion}`
- Procesa archivos de certificado (.cer, .key)
- Valida password de llave privada
- Llama a MveSignService::firmarYEnviarManifestacion()
- Redirige al acuse si es exitoso

**3. `showAcuse($manifestacionId)`**
- GET `/mve/acuse/{manifestacion}`
- Muestra detalles completos del acuse
- Información de envío y respuesta
- Links de descarga de PDF y XML

**4. `downloadAcusePdf($manifestacionId)`**
- GET `/mve/acuse/{manifestacion}/pdf`
- Descarga el PDF del acuse
- Decodifica base64 a binario
- Headers apropiados para descarga

**5. `downloadAcuseXml($manifestacionId)`**
- GET `/mve/acuse/{manifestacion}/xml`
- Descarga el XML de respuesta
- Headers application/xml

#### Método Modificado:

**`saveFinalManifestacion($applicantId)`**
- Ahora retorna `redirect_url` para ir a firma
- Cambia status de 'borrador' a 'completa'
- Retorna ID de manifestación guardada

## 5. Rutas

### `routes/web.php`

```php
// Rutas para firma y envío a VUCEM
Route::get('/mve/firmar/{manifestacion}', [MveController::class, 'showSign'])
    ->name('mve.firmar');
    
Route::post('/mve/firmar/{manifestacion}', [MveController::class, 'processSign'])
    ->name('mve.firmar.procesar');
    
Route::get('/mve/acuse/{manifestacion}', [MveController::class, 'showAcuse'])
    ->name('mve.acuse');
    
Route::get('/mve/acuse/{manifestacion}/pdf', [MveController::class, 'downloadAcusePdf'])
    ->name('mve.acuse.pdf');
    
Route::get('/mve/acuse/{manifestacion}/xml', [MveController::class, 'downloadAcuseXml'])
    ->name('mve.acuse.xml');
```

## 6. Vistas

### `resources/views/mve/sign.blade.php`
**Características:**
- Formulario de carga de certificados (.cer y .key)
- Input de password con toggle de visibilidad
- Drag & drop para archivos
- Indicadores de archivo seleccionado
- Información de la manifestación a firmar
- Estado del acuse previo (si existe)
- Loading state durante el envío
- Advertencia sobre modo prueba/producción
- Responsive design con Tailwind CSS

**Campos del formulario:**
- `certificado`: File input (.cer, .crt)
- `llave_privada`: File input (.key, .pem)
- `password_llave`: Password input

### `resources/views/mve/acuse.blade.php`
**Características:**
- Estado visual del acuse (ACEPTADO/RECHAZADO/ENVIADO)
- Información completa del envío
- Fechas de envío y respuesta
- Folio de manifestación
- Mensaje de VUCEM
- Información del solicitante
- Sección de descargas (PDF y XML)
- Botón de reintento si fue rechazado
- Función de impresión
- Diseño responsive

## 7. JavaScript

### `resources/js/mve-manual.js`

#### Función Modificada:
**`confirmarGuardadoFinal()`**
- Ahora redirige a `result.redirect_url` si está disponible
- Redirige a página de firma automáticamente después de guardar
- Timeout reducido a 1.5 segundos

## 8. Configuración

### `config/vucem.php`

**Nuevas configuraciones agregadas:**

```php
// Manifestación de Valor
'send_manifestation_enabled' => env('VUCEM_SEND_MANIFESTATION_ENABLED', false),

'mv_endpoint' => env('VUCEM_MV_ENDPOINT', 'https://privados.ventanillaunica.gob.mx:8104/IngresoManifestacionImpl/IngresoManifestacionService'),

'mv_wsdl' => env('VUCEM_MV_WSDL', 'https://privados.ventanillaunica.gob.mx/IngresoManifestacionImpl/IngresoManifestacionService?wsdl'),

'mv_consulta_endpoint' => env('VUCEM_MV_CONSULTA_ENDPOINT', 'https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService'),

'mv_consulta_wsdl' => env('VUCEM_MV_CONSULTA_WSDL', 'https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService?wsdl'),

'soap_timeout' => env('VUCEM_SOAP_TIMEOUT', 30),
```

### Variables de Entorno (.env)

```env
# Ya existe en el archivo .env
VUCEM_SEND_MANIFESTATION_ENABLED=false

# Agregar si se necesitan customizar (opcionales, usan defaults):
VUCEM_MV_ENDPOINT=https://privados.ventanillaunica.gob.mx:8104/IngresoManifestacionImpl/IngresoManifestacionService
VUCEM_MV_WSDL=https://privados.ventanillaunica.gob.mx/IngresoManifestacionImpl/IngresoManifestacionService?wsdl
VUCEM_MV_CONSULTA_ENDPOINT=https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService
VUCEM_MV_CONSULTA_WSDL=https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService?wsdl
VUCEM_SOAP_TIMEOUT=30
```

## 9. Flujo de Trabajo Completo

### 1. Usuario llena la manifestación
- Datos de Manifestación (Sección 1)
- Información COVE (Sección 2)
- Valor en Aduana (Sección 3)
- Documentos (Sección 4)

### 2. Vista previa y guardado
- Click en "Vista Previa"
- Verifica que todas las secciones estén completas
- Click en "Guardar Manifestación"
- Status cambia de 'borrador' a 'completa'

### 3. Redirección automática a firma
- Automáticamente redirige a `/mve/firmar/{id}`
- Muestra información de la manifestación

### 4. Subida de certificados
- Usuario sube archivo .cer (certificado)
- Usuario sube archivo .key (llave privada)
- Usuario ingresa password de la llave

### 5. Proceso de firma
- Validación de certificados
- Generación de cadena original
- Firma digital con OpenSSL SHA256
- Construcción de XML VUCEM
- Envío al endpoint de VUCEM (o simulación)

### 6. Procesamiento de respuesta
- VUCEM responde con XML
- Se extrae folio, status, mensaje
- Se decodifica PDF del acuse (si existe)
- Se guarda todo en tabla `mv_acuses`

### 7. Visualización del acuse
- Redirección a `/mve/acuse/{id}`
- Muestra estado (ACEPTADO/RECHAZADO)
- Opciones de descarga (PDF y XML)
- Opción de reintento si fue rechazado

## 10. Modos de Operación

### Modo Prueba (VUCEM_SEND_MANIFESTATION_ENABLED=false)
- **Comportamiento:** NO se envía a VUCEM real
- **Respuesta:** Simulada localmente con folio ficticio
- **Uso:** Testing, desarrollo, demostraciones
- **PDF:** No se genera (simulado)
- **Folio:** MV-TEST-{timestamp}

### Modo Producción (VUCEM_SEND_MANIFESTATION_ENABLED=true)
- **Comportamiento:** Envío REAL a VUCEM
- **Respuesta:** Real de servidores VUCEM
- **Uso:** Operación en producción
- **PDF:** Generado por VUCEM
- **Folio:** Asignado por VUCEM

## 11. Seguridad

### Validaciones Implementadas:
- ✅ Autenticación requerida en todas las rutas
- ✅ Verificación de propiedad del solicitante
- ✅ Validación de estado de manifestación antes de firmar
- ✅ Validación de tipos de archivo (mimes)
- ✅ Validación de password de llave privada
- ✅ Tokens CSRF en todos los formularios
- ✅ Sanitización de inputs
- ✅ Manejo seguro de certificados (no se almacenan)

### Datos Sensibles:
- **Certificados:** Solo se leen, NO se almacenan en BD
- **Passwords:** Solo se usan en memoria, NO se guardan
- **Llave privada:** Solo se usa para firma, luego se descarta

## 12. Manejo de Errores

### Errores Comunes y Respuestas:

**1. Certificado inválido**
```
Error: No se pudo leer el certificado
Solución: Verificar formato del archivo .cer
```

**2. Password incorrecta**
```
Error: No se pudo abrir la llave privada
Solución: Verificar contraseña
```

**3. Error de conexión VUCEM**
```
Error: No se pudo conectar con VUCEM
Solución: Verificar conectividad, endpoints
```

**4. Manifestación incompleta**
```
Error: La manifestación debe estar completa
Solución: Completar todas las secciones
```

**5. SOAP Fault**
```
Error: Captura SoapFault y extrae mensaje
Log: Registra en logs de Laravel
```

## 13. Logging y Auditoría

### Información Registrada:
- XML enviado (campo `xml_enviado`)
- XML respuesta (campo `xml_respuesta`)
- Timestamp de envío (`fecha_envio`)
- Timestamp de respuesta (`fecha_respuesta`)
- Status resultante
- Mensaje de VUCEM
- PDF del acuse (base64)

### Trazabilidad:
- Cada manifestación tiene un solo acuse
- FK a `applicant_id` y `datos_manifestacion_id`
- Folio único de VUCEM
- Histórico completo en tabla `mv_acuses`

## 14. Testing

### Para Probar el Sistema:

1. **Activar modo prueba:**
   ```env
   VUCEM_SEND_MANIFESTATION_ENABLED=false
   ```

2. **Crear una manifestación completa:**
   - Llenar todas las secciones
   - Guardar manifestación

3. **Probar firma:**
   - Usar certificados de prueba
   - Verificar que genera folio simulado
   - Verificar que crea registro en `mv_acuses`

4. **Verificar acuse:**
   - Ver página de acuse
   - Intentar descargar XML
   - Verificar datos guardados

5. **Para modo producción:**
   ```env
   VUCEM_SEND_MANIFESTATION_ENABLED=true
   ```
   - Usar certificados REALES de e.firma
   - Verificar conectividad con VUCEM
   - Probar con datos reales

## 15. Mantenimiento

### Tareas Periódicas:

**1. Limpiar acuses antiguos**
```sql
DELETE FROM mv_acuses WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

**2. Monitorear errores**
```bash
tail -f storage/logs/laravel.log | grep "VUCEM"
```

**3. Verificar espacio de BD**
- Campo `acuse_pdf` es LONGTEXT (puede crecer)
- Considerar archivo externo para PDFs grandes

### Optimizaciones Futuras:

- [ ] Cache de certificados durante sesión
- [ ] Cola de trabajos para envío asíncrono
- [ ] Notificaciones por email al recibir acuse
- [ ] Dashboard de estadísticas de envíos
- [ ] Integración con servicio de consulta de status
- [ ] Reintentos automáticos en caso de error temporal
- [ ] Compresión de XMLs almacenados

## 16. Documentación de API VUCEM

### Endpoint de Ingreso:
```
POST https://privados.ventanillaunica.gob.mx:8104/IngresoManifestacionImpl/IngresoManifestacionService
```

### Estructura XML de Solicitud:
```xml
<ManifestacionValor>
    <DatosGenerales>...</DatosGenerales>
    <InformacionCove>...</InformacionCove>
    <Documentos>...</Documentos>
    <Firma>
        <SelloDigital>...</SelloDigital>
        <Certificado>...</Certificado>
    </Firma>
</ManifestacionValor>
```

### Estructura XML de Respuesta:
```xml
<RespuestaManifestacion>
    <Folio>MV-123456789</Folio>
    <Status>ACEPTADO</Status>
    <Mensaje>Manifestación aceptada</Mensaje>
    <AcusePDF>[base64]</AcusePDF>
    <FechaRecepcion>2026-01-29T10:30:00</FechaRecepcion>
</RespuestaManifestacion>
```

## 17. Conclusión

El sistema está completamente implementado y listo para uso. Incluye:

- ✅ Base de datos completa
- ✅ Modelos y relaciones
- ✅ Servicio de firma con OpenSSL
- ✅ Controladores y rutas
- ✅ Vistas responsive
- ✅ JavaScript integrado
- ✅ Configuración flexible
- ✅ Modo prueba/producción
- ✅ Manejo de errores
- ✅ Seguridad implementada
- ✅ Logging y auditoría
- ✅ Descarga de acuses

**El flujo completo funciona exactamente como ConsultarEdocument pero adaptado para Manifestación de Valor.**

---
**Última actualización:** 29 de Enero 2026
**Versión:** 1.0.0
**Autor:** Sistema de Manifestación de Valor - SEI
