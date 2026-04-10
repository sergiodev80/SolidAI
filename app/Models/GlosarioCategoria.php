<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlosarioCategoria extends Model
{
    protected $table = 'glosario_categorias';

    protected $fillable = ['nombre', 'descripcion', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function terminos(): HasMany
    {
        return $this->hasMany(GlosarioTermino::class);
    }
}
