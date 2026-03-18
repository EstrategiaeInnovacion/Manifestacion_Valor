# Referencia de Controllers — MVE

> **Tipo Diátaxis:** Reference + Explanation — descripción técnica e impacto de cada controller HTTP.  
> **Audiencia:** Desarrolladores del equipo que necesitan conocer qué hace cada controller, sus rutas, roles requeridos, comportamientos clave, y qué consecuencias tiene modificar las partes críticas.

---

## Tabla de contenido

| Controller | Responsabilidad |
|---|---|
| [AdminSettingsController](#adminsettingscontroller) | Configuración del sistema |
| [AdminStatsController](#adminstatscontroller) | Estadísticas y métricas VUCEM |
| [AnnouncementController](#announcementcontroller) | Avisos generales a usuarios |
| [ApplicantController](#applicantcontroller) | CRUD de solicitantes (clientes) |
| [DigitalizacionController](#digitalizacioncontroller) | Digitalización de documentos en VUCEM |
| [DocumentUploadController](#documentuploadcontroller) | Subida y conversión de PDFs para MVE |
| [EDocumentConsultaController](#edocumentconsultacontroller) | Consulta de COVE/eDocuments en VUCEM |
| [LicenseController](#licensecontroller) | Gestión de licencias de Admin |
| [MveController](#mvecontroller) | Flujo principal de Manifestación de Valor Exterior |
| [ProfileController](#profilecontroller) | Perfil del usuario autenticado |
| [SupportController](#supportcontroller) | Envío de solicitudes de soporte técnico |
| [TicketController](#ticketcontroller) | Gestión y seguimiento de tickets de soporte |
| [UserManagementController](#usermanagementcontroller) | CRUD de usuarios del sistema |
| [UserManualController](#usermanualcontroller) | Manuales de usuario en PDF |
| [VucemValidatorController](#vucemvalidatorcontroller) | Validación de PDFs según requisitos VUCEM |

---

## AdminSettingsController

**Archivo:** `app/Http/Controllers/AdminSettingsController.php`  
**Rol requerido:** `SuperAdmin`

Gestiona la configuración global de la aplicación: textos legales (aviso de privacidad, condiciones de uso), banner de notificaciones, manuales y avisos.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/admin/settings` | `admin.settings` | `index()` |
| `PATCH` | `/admin/settings` | `admin.settings.update` | `update()` |

#### `index()`
Recupera y pasa a la vista `admin.settings` los siguientes datos:
- `aviso_privacidad_sellos` y `aviso_privacidad_completo` — textos del aviso de privacidad.
- `condiciones_uso` — texto de condiciones de uso.
- `manuals` — manuales de usuario ordenados por fecha de creación.
- `announcements` — avisos generales con su creador.
- `bannerEnabled` / `bannerMessage` — estado y contenido del banner global.

#### `update(Request $request)`
Actualiza un valor de configuración en `AppSetting`. La clave debe ser una de las siguientes (validación estricta):
- `aviso_privacidad_sellos`
- `aviso_privacidad_completo`
- `condiciones_uso`
- `banner_message`
- `banner_enabled`

Redirige de vuelta con mensaje de éxito.

---

## AdminStatsController

**Archivo:** `app/Http/Controllers/AdminStatsController.php`  
**Rol requerido:** `SuperAdmin`

Centraliza métricas operativas del sistema: errores VUCEM, tickets de soporte, usuarios y confirmaciones de envío.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/admin/estadisticas` | `admin.estadisticas` | `index()` |

#### `index()`
Compila estadísticas de los últimos 7 y 30 días para la vista `admin.estadisticas`:

- **VUCEM:** total de errores, tasa de error, errores por día (gráfica), agrupados por servicio y tipo de error, top 5 usuarios con más errores.
- **Tickets de soporte:** abiertos, en progreso, resueltos.
- **Usuarios:** total de usuarios únicos.
- **Diagnóstico VUCEM:** estado del servicio vía `VucemDiagnosticService`.

Los datos de la gráfica diaria se construyen iterando los 7 días anteriores, cruzando errores (`VucemErrorLog`) y envíos exitosos (`MvAcuse`).

---

## AnnouncementController

**Archivo:** `app/Http/Controllers/AnnouncementController.php`  
**Rol requerido:** `SuperAdmin` para crear/eliminar. Todos los autenticados para marcar como leído.

Gestiona avisos globales que se muestran a todos los usuarios y se envían por correo al momento de publicarse.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `POST` | `/announcements` | `announcements.store` | `store()` |
| `DELETE` | `/announcements/{announcement}` | `announcements.destroy` | `destroy()` |
| `POST` | `/announcements/{announcement}/read` | `announcements.read` | `markRead()` |

#### `store(Request $request)`
Crea un aviso y envía el correo `AnnouncementMail` a **todos** los usuarios registrados (`User::all()`).  
Campos requeridos: `title` (máx. 255), `body` (máx. 10 000 caracteres).

#### `destroy(Announcement $announcement)`
Elimina el aviso de la base de datos. No hay soft-delete.

#### `markRead(Announcement $announcement)`
Registra en `AnnouncementRead` que el usuario autenticado leyó el aviso (idempotente con `firstOrCreate`). Responde JSON `{ "ok": true }`.

---

## ApplicantController

**Archivo:** `app/Http/Controllers/ApplicantController.php`  
**Rol requerido:** `SuperAdmin` / `Admin` para crear y administrar. `Usuario` solo ve los solicitantes asignados.

Gestiona el CRUD de `MvClientApplicant` (solicitantes/clientes). Contiene información sensible: credenciales VUCEM, RFC, certificados FIEL.

### Métodos (Resource Controller)

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/applicants` | `applicants.index` | `index()` |
| `GET` | `/applicants/create` | `applicants.create` | `create()` |
| `POST` | `/applicants` | `applicants.store` | `store()` |
| `GET` | `/applicants/{applicant}` | `applicants.show` | `show()` |
| `GET` | `/applicants/{applicant}/edit` | `applicants.edit` | `edit()` |
| `PUT/PATCH` | `/applicants/{applicant}` | `applicants.update` | `update()` |
| `DELETE` | `/applicants/{applicant}` | `applicants.destroy` | `destroy()` |

#### Reglas de visibilidad por rol

| Rol | Qué ve en `index()` |
|---|---|
| `SuperAdmin` | Solo los solicitantes que él mismo creó (+ legacy por `user_email`) |
| `Admin` | Solo los solicitantes que él mismo creó (+ legacy por `user_email`) |
| `Usuario` | Solo los solicitantes asignados explícitamente o por `user_email` |

> **Nota:** No existe visibilidad cruzada entre distintos Admins. Los solicitantes contienen credenciales VUCEM sensibles.

#### `store()` — Límite de solicitantes
Para el rol `Admin`, se aplica un límite de solicitantes definido por su licencia activa. Si se excede, se redirige con error.

---

## DigitalizacionController

**Archivo:** `app/Http/Controllers/DigitalizacionController.php`  
**Rol requerido:** Usuarios autenticados con licencia activa.

Permite digitalizar (registrar en VUCEM) documentos como el eDocument/COVE. Inyecta `DigitalizarDocumentoService` y `DocumentUploadService`.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/digitalizacion` | `digitalizacion.create` | `create()` |
| `POST` | `/digitalizacion` | `digitalizacion.store` | `store()` |

#### `create()`
Carga la vista `digitalizacion.create` con:
- Solicitantes del usuario (por `getApplicantOwnerEmail()`).
- Flags de credenciales VUCEM configuradas (sin desencriptar valores completos).
- Catálogo de tipos de documento.
- Historial de los últimos 20 eDocuments del solicitante.

#### `store(Request $request)`
Flujo:
1. Valida `applicant_id`.
2. Aplica reglas dinámicas: si el solicitante ya tiene credenciales guardadas, los campos de certificado/key/password son opcionales.
3. Envía el documento a VUCEM usando `DigitalizarDocumentoService`.
4. Si tiene éxito, registra el folio en `EdocumentRegistrado`.

---

## DocumentUploadController

**Archivo:** `app/Http/Controllers/DocumentUploadController.php`  
**Rol requerido:** Usuarios autenticados con licencia activa.

Gestiona la subida de documentos PDF para adjuntarlos a una MVE. Delega el procesamiento a `DocumentUploadService` (validación de formato, conversión a PDF/A-1b compatible con VUCEM).

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `POST` | `/mve/upload-document` | `mve.upload-document` | `uploadDocument()` |
| `DELETE` | `/mve/documents/{document}` | `mve.delete-document` | `deleteDocument()` |

#### `uploadDocument(Request $request)`
Campos requeridos:
- `applicant_id` — debe pertenecer al usuario autenticado (verificación de acceso).
- `document_name` — nombre del documento (máx. 255 caracteres).
- `document_file` — archivo PDF. El tamaño máximo se lee de `config('pdftools.max_size_mb')`.

Responde con JSON `{ success, document_id, ... }` o `{ success: false, error: '...' }` con HTTP 4xx/5xx.

#### `deleteDocument(MvDocumentos $document)`
Elimina el documento de la base de datos y del almacenamiento local. Verifica que pertenezca al usuario autenticado antes de eliminar.

---

## EDocumentConsultaController

**Archivo:** `app/Http/Controllers/EDocumentConsultaController.php`  
**Rol requerido:** Usuarios autenticados con licencia activa.

Permite consultar un COVE (Comprobante de Valor Electrónico) directamente desde VUCEM usando el folio de eDocument, e importar sus valores/mercancías a una MVE.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/edocument/consulta` | `edocument.consulta` | `index()` |
| `GET` | `/edocument/check-credentials/{applicant}` | `edocument.check-credentials` | `checkCredentials()` |
| `POST` | `/edocument/consultar` | `edocument.consultar` | `consultar()` |

#### `checkCredentials(int $applicant)`
Verifica si un solicitante tiene credenciales VUCEM almacenadas sin exponer los valores encriptados. Responde JSON con flags booleanos: `has_cert`, `has_key`, `has_fiel_pass`, `has_ws_key`.

Reglas de acceso por rol:

| Rol | Alcance de búsqueda |
|---|---|
| `SuperAdmin` | Todos los solicitantes de su empresa |
| `Admin` | Sus solicitantes (creados o legacy por `user_email`) |
| `Usuario` | Solo solicitantes asignados |

#### `consultar()`
Usa `ConsultarEdocumentService` para llamar al web service de VUCEM. Los resultados se cachean temporalmente (vía `Cache`) para evitar consultas duplicadas. Si el COVE ya fue consultado anteriormente, actualiza `fecha_ultima_consulta` en `EdocumentRegistrado`.

---

## LicenseController

**Archivo:** `app/Http/Controllers/LicenseController.php`  
**Rol requerido:** `SuperAdmin` exclusivamente.

Gestiona el ciclo de vida de licencias asignadas a usuarios con rol `Admin`. Una licencia define el período de acceso al sistema y los límites operativos del Admin.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/admin/licenses` | `admin.licenses.index` | `index()` |
| `POST` | `/admin/licenses` | `admin.licenses.store` | `store()` |
| `POST` | `/admin/licenses/{license}/renew` | `admin.licenses.renew` | `renew()` |
| `PATCH` | `/admin/licenses/{license}/revoke` | `admin.licenses.revoke` | `revoke()` |
| `PATCH` | `/admin/licenses/limits/{user}` | `admin.licenses.limits` | `updateLimits()` |

#### `store(Request $request)`
Pasos:
1. Valida `admin_id` (debe ser rol `Admin`) y `duration_type` (`1min`, `1month`, `6months`, `1year`).
2. Revoca la licencia activa anterior del Admin.
3. Crea nueva licencia con `License::generateKey()` y calcula `expires_at` con `License::calculateExpiration()`.
4. Registra el evento en log.
5. Envía `LicenseAssigned` por correo al Admin.

#### `renew(Request $request, License $license)`
Revoca la licencia actual y crea una nueva con la duración indicada. Mismo flujo de notificación por correo.

#### `revoke(License $license)`
Cambia el status de la licencia a `revoked` sin eliminarla.

#### `updateLimits(Request $request, User $user)`
Actualiza los límites operativos del Admin (ej. número máximo de solicitantes).

---

## MveController

**Archivo:** `app/Http/Controllers/MveController.php`  
**Rol requerido:** Usuarios autenticados con licencia activa.

Controller principal del flujo de **Manifestación de Valor Exterior (MVE)**. Orquesta la creación, edición, guardado, firma y envío a VUCEM de una MVE.

### Métodos principales

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/mve/select-applicant` | `mve.select-applicant` | `selectApplicant()` |
| `GET` | `/mve/manual/{applicant}` | `mve.create-manual` | `createManual()` |
| `GET` | `/mve/archivo-m/{applicant}` | `mve.upload-file` | `createWithFile()` |
| `POST` | `/mve/save-datos` | `mve.save-datos` | `saveDatos()` |
| `POST` | `/mve/save-cove` | `mve.save-cove` | `saveCove()` |
| `POST` | `/mve/save-documentos` | `mve.save-documentos` | `saveDocumentos()` |
| `POST` | `/mve/submit/{applicant}/{mve}` | `mve.submit` | `submit()` |
| `GET` | `/mve/pendientes` | `mve.pendientes` | `pendientes()` |
| `GET` | `/mve/completadas` | `mve.completadas` | `completadas()` |
| `GET` | `/mve/acuse/{acuse}` | `mve.acuse` | `showAcuse()` |
| `DELETE` | `/mve/{mve}` | `mve.destroy` | `destroy()` |

#### `getAccessibleApplicants()` — método privado
Aplica filtros de visibilidad según rol del usuario autenticado:

| Rol | Alcance |
|---|---|
| `SuperAdmin` | Todos los solicitantes |
| `Admin` | Sus solicitantes (creados o legacy por `user_email`) |
| `Usuario` | Solicitantes asignados o por `user_email` |

#### `createManual($applicantId)`
Carga el formulario de creación de MVE en 3 pasos. Si se pasa `?edit={id}`, carga una MVE existente en estado `borrador`, `guardado` o `rechazado`. Determina el `$initialStep` según las secciones ya guardadas.

Pasa a la vista los catálogos VUCEM:
- `tiposFigura`, `metodosValoracion`, `incoterms`, `aduanas`, `incrementables`, `decrementables`, `formasPago`, `tiposDocumentoMve`.

#### `submit($applicant, $mve)`
Flujo de envío:
1. Verifica acceso al solicitante con `canAccessApplicant()`.
2. Llama a `ManifestacionValorService` para generar el XML.
3. Firma con `MveSignService` usando la FIEL del solicitante.
4. Envía a VUCEM vía web service.
5. Guarda el acuse en `MvAcuse`.
6. Envía correo `MveSubmitted` al usuario.
7. Registra diagnóstico en `VucemDiagnosticService`.

---

## ProfileController

**Archivo:** `app/Http/Controllers/ProfileController.php`  
**Rol requerido:** Cualquier usuario autenticado.

Gestiona el perfil del usuario autenticado: actualización de datos personales y eliminación de cuenta.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/profile` | `profile.edit` | `edit()` |
| `PATCH` | `/profile` | `profile.update` | `update()` |
| `DELETE` | `/profile` | `profile.destroy` | `destroy()` |
| `PATCH` | `/profile/preferences` | `profile.preferences` | `updatePreferences()` |

#### `updatePreferences()`
Permite guardar preferencias de UI del usuario (ej. tema, idioma) sin modificar datos de cuenta.

---

## SupportController

**Archivo:** `app/Http/Controllers/SupportController.php`  
**Rol requerido:** Cualquier usuario autenticado.

Gestiona el envío inicial de una solicitud de soporte técnico, creando un ticket y enviando el correo al equipo de soporte.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `POST` | `/support/send` | `support.send` | `send()` |

#### `send(Request $request)`
Campos validados:
- `category` — categoría del problema (máx. 100 caracteres).
- `subject` — asunto (máx. 255 caracteres).
- `description` — descripción detallada (máx. 5 000 caracteres).
- `screenshots` — hasta 5 imágenes (jpeg, png, jpg, gif, webp, máx. 10 MB cada una).

Flujo:
1. Crea `SupportTicket` con status `open`.
2. Si hay capturas de pantalla, crea un `SupportTicketMessage` inicial y adjunta los archivos en `support-attachments/ticket-{id}/`.
3. Convierte las imágenes a adjuntos Microsoft Graph (base64) para el correo.
4. Envía `SupportRequest` mail al equipo de soporte.

---

## TicketController

**Archivo:** `app/Http/Controllers/TicketController.php`  
**Rol requerido:** Cualquier usuario autenticado. `SuperAdmin` tiene acceso a todos los tickets.

Gestiona el ciclo completo de los tickets de soporte: listado, detalle, respuestas y cambios de estado.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/tickets` | `tickets.index` | `index()` |
| `GET` | `/tickets/{ticket}` | `tickets.show` | `show()` |
| `POST` | `/tickets/{ticket}/respond` | `tickets.respond` | `respond()` |
| `PATCH` | `/tickets/{ticket}/status` | `tickets.status` | `updateStatus()` |
| `GET` | `/tickets/attachment/{attachment}` | `tickets.attachment` | `downloadAttachment()` |
| `POST` | `/tickets/{ticket}/cancel` | `tickets.cancel` | `cancel()` |

#### Visibilidad en `index()`

| Rol | Qué ve |
|---|---|
| `SuperAdmin` | Todos los tickets excepto los cancelados (filtrables por status) |
| `Admin` / `Usuario` | Solo sus propios tickets |

Paginación: 20 por página.

#### `show(SupportTicket $ticket)`
Solo el dueño del ticket o el `SuperAdmin` pueden verlo. Carga relaciones `user`, `messages.sender`, `messages.attachments`.

#### `respond()`
Agrega un mensaje al ticket. Si el respondente es `SuperAdmin`, el mensaje se marca como `is_support_response = true` y se envía `TicketResponseMail` al usuario propietario.

#### `updateStatus()`
Solo `SuperAdmin` puede cambiar el estado del ticket (`open`, `in_progress`, `resolved`).

#### `cancel()`
Permite al usuario propietario cancelar su propio ticket (cambia status a `cancelled`).

---

## UserManagementController

**Archivo:** `app/Http/Controllers/UserManagementController.php`  
**Rol requerido:** `SuperAdmin` y `Admin`.

Gestiona el CRUD de usuarios del sistema. Los roles y permisos de creación están restringidos por jerarquía.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/users` | `users.index` | `index()` |
| `GET` | `/users/add` | `users.create` | `create()` |
| `POST` | `/users` | `users.store` | `store()` |
| `GET` | `/users/{user}/edit` | `users.edit` | `edit()` |
| `PUT` | `/users/{user}` | `users.update` | `update()` |
| `DELETE` | `/users/{user}` | `users.destroy` | `destroy()` |

#### Reglas de `index()` por rol

| Rol | Qué ve |
|---|---|
| `SuperAdmin` | Todos los usuarios separados por rol (SuperAdmins, Admins, Usuarios) |
| `Admin` | Solo los usuarios que él creó |

#### `store()` — Roles disponibles por creador

| Creador | Roles que puede asignar |
|---|---|
| `SuperAdmin` | `SuperAdmin`, `Admin`, `Usuario` |
| `Admin` | Solo `Usuario` |

Al crear un usuario se envía `WelcomeNewUser` por correo con las credenciales temporales.

---

## UserManualController

**Archivo:** `app/Http/Controllers/UserManualController.php`  
**Rol requerido:** `SuperAdmin` para subir/eliminar. Todos los autenticados para ver.

Gestiona los manuales de usuario en formato PDF, almacenados en `storage/app/manuals/`.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/manuals` | `manuals.index` | `index()` |
| `GET` | `/manuals/{manual}` | `manuals.show` | `show()` |
| `POST` | `/manuals` | `manuals.store` | `store()` |
| `DELETE` | `/manuals/{manual}` | `manuals.destroy` | `destroy()` |

#### `store(Request $request)`
Campos requeridos: `version` (máx. 50 caracteres), `manual` (PDF, máx. 50 MB).  
El nombre del archivo se genera como `manual_{slug-version}_{timestamp}.pdf` y se almacena localmente.

#### `show(UserManual $manual)`
Retorna el PDF como `BinaryFileResponse` con el Content-Disposition adecuado para visualización inline en el navegador.

---

## VucemValidatorController

**Archivo:** `app/Http/Controllers/VucemValidatorController.php`  
**Rol requerido:** Usuarios autenticados con licencia activa.

Valida que un archivo PDF cumpla con los requisitos técnicos de VUCEM antes de enviarlo.

### Métodos

| Método HTTP | Ruta | Nombre de ruta | Acción |
|---|---|---|---|
| `GET` | `/vucem/validador` | `vucem.validador` | `index()` |
| `POST` | `/vucem/validar-pdf` | `vucem.validar-pdf` | `validatePdf()` |

#### `validatePdf(Request $request)`
El archivo se sube temporalmente a `storage/tmp/validador/` y se ejecutan las siguientes verificaciones con **Ghostscript**:

| Verificación | Criterio VUCEM |
|---|---|
| Tamaño | ≤ 4 MB |
| Versión PDF | Exactamente `1.4` |
| Escala de grises | Sin contenido de color (ink coverage) |
| Páginas | Sin restricciones detectadas |

Responde JSON con el resultado de cada check (`ok: bool`, `value`, `label`). El archivo temporal se elimina al finalizar.

> **Dependencia externa:** Requiere que **Ghostscript** esté instalado y disponible en el `PATH` del servidor.

---

## Convenciones generales

### Autenticación y middleware
Todas las rutas (excepto `/` y `/privacidad`) requieren el middleware `auth`. Las rutas de negocio también requieren `license` (verificación de licencia activa).

### Control de acceso por rol
Los roles del sistema son: `SuperAdmin` > `Admin` > `Usuario`. El middleware `role:RolA,RolB` restringe el acceso a rutas específicas.

### Respuestas de error estándar
- `abort(403)` para acceso denegado a recursos de otro usuario.
- `findOrFail()` para recursos no encontrados (lanza HTTP 404).
- Las respuestas JSON de error siguen la forma `{ "success": false, "error": "mensaje" }`.

### Logging
Los eventos críticos (envío VUCEM, asignación de licencias, errores) se registran en el canal `Log::info` / `Log::error` estándar de Laravel, visible en `storage/logs/laravel.log`.

---

## Explicación de partes críticas

> **Tipo Diátaxis:** Explanation — análisis de por qué cada sección existe y qué impacto tiene modificarla.

Las siguientes son las zonas del código con mayor repercusión en el sistema. Una modificación incorrecta en cualquiera de ellas puede afectar la seguridad, la integridad de datos o el funcionamiento del flujo VUCEM.

---

### 1. `MveController::getAccessibleApplicants()` — Filtro central de visibilidad

**Ubicación:** `MveController.php`, método privado usado en `selectApplicant()`, `pendientes()`, `completadas()` y en todos los métodos que necesitan listar MVEs.

```php
private function getAccessibleApplicants()
{
    $user = auth()->user();

    if ($user->role === 'SuperAdmin') {
        return MvClientApplicant::query(); // Todos sin filtro
    }

    if ($user->role === 'Admin') {
        return MvClientApplicant::where(function($q) use ($user) {
            $q->where('created_by_user_id', $user->id)
              ->orWhere(fn($sub) => $sub->whereNull('created_by_user_id')
                  ->where('user_email', $user->email));
        });
    }

    // Usuario
    return MvClientApplicant::where(function($q) use ($user) {
        $q->whereHas('assignedUsers', fn($sub) => $sub->where('user_id', $user->id))
          ->orWhere('user_email', $user->email);
    });
}
```

**Por qué existe así:**  
Los solicitantes almacenan credenciales VUCEM encriptadas (certificados FIEL, claves de web service). Si un usuario pudiera ver solicitantes de otro Admin, tendría acceso indirecto a esas credenciales. La cláusula legacy `orWhere('user_email', ...)` existe para preservar registros creados antes de que se introdujera el campo `created_by_user_id`.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Eliminar el filtro `Admin` y devolver todos | Cualquier Admin vería los solicitantes y credenciales de otros Admins |
| Cambiar `'SuperAdmin'` a otro rol | Ese rol ganaría acceso total al sistema, sin restricciones |
| Eliminar la cláusula `orWhere('user_email', ...)` | Los solicitantes legacy (sin `created_by_user_id`) quedan huérfanos, invisibles para su Admin |
| Agregar un rol nuevo sin incluirlo aquí | El rol nuevo no podrá ver ningún solicitante, bloqueando cualquier funcionalidad de MVE |

---

### 2. `MveController::firmarYEnviarAjax()` — Flujo de envío a VUCEM

**Ubicación:** `MveController.php`, invocado desde el paso final del formulario de MVE vía AJAX.

Este método es el núcleo del sistema: ejecuta la cadena completa para enviar una Manifestación de Valor a VUCEM.

**Flujo interno:**

```
1. Verificar acceso al solicitante (canAccessApplicant)
2. Determinar origen de credenciales: almacenadas vs manuales
3. Validar estructura del XML SOAP (MvVucemSoapService::buildSoapXml — sin firma, solo estructura)
4. Escribir cert/key en archivos temporales (sys_get_temp_dir)
5. Generar cadena original (ManifestacionValorService::buildCadenaOriginal)
6. Firmar con e.firma (EFirmaService::generarFirmaElectronicaConArchivos — usa PhpCfdi)
7. Enviar a VUCEM (MveSignService::firmarYEnviarManifestacion)
8. Si éxito:
   a. Actualizar status → 'enviado' en MvDatosManifestacion, MvInformacionCove, MvDocumentos
   b. Consultar folio real (MNVA...) en VUCEM
   c. Enviar correo MveSubmitted al usuario y en CC al Admin creador del solicitante
9. Si rechazo VUCEM:
   a. Actualizar status → 'rechazado'
   b. Retornar diagnóstico de conectividad si aplica
10. Limpiar archivos temporales (bloque finally)
```

**Por qué la validación XML ocurre antes de la firma:**  
Generar la firma es una operación costosa (criptografía). Validar primero la estructura del XML evita desperdiciar tiempo si los datos del formulario tienen errores que VUCEM rechazaría de todas formas.

**Por qué se usan archivos temporales en `sys_get_temp_dir()`:**  
Las librerías de firma (OpenSSL vía PhpCfdi) requieren rutas de archivo físico, no streams de memoria. Los archivos se eliminan en el bloque `finally` para garantizar que no queden credenciales en disco aunque ocurra una excepción.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Omitir el paso de validación XML | Se generan firmas inútiles para datos que VUCEM rechazará, consumiendo tiempo y credenciales |
| No limpiar archivos temporales | Los archivos `.cer` y `.key` con credenciales FIEL quedan en disco, riesgo de seguridad |
| Cambiar el orden: firma antes de validación XML | Los errores de datos no se capturan hasta después de la operación criptográfica |
| Omitir la actualización de status a `'enviado'` | La MVE queda en `'guardado'` y aparece como pendiente, el usuario puede intentar reenviarla |
| Omitir la actualización a `'rechazado'` cuando VUCEM rechaza | La MVE queda en `'guardado'`, no se notifica el rechazo, el usuario no sabe que debe corregirla |
| No enviar CC al Admin en `MveSubmitted` | El Admin no recibe notificación del envío de un usuario subordinado |

---

### 3. `MveController::saveDatosManifestacion()` / `saveInformacionCove()` / `saveDocumentos()` — Cadena de guardado por secciones

**Por qué el guardado es por secciones y no en un solo paso:**  
El formulario de MVE es extenso (datos del importador, información COVE multi-entrada, documentos). Guardando sección por sección se le permite al usuario salir y retomar sin perder datos. Cada save devuelve el `section_id` para que el frontend lo mantenga en memoria durante la sesión.

**Comportamiento del `status`:**  
Cada vez que el usuario edita cualquier sección, el status de `MvDatosManifestacion` vuelve a `'borrador'`. Esto es intencional: garantiza que no se pueda firmar una MVE que tenga cambios sin pasar nuevamente por `saveFinalManifestacion()` (que la pone en `'guardado'`).

```php
$datosActualizar = [
    // ...campos...
    'status' => 'borrador', // Al editar, siempre vuelve a borrador
];
```

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Mantener `status` en `'guardado'` al editar | El usuario podría firmar y enviar una versión con cambios no revisados |
| No devolver `section_id` en la respuesta JSON | El frontend pierde la referencia al `mve_id`, las siguientes secciones crean registros nuevos desconectados |
| Cambiar la clave `datos_manifestacion_id` a otro nombre en el JSON | Las secciones posteriores no encontrarán la MVE padre, los datos quedarán huérfanos en la BD |

**Relación entre tablas (dependencia en `datos_manifestacion_id`):**

```
mv_datos_manifestacion (id) ─┬─► mv_informacion_cove (datos_manifestacion_id)
                              └─► mv_documentos (datos_manifestacion_id)
```

Si se crea un `MvInformacionCove` o `MvDocumentos` sin `datos_manifestacion_id`, queda asociado solo por `applicant_id`, lo que puede causar que `checkCompletion()` y `previewData()` lean datos de una MVE diferente.

---

### 4. `MveController::borrarBorrador()` y `descartarManifestacion()` — Eliminación en cascada manual

**Ubicación:** `MveController.php`

```php
MvDocumentos::where('datos_manifestacion_id', $mveId)->delete();
MvInformacionCove::where('datos_manifestacion_id', $mveId)->delete();
MvDatosManifestacion::where('id', $mveId)->where('applicant_id', $applicantId)->delete();
```

**Por qué se hace manualmente y no con `onDelete('cascade')` en la migración:**  
La eliminación manual permite controlar el orden y agregar lógica adicional por fila en el futuro (ej. eliminar archivos físicos de `Storage` antes de borrar el registro). También hace explícita la intención para futuros desarrolladores.

**`descartarManifestacion()` tiene una guarda de estado:**  
Solo permite eliminar si el status es `'borrador'`, `'guardado'` o `'rechazado'`. Una MVE en estado `'enviado'` no puede ser descartada.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Omitir la eliminación de `MvDocumentos` o `MvInformacionCove` | Registros huérfanos en BD, asociados a un `datos_manifestacion_id` que ya no existe; próximas MVEs del mismo solicitante pueden leerlos por error |
| Eliminar la guarda de status | Un usuario podría borrar una MVE ya enviada a VUCEM, perdiendo el acuse y el historial |
| No verificar `applicant_id` junto con `$mveId` | Se podría borrar la MVE de otro solicitante si se conoce el ID |

---

### 5. `MveController::agregarDocumentoAMve()` — Vinculación servidor-lado de eDocuments

**Ubicación:** `MveController.php`, método privado llamado desde `digitalizarDocumento()` y `consultarOperacion()`.

```php
private function agregarDocumentoAMve(int $applicantId, array $documento, ?int $mveId = null): void
{
    // Obtiene o crea MvDocumentos para la MVE
    // Verifica duplicados por folio_edocument antes de agregar
    // Guarda en la columna JSON `documentos`
}
```

**Por qué existe:**  
Cuando el usuario digitaliza un documento en VUCEM desde el paso 3 del formulario, el eDocument resultante debe quedar automáticamente asociado a la MVE activa. Sin este método, el usuario tendría que ingresarlo manualmente en el campo de folio, lo que introduce error humano.

**La verificación de duplicados es crítica:**  
Si el mismo folio se agrega dos veces al array JSON, VUCEM rechazará la MVE al recibir documentos duplicados.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Eliminar la verificación de duplicados | Folios duplicados en `mv_documentos.documentos`, VUCEM rechazará la MVE |
| No usar `$mveId` para buscar el registro | El documento se agrega al registro más reciente del solicitante, pudiendo contaminar otra MVE en progreso |
| Quitar el `try/catch` que lo rodea | Un fallo en este paso lanzará excepción al usuario aunque la digitalización VUCEM fue exitosa |

---

### 6. `MveController::createWithFile()` — Validación de RFC del Archivo M

**Ubicación:** `MveController.php`

```php
if (strtoupper($rfcArchivoM) !== strtoupper($applicant->applicant_rfc)) {
    return redirect()->back()->withErrors([
        'archivo_m' => 'El RFC del archivo no coincide con el RFC del solicitante.'
    ]);
}
```

**Por qué existe:**  
El archivo M (formato de texto de pedimento) puede provenir de cualquier fuente. Es posible que el usuario cargue accidentalmente (o intencionalmente) un archivo de otro importador. Cargar datos de un RFC diferente corrompería los datos de la MVE y el envío a VUCEM.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Eliminar esta validación | Se puede crear una MVE con datos de un RFC diferente al del solicitante, VUCEM la rechazará o, peor, la aceptará con datos de otro contribuyente |
| Hacer la comparación case-sensitive sin `strtoupper` | RFCs en minúsculas del archivo M fallarán la validación aunque sean correctos |

---

### 7. `ApplicantController::store()` — Límite de solicitantes por licencia

**Ubicación:** `ApplicantController.php`

```php
if ($user->role === 'Admin') {
    $maxApplicants = $user->max_applicants ?? 10;
    $currentCount = MvClientApplicant::where('created_by_user_id', $user->id)->count();
    if ($currentCount >= $maxApplicants) {
        return redirect()->back()->withErrors(['limit' => "Has alcanzado el límite..."]);
    }
}
```

**Por qué existe:**  
El modelo de negocio del sistema cobra por el número de solicitantes que puede gestionar un Admin. El límite se define en `users.max_applicants`, que el SuperAdmin controla desde `LicenseController::updateLimits()`.

**El valor por defecto es `10`** cuando `max_applicants` es `null`. Esto es importante en instalaciones nuevas o al crear un Admin sin asignarle límite explícito.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Eliminar la verificación | Los Admins podrían crear solicitantes sin límite, rompiendo el modelo de negocio |
| Cambiar el default `?? 10` a `?? 0` | Ningún Admin podría crear solicitantes hasta que el SuperAdmin configure explícitamente su límite |
| Cambiar el default `?? 10` a `?? PHP_INT_MAX` | Efectivamente elimina el límite para todos los Admins sin límite configurado |
| Contar solicitantes por `user_email` en lugar de `created_by_user_id` | La cuenta incluirá solicitantes legacy que podrían no pertenecer al Admin, bloqueándolo prematuramente |

---

### 8. `LicenseController::store()` / `renew()` — Revocación antes de asignar

**Ubicación:** `LicenseController.php`

```php
// Revocar licencia activa anterior si existe
License::where('admin_id', $admin->id)
    ->where('status', 'active')
    ->update(['status' => 'revoked']);

// Crear nueva licencia
$license = License::create([...]);
```

**Por qué se revoca primero y no se actualiza:**  
Se mantiene el historial completo de licencias. Actualizar la misma fila borraría las fechas originales. Revocar y crear garantiza trazabilidad: el SuperAdmin puede ver cuándo se asignó cada licencia, quién la creó y por cuánto tiempo.

**Por qué no se usa una transacción de BD:**  
Si la revocación tiene éxito pero la creación falla, el Admin queda sin licencia activa. Este es un riesgo conocido. Si se requiere mayor robustez (ej. en producción con muchos usuarios), este bloque debería envolverse en `DB::transaction()`.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| No revocar la licencia anterior | El Admin puede tener múltiples licencias activas simultáneamente; el middleware `license` se comportará de forma impredecible al elegir cuál verificar |
| Actualizar en lugar de crear nueva | Se pierde el historial; no se puede auditar cuándo fue cada asignación |
| Remover el envío de `LicenseAssigned` | El Admin no recibe notificación de su nueva licencia ni de la fecha de expiración |

---

### 9. `EDocumentConsultaController::consultar()` / `MveController::digitalizarDocumento()` — Resolución de credenciales almacenadas vs manuales

Este patrón aparece en **múltiples métodos** del sistema: `consultar()`, `digitalizarDocumento()`, `consultarOperacion()`, `firmarYEnviarAjax()` y `buscarCoveInfo()`.

```php
$useStoredCreds = $solicitante->hasVucemCredentials() && !$request->hasFile('certificado');
$useStoredWs    = $solicitante->hasWebserviceKey()    && !$request->filled('clave_webservice');

$claveWebService = $useStoredWs
    ? $solicitante->vucem_webservice_key  // desencriptado por Laravel automáticamente
    : $request->input('clave_webservice');
```

**Por qué existe la prioridad "manual > almacenado":**  
Si el usuario proporciona archivos manualmente, significa que quiere usar credenciales diferentes a las almacenadas (ej. las almacenadas están vencidas o son incorrectas). La prioridad manual permite esta corrección sin tener que modificar el perfil del solicitante.

**Por qué los archivos físicos se escriben en `sys_get_temp_dir()` y no en `storage/`:**  
Las librerías de firma (OpenSSL) necesitan acceso al sistema de archivos real, no a paths virtuales de Laravel. `sys_get_temp_dir()` garantiza que el sistema operativo lo limpie eventualmente, y el bloque `finally` lo elimina inmediatamente.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Invertir la prioridad (almacenadas > manuales siempre) | El usuario no puede usar credenciales diferentes a las almacenadas para situaciones de emergencia |
| No escribir en temporales y pasar datos en memoria | Las librerías OpenSSL lanzarán excepciones al no encontrar rutas de archivo válidas |
| No eliminar los temporales en el bloque `finally` | Los archivos `.cer` y `.key` con material criptográfico quedan en disco |
| Eliminar la verificación `!$certContent || !$keyContent` | Se pasan cadenas vacías a la firma, generando firmas inválidas que VUCEM rechazará |

---

### 10. `MveController::checkCompletion()` — Puerta antes de finalizar la MVE

**Ubicación:** `MveController.php`, llamado internamente por `saveFinalManifestacion()` antes de cambiar el status a `'guardado'`.

```php
$allComplete = $datosManifestacion && $informacionCove;
// Nota: documentos son verificados pero NO bloquean la finalización
```

**Por qué los documentos no bloquean:**  
El campo de documentos es legalmente opcional en algunos escenarios de MVE (una MVE puede enviarse sin eDocuments adjuntos si no corresponde). Esto es un punto de atención: si los reglamentos cambian y los documentos pasan a ser obligatorios, esta lógica deberá actualizarse.

**El `mve_id` es la clave de todo:**  
Si `mve_id` llega como `null` en cualquiera de estos métodos, la lógica cae al `else` que toma "el registro más reciente del solicitante". Esto puede traer datos de otra MVE del mismo solicitante si hay más de una en progreso. Es un caso de borde que se mitiga porque el frontend siempre envía `mve_id` después del primer save.

**Impacto si se modifica:**

| Cambio | Consecuencia |
|---|---|
| Agregar documentos como condición de `$allComplete` | Los usuarios sin eDocuments no podrán finalizar ninguna MVE, bloqueando el flujo |
| Eliminar la verificación de `informacionCove` | Se pueden enviar MVEs sin datos del COVE, que VUCEM rechazará por estructura XML incompleta |
| Cambiar el fallback de `mve_id null` | Si se toma un registro diferente al activo, `saveFinalManifestacion()` marcará como `'guardado'` la MVE equivocada |

---

### Resumen de dependencias críticas entre métodos

```
selectApplicant()
    └─ getAccessibleApplicants()           ← filtro de visibilidad global

createManual() / createWithFile()
    └─ canAccessApplicant()                ← verificación de propiedad

saveDatosManifestacion()  →  section_id (mve_id)
    └─ saveInformacionCove(mve_id)         ← asocia con datos_manifestacion_id
        └─ saveDocumentos(mve_id)          ← asocia con datos_manifestacion_id

checkCompletion(mve_id)
    └─ saveFinalManifestacion(mve_id)      ← cambia status a 'guardado'
        └─ firmarYEnviarAjax(mve_id)       ← único punto de envío a VUCEM
            ├─ EFirmaService               ← genera firma con PhpCfdi
            ├─ MveSignService              ← construye XML SOAP y envía
            └─ MveConsultaService          ← consulta folio real MNVA...

digitalizarDocumento()
    └─ agregarDocumentoAMve(mve_id)        ← vincula eDocument al paso 3

borrarBorrador() / descartarManifestacion()
    └─ elimina en orden: MvDocumentos → MvInformacionCove → MvDatosManifestacion
```
