# Estructura JSON de los Campos de Manifestación de Valor

## 📋 Mapeo: Campos de Vista → Campos BD

### 1️⃣ **rfc_importador** (TEXT encriptado)
**Campos de la vista:**
- RFC del Importador
- Registro Federal de Contribuyentes  
- Nombre o Razón social

**JSON guardado:**
```json
{
  "rfc": "NET070608EM9",
  "razon_social": "NETXICO SA DE CV"
}
```

---

### 2️⃣ **persona_consulta** (TEXT encriptado - Array)
**Campos de la vista:**
- RFC
- Razón social
- Tipo de figura

**JSON guardado:**
```json
[
  {
    "rfc": "ABC123456XYZ",
    "razon_social": "EMPRESA CONSULTORA SA",
    "tipo_figura": "TIPFIG.AGE"
  },
  {
    "rfc": "DEF789012ABC",
    "razon_social": "OTRA EMPRESA SA",
    "tipo_figura": "TIPFIG.REP"
  }
]
```

---

### 3️⃣ **informacion_cove** (TEXT encriptado - Array)
**Campos de la vista:**
- Acuse de Valor (COVE)
- Método de Valoración aduanera
- # Factura
- Fecha de expedición
- Emisor original
- Destinatario

**JSON guardado:**
```json
[
  {
    "cove": "COVE123456789",
    "metodo_valoracion": "VALADU.VTM",
    "numero_factura": "FAC-2026-001",
    "fecha_expedicion": "2026-01-14",
    "emisor_original": "PROVEEDOR INTERNACIONAL SA",
    "destinatario": "NETXICO SA DE CV",
    "incoterm": "TIPINC.FOB"
  }
]
```

---

### 4️⃣ **valor_en_aduana** (TEXT encriptado - Object)
**Campos de la vista:**
- Precio pagado
- Precio por pagar
- Total incrementables
- Total decrementables
- Total valor en aduana

**JSON guardado:**
```json
{
  "total_precio_pagado": 150000.00,
  "total_precio_por_pagar": 50000.00,
  "total_incrementables": 10000.00,
  "total_decrementables": 5000.00,
  "total_valor_aduana": 205000.00
}
```

---

### 5️⃣ **incrementables** (TEXT encriptado - Array)
**Campos de la vista:**
- Tipo incrementable
- Fecha erogación
- Importe
- Tipo moneda
- Tipo cambio

**JSON guardado:**
```json
[
  {
    "tipo_incrementable": "INCRE.CG",
    "fecha_erogacion": "2026-01-10",
    "importe": 5000.00,
    "a_cargo_importador": 1,
    "tipo_moneda": "USD",
    "tipo_cambio": 18.50
  }
]
```

---

### 6️⃣ **decrementables** (TEXT encriptado - Array)
**Campos de la vista:**
- Tipo decrementable
- Fecha erogación
- Importe
- Tipo moneda
- Tipo cambio

**JSON guardado:**
```json
[
  {
    "tipo_decrementable": "DECRE.GR",
    "fecha_erogacion": "2026-01-12",
    "importe": 2500.00,
    "tipo_moneda": "MXN",
    "tipo_cambio": 1.00
  }
]
```

---

### 7️⃣ **documentos** (TEXT encriptado - Array)
**Campos de la vista:**
- Nombre del documento
- Cargar documento (PDF)

**JSON guardado:**
```json
[
  {
    "nombre": "Factura Comercial",
    "e_document": "DOC001",
    "ruta": "storage/mv_documentos/applicant_1/factura_123.pdf",
    "mime_type": "application/pdf",
    "tamanio": 245678
  }
]
```

---

### 8️⃣ **precio_pagado** (TEXT encriptado - Object)
**Campos en el request:**
- Fecha de pago
- Total
- Tipo de pago
- Especifique (si es "Otro")
- Tipo moneda
- Tipo cambio

**JSON guardado:**
```json
{
  "fecha_pago": "2026-01-10",
  "total": 150000.00,
  "tipo_pago": "FORPAG.TE",
  "especifique": null,
  "tipo_moneda": "USD",
  "tipo_cambio": 18.50
}
```

---

### 9️⃣ **precio_por_pagar** (TEXT encriptado - Object)
**JSON guardado:**
```json
{
  "fecha_pago": "2026-02-15",
  "total": 50000.00,
  "situacion_no_fecha_pago": "Pago diferido a 30 días",
  "tipo_pago": "FORPAG.LC",
  "especifique": null,
  "tipo_moneda": "USD",
  "tipo_cambio": 18.50
}
```

---

### 🔟 **compenso_pago** (TEXT encriptado - Object)
**JSON guardado:**
```json
{
  "fecha": "2026-01-14",
  "tipo_pago": "FORPAG.OT",
  "motivo": "Compensación por devolución",
  "prestacion_mercancia": "Entrega de mercancía equivalente",
  "especifique": "Trueque de productos"
}
```

---

## 🔑 Campos Simples (No JSON)

### **metodo_valoracion** (TEXT encriptado)
Valor directo: `"VALADU.VTM"`

### **existe_vinculacion** (INTEGER)
Valor directo: `1` o `0`

### **pedimento** (TEXT encriptado)
Valor directo: `"26 3124 0001234"`

### **patente** (TEXT encriptado)
Valor directo: `"1234"`

### **aduana** (TEXT encriptado)
Valor directo: `"02 Tijuana"`

---

## 🔐 Nota Importante
**TODOS los campos TEXT están encriptados en la BD**. El modelo DatosMv automáticamente:
1. **Al guardar**: Convierte a JSON → Encripta
2. **Al leer**: Desencripta → Convierte de JSON a Array/Object

**Ejemplo en código:**
```php
// Guardar
$datosMv->informacion_cove = [
    ['cove' => 'COVE123', 'factura' => 'FAC001']
];

// Leer
$coves = $datosMv->informacion_cove; // Array automáticamente desencriptado
```
