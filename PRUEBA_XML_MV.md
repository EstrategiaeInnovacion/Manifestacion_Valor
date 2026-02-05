# Sistema de Prueba XML SOAP para Manifestación de Valor (MVE)

## Descripción General

Este sistema permite validar que los datos guardados de una Manifestación de Valor coincidan correctamente con el archivo XML SOAP que se enviará a VUCEM, **sin realizar el envío real**. Es similar al método utilizado en la digitalización de documentos pero aplicado específicamente a las manifestaciones de valor.

## Características

### 1. Modo Prueba Activado
Cuando `VUCEM_SEND_MANIFESTATION_ENABLED=false` (configuración por defecto), el sistema:
- Genera el XML SOAP completo según el XSD de VUCEM
- Valida la estructura del XML
- Muestra el mapeo de campos BD → XSD
- **NO envía nada a VUCEM**

### 2. Nuevo Servicio: `MvVucemSoapService`
Ubicación: `app/Services/MvVucemSoapService.php`

Este servicio genera el XML SOAP exacto según la estructura definida en:
- `mve_vucem/vucem/Manifestacion_Valor/IngresoMV/IngresoManifestacionService.xsd`
- `mve_vucem/vucem/Manifestacion_Valor/IngresoMV/IngresoManifestacionService.wsdl`

### 3. Namespace de VUCEM
```
http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx
```

## Cómo Usar

### Acceso al Modo Prueba

1. **Desde la MVE**: En la pantalla de creación/edición de manifestación de valor, aparece el botón "Probar XML" (naranja) cuando hay datos guardados.

2. **URL Directa**: `/mve/test-soap/{applicant_id}`

### Funcionalidades Disponibles

#### Generar XML (sin firma)
- Genera el XML SOAP completo para visualización
- Muestra validación de estructura
- No requiere archivos de e.firma

#### Ver Mapeo de Campos
- Muestra la correspondencia entre campos de BD y XSD
- Visualiza los valores actuales de cada campo
- Referencia del XSD de VUCEM

#### Probar con e.Firma
- Sube archivos .cer y .key
- Genera el XML con firma real
- El XML **NO se envía** a VUCEM
- Guarda un acuse de prueba con folio `MVT*`

#### Descargar XML
- Descarga el último XML generado
- Útil para validación externa

## Rutas Disponibles

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/mve/test-soap/{applicant}` | Vista principal de prueba |
| POST | `/mve/test-soap/{applicant}/generate` | Generar XML (AJAX) |
| GET | `/mve/test-soap/{applicant}/mapping` | Ver mapeo de campos |
| POST | `/mve/test-soap/{applicant}/send` | Probar con firma (AJAX) |
| GET | `/mve/test-soap/{applicant}/download` | Descargar XML |

## Mapeo de Campos BD → XSD

| Campo BD | Campo XSD | Tipo |
|----------|-----------|------|
| `mv_datos_manifestacion.rfc_importador` | `importador-exportador/rfc` | string |
| `mv_datos_manifestacion.persona_consulta` | `datosManifestacionValor/personaConsulta[]` | PersonaConsulta[] |
| `mv_documentos.documentos` | `datosManifestacionValor/documentos[]` | Documento[] |
| `mv_informacion_cove.informacion_cove` | `datosManifestacionValor/informacionCove[]` | InformacionCove[] |
| `mv_informacion_cove.pedimentos` | `informacionCove/pedimento[]` | Pedimento[] |
| `mv_informacion_cove.precio_pagado` | `informacionCove/precioPagado[]` | PrecioPagado[] |
| `mv_informacion_cove.precio_por_pagar` | `informacionCove/precioPorPagar[]` | PrecioPorPagar[] |
| `mv_informacion_cove.compenso_pago` | `informacionCove/compensoPago[]` | CompensoPago[] |
| `mv_informacion_cove.incrementables` | `informacionCove/incrementables[]` | Incrementables[] |
| `mv_informacion_cove.decrementables` | `informacionCove/decrementables[]` | Decrementables[] |
| `mv_informacion_cove.valor_en_aduana` | `datosManifestacionValor/valorEnAduana` | ValorEnAduana |

## Estructura del XML SOAP Generado

```xml
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:mv="http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="...">
         <wsse:UsernameToken>
            <wsse:Username>RFC_USUARIO</wsse:Username>
            <wsse:Password Type="...">CLAVE_WEBSERVICE</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <mv:registroManifestacion>
         <mv:informacionManifestacion>
            <mv:firmaElectronica>
               <mv:certificado>BASE64...</mv:certificado>
               <mv:cadenaOriginal>||campos|separados|por|pipes||</mv:cadenaOriginal>
               <mv:firma>BASE64...</mv:firma>
            </mv:firmaElectronica>
            <mv:importador-exportador>
               <mv:rfc>RFC_IMPORTADOR</mv:rfc>
            </mv:importador-exportador>
            <mv:datosManifestacionValor>
               <!-- personaConsulta, documentos, informacionCove, valorEnAduana -->
            </mv:datosManifestacionValor>
         </mv:informacionManifestacion>
      </mv:registroManifestacion>
   </soapenv:Body>
</soapenv:Envelope>
```

## Configuración

En `.env`:
```env
# Habilitar/Deshabilitar envío real a VUCEM
VUCEM_SEND_MANIFESTATION_ENABLED=false  # Modo prueba (recomendado)
# VUCEM_SEND_MANIFESTATION_ENABLED=true  # Envío real (producción)

# Endpoint de VUCEM
VUCEM_MV_ENDPOINT=https://privados.ventanillaunica.gob.mx:8104/IngresoManifestacionImpl/IngresoManifestacionService
```

## Validaciones Realizadas

El sistema valida:
1. **XML Válido**: Sintaxis XML correcta
2. **SOAP Envelope**: Estructura SOAP correcta
3. **SOAP Header**: Autenticación WS-Security
4. **SOAP Body**: Contenido de la solicitud
5. **registroManifestacion**: Elemento principal presente

## Errores Comunes

- **eDocument vacío**: Falta folio de documento digitalizado
- **Número COVE vacío**: Falta el número de COVE
- **Datos incompletos**: Faltan secciones requeridas

## Archivos Creados/Modificados

### Nuevos Archivos
- `app/Services/MvVucemSoapService.php` - Servicio de generación XML SOAP
- `resources/views/mve/test-soap.blade.php` - Vista de prueba
- `resources/views/mve/field-mapping.blade.php` - Vista de mapeo de campos
- `PRUEBA_XML_MV.md` - Esta documentación

### Archivos Modificados
- `app/Http/Controllers/MveController.php` - Nuevos métodos de prueba
- `routes/web.php` - Nuevas rutas de prueba
- `resources/views/mve/create-manual.blade.php` - Botón de acceso a prueba
