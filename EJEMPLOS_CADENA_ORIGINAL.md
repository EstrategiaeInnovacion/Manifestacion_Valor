# Ejemplos Visuales de Cadena Original MVE

## 🎯 Ejemplos Prácticos de Cadena Original

Este archivo proporciona ejemplos visuales y explicados de cómo se construye la cadena original para diferentes escenarios de Manifestación de Valor.

## 📋 Ejemplo 1: Manifestación Básica

### Datos de Entrada:
- **RFC Importador**: `ABC123456789`
- **Personas Consulta**: 1 representante
- **Documentos**: 2 archivos PDF
- **COVE**: 1 operación con pedimento
- **Precios**: Solo precio pagado
- **Sin incrementables/decrementables**

### Cadena Resultante:
```
||ABC123456789|REP123456789|REPRESENTANTE|factura.pdf|conocimiento.pdf|COV123456|FOB|0|25480458215698425|4805|48|2026-01-15|10000.00|TRANSFERENCIA||USD|20.50|VALADU.VTM|10000.00|||||10000.00||
```

### Desglose Visual:
```
Campo  | Valor                | Descripción
-------|---------------------|------------------------------------------
1      | ABC123456789        | RFC Importador
2      | REP123456789        | RFC Persona Consulta
3      | REPRESENTANTE       | Tipo Figura
4      | factura.pdf         | Documento 1
5      | conocimiento.pdf    | Documento 2
6      | COV123456           | Número COVE
7      | FOB                 | Incoterm
8      | 0                   | No hay vinculación
9      | 25480458215698425   | Número Pedimento
10     | 4805                | Patente
11     | 48                  | Aduana
12     | 2026-01-15          | Fecha Pago
13     | 10000.00            | Total Pagado
14     | TRANSFERENCIA       | Tipo de Pago
15     | (vacío)             | Especifique
16     | USD                 | Moneda
17     | 20.50               | Tipo de Cambio
18     | VALADU.VTM          | Método Valoración
19     | 10000.00            | Total Precio Pagado
20     | (vacío)             | Total Precio Por Pagar
21     | (vacío)             | Total Incrementables
22     | (vacío)             | Total Decrementables
23     | 10000.00            | Total Valor Aduana
```

---

## 📋 Ejemplo 2: Manifestación Compleja

### Datos de Entrada:
- **RFC Importador**: `XYZ987654321`
- **Personas Consulta**: 2 personas (representante y agente)
- **Documentos**: 3 archivos PDF
- **COVE**: 1 operación completa
- **Precios**: Pagado + Por Pagar + Compenso
- **Incrementables**: 2 elementos
- **Decrementables**: 1 elemento

### Cadena Resultante:
```
||XYZ987654321|REP123456789|REPRESENTANTE|AGE987654321|AGENTE|factura.pdf|bl.pdf|anexos.pdf|COV789123|CIF|1|25480458215698426|4805|48|2026-01-15|8000.00|TRANSFERENCIA||USD|20.50|2026-02-15|2000.00|POSTERIOR_IMPORTACION|CHEQUE||USD|20.50|MERCANCIA|2026-01-10|DESCUENTO||VALADU.VTM|COMISIONES|2026-01-12|500.00|USD|20.50|1|FLETES|2026-01-11|300.00|EUR|22.00|1|DESCUENTOS|2026-01-11|200.00|USD|20.50|8000.00|2000.00|800.00|200.00|10600.00||
```

### Desglose por Secciones:

#### 🏢 Datos Básicos (campos 1-3)
```
XYZ987654321     → RFC Importador
REP123456789     → RFC Persona Consulta 1
REPRESENTANTE    → Tipo Figura Persona 1
```

#### 👥 Personas Adicionales (campos 4-5)
```
AGE987654321     → RFC Persona Consulta 2
AGENTE           → Tipo Figura Persona 2
```

#### 📄 Documentos (campos 6-8)
```
factura.pdf      → Documento 1
bl.pdf           → Documento 2
anexos.pdf       → Documento 3
```

#### 🚢 Información COVE (campos 9-13)
```
COV789123                  → Número COVE
CIF                       → Incoterm
1                         → Existe Vinculación
25480458215698426         → Número Pedimento
4805                      → Patente
48                        → Aduana
```

#### 💰 Precios Pagados (campos 14-19)
```
2026-01-15       → Fecha Pago
8000.00          → Total
TRANSFERENCIA    → Tipo Pago
(vacío)          → Especifique
USD              → Moneda
20.50            → Tipo Cambio
```

