<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $connection = 'erp';
    protected $table = 'presupuestos';
    protected $primaryKey = 'id_pres';

    public $timestamps = false;

    public function adjuntos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresupAdj::class, 'id_presup', 'id_pres');
    }

    public function procesoEst(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProcesoEst::class, 'id_proc_est', 'id_proc_est');
    }
}
