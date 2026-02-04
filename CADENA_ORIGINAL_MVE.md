# Cadena Original para Manifestación de Valor (MVE)

## 📋 Estructura de Cadena Original VUCEM

Este documento define la estructura exacta de la cadena original para las operaciones de Manifestación de Valor en VUCEM, siguiendo las especificaciones del XSD y las mejores prácticas de integración.

## 🔧 Formato General

La cadena original de VUCEM utiliza el formato:
```
||campo1|campo2|campo3|...||
```

- **Envolver**: `||` al inicio y final
- **Separador**: `|` entre campos
- **Campos opcionales**: Si no existe valor, se deja vacío entre pipes `||`, pero se mantiene el "slot" para preservar la posición

## 📋 1. registroManifestacion

### Orden de Campos (siguiendo XSD):

#### 1. RFC Importador-Exportador
- `rfc` - RFC del importador/exportador (12-13 caracteres)

#### 2. Por cada personaConsulta (repetible)
- `rfc` - RFC de la persona de consulta
- `tipoFigura` - Tipo de figura (clave VUCEM)

#### 3. Por cada documentos (repetible)
- `eDocument` - Nombre del documento/archivo

#### 4. Por cada informacionCove (repetible)
- `cove` - Número de COVE
- `incoterm` - Incoterm (clave VUCEM)
- `existeVinculacion` - Existe vinculación (0 o 1)

#### 5. Por cada pedimento (dentro de informacionCove)
- `pedimento` - Número de pedimento
- `patente` - Patente
- `aduana` - Aduana

#### 6. Por cada precioPagado (dentro de informacionCove)
- `fechaPago` - Fecha de pago
- `total` - Total pagado
- `tipoPago` - Tipo de pago
- `especifique` - Especificación (si aplica)
- `tipoMoneda` - Tipo de moneda
- `tipoCambio` - Tipo de cambio

#### 7. Por cada precioPorPagar (dentro de informacionCove)
- `fechaPago` - Fecha de pago
- `total` - Total por pagar
- `situacionNofechaPago` - Situación/momento cuando no hay fecha de pago
- `tipoPago` - Tipo de pago
- `especifique` - Especificación (si aplica)
- `tipoMoneda` - Tipo de moneda
- `tipoCambio` - Tipo de cambio

#### 8. Por cada compensoPago (dentro de informacionCove)
- `tipoPago` - Tipo de pago/forma de pago
- `fecha` - Fecha
- `motivo` - Motivo
- `prestacionMercancia` - Prestación en mercancía
- `especifique` - Especificación

#### 9. Método de Valoración
- `metodoValoracion` - Método de valoración (clave VUCEM)

#### 10. Por cada incrementables
- `tipoIncrementable` - Tipo de incrementable
- `fechaErogacion` - Fecha de erogación
- `importe` - Importe
- `tipoMoneda` - Tipo de moneda
- `tipoCambio` - Tipo de cambio
- `aCargoImportador` - A cargo del importador (0 o 1)

#### 11. Por cada decrementables
- `tipoDecrementable` - Tipo de decrementable
- `fechaErogacion` - Fecha de erogación
- `importe` - Importe
- `tipoMoneda` - Tipo de moneda
- `tipoCambio` - Tipo de cambio

#### 12. Valor en Aduana (totales)
- `totalPrecioPagado` - Total precio pagado
- `totalPrecioPorPagar` - Total precio por pagar
- `totalIncrementables` - Total incrementables
- `totalDecrementables` - Total decrementables
- `totalValorAduana` - Total valor en aduana

### Ejemplo de Estructura:
```
||RFC_IMP_EXP|persona1_rfc|persona1_tipoFigura|doc1.pdf|cove1|incoterm1|1|pedimento1|patente1|aduana1|fecha1|total1|tipoPago1||tipoMoneda1|tipoCambio1|fechaPorPagar1|totalPorPagar1|situacion1|tipoPago2||tipoMoneda2|tipoCambio2|tipoPago3|fecha3|motivo1|prestacion1||VALADU.VTM|incrementable1|fecha_erog1|importe1|USD|20.50|1|decrementable1|fecha_erog2|importe2|USD|20.50|1000.00|500.00|200.00|100.00|1600.00||
```

## 📋 2. actualizarManifestacion

### Orden de Campos:

#### 1. Número MV
- `numeroMV` - Número de Manifestación de Valor existente

#### 2. Por cada documentos
- `eDocument` - Nombre del documento/archivo

#### 3. Por cada personaConsulta
- `rfc` - RFC de la persona de consulta
- `tipoFigura` - Tipo de figura (clave VUCEM)

### Ejemplo de Estructura:
```
||MV123456|documento1.pdf|documento2.pdf|RFC123456789|REPRESENTANTE||
```

## ⚡ Implementación Técnica

### Métodos en MveController:

#### `buildCadenaOriginal()` - Para registro
```php
private function buildCadenaOriginal(
    MvClientApplicant $applicant,
    ?MvDatosManifestacion $datosManifestacion,
    ?MvInformacionCove $informacionCove,
    ?MvValorAduana $valorAduana,
    $documentos
): string
```

#### `buildCadenaOriginalActualizar()` - Para actualización
```php
private function buildCadenaOriginalActualizar(
    string $numeroMV,
    $documentos,
    ?MvDatosManifestacion $datosManifestacion
): string
```

## 🔍 Validaciones

- **Formato requerido**: Debe iniciar y terminar con `||`
- **Separadores**: Solo usar `|` entre campos
- **Campos vacíos**: Mantener espacios vacíos `||` para campos opcionales
- **Orden estricto**: Seguir exactamente el orden definido en el XSD
- **Listas**: Repetir el bloque completo por cada elemento de la lista
- **Codificación**: UTF-8 sin BOM

## 📝 Notas Importantes

1. **Sin XSLT**: Al no tener acceso al XSLT oficial de VUCEM, esta implementación sigue la estructura lógica del XSD
2. **Orden crítico**: El orden de los campos debe coincidir exactamente con el XSD
3. **Campos anidados**: Los elementos dentro de `informacionCove` se expanden en el mismo orden
4. **Compatibilidad**: Esta estructura es compatible con los integradores que no tienen acceso al XSLT oficial
5. **Mantenimiento**: Al actualizar el XSD, revisar y actualizar la estructura si es necesario

## 🚀 Uso

La cadena original se genera automáticamente en el momento de crear la vista previa de la manifestación y está lista para ser utilizada en la firma electrónica VUCEM.