#### 💳 Precios Por Pagar (campos 20-26)
```
2026-02-15              → Fecha Pago
2000.00                 → Total
POSTERIOR_IMPORTACION   → Situación
CHEQUE                  → Tipo Pago
(vacío)                 → Especifique
USD                     → Moneda
20.50                   → Tipo Cambio
```

#### 🎁 Compenso Pago (campos 27-31)
```
MERCANCIA        → Tipo Pago
2026-01-10       → Fecha
DESCUENTO        → Motivo
(vacío)          → Prestación Mercancía
(vacío)          → Especifique
```

#### ⚙️ Método Valoración (campo 32)
```
VALADU.VTM       → Método de Valoración
```

#### ⬆️ Incrementables (campos 33-44)
```
COMISIONES       → Tipo Incrementable 1
2026-01-12       → Fecha Erogación 1
500.00           → Importe 1
USD              → Moneda 1
20.50            → Tipo Cambio 1
1                → A Cargo Importador 1

FLETES           → Tipo Incrementable 2
2026-01-11       → Fecha Erogación 2
300.00           → Importe 2
EUR              → Moneda 2
22.00            → Tipo Cambio 2
1                → A Cargo Importador 2
```

#### ⬇️ Decrementables (campos 45-49)
```
DESCUENTOS       → Tipo Decrementable
2026-01-11       → Fecha Erogación
200.00           → Importe
USD              → Moneda
20.50            → Tipo Cambio
```

#### 💼 Totales Valor Aduana (campos 50-54)
```
8000.00          → Total Precio Pagado
2000.00          → Total Precio Por Pagar
800.00           → Total Incrementables
200.00           → Total Decrementables
10600.00         → Total Valor Aduana
```

---

## 📋 Ejemplo 3: Actualizar Manifestación

### Datos de Entrada:
- **Número MV**: `MV202600123456`
- **Documentos**: 2 archivos adicionales
- **Persona Consulta**: 1 consultor

### Cadena Resultante:
```
||MV202600123456|documento_adicional.pdf|anexo_tecnico.pdf|UPD123456789|CONSULTOR||
```

### Desglose:
```
Campo  | Valor                    | Descripción
-------|--------------------------|---------------------------
1      | MV202600123456           | Número de MV existente
2      | documento_adicional.pdf  | Documento adicional 1
3      | anexo_tecnico.pdf        | Documento adicional 2
4      | UPD123456789            | RFC Persona Consulta
5      | CONSULTOR               | Tipo Figura
```

---

## 🎨 Visualización de Estructura

### registroManifestacion
```
||
  RFC_IMPORTADOR
  |[PERSONAS_CONSULTA:rfc|tipoFigura]*
  |[DOCUMENTOS:nombre]*
  |[COVE_INFO:cove|incoterm|vinculacion]*
    |[PEDIMENTOS:pedimento|patente|aduana]*
    |[PRECIO_PAGADO:fecha|total|tipo|especifique|moneda|cambio]*
    |[PRECIO_POR_PAGAR:fecha|total|situacion|tipo|especifique|moneda|cambio]*
    |[COMPENSO:tipo|fecha|motivo|prestacion|especifique]*
  |METODO_VALORACION
  |[INCREMENTABLES:tipo|fecha|importe|moneda|cambio|aCargoImp]*
  |[DECREMENTABLES:tipo|fecha|importe|moneda|cambio]*
  |TOTAL_PAGADO|TOTAL_POR_PAGAR|TOTAL_INC|TOTAL_DEC|TOTAL_ADUANA
||
```

### actualizarManifestacion
```
||
  NUMERO_MV
  |[DOCUMENTOS:nombre]*
  |[PERSONAS_CONSULTA:rfc|tipoFigura]*
||
```

**Leyenda:**
- `*` = Repetible (0 o más elementos)
- `[]` = Grupo de campos relacionados
- `|` = Separador de campo

---

## ⚡ Consejos de Implementación

### ✅ Buenas Prácticas:
- Siempre usar `||` al inicio y final
- Mantener espacios vacíos para campos opcionales
- Respetar el orden exacto del XSD
- Validar longitud total antes de enviar

### ❌ Errores Comunes:
- Omitir campos opcionales (rompe la estructura)
- Cambiar el orden de los campos
- No manejar correctamente las listas vacías
- Usar separadores incorrectos

### 🔧 Testing:
```bash
# Ejecutar test de cadena original
php test_cadena_original.php

# Validar formato manualmente
$valido = str_starts_with($cadena, '||') && str_ends_with($cadena, '||');
```