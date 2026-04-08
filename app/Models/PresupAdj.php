<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupAdj extends Model
{
    protected $connection = 'erp';
    protected $table = 'presup_adj';
    protected $primaryKey = 'id_adjun';

    public $timestamps = false;

    protected $fillable = [
        'id_presup',
        'adjun_adjun',
    ];

    public function presupuesto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Presupuesto::class, 'id_presup', 'id_pres');
    }

    public function asignaciones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresupAdjAsignacion::class, 'id_adjun', 'id_adjun');
    }

    public function getNombreArchivoAttribute(): string
    {
        if (empty($this->adjun_adjun)) {
            return 'Sin nombre';
        }

        $blob = is_resource($this->adjun_adjun)
            ? stream_get_contents($this->adjun_adjun)
            : $this->adjun_adjun;

        // Busca secuencias tipo "nombre.ext" dentro del blob binario
        // Patrones más amplios para capturar distintos formatos de nombre
        if (preg_match('/[a-zA-Z0-9\-_. ]+\.(pdf|docx?|xlsx?|pptx?|txt|zip|rar|jpg|jpeg|png|gif|bmp)/i', $blob, $matches)) {
            return trim($matches[0]);
        }

        // Si aún no encuentra, intenta buscar cualquier secuencia que parezca un archivo
        if (preg_match('/([a-zA-Z0-9\-_. áéíóúñ]+\.[a-zA-Z0-9]{2,4})/i', $blob, $matches)) {
            return trim($matches[1]);
        }

        return 'Documento-' . $this->id_adjun;
    }
}
