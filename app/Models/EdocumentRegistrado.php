<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdocumentRegistrado extends Model
{
    use HasFactory;

    protected $table = 'edocuments_registrados';

    protected $fillable = [
        'folio_edocument',
        'existe_en_vucem',
        'fecha_ultima_consulta',
        'response_code',
        'response_message',
        'cove_data',
    ];

    protected $casts = [
        'existe_en_vucem' => 'boolean',
        'fecha_ultima_consulta' => 'datetime',
        'cove_data' => 'json',
    ];
}
