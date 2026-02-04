<?php

namespace App\Exceptions;

use Exception;

class CoveConsultaException extends Exception
{
    /**
     * Excepción personalizada para errores en consultas COVE
     */
    public function __construct($message = "Error en consulta COVE", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}