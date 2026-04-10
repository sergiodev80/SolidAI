<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupAdjAsignacion extends Model
{
    protected $connection = 'erp';
    protected $table = 'presup_adj_asignaciones';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_adjun',
        'login',
        'rol',
        'pag_inicio',
        'pag_fin',
        'id_idiom',
        'id_idiom_original',
        'estado',
        'comentario',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function adjunto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PresupAdj::class, 'id_adjun', 'id_adjun');
    }

    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SeccUser::class, 'login', 'login');
    }
}
