<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Herramientas PDF para VUCEM
    |--------------------------------------------------------------------------
    |
    | Configuración de las herramientas necesarias para procesar archivos PDF
    | y convertirlos al formato requerido por VUCEM.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Ghostscript
    |--------------------------------------------------------------------------
    |
    | Ruta al ejecutable de Ghostscript. Si se deja vacío, el sistema intentará
    | autodetectar la instalación.
    |
    | Windows: 'C:\Program Files\gs\gs10.02.1\bin\gswin64c.exe'
    | Linux/Mac: 'gs' o '/usr/bin/gs'
    |
    */

    'ghostscript' => env('GHOSTSCRIPT_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | PDFImages (Poppler Utils)
    |--------------------------------------------------------------------------
    |
    | Ruta al ejecutable de pdfimages (parte de Poppler Utils).
    | Si se deja vacío, el sistema intentará autodetectar.
    |
    | Windows: Descargar desde https://poppler.freedesktop.org/
    | Linux: sudo apt-get install poppler-utils
    | Mac: brew install poppler
    |
    */

    'pdfimages' => env('PDFIMAGES_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Configuración de procesamiento
    |--------------------------------------------------------------------------
    |
    | Parámetros para el procesamiento de archivos PDF.
    |
    */

    'max_size_mb' => env('PDF_MAX_SIZE_MB', 50),
    'output_dpi' => env('PDF_OUTPUT_DPI', 300),
    'target_version' => env('PDF_TARGET_VERSION', '1.4'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de conversión VUCEM
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para cumplir con los requisitos VUCEM.
    |
    */

    'vucem' => [
        'max_final_size_mb' => 3,      // Tamaño máximo final según VUCEM
        'required_version' => '1.4',    // Versión PDF requerida por VUCEM
        'required_dpi' => 300,          // DPI exactos requeridos por VUCEM
        'force_grayscale' => true,      // Forzar conversión a escala de grises
        'remove_encryption' => true,    // Remover protección de documentos
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    |
    | Tiempo límite para operaciones de conversión (en segundos).
    |
    */

    'timeouts' => [
        'conversion' => 120,    // 2 minutos para conversión
        'validation' => 30,     // 30 segundos para validación
        'ghostscript' => 60,    // 1 minuto para operaciones GS
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths temporales
    |--------------------------------------------------------------------------
    |
    | Configuración de directorios temporales para procesamiento.
    |
    */

    'temp_directory' => env('PDF_TEMP_DIR', sys_get_temp_dir()),
    'cleanup_temp_files' => true,   // Limpiar archivos temporales automáticamente

];