<?php

/**
 * DEBUG: Análisis de Cadena Original MVE
 * 
 * Herramienta para debuggear y analizar problemas en la generación
 * de la cadena original VUCEM.
 */

require_once 'vendor/autoload.php';

use App\Constants\VucemCatalogs;

class DebugCadenaOriginal
{
    public static function analizarCadenaActual($cadena)
    {
        echo "=== ANÁLISIS DE CADENA ORIGINAL ACTUAL ===\n\n";
        
        // Validar formato básico
        if (!str_starts_with($cadena, '||') || !str_ends_with($cadena, '||')) {
            echo "❌ ERROR: La cadena no tiene el formato VUCEM correcto (||...||)\n";
            return;
        }
        
        // Extraer contenido
        $contenido = substr($cadena, 2, -2);
        $campos = explode('|', $contenido);
        
        echo "📊 Total de campos encontrados: " . count($campos) . "\n";
        echo "📏 Longitud total: " . strlen($cadena) . " caracteres\n\n";
        
        // Análisis de estructura esperada
        echo "=== ESTRUCTURA ESPERADA vs ACTUAL ===\n\n";
        
        $estructura = [
            1 => 'RFC Importador',
            2 => 'RFC Persona Consulta 1', 
            3 => 'Tipo Figura Persona 1',
            4 => 'Documento 1',
            5 => 'Número COVE',
            6 => 'Incoterm',
            7 => 'Existe Vinculación',
            8 => 'Número Pedimento',
            9 => 'Patente',
            10 => 'Aduana',
            11 => 'Fecha Precio Pagado',
            12 => 'Total Precio Pagado',
            13 => 'Tipo Pago Precio Pagado',
            14 => 'Especifique Precio Pagado',
            15 => 'Moneda Precio Pagado',
            16 => 'Tipo Cambio Precio Pagado',
            17 => 'Fecha Precio Por Pagar',
            18 => 'Total Precio Por Pagar',
            19 => 'Situación Precio Por Pagar',
            20 => 'Tipo Pago Precio Por Pagar',
            21 => 'Especifique Precio Por Pagar',
            22 => 'Moneda Precio Por Pagar',
            23 => 'Tipo Cambio Precio Por Pagar',
            24 => 'Tipo Pago Compenso',
            25 => 'Fecha Compenso',
            26 => 'Motivo Compenso',
            27 => 'Prestación Mercancía Compenso',
            28 => 'Especifique Compenso',
            29 => 'Método Valoración',
            30 => 'Tipo Incrementable 1',
            31 => 'Fecha Erogación Incrementable 1',
            32 => 'Importe Incrementable 1',
            33 => 'Moneda Incrementable 1',
            34 => 'Tipo Cambio Incrementable 1',
            35 => 'A Cargo Importador Incrementable 1',
            36 => 'Total Precio Pagado',
            37 => 'Total Precio Por Pagar',
            38 => 'Total Incrementables',
            39 => 'Total Decrementables',
            40 => 'Total Valor Aduana'
        ];
        
        foreach ($estructura as $pos => $descripcion) {
            $valor = $campos[$pos - 1] ?? '(FALTA)';
            $status = ($campos[$pos - 1] ?? null) !== null ? '✅' : '❌';
            
            echo sprintf("%2d. %s %-35s | %s\n", 
                $pos, $status, $descripcion, 
                $valor === '' ? '(vacío)' : $valor
            );
        }
        
        // Campos extra
        if (count($campos) > count($estructura)) {
            echo "\n=== CAMPOS EXTRA ===\n";
            for ($i = count($estructura); $i < count($campos); $i++) {
                echo sprintf("%2d. EXTRA: %s\n", $i + 1, $campos[$i]);
            }
        }
        
        // Análisis específico de problemas
        echo "\n=== ANÁLISIS DE PROBLEMAS DETECTADOS ===\n";
        
        // Verificar tipo de figura
        if (isset($campos[2])) {
            $tipoFigura = $campos[2];
            if (strpos($tipoFigura, '.') === false) {
                echo "❌ Tipo Figura '$tipoFigura' parece ser descripción, no clave VUCEM\n";
                $clave = self::buscarClaveVucem($tipoFigura, VucemCatalogs::$tiposFigura);
                if ($clave) {
                    echo "   ✅ Clave correcta debería ser: $clave\n";
                }
            } else {
                echo "✅ Tipo Figura usa clave VUCEM correcta: $tipoFigura\n";
            }
        }
        
        // Verificar si faltan campos obligatorios
        $camposFaltantes = [];
        for ($i = 17; $i <= 23; $i++) { // Precio por pagar
            if (!isset($campos[$i - 1])) {
                $camposFaltantes[] = "Campo $i (Precio Por Pagar)";
            }
        }
        
        for ($i = 24; $i <= 28; $i++) { // Compenso
            if (!isset($campos[$i - 1])) {
                $camposFaltantes[] = "Campo $i (Compenso Pago)";
            }
        }
        
        if (!empty($camposFaltantes)) {
            echo "❌ Faltan campos obligatorios:\n";
            foreach ($camposFaltantes as $faltante) {
                echo "   - $faltante\n";
            }
        }
    }
    
