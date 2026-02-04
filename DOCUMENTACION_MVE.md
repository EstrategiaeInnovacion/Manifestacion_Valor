# Manifestación de Valor en Aduana (MVE)

Sistema desarrollado en Laravel para la gestión de Manifestaciones de Valor en Aduana con cumplimiento automático de estándares VUCEM.

## ✨ Funcionalidades Principales

### 📄 Gestión de Documentos PDF con Validación VUCEM
- **Validación automática** de requisitos VUCEM (PDF 1.4, escala de grises, 300 DPI, sin encriptación)
- **Conversión automática** de PDFs que no cumplen requisitos
- **Subida múltiple** de documentos con preview y estado de validación
- **Gestión completa** (descarga, eliminación individual y por lotes)

### 🏢 Gestión de Solicitantes
- Registro y administración de solicitantes
- Validación de RFC y datos fiscales
- Historial de manifestaciones por solicitante

### 📋 Manifestación de Valor
- **Datos de Manifestación**: Información básica del trámite
- **Información COVE**: Datos de comercio exterior con validación automática
- **Valor en Aduana**: Cálculos de incrementables, decrementables, precios pagados/por pagar
- **Sistema de borradores**: Guardado automático por secciones
- **Cadena Original VUCEM**: Generación automática de cadena original siguiendo especificaciones del XSD

### 🔐 Cadena Original VUCEM
- **Estructura completa**: Implementación conforme al XSD de VUCEM
- **Dos operaciones**: `registroManifestacion` y `actualizarManifestacion`
- **Formato estándar**: `||campo1|campo2|...||` con campos vacíos preservados
- **Orden estricto**: Siguiendo la secuencia del XSD para compatibilidad
- **Documentación completa**: Ver `CADENA_ORIGINAL_MVE.md` para detalles técnicos

### 🔄 Integración con Servicios Externos  
- **API de Banxico**: Consulta automática de tipos de cambio
- **Validación RFC**: Verificación de existencia y estatus de RFCs
- **Cache inteligente**: Optimización de consultas recurrentes

## ⚙️ Configuración de Herramientas PDF

### Verificar Configuración
```bash
php artisan pdf:check-tools
```

### Variables de Entorno (`.env`)
```env
# Herramientas PDF para conversión VUCEM
GHOSTSCRIPT_PATH="gswin64c.exe"          # Windows: ruta a Ghostscript
PDFIMAGES_PATH="pdfimages"               # Linux/Mac: comando pdfimages

# Configuración PDF
PDF_MAX_SIZE_MB=50                       # Tamaño máximo entrada (MB)
PDF_OUTPUT_DPI=300                       # DPI de salida
PDF_TARGET_VERSION=1.4                   # Versión PDF objetivo

# API Externa
BANXICO_TOKEN=tu_token_de_banxico        # Token para consultas de tipo de cambio
```

### Instalación de Dependencias PDF

