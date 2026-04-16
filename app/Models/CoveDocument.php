<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CoveDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'created_by_user_id',
        'tipo_operacion',
        'patente_aduanal',
        'status',
        'numero_operacion',
        'e_document',
        'payload',
        'xml_enviado',
        'xml_respuesta',
        'acuse_pdf',
    ];

    /**
     * Mutator y Accessor para setear y obtener el payload cifrado
     * Se almacena como un JSON encriptado en la db
     */
    public function getPayloadAttribute($value)
    {
        try {
            return $value ? json_decode(Crypt::decryptString($value), true) : [];
        } catch (\Exception $e) {
            return []; // En caso de que se haya corrompido o cambiado la llave
        }
    }

    public function setPayloadAttribute($value)
    {
        $this->attributes['payload'] = Crypt::encryptString(json_encode($value));
    }

    // Relaciones
    public function applicant()
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