    private static function buscarClaveVucem($descripcion, $catalogo)
    {
        return array_search($descripcion, $catalogo, true);
    }
    
    public static function generarCadenaCorrecta($datos)
    {
        echo "=== GENERACIÓN DE CADENA CORRECTA ===\n\n";
        
        $campos = [];
        
        // Usar los datos proporcionados para generar la cadena correcta
        $campos[] = $datos['rfc_importador'] ?? 'RFC_IMPORTADOR';
        
        // Persona consulta con clave VUCEM correcta
        $campos[] = $datos['rfc_consulta'] ?? 'RFC_CONSULTA';
        $tipoFigura = $datos['tipo_figura'] ?? 'Representante Legal';
        $claveVucem = self::buscarClaveVucem($tipoFigura, VucemCatalogs::$tiposFigura);
        $campos[] = $claveVucem ?: $tipoFigura;
        
        // Documento
        $campos[] = $datos['documento'] ?? 'DOCUMENTO.pdf';
        
        // COVE básico
        $campos[] = $datos['numero_cove'] ?? 'COVE123456';
        $campos[] = $datos['incoterm'] ?? 'FOB';
        $campos[] = $datos['existe_vinculacion'] ?? '0';
        
        // Pedimento
        $campos[] = $datos['pedimento'] ?? '';
        $campos[] = $datos['patente'] ?? '';
        $campos[] = $datos['aduana'] ?? '';
        
        // Precio pagado (6 campos)
        $campos[] = $datos['fecha_pagado'] ?? '';
        $campos[] = $datos['total_pagado'] ?? '';
        $campos[] = $datos['tipo_pago_pagado'] ?? '';
        $campos[] = $datos['especifique_pagado'] ?? '';
        $campos[] = $datos['moneda_pagado'] ?? '';
        $campos[] = $datos['cambio_pagado'] ?? '';
        
        // Precio por pagar (7 campos) - OBLIGATORIOS aunque estén vacíos
        $campos[] = $datos['fecha_por_pagar'] ?? '';
        $campos[] = $datos['total_por_pagar'] ?? '';
        $campos[] = $datos['situacion_por_pagar'] ?? '';
        $campos[] = $datos['tipo_pago_por_pagar'] ?? '';
        $campos[] = $datos['especifique_por_pagar'] ?? '';
        $campos[] = $datos['moneda_por_pagar'] ?? '';
        $campos[] = $datos['cambio_por_pagar'] ?? '';
        
        // Compenso pago (5 campos) - OBLIGATORIOS aunque estén vacíos
        $campos[] = $datos['tipo_compenso'] ?? '';
        $campos[] = $datos['fecha_compenso'] ?? '';
        $campos[] = $datos['motivo_compenso'] ?? '';
        $campos[] = $datos['prestacion_compenso'] ?? '';
        $campos[] = $datos['especifique_compenso'] ?? '';
        
        // Método valoración
        $campos[] = $datos['metodo_valoracion'] ?? '';
        
        // Incrementables (si existen)
        if (!empty($datos['incrementables'])) {
            foreach ($datos['incrementables'] as $inc) {
                $campos[] = $inc['tipo'] ?? '';
                $campos[] = $inc['fecha'] ?? '';
                $campos[] = $inc['importe'] ?? '';
                $campos[] = $inc['moneda'] ?? '';
                $campos[] = $inc['cambio'] ?? '';
                $campos[] = $inc['a_cargo'] ?? '';
            }
        }
        
        // Decrementables (si existen)
        if (!empty($datos['decrementables'])) {
            foreach ($datos['decrementables'] as $dec) {
                $campos[] = $dec['tipo'] ?? '';
                $campos[] = $dec['fecha'] ?? '';
                $campos[] = $dec['importe'] ?? '';
                $campos[] = $dec['moneda'] ?? '';
                $campos[] = $dec['cambio'] ?? '';
            }
        }
        
        // Totales valor aduana
        $campos[] = $datos['total_precio_pagado'] ?? '';
        $campos[] = $datos['total_precio_por_pagar'] ?? '';
        $campos[] = $datos['total_incrementables'] ?? '';
        $campos[] = $datos['total_decrementables'] ?? '';
        $campos[] = $datos['total_valor_aduana'] ?? '';
        
        $cadena = '||' . implode('|', $campos) . '||';
        
        echo "Cadena corregida generada:\n";
        echo "$cadena\n\n";
        echo "Total de campos: " . count($campos) . "\n";
        
        return $cadena;
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🔍 DEBUG CADENA ORIGINAL - MVE VUCEM\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Analizar la cadena problemática
    $cadenaProblematica = "||NET070608EM9|NET070608EM9|Representante Legal|COTIZACION NETXICO GDL.pdf|COVE257R70UK6|TIPINC.FCA|0|2548034295001084|3429|480|2025-09-29|8834.7|En cuanto la mercancia llegue a aduana|TRANSFERENCIA||USD|18.3507||GASTOS_TRANSPORTE|2025-09-29|61482|MXN|18.326|1|161902|0|61482|0|223384||";
    
    DebugCadenaOriginal::analizarCadenaActual($cadenaProblematica);
    
    echo "\n" . str_repeat("-", 80) . "\n";
    
    // Generar versión correcta
    $datosEjemplo = [
        'rfc_importador' => 'NET070608EM9',
        'rfc_consulta' => 'NET070608EM9',
        'tipo_figura' => 'Representante Legal',
        'documento' => 'COTIZACION NETXICO GDL.pdf',
        'numero_cove' => 'COVE257R70UK6',
        'incoterm' => 'TIPINC.FCA',
        'existe_vinculacion' => '0',
        'pedimento' => '2548034295001084',
        'patente' => '3429',
        'aduana' => '480',
        'fecha_pagado' => '2025-09-29',
        'total_pagado' => '8834.7',
        'tipo_pago_pagado' => 'TRANSFERENCIA',
        'especifique_pagado' => 'En cuanto la mercancia llegue a aduana',
        'moneda_pagado' => 'USD',
        'cambio_pagado' => '18.3507',
        'metodo_valoracion' => 'VALADU.VTM',
        'incrementables' => [
            [
                'tipo' => 'GASTOS_TRANSPORTE',
                'fecha' => '2025-09-29', 
                'importe' => '61482',
                'moneda' => 'MXN',
                'cambio' => '18.326',
                'a_cargo' => '1'
            ]
        ],
        'total_precio_pagado' => '161902',
        'total_precio_por_pagar' => '0',
        'total_incrementables' => '61482',
        'total_decrementables' => '0',
        'total_valor_aduana' => '223384'
    ];
    
    DebugCadenaOriginal::generarCadenaCorrecta($datosEjemplo);
}