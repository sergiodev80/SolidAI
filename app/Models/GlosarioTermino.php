<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlosarioTermino extends Model
{
    protected $table = 'glosario_terminos';

    protected $fillable = [
        'termino_original',
        'id_idiom_original',
        'termino_traducido',
        'id_idiom_traducido',
        'contexto',
        'glosario_categoria_id',
        'nivel',
        'cliente_id',
        'documento_id',
        'estado',
        'created_by',
        'approved_by',
        'approved_at',
        'usos',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function idiomaOriginal(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'id_idiom_original', 'id_idiom');
    }

    public function idiomaTraducido(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'id_idiom_traducido', 'id_idiom');
    }

    // Alias para compatibilidad
    public function idioma(): BelongsTo
    {
        return $this->idiomaOriginal();
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(GlosarioCategoria::class, 'glosario_categoria_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'propuesto');
    }

    public function scopeEmpresa($query)
    {
        return $query->where('nivel', 'empresa');
    }

    public function scopeCategoria($query)
    {
        return $query->where('nivel', 'categoria');
    }

    public function scopeCliente($query)
    {
        return $query->where('nivel', 'cliente');
    }

    public function scopeDocumento($query)
    {
        return $query->where('nivel', 'documento');
    }
}