#### Windows
1. **Ghostscript**: Descargar desde [ghostscript.com](https://www.ghostscript.com/download/gsdnld.html)
2. **Poppler Utils** (opcional): Para mejor validación de calidad

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get install ghostscript poppler-utils
```

#### macOS
```bash
brew install ghostscript poppler
```

## 🚀 Instalación del Proyecto

### 1. Clonar repositorio
```bash
git clone [url-repositorio]
cd Manifestacion_Valor
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Configurar entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar base de datos
```bash
# Configurar .env con datos de BD
php artisan migrate
```

### 5. Verificar herramientas PDF
```bash
php artisan pdf:check-tools
```

### 6. Compilar assets
```bash
npm run build
# Para desarrollo: npm run dev
```

### 7. Iniciar servidor
```bash
php artisan serve
```

## 📋 Uso del Sistema

### 1. Crear Solicitante
- Acceder a "Gestión de Solicitantes"
- Registrar empresa con RFC válido
- Completar datos de contacto

### 2. Crear Manifestación de Valor
- Seleccionar solicitante existente
- Completar formulario por secciones:
  - **Datos Manifestación**: Tipo figura, método valoración, observaciones
  - **Información COVE**: Incoterms, aduana, pedimentos, incrementables/decrementables
  - **Valor Aduana**: Cálculos detallados de precios y totales
  - **Documentos**: Subir PDFs con conversión automática a formato VUCEM

### 3. Gestión de Documentos PDF
- **Subir**: Arrastar archivos PDF o hacer clic para seleccionar
- **Validación**: Automática según estándares VUCEM
- **Conversión**: Si no cumple requisitos, se convierte automáticamente
- **Estado Visual**: 
  - ✅ **Válido VUCEM**: Cumple todos los requisitos
  - ⚠️ **Convertido**: Fue convertido automáticamente al formato correcto

### 4. Sistema de Borradores
- **Guardado automático** por secciones
- **Recuperación** de trabajo previo
- **Gestión de borradores** pendientes desde dashboard

## 🔧 Arquitectura Técnica

### Servicios Principales
- **`DocumentUploadService`**: Procesamiento completo de PDFs
- **`VucemPdfConverter`**: Conversión específica para cumplir VUCEM
- **`BanxicoService`**: Integración con tipos de cambio oficiales

### Controladores
- **`MveController`**: Gestión completa de manifestaciones
- **`DocumentUploadController`**: APIs para manejo de documentos
- **`ApplicantController`**: Administración de solicitantes

### Modelos de Datos
- **`MvClientApplicant`**: Información de solicitantes
- **`MvDatosManifestacion`**: Datos básicos de la manifestación
- **`MvInformacionCove`**: Información específica de comercio exterior
- **`MvValorAduana`**: Cálculos y valores de aduana
- **`MvDocumentos`**: Documentos adjuntos con metadatos VUCEM

## 🌐 API Endpoints

### Documentos
```http
POST   /documents/upload              # Subir y validar documento PDF
GET    /documents/applicant/{id}      # Listar documentos de un solicitante
DELETE /documents/{id}               # Eliminar documento específico
GET    /documents/download/{id}       # Descargar documento procesado
POST   /documents/validate-preview    # Validar PDF sin guardarlo
```

### Manifestaciones
```http
GET    /mve/manual/{applicant}                      # Formulario de manifestación
POST   /mve/save-datos-manifestacion/{applicant}    # Guardar datos básicos
POST   /mve/save-informacion-cove/{applicant}       # Guardar info comercio exterior
POST   /mve/save-valor-aduana/{applicant}           # Guardar cálculos de valor
DELETE /mve/borrar-borrador                         # Eliminar borrador
```

## 📊 Validaciones VUCEM Automáticas

El sistema garantiza que todos los PDFs cumplan con:

- **✅ Versión PDF 1.4** (requerida por VUCEM)
- **✅ Escala de grises** (sin colores)
- **✅ 300 DPI exactos** (calidad específica)
- **✅ Tamaño máximo 3MB** (límite VUCEM)
- **✅ Sin encriptación** (acceso libre)

Si un PDF no cumple algún requisito, **se convierte automáticamente** manteniendo la calidad y contenido original.

## 🛠️ Comandos Útiles

```bash
# Verificar herramientas PDF configuradas
php artisan pdf:check-tools

# Limpiar cache de aplicación
php artisan cache:clear
php artisan config:clear

# Ejecutar migraciones
php artisan migrate

# Compilar assets para producción
npm run build

# Modo desarrollo con hot reload
npm run dev
```

## 📝 Logs y Depuración

- **Laravel Logs**: `storage/logs/laravel.log`
- **Errores PDF**: Se registran automáticamente con contexto completo
- **Debug Mode**: Configurar `APP_DEBUG=true` en `.env` para desarrollo

## 🔒 Seguridad

- **Autenticación Laravel**: Sistema completo de usuarios
- **Validación de RFC**: Verificación contra base de datos oficial
- **Encriptación de datos**: Información sensible protegida
- **Validación de archivos**: Solo PDFs, límites de tamaño, verificación de integridad

---

**💻 Desarrollado con Laravel 11 + Tailwind CSS + JavaScript ES6**
**🎯 Especializado en cumplimiento VUCEM automático**