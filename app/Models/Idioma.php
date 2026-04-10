<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idioma extends Model
{
    protected $table = 'idiomas';
    protected $primaryKey = 'id_idiom';
    public $timestamps = false; // Si la tabla no tiene timestamps

    protected $fillable = ['cod_idiom', 'nombre_idiom'];

    public function terminos(): HasMany
    {
        return $this->hasMany(GlosarioTermino::class, 'id_idiom', 'id_idiom');
    }
}
