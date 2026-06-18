<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MvDocumentoStaging extends Model
{
    use HasFactory;

    protected $table = 'mv_documentos_staging';

    protected $fillable = [
        'applicant_id',
        'datos_manifestacion_id',
        'created_by_user_id',
        'tipo_documento',
        'nombre_documento',
        'original_filename',
        'file_path',
        'file_size',
        'is_vucem_compliant',
        'was_converted',
        'rfc_consulta',
        'numero_operacion',
        'last_error',
    ];

    protected $casts = [
        'is_vucem_compliant' => 'boolean',
        'was_converted' => 'boolean',
        'file_size' => 'integer',
        'datos_manifestacion_id' => 'integer',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    public function deleteWithFile(): void
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
        $this->delete();
    }
}
