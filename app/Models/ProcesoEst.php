<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoEst extends Model
{
    protected $connection = 'erp';
    protected $table = 'proceso_est';
    protected $primaryKey = 'id_proc_est';

    public $timestamps = false;

    protected $fillable = [
        'id_proc_est',
        'proc_estado',
    ];
}
