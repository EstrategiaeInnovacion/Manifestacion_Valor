<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VucemErrorLog extends Model
{
    protected $table = 'vucem_error_logs';

    protected $fillable = [
        'user_id',
        'applicant_id',
        'servicio',
        'tipo_error',
        'curl_error_raw',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applicant()
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }
}
