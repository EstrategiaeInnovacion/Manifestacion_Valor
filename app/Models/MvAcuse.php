<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MvAcuse extends Model
{
    use HasFactory;

    protected $table = 'mv_acuses';

    protected $fillable = [
        'applicant_id',
        'datos_manifestacion_id',
        'folio_manifestacion',
        'numero_pedimento',
        'numero_cove',
        'xml_enviado',
        'xml_respuesta',
        'acuse_pdf',
        'status',
        'mensaje_vucem',
        'fecha_envio',
        'fecha_respuesta',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_respuesta' => 'datetime',
    ];

    /**
     * Relación con el solicitante
     */
    public function applicant()
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    /**
     * Relación con los datos de manifestación
     */
    public function datosManifestacion()
    {
        return $this->belongsTo(MvDatosManifestacion::class, 'datos_manifestacion_id');
    }
}
