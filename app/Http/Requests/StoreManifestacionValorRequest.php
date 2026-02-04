<?php

/**
 * VALIDACIÓN UNIFICADA FINAL: REGLAS Y MENSAJES (PASOS 1 AL 5)
 * Basado estrictamente en el Diccionario de Datos MV 2025.
 */

return [
    'rules' => [
        /** --- PASO 1: SEGURIDAD Y TRANSMISIÓN (Pág. 3) --- **/
        'Username'       => 'required|string|min:12|max:13',
        'Password'       => 'required|string|size:64',
        'certificado'    => 'required|string', 
        'cadenaOriginal' => 'required|string',
        'firma'          => 'required|string|max:1000',

        /** --- PASO 2: OPERACIÓN Y VINCULACIÓN (Pág. 4 y 5) --- **/
        'rfc'               => 'required|string|min:12|max:13',
        'personaConsulta'   => 'nullable|array',
        'personaConsulta.*.rfc'        => 'required_with:personaConsulta|string|min:12|max:13',
        'personaConsulta.*.tipoFigura' => 'required_with:personaConsulta|string|max:15',
        'documentos'        => 'nullable|array',
        'documentos.*.eDocument'       => 'required_with:documentos|string|min:8|max:30',
        'informacionCove'   => 'required|array|min:1',
        'informacionCove.*.cove'       => 'required|string|max:20',
        'informacionCove.*.incoterm'   => 'required|string|max:15',
        'existeVinculacion' => 'required|integer|in:0,1',
        'pedimento'         => 'required|string|max:20',
        'patente'           => 'required|string|max:20',
        'aduana'            => 'required|string|max:20',

        /** --- PASO 3: SECCIÓN FINANCIERA Y COMPENSO (Pág. 5 y 6) --- **/
        'precioPagado'             => 'nullable|array',
        'precioPagado.fechaPago'   => 'required_with:precioPagado|date',
        'precioPagado.total'       => 'required_with:precioPagado|numeric|between:0,999999999999999.999',
        'precioPagado.tipoPago'    => 'required_with:precioPagado|string|max:20',
        'precioPagado.especifique' => 'required_if:precioPagado.tipoPago,FORPAG.OT|string|max:70',
        'precioPagado.tipoMoneda'  => 'required_with:precioPagado|string|size:3',
        'precioPagado.tipoCambio'  => 'required_with:precioPagado|numeric|between:0,9999999999999.999',

        'precioPorPagar'                      => 'nullable|array',
        'precioPorPagar.fechaPago'            => 'required_with:precioPorPagar|date',
        'precioPorPagar.total'                => 'required_with:precioPorPagar|numeric|between:0,999999999999999.999',
        'precioPorPagar.situacionNofechaPago' => 'required_with:precioPorPagar|string|max:1000',
        'precioPorPagar.tipoPago'             => 'required_with:precioPorPagar|string|max:20',
        'precioPorPagar.especifique'          => 'required_if:precioPorPagar.tipoPago,FORPAG.OT|string|max:70',
        'precioPorPagar.tipoMoneda'           => 'required_with:precioPorPagar|string|size:3',
        'precioPorPagar.tipoCambio'           => 'required_with:precioPorPagar|numeric|between:0,9999999999999.999',

        'compensoPago'                     => 'nullable|array',
        'compensoPago.fecha'               => 'required_with:compensoPago|date',
        'compensoPago.tipoPago'            => 'required_with:compensoPago|string|max:20',
        'compensoPago.motivo'              => 'required_with:compensoPago|string|max:1000',
        'compensoPago.prestacionMercancia' => 'required_with:compensoPago|string|max:1000',
        'compensoPago.especifique'         => 'required_if:compensoPago.tipoPago,FORPAG.OT|string|max:70',

        /** --- PASO 4: VALORACIÓN E INCREMENTABLES (Pág. 7) --- **/
        'metodoValoracion' => 'required|string|max:20',
        'incrementables'   => 'nullable|array',
        'incrementables.*.tipoIncrementable' => 'required_with:incrementables|string|max:20',
        'incrementables.*.fechaErogacion'    => 'required_with:incrementables|date',
        'incrementables.*.importe'           => 'required_with:incrementables|numeric|between:0,999999999999999.999',
        'incrementables.*.aCargoImportador'  => 'required_with:incrementables|integer|in:0,1',
        'incrementables.*.tipoMoneda'        => 'required_with:incrementables|string|size:3',
        'incrementables.*.tipoCambio'        => 'required_with:incrementables|numeric|between:0,9999999999999.999',

        'decrementables'   => 'nullable|array',
        'decrementables.*.tipoDecrementable' => 'required_with:decrementables|string|max:20',
        'decrementables.*.fechaErogacion'    => 'required_with:decrementables|date',
        'decrementables.*.importe'           => 'required_with:decrementables|numeric|between:0,999999999999999.999',
        'decrementables.*.tipoMoneda'        => 'required_with:decrementables|string|size:3',
        'decrementables.*.tipoCambio'        => 'required_with:decrementables|numeric|between:0,9999999999999.999',

        'precioPagado'   => 'nullable|array',
        'precioPagado.*.fecha'               => 'required_with:precioPagado|date',
        'precioPagado.*.importe'             => 'required_with:precioPagado|numeric|between:0,999999999999999.999',
        'precioPagado.*.formaPago'           => 'required_with:precioPagado|string|max:20',
        'precioPagado.*.tipoMoneda'          => 'required_with:precioPagado|string|size:3',
        'precioPagado.*.tipoCambio'          => 'required_with:precioPagado|numeric|between:0,9999999999999.999',

        'precioPorPagar'   => 'nullable|array',
        'precioPorPagar.*.fecha'             => 'required_with:precioPorPagar|date',
        'precioPorPagar.*.importe'           => 'required_with:precioPorPagar|numeric|between:0,999999999999999.999',
        'precioPorPagar.*.formaPago'         => 'required_with:precioPorPagar|string|max:20',
        'precioPorPagar.*.momentoSituacion'  => 'required_with:precioPorPagar|string|max:500',
        'precioPorPagar.*.tipoMoneda'        => 'required_with:precioPorPagar|string|size:3',
        'precioPorPagar.*.tipoCambio'        => 'required_with:precioPorPagar|numeric|between:0,9999999999999.999',

        'compensoPago'   => 'nullable|array',
        'compensoPago.*.fecha'               => 'required_with:compensoPago|date',
        'compensoPago.*.formaPago'           => 'required_with:compensoPago|string|max:20',
        'compensoPago.*.motivo'              => 'required_with:compensoPago|string|max:500',
        'compensoPago.*.prestacionMercancia' => 'required_with:compensoPago|string|max:500',

        /** --- PASO 5: TOTALES (Pág. 8) --- **/
        'valorEnAduana' => 'required|array',
        'valorEnAduana.totalPrecioPagado'   => 'required|numeric|between:0,999999999999999.999',
        'valorEnAduana.totalPrecioPorPagar' => 'required|numeric|between:0,999999999999999.999',
        'valorEnAduana.totalIncrementables' => 'required|numeric|between:0,999999999999999.999',
        'valorEnAduana.totalDecrementables' => 'required|numeric|between:0,999999999999999.999',
        'valorEnAduana.totalValorAduana'    => 'required|numeric|between:0,999999999999999.999',
    ],

    'messages' => [
        /** Mensajes Paso 1 **/
        'Username.required'      => 'El RFC de quien transmite (Username) es obligatorio.',
        'Password.size'          => 'El Password debe ser una contraseña cifrada de exactamente 64 caracteres.',
        'firma.max'              => 'La firma electrónica no debe exceder los 1000 caracteres.',

        /** Mensajes Paso 2 **/
        'rfc.required'           => 'El RFC del Importador es obligatorio.',
        'informacionCove.*.cove.required' => 'El número de COVE es obligatorio para identificar la operación.',
        'existeVinculacion.in'   => 'Especifique si existe vinculación: 0 para NO, 1 para SÍ.',
        'pedimento.max'          => 'El número de pedimento no debe exceder los 20 caracteres.',

        /** Mensajes Paso 3 **/
        'precioPagado.total.numeric'           => 'El total del precio pagado debe ser numérico con hasta 3 decimales.',
        'precioPagado.especifique.required_if' => 'Debe especificar el motivo al seleccionar "Otro tipo de pago" (máx 70 carac.).',
        'precioPorPagar.situacionNofechaPago.max' => 'La situación de no fecha de pago no debe exceder los 1000 caracteres.',
        'compensoPago.motivo.max'              => 'El motivo de la compensación no debe exceder los 1000 caracteres.',
        'compensoPago.prestacionMercancia.max' => 'La descripción de prestación de mercancía permite hasta 1000 caracteres.',

        /** Mensajes Paso 4 **/
        'metodoValoracion.required'            => 'El Método de Valoración es obligatorio (máx 20 carac.).',
        'incrementables.*.importe.numeric'     => 'El importe del incrementable debe ser numérico (precisión 19,3).',
        'incrementables.*.aCargoImportador.in' => 'Indique si el pago está a cargo del importador (0: No, 1: Sí).',
        'decrementables.*.tipoCambio.numeric'  => 'El tipo de cambio del decrementable debe ser numérico (precisión 16,3).',

        /** Mensajes Paso 5 **/
        'valorEnAduana.totalValorAduana.required' => 'El total del valor en aduana es un campo calculado obligatorio.',
        'valorEnAduana.totalIncrementables.numeric' => 'El total de incrementables debe ser numérico conforme a la pág. 8.',
    ]
];
