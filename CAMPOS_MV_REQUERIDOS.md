# Campos de Manifestación de Valor - Requeridos vs Opcionales

## ✅ CAMPOS OBLIGATORIOS (Required)

### Paso 1: Datos Básicos
- `rfc_importador` - RFC del Importador (12-13 caracteres)
- `metodo_valoracion` - Método de Valoración (clave VUCEM, max 20)

### Paso 2: Información COVE
- `informacion_cove` - Array de COVEs (mínimo 1)
  - `cove` - Número de COVE (max 20)
  - `incoterm` - Incoterm (clave VUCEM, max 15)

### Paso 3: Totales
- `valor_en_aduana` - Objeto con totales
  - `totalPrecioPagado` - Total precio pagado (numeric 19,3)
  - `totalPrecioPorPagar` - Total precio por pagar (numeric 19,3)
  - `totalIncrementables` - Total incrementables (numeric 19,3)
  - `totalDecrementables` - Total decrementables (numeric 19,3)
  - `totalValorAduana` - Total valor en aduana (numeric 19,3)

## ⚠️ CAMPOS OPCIONALES (Nullable/Pueden estar vacíos en borrador)

### Operación y Vinculación
- `existe_vinculacion` - Existe vinculación (0 o 1)
- `pedimento` - Número de pedimento (max 20)
- `patente` - Patente (max 20)
- `aduana` - Aduana (max 20)

### RFC's de Consulta
- `persona_consulta` - Array de personas (opcional)
  - `rfc` - RFC consulta (12-13 caracteres)
  - `tipoFigura` - Tipo de figura (clave VUCEM, max 15)

### Precios y Pagos
- `precio_pagado` - Objeto opcional
  - `fechaPago` - Fecha de pago (date)
  - `total` - Total (numeric 19,3)
  - `tipoPago` - Tipo de pago (clave VUCEM, max 20)
  - `especifique` - Especificar si es "Otro" (max 70)
  - `tipoMoneda` - Tipo de moneda (3 caracteres)
  - `tipoCambio` - Tipo de cambio (numeric 16,3)

- `precio_por_pagar` - Objeto opcional
  - `fechaPago` - Fecha de pago (date)
  - `total` - Total (numeric 19,3)
  - `situacionNofechaPago` - Situación sin fecha (max 1000)
  - `tipoPago` - Tipo de pago (clave VUCEM, max 20)
  - `especifique` - Especificar si es "Otro" (max 70)
  - `tipoMoneda` - Tipo de moneda (3 caracteres)
  - `tipoCambio` - Tipo de cambio (numeric 16,3)

- `compenso_pago` - Objeto opcional
  - `fecha` - Fecha (date)
  - `tipoPago` - Tipo de pago (clave VUCEM, max 20)
  - `motivo` - Motivo (max 1000)
  - `prestacionMercancia` - Prestación de mercancía (max 1000)
  - `especifique` - Especificar si es "Otro" (max 70)

### Incrementables y Decrementables
- `incrementables` - Array opcional
  - `tipoIncrementable` - Tipo (clave VUCEM, max 20)
  - `fechaErogacion` - Fecha (date)
  - `importe` - Importe (numeric 19,3)
  - `aCargoImportador` - A cargo importador (0 o 1)
  - `tipoMoneda` - Tipo de moneda (3 caracteres)
  - `tipoCambio` - Tipo de cambio (numeric 16,3)

- `decrementables` - Array opcional
  - `tipoDecrementable` - Tipo (clave VUCEM, max 20)
  - `fechaErogacion` - Fecha (date)
  - `importe` - Importe (numeric 19,3)
  - `tipoMoneda` - Tipo de moneda (3 caracteres)
  - `tipoCambio` - Tipo de cambio (numeric 16,3)

### Documentos
- `documentos` - Array opcional
  - `eDocument` - Tipo de documento (max 20)
  - `nombre` - Nombre del archivo
  - `ruta` - Ruta del archivo almacenado

## 💾 ESTADO DEL BORRADOR

- `status` - Estado: 'borrador' o 'completada'
- Los campos opcionales pueden estar vacíos cuando status='borrador'
- Todos los campos obligatorios deben estar completos para status='completada'

## 🔐 SEGURIDAD

- **TODOS** los campos están encriptados en la BD
- Los campos JSON se encriptan después de serializarse
- Solo se desencriptan al recuperarse del modelo